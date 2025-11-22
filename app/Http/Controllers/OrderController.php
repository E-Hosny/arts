<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\CreateOrderRequest;
use App\Models\Order;
use App\Models\Artwork;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders
     */
    public function index(Request $request)
    {
        try {
            if (!$request->user()->isBuyer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح: يجب أن تكون مشتريًا للوصول إلى هذا المسار.',
                    'errors' => ['authorization' => ['يجب أن تكون مشتريًا.']],
                ], 403);
            }

            $perPage = min(max($request->get('per_page', 10), 1), 50);

            $orders = Order::with(['artwork', 'artist.user'])
                ->where('buyer_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $ordersData = $orders->getCollection()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'artwork' => [
                        'id' => $order->artwork->id,
                        'title' => $order->artwork->title,
                        'main_image' => $order->artwork->images && count($order->artwork->images) > 0 
                            ? url($order->artwork->images[0]) : null,
                    ],
                    'artist' => [
                        'id' => $order->artist->id,
                        'name' => $order->artist->user->name,
                        'city' => $order->artist->city,
                    ],
                    'total_amount' => $order->total_amount,
                    'payment_status' => $order->payment_status,
                    'shipping_status' => $order->shipping_status,
                    'tracking_number' => $order->tracking_number,
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
            Log::error('خطأ في جلب الطلبات', [
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
     * Store a newly created order
     */
    public function store(CreateOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $artwork = $request->getArtwork();
            if (!$artwork) {
                return response()->json([
                    'success' => false,
                    'message' => 'العمل الفني غير موجود',
                    'errors' => ['artwork_id' => ['العمل الفني المحدد غير موجود.']],
                ], 404);
            }

            // Calculate amounts
            $totalAmount = $artwork->price;
            $calculations = Order::calculateCommission($totalAmount, $artwork->artist->commission_rate);

            // Generate payment ID for simulation
            $paymentId = 'PAY_' . Str::upper(Str::random(10)) . '_' . time();

            // Create order
            $order = Order::create([
                'buyer_id' => $request->user()->id,
                'artwork_id' => $artwork->id,
                'artist_id' => $artwork->artist_id,
                'total_amount' => $totalAmount,
                'commission' => $calculations['commission'],
                'artist_earnings' => $calculations['artist_earnings'],
                'payment_method' => $request->payment_method,
                'payment_status' => Order::PAYMENT_COMPLETED, // Simulate successful payment
                'payment_id' => $paymentId,
                'shipping_status' => Order::SHIPPING_PENDING,
                'buyer_name' => $request->buyer_name,
                'buyer_phone' => $request->buyer_phone,
                'shipping_address' => $request->shipping_address,
            ]);

            // Mark artwork as sold
            $artwork->update(['status' => Artwork::STATUS_SOLD]);

            // Update artist total sales
            $artwork->artist->increment('total_sales', $totalAmount);

            // Create transaction (for future processing)
            Transaction::create([
                'order_id' => $order->id,
                'artist_id' => $artwork->artist_id,
                'amount' => $totalAmount,
                'commission' => $calculations['commission'],
                'net_amount' => $calculations['artist_earnings'],
                'status' => Transaction::STATUS_PENDING, // Will be completed when delivered
            ]);

            DB::commit();

            // TODO: Send order confirmation email to buyer
            // TODO: Send new order notification to artist
            $this->sendOrderConfirmationEmail($order);
            $this->sendOrderNotificationToArtist($order);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح',
                'data' => [
                    'order_id' => $order->id,
                    'payment_id' => $paymentId,
                    'payment_status' => $order->payment_status,
                    'shipping_status' => $order->shipping_status,
                    'total_amount' => $order->total_amount,
                    'commission' => $order->commission,
                    'artist_earnings' => $order->artist_earnings,
                    'artwork' => [
                        'id' => $artwork->id,
                        'title' => $artwork->title,
                    ],
                    'artist' => [
                        'id' => $artwork->artist->id,
                        'name' => $artwork->artist->user->name,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('خطأ في إنشاء الطلب', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'artwork_id' => $request->artwork_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الطلب',
                'errors' => ['system' => ['خطأ في الخادم، يرجى المحاولة لاحقاً.']],
            ], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, Order $order)
    {
        try {
            // Check if user owns this order
            if ($order->buyer_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح: لا يمكنك الوصول إلى هذا الطلب.',
                    'errors' => ['authorization' => ['هذا الطلب لا ينتمي إليك.']],
                ], 403);
            }

            // Load relationships
            $order->load(['artwork', 'artist.user', 'transaction']);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب تفاصيل الطلب بنجاح',
                'data' => [
                    'id' => $order->id,
                    'buyer_name' => $order->buyer_name,
                    'buyer_phone' => $order->buyer_phone,
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
                    
                    // Artwork snapshot
                    'artwork' => [
                        'id' => $order->artwork->id,
                        'title' => $order->artwork->title,
                        'description' => $order->artwork->description,
                        'price' => $order->artwork->price,
                        'category' => $order->artwork->category,
                        'dimensions' => $order->artwork->dimensions,
                        'materials' => $order->artwork->materials,
                        'images' => $order->artwork->images ? 
                            array_map(fn($img) => url($img), $order->artwork->images) : [],
                    ],
                    
                    // Artist basic info
                    'artist' => [
                        'id' => $order->artist->id,
                        'name' => $order->artist->user->name,
                        'city' => $order->artist->city,
                        'verified' => $order->artist->verified,
                        'featured' => $order->artist->featured,
                    ],
                    
                    // Transaction info if exists
                    'transaction' => $order->transaction ? [
                        'id' => $order->transaction->id,
                        'status' => $order->transaction->status,
                        'transfer_date' => $order->transaction->transfer_date?->toISOString(),
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في جلب تفاصيل الطلب', [
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
     * Send order confirmation email to buyer (stub)
     */
    private function sendOrderConfirmationEmail(Order $order): void
    {
        // TODO: Implement email sending logic
        Log::info('إرسال بريد تأكيد الطلب للمشتري', [
            'order_id' => $order->id,
            'buyer_email' => $order->buyer->email,
        ]);
    }

    /**
     * Send order notification to artist (stub)
     */
    private function sendOrderNotificationToArtist(Order $order): void
    {
        // TODO: Implement email/notification sending logic
        Log::info('إرسال إشعار طلب جديد للفنان', [
            'order_id' => $order->id,
            'artist_email' => $order->artist->user->email,
        ]);
    }
}
