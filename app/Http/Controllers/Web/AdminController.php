<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\User;
use App\Models\Artwork;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        // Statistics for dashboard
        $stats = [
            'pending_artists' => Artist::where('status', Artist::STATUS_PENDING)->count(),
            'approved_artists' => Artist::where('status', Artist::STATUS_APPROVED)->count(),
            'rejected_artists' => Artist::where('status', Artist::STATUS_REJECTED)->count(),
            'total_users' => User::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Show pending artists list
     */
    public function pendingArtists(Request $request)
    {
        $perPage = $request->get('per_page', 12);
        
        $artists = Artist::with(['user', 'samples'])
            ->where('status', Artist::STATUS_PENDING)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        return view('admin.artists.pending', compact('artists'));
    }

    /**
     * Show artist details for review
     */
    public function reviewArtist(Artist $artist)
    {
        $artist->load(['user', 'samples']);
        
        return view('admin.artists.review', compact('artist'));
    }

    /**
     * Show login form for admin
     */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'ليس لديك صلاحية للوصول إلى لوحة التحكم.',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        }

        return back()->withErrors([
            'email' => 'بيانات تسجيل الدخول غير صحيحة.',
        ]);
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/admin/login');
    }

    /**
     * Approve artist (web route)
     */
    public function approveArtist(Request $request, Artist $artist)
    {
        $request->validate([
            'commission_rate' => 'sometimes|integer|min:5|max:50',
            'featured' => 'sometimes|boolean',
            'notes_admin' => 'nullable|string|max:1000',
        ]);

        if ($artist->status !== Artist::STATUS_PENDING) {
            return back()->with('error', 'لا يمكن قبول هذا الفنان في الوقت الحالي');
        }

        $artist->update([
            'status' => Artist::STATUS_APPROVED,
            'commission_rate' => $request->get('commission_rate', 25),
            'featured' => $request->has('featured') && $request->featured == '1',
            'notes_admin' => $request->get('notes_admin'),
            'approved_by' => Auth::id(),
            'can_reapply_at' => null,
            'rejection_reason' => null,
        ]);

        // TODO: Send notification email

        return redirect()->route('admin.artists.pending')
            ->with('success', 'تم قبول الفنان بنجاح');
    }

    /**
     * Reject artist (web route)
     */
    public function rejectArtist(Request $request, Artist $artist)
    {
        $request->validate([
            'reason_rejection' => 'required|string|max:1000',
            'notes_admin' => 'nullable|string|max:1000',
        ]);

        if ($artist->status !== Artist::STATUS_PENDING) {
            return back()->with('error', 'لا يمكن رفض هذا الفنان في الوقت الحالي');
        }

        $artist->update([
            'status' => Artist::STATUS_REJECTED,
            'rejection_reason' => $request->reason_rejection,
            'notes_admin' => $request->get('notes_admin'),
            'approved_by' => Auth::id(),
            'can_reapply_at' => now()->addDays(30),
            'featured' => false,
        ]);

        // TODO: Send notification email

        return redirect()->route('admin.artists.pending')
            ->with('success', 'تم رفض الفنان');
    }

    /**
     * Show artworks management page
     */
    public function artworksIndex(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $query = Artwork::with(['artist.user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by artist status
        if ($request->filled('artist_status')) {
            $query->whereHas('artist', function ($q) use ($request) {
                $q->where('status', $request->artist_status);
            });
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'most_viewed':
                $query->orderBy('views', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $artworks = $query->paginate($perPage);

        // Statistics
        $stats = [
            'total' => Artwork::count(),
            'available' => Artwork::where('status', Artwork::STATUS_AVAILABLE)->count(),
            'sold' => Artwork::where('status', Artwork::STATUS_SOLD)->count(),
            'pending' => Artwork::where('status', Artwork::STATUS_PENDING)->count(),
        ];

        return view('admin.artworks.index', compact('artworks', 'stats'));
    }

    /**
     * Show artwork details
     */
    public function artworkShow(Artwork $artwork)
    {
        $artwork->load(['artist.user', 'artist.samples', 'orders']);
        return view('admin.artworks.show', compact('artwork'));
    }

    /**
     * Update artwork status
     */
    public function artworkUpdateStatus(Request $request, Artwork $artwork)
    {
        $request->validate([
            'status' => 'required|in:available,pending,sold',
        ]);

        $artwork->update(['status' => $request->status]);

        return back()->with('success', 'تم تحديث حالة العمل الفني بنجاح');
    }

    /**
     * Show orders management page
     */
    public function ordersIndex(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $query = Order::with(['buyer', 'artist.user', 'artwork']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('artist.user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by shipping status
        if ($request->filled('shipping_status')) {
            $query->where('shipping_status', $request->shipping_status);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'amount_high':
                $query->orderBy('total_amount', 'desc');
                break;
            case 'amount_low':
                $query->orderBy('total_amount', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $orders = $query->paginate($perPage);

        // Statistics
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('shipping_status', Order::SHIPPING_PENDING)->count(),
            'shipped' => Order::where('shipping_status', Order::SHIPPING_SHIPPED)->count(),
            'delivered' => Order::where('shipping_status', Order::SHIPPING_DELIVERED)->count(),
        ];

        return view('admin.orders.index', compact('orders', 'stats'));
    }

    /**
     * Show order details
     */
    public function orderShow(Order $order)
    {
        $order->load(['buyer', 'artist.user', 'artwork', 'transaction']);
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order shipping status
     */
    public function orderShip(Request $request, Order $order)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:255',
        ]);

        if (!$order->canBeShipped()) {
            return back()->with('error', 'لا يمكن شحن هذا الطلب في حالته الحالية');
        }

        $order->markAsShipped($request->tracking_number);

        return back()->with('success', 'تم تحديث حالة الشحن بنجاح');
    }

    /**
     * Mark order as delivered
     */
    public function orderDeliver(Order $order)
    {
        if (!$order->canBeDelivered()) {
            return back()->with('error', 'لا يمكن تسليم هذا الطلب في حالته الحالية');
        }

        $order->markAsDelivered();

        return back()->with('success', 'تم تأكيد تسليم الطلب بنجاح');
    }
}