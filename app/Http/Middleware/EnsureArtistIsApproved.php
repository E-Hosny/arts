<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureArtistIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول للوصول إلى هذا المسار',
                'errors' => [
                    'authentication' => ['غير مصرح بالوصول'],
                ],
            ], 401);
        }

        // Check if user is an artist
        if (!$request->user()->isArtist()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المسار مخصص للفنانين فقط',
                'errors' => [
                    'authorization' => ['يجب أن تكون فناناً للوصول إلى هذا المسار'],
                ],
            ], 403);
        }

        // Check if artist profile exists
        $artist = $request->user()->artist;
        if (!$artist) {
            return response()->json([
                'success' => false,
                'message' => 'ملف الفنان غير موجود',
                'errors' => [
                    'artist_profile' => ['لا يوجد ملف فنان مرتبط بهذا الحساب'],
                ],
            ], 404);
        }

        // Check if artist is approved
        if (!$artist->isApproved()) {
            $message = 'يجب اعتماد ملفك كفنان أولاً';
            $errorDetail = ['ملف الفنان غير معتمد'];

            if ($artist->isPending()) {
                $message = 'ملفك قيد المراجعة، يرجى الانتظار';
                $errorDetail = ['ملف الفنان قيد المراجعة من قبل الإدارة'];
            } elseif ($artist->isRejected()) {
                $message = 'تم رفض ملفك كفنان';
                $errorDetail = ['تم رفض ملف الفنان'];
                if ($artist->canReapply()) {
                    $errorDetail[] = 'يمكنك إعادة التقديم الآن';
                } elseif ($artist->can_reapply_at) {
                    $errorDetail[] = 'يمكنك إعادة التقديم في: ' . $artist->can_reapply_at->format('Y-m-d');
                }
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    'artist_status' => $errorDetail,
                ],
                'data' => [
                    'status' => $artist->status,
                    'can_reapply' => $artist->canReapply(),
                    'can_reapply_at' => $artist->can_reapply_at?->toISOString(),
                ],
            ], 403);
        }

        // Add artist to request for easy access
        $request->merge(['artist' => $artist]);

        return $next($request);
    }
}