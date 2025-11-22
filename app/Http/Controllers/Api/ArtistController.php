<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Artist\StoreArtistRegistrationRequest;
use App\Models\Artist;
use App\Models\ArtistSample;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArtistController extends Controller
{
    /**
     * Register a new artist
     */
    public function register(StoreArtistRegistrationRequest $request)
    {
        DB::beginTransaction();

        try {
            // 1. Create user with artist role
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_ARTIST,
                'email_verified' => false,
            ]);

            // 2. Create artist profile
            $artist = Artist::create([
                'user_id' => $user->id,
                'status' => Artist::STATUS_PENDING,
                'bio' => $request->bio,
                'phone' => $request->phone,
                'city' => $request->city,
                'subscription_tier' => Artist::TIER_BASIC,
                'commission_rate' => 25,
                'total_sales' => 0,
                'verified' => false,
                'featured' => false,
            ]);

            // 3. Process and store samples
            $samplesData = [];
            foreach ($request->samples as $sampleData) {
                $imageUrl = $this->storeBase64Image($sampleData['image'], 'artist_samples');
                
                $sample = ArtistSample::create([
                    'artist_id' => $artist->id,
                    'title' => $sampleData['title'],
                    'description' => $sampleData['description'] ?? null,
                    'image_url' => $imageUrl,
                ]);

                $samplesData[] = [
                    'title' => $sample->title,
                    'image_url' => $sample->image_url,
                ];
            }

            // 4. Send email notification (placeholder for now)
            $emailSent = $this->sendArtistRegistrationEmail($user, $artist);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم استلام طلبك بنجاح. سيتم مراجعة أعمالك والرد خلال 3-5 أيام عمل.',
                'data' => [
                    'artist_id' => $artist->id,
                    'status' => $artist->status,
                    'samples_count' => count($samplesData),
                    'email_sent' => $emailSent,
                    'estimated_review_date' => now()->addDays(7)->toISOString(),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up any uploaded files
            if (isset($samplesData)) {
                foreach ($samplesData as $sample) {
                    if (isset($sample['image_url'])) {
                        Storage::disk('public')->delete(str_replace('/storage/', '', $sample['image_url']));
                    }
                }
            }

            Log::error('Artist registration error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->except('samples'), // Don't log image data
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الفنان',
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
     * Get artist registration status
     */
    public function status(Request $request)
    {
        try {
            $user = $request->user();

            // Check if user is an artist
            if (!$user->isArtist()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الحساب ليس حساب فنان',
                    'errors' => [
                        'role' => ['يجب أن تكون فناناً للوصول إلى هذه المعلومات'],
                    ],
                ], 403);
            }

            $artist = $user->artist;

            if (!$artist) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على ملف الفنان',
                    'errors' => [
                        'artist' => ['ملف الفنان غير موجود'],
                    ],
                ], 404);
            }

            // Build response based on status
            $responseData = $this->buildStatusResponse($artist);

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Artist status error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب حالة الفنان',
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
     * Store base64 image to storage
     */
    private function storeBase64Image(string $base64Data, string $directory): string
    {
        // Remove data URL prefix if exists (supports various formats)
        $cleanBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        
        // Decode base64 with strict mode
        $imageData = @base64_decode($cleanBase64, true);
        
        if ($imageData === false || empty($imageData)) {
            throw new \InvalidArgumentException('Invalid base64 image data');
        }

        // Detect image type from decoded data
        $imageInfo = @getimagesizefromstring($imageData);
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('Invalid image data');
        }

        // Determine file extension from MIME type
        $extension = 'jpg'; // default
        $mimeType = $imageInfo['mime'] ?? '';
        if (strpos($mimeType, 'png') !== false) {
            $extension = 'png';
        } elseif (strpos($mimeType, 'gif') !== false) {
            $extension = 'gif';
        } elseif (strpos($mimeType, 'webp') !== false) {
            $extension = 'webp';
        }

        // Generate unique filename
        $filename = Str::uuid() . '.' . $extension;
        $path = $directory . '/' . $filename;

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($directory);

        // Store file
        Storage::disk('public')->put($path, $imageData);

        // Return public URL
        return '/storage/' . $path;
    }

    /**
     * Build status response based on artist status
     */
    private function buildStatusResponse(Artist $artist): array
    {
        $baseData = [
            'status' => $artist->status,
        ];

        switch ($artist->status) {
            case Artist::STATUS_PENDING:
                return array_merge($baseData, [
                    'submitted_at' => $artist->created_at->toISOString(),
                    'samples_count' => $artist->samples()->count(),
                    'estimated_review_date' => $artist->created_at->addDays(7)->toISOString(),
                ]);

            case Artist::STATUS_APPROVED:
                return array_merge($baseData, [
                    'approved_at' => $artist->updated_at->toISOString(),
                    'commission_rate' => $artist->commission_rate,
                    'subscription_tier' => $artist->subscription_tier,
                    'total_sales' => $artist->total_sales,
                    'verified' => $artist->verified,
                    'featured' => $artist->featured,
                ]);

            case Artist::STATUS_REJECTED:
                return array_merge($baseData, [
                    'rejected_at' => $artist->updated_at->toISOString(),
                    'reason_rejection' => $artist->rejection_reason ?? 'لم يتم تحديد سبب الرفض',
                    'can_reapply_at' => $artist->can_reapply_at?->toISOString(),
                    'can_reapply_now' => $artist->canReapply(),
                ]);

            default:
                return $baseData;
        }
    }

    /**
     * Send artist registration email (placeholder)
     */
    private function sendArtistRegistrationEmail(User $user, Artist $artist): bool
    {
        // TODO: Implement actual email sending
        // This is a placeholder for future email implementation
        
        Log::info('Artist registration email should be sent', [
            'user_id' => $user->id,
            'artist_id' => $artist->id,
            'email' => $user->email,
            'name' => $user->name,
        ]);

        // For now, always return true
        return true;
    }
}