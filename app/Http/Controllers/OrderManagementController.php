<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\UpdateShippingRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderManagementController extends Controller
{
    /**
     * Get orders for the current artist
     */
    public function index(Request $request)
    {
        try {
            // Check if user is an approved artist
            if (!$request->user()->isArtist() || !$request->user()->artist->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح: يجب أن تكون فنانًا معتمدًا للوصول إلى هذا المسار.',
                    'errors' => ['authorization' => ['يجب أن تكون فنانًا معتمدًا.']],
                ], 403);
            }

            $perPage = min(max($request->get('per_page', 10), 1), 50);
            $status = $request->get('status'); // Filter by shipping status

            $query = Order::with(['artwork', 'buyer'])
                ->where('artist_id', $request->user()->artist->id)
                ->orderBy('created_at', 'desc');

            // Filter by shipping status if provided
            if ($status && in_array($status, ['pending', 'shipped', 'delivered', 'cancelled'])) {
                $query->where('shipping_status', $status);
            }

            $orders = $query->paginate($perPage);

            $ordersData = $orders->getCollection()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'artwork' => [
                        'id' => $order->artwork->id,
                        'title' => $order->artwork->title,
                        'main_image' => $order->artwork->images && count($order->artwork->images) > 0 
                            ? url($order->artwork->images[0]) : null,
                    ],
                    'buyer' => [
                        'name' => $order->buyer_name,
                        'phone' => $order->buyer_phone,
                        'email' => $order->buyer->email,
                    ],
                    'total_amount' => $order->total_amount,
                    'artist_earnings' => $order->artist_earnings,
                    'commission' => $order->commission,
                    'payment_status' => $order->payment_status,
                    'shipping_status' => $order->shipping_status,
                    'tracking_number' => $order->tracking_number,
                    'shipping_address' => $order->shipping_address,
                    'can_ship' => $order->canBeShipped(),
                    'can_deliver' => $order->canBeDelivered(),
                    'created_at' => $order->created_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'تم جلب الطلبات بنجاح',
                'data' => [
                    'orders' => $ordersData,
                    'pagination' => [
                        'total' => $orders->total(),
                        'count' => $orders->count(),
                        'per_page' => $orders->perPage(),
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'from' => $orders->firstItem(),
                        'to' => $orders->lastItem(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في جلب طلبات الفنان', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الطلبات',
                'errors' => ['system' => ['خطأ في الخادم، يرجى المحاولة لاحقاً.']],
            ], 500);
        }
    }

    /**
     * Mark order as shipped
     */
    public function ship(UpdateShippingRequest $request, Order $order)
    {
        try {
            // Check authorization
            if (!$this->canManageOrder($request->user(), $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح: لا يمكنك إدارة هذا الطلب.',
                    'errors' => ['authorization' => ['هذا الطلب لا ينتمي إليك.']],
                ], 403);
            }

            if (!$order->canBeShipped()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن شحن هذا الطلب في الوقت الحالي.',
                    'errors' => [
                        'shipping' => ['الطلب يجب أن يكون مدفوعًا وفي حالة انتظار الشحن.']
                    ],
                ], 400);
            }

            $updated = $order->markAsShipped($request->tracking_number);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في تحديث حالة الشحن.',
                    'errors' => ['system' => ['حدث خطأ أثناء تحديث البيانات.']],
                ], 500);
            }

            // TODO: Send shipping notification to buyer
            $this->sendShippingNotification($order);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الطلب إلى "تم الشحن" بنجاح',
                'data' => [
                    'order_id' => $order->id,
                    'shipping_status' => $order->shipping_status,
                    'tracking_number' => $order->tracking_number,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في تحديث حالة الشحن', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'order_id' => $order->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة الشحن',
                'errors' => ['system' => ['خطأ في الخادم، يرجى المحاولة لاحقاً.']],
            ], 500);
        }
    }

    /**
     * Mark order as delivered
     */
    public function deliver(Request $request, Order $order)
    {
        try {
            // Check authorization
            if (!$this->canManageOrder($request->user(), $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح: لا يمكنك إدارة هذا الطلب.',
                    'errors' => ['authorization' => ['هذا الطلب لا ينتمي إليك.']],
                ], 403);
            }

            if (!$order->canBeDelivered()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تسليم هذا الطلب في الوقت الحالي.',
                    'errors' => [
                        'delivery' => ['الطلب يجب أن يكون مشحونًا ومدفوعًا.']
                    ],
                ], 400);
            }

            DB::beginTransaction();

            $updated = $order->markAsDelivered();

            if (!$updated) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في تحديث حالة التسليم.',
                    'errors' => ['system' => ['حدث خطأ أثناء تحديث البيانات.']],
                ], 500);
            }

            DB::commit();

            // TODO: Send delivery confirmation to buyer
            // TODO: Send payment notification to artist
            $this->sendDeliveryConfirmation($order);
            $this->sendPaymentNotification($order);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الطلب إلى "تم التسليم" بنجاح',
                'data' => [
                    'order_id' => $order->id,
                    'shipping_status' => $order->shipping_status,
                    'delivered_at' => $order->delivered_at->toISOString(),
                    'transaction' => [
                        'status' => $order->transaction->status,
                        'net_amount' => $order->transaction->net_amount,
                        'transfer_date' => $order->transaction->transfer_date->toISOString(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('خطأ في تحديث حالة التسليم', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'order_id' => $order->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة التسليم',
                'errors' => ['system' => ['خطأ في الخادم، يرجى المحاولة لاحقاً.']],
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific order
     */
    public function show(Request $request, Order $order)
    {
        try {
            // Check authorization
            if (!$this->canManageOrder($request->user(), $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح: لا يمكنك الوصول إلى هذا الطلب.',
                    'errors' => ['authorization' => ['هذا الطلب لا ينتمي إليك.']],
                ], 403);
            }

            // Load relationships
            $order->load(['artwork', 'buyer', 'transaction']);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب تفاصيل الطلب بنجاح',
                'data' => [
                    'id' => $order->id,
                    'buyer_name' => $order->buyer_name,
                    'buyer_phone' => $order->buyer_phone,
                    'buyer_email' => $order->buyer->email,
                    'shipping_address' => $order->shipping_address,
                    'total_amount' => $order->total_amount,
                    'commission' => $order->commission,
                    'artist_earnings' => $order->artist_earnings,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'payment_id' => $order->payment_id,
                    'shipping_status' => $order->shipping_status,
                    'tracking_number' => $order->tracking_number,
                    'delivered_at' => $order->delivered_at?->toISOString(),
                    'created_at' => $order->created_at->toISOString(),
                    'can_ship' => $order->canBeShipped(),
                    'can_deliver' => $order->canBeDelivered(),
                    
                    // Artwork info
                    'artwork' => [
                        'id' => $order->artwork->id,
                        'title' => $order->artwork->title,
                        'description' => $order->artwork->description,
                        'category' => $order->artwork->category,
                        'dimensions' => $order->artwork->dimensions,
                        'materials' => $order->artwork->materials,
                        'images' => $order->artwork->images ? 
                            array_map(fn($img) => url($img), $order->artwork->images) : [],
                    ],
                    
                    // Transaction info if exists
                    'transaction' => $order->transaction ? [
                        'id' => $order->transaction->id,
                        'status' => $order->transaction->status,
                        'net_amount' => $order->transaction->net_amount,
                        'transfer_date' => $order->transaction->transfer_date?->toISOString(),
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في جلب تفاصيل الطلب للفنان', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'order_id' => $order->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل الطلب',
                'errors' => ['system' => ['خطأ في الخادم، يرجى المحاولة لاحقاً.']],
            ], 500);
        }
    }

    /**
     * Check if user can manage this order
     */
    private function canManageOrder($user, Order $order): bool
    {
        // Admin can manage any order
        if ($user->isAdmin()) {
            return true;
        }

        // Artist can manage their own orders
        if ($user->isArtist() && $user->artist && $user->artist->isApproved()) {
            return $order->artist_id === $user->artist->id;
        }

        return false;
    }

    /**
     * Send shipping notification to buyer (stub)
     */
    private function sendShippingNotification(Order $order): void
    {
        Log::info('إرسال إشعار شحن الطلب للمشتري', [
            'order_id' => $order->id,
            'buyer_email' => $order->buyer->email,
            'tracking_number' => $order->tracking_number,
        ]);
    }

    /**
     * Send delivery confirmation to buyer (stub)
     */
    private function sendDeliveryConfirmation(Order $order): void
    {
        Log::info('إرسال تأكيد تسليم الطلب للمشتري', [
            'order_id' => $order->id,
            'buyer_email' => $order->buyer->email,
        ]);
    }

    /**
     * Send payment notification to artist (stub)
     */
    private function sendPaymentNotification(Order $order): void
    {
        Log::info('إرسال إشعار تحويل المبلغ للفنان', [
            'order_id' => $order->id,
            'artist_email' => $order->artist->user->email,
            'amount' => $order->artist_earnings,
        ]);
    }
}
