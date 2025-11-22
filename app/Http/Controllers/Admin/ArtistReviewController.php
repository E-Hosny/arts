<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ArtistReviewController extends Controller
{
    /**
     * Get pending artists list with pagination
     */
    public function indexPending(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $perPage = min(max($perPage, 1), 50); // Limit between 1 and 50

            // Get pending artists with their user data and samples
            $artists = Artist::with(['user', 'samples'])
                ->where('status', Artist::STATUS_PENDING)
                ->orderBy('created_at', 'asc')
                ->paginate($perPage);

            // Transform the data
            $transformedArtists = $artists->map(function ($artist) {
                return [
                    'id' => $artist->id,
                    'name' => $artist->user->name,
                    'email' => $artist->user->email,
                    'phone' => $artist->phone,
                    'city' => $artist->city,
                    'bio' => $artist->bio,
                    'submitted_at' => $artist->created_at->toISOString(),
                    'samples' => $artist->samples->map(function ($sample) {
                        return [
                            'id' => $sample->id,
                            'title' => $sample->title,
                            'description' => $sample->description,
                            'image_url' => url($sample->image_url),
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'artists' => $transformedArtists,
                    'total' => $artists->total(),
                    'page' => $artists->currentPage(),
                    'per_page' => $artists->perPage(),
                    'last_page' => $artists->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Admin pending artists error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'admin_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة الفنانين',
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
     * Approve an artist
     */
    public function approve(Request $request, Artist $artist)
    {
        $validator = Validator::make($request->all(), [
            'commission_rate' => 'sometimes|integer|min:5|max:50',
            'featured' => 'sometimes|boolean',
            'notes_admin' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات القبول غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Check if artist is pending
            if ($artist->status !== Artist::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن قبول هذا الفنان في الوقت الحالي',
                    'errors' => [
                        'status' => ['الفنان ليس في حالة انتظار المراجعة'],
                    ],
                ], 400);
            }

            // Update artist status
            $artist->update([
                'status' => Artist::STATUS_APPROVED,
                'commission_rate' => $request->get('commission_rate', 25),
                'featured' => $request->get('featured', false),
                'notes_admin' => $request->get('notes_admin'),
                'approved_by' => $request->user()->id,
                'can_reapply_at' => null, // Clear reapply date
                'rejection_reason' => null, // Clear rejection reason
            ]);

            // Send approval notification (placeholder)
            $notificationSent = $this->sendArtistApprovalNotification($artist);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم قبول الفنان بنجاح',
                'data' => [
                    'artist_id' => $artist->id,
                    'status' => $artist->status,
                    'commission_rate' => $artist->commission_rate,
                    'featured' => $artist->featured,
                    'notification_sent' => $notificationSent,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Artist approval error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artist_id' => $artist->id,
                'admin_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء قبول الفنان',
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
     * Reject an artist
     */
    public function reject(Request $request, Artist $artist)
    {
        $validator = Validator::make($request->all(), [
            'reason_rejection' => 'required|string|max:1000',
            'notes_admin' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الرفض غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Check if artist is pending
            if ($artist->status !== Artist::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن رفض هذا الفنان في الوقت الحالي',
                    'errors' => [
                        'status' => ['الفنان ليس في حالة انتظار المراجعة'],
                    ],
                ], 400);
            }

            // Update artist status
            $artist->update([
                'status' => Artist::STATUS_REJECTED,
                'rejection_reason' => $request->reason_rejection,
                'notes_admin' => $request->get('notes_admin'),
                'approved_by' => $request->user()->id,
                'can_reapply_at' => now()->addDays(30), // Can reapply after 30 days
                'featured' => false,
            ]);

            // Send rejection notification (placeholder)
            $notificationSent = $this->sendArtistRejectionNotification($artist);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم رفض الفنان',
                'data' => [
                    'artist_id' => $artist->id,
                    'status' => $artist->status,
                    'reason_rejection' => $artist->rejection_reason,
                    'can_reapply_at' => $artist->can_reapply_at->toISOString(),
                    'notification_sent' => $notificationSent,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Artist rejection error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artist_id' => $artist->id,
                'admin_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفض الفنان',
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
     * Get artist details for review
     */
    public function show(Artist $artist)
    {
        try {
            $artist->load(['user', 'samples']);

            $artistData = [
                'id' => $artist->id,
                'name' => $artist->user->name,
                'email' => $artist->user->email,
                'phone' => $artist->phone,
                'city' => $artist->city,
                'bio' => $artist->bio,
                'status' => $artist->status,
                'commission_rate' => $artist->commission_rate,
                'featured' => $artist->featured,
                'submitted_at' => $artist->created_at->toISOString(),
                'notes_admin' => $artist->notes_admin,
                'rejection_reason' => $artist->rejection_reason,
                'can_reapply_at' => $artist->can_reapply_at?->toISOString(),
                'samples' => $artist->samples->map(function ($sample) {
                    return [
                        'id' => $sample->id,
                        'title' => $sample->title,
                        'description' => $sample->description,
                        'image_url' => url($sample->image_url),
                        'created_at' => $sample->created_at->toISOString(),
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'artist' => $artistData,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Artist show error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artist_id' => $artist->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الفنان',
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
     * Send artist approval notification (placeholder)
     */
    private function sendArtistApprovalNotification(Artist $artist): bool
    {
        // TODO: Implement actual email/notification sending
        Log::info('Artist approval notification should be sent', [
            'artist_id' => $artist->id,
            'user_id' => $artist->user_id,
            'email' => $artist->user->email,
            'name' => $artist->user->name,
        ]);

        return true;
    }

    /**
     * Send artist rejection notification (placeholder)
     */
    private function sendArtistRejectionNotification(Artist $artist): bool
    {
        // TODO: Implement actual email/notification sending
        Log::info('Artist rejection notification should be sent', [
            'artist_id' => $artist->id,
            'user_id' => $artist->user_id,
            'email' => $artist->user->email,
            'name' => $artist->user->name,
            'reason' => $artist->rejection_reason,
        ]);

        return true;
    }
}