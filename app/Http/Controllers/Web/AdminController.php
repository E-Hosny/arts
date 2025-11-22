<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\User;
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
}