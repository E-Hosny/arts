<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
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

        // Check if user is admin
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المسار',
                'errors' => [
                    'authorization' => ['يجب أن تكون مديراً للوصول إلى هذه الصفحة'],
                ],
            ], 403);
        }

        return $next($request);
    }
}