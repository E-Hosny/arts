<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user (buyer only)
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_BUYER, // Only buyers can register directly
                'avatar_url' => $request->avatar_url,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'تم التسجيل بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'avatar_url' => $user->avatar_url,
                        'email_verified' => $user->email_verified,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Registration error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التسجيل',
                'errors' => [
                    'general' => [
                        config('app.debug') 
                            ? $e->getMessage() 
                            : 'حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى'
                    ],
                ],
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات تسجيل الدخول غير صحيحة',
                    'errors' => [
                        'credentials' => ['البريد الإلكتروني أو كلمة المرور غير صحيحة'],
                    ],
                ], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'avatar_url' => $user->avatar_url,
                        'email_verified' => $user->email_verified,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Login error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->only('email'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول',
                'errors' => [
                    'general' => [
                        config('app.debug') 
                            ? $e->getMessage() 
                            : 'حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى'
                    ],
                ],
            ], 500);
        }
    }

    /**
     * Get authenticated user info
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
                'email_verified' => $user->email_verified,
                'created_at' => $user->created_at,
            ];

            // If user is an artist, include artist profile
            if ($user->isArtist() && $user->artist) {
                $userData['artist_profile'] = [
                    'id' => $user->artist->id,
                    'status' => $user->artist->status,
                    'bio' => $user->artist->bio,
                    'phone' => $user->artist->phone,
                    'city' => $user->artist->city,
                    'subscription_tier' => $user->artist->subscription_tier,
                    'commission_rate' => $user->artist->commission_rate,
                    'total_sales' => $user->artist->total_sales,
                    'verified' => $user->artist->verified,
                    'featured' => $user->artist->featured,
                    'can_reapply_at' => $user->artist->can_reapply_at,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب بيانات المستخدم بنجاح',
                'data' => [
                    'user' => $userData,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المستخدم',
                'errors' => [
                    'general' => ['حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى'],
                ],
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            // Revoke the token that was used to authenticate the current request
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'errors' => [
                    'general' => ['حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى'],
                ],
            ], 500);
        }
    }
}