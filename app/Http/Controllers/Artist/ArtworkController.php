<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Artwork\StoreArtworkRequest;
use App\Http\Requests\Artwork\UpdateArtworkRequest;
use App\Http\Resources\ArtworkCollection;
use App\Http\Resources\ArtworkResource;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArtworkController extends Controller
{
    /**
     * Display a listing of the artist's artworks
     */
    public function index(Request $request)
    {
        try {
            $artist = $request->artist; // From middleware
            $perPage = min(max($request->get('per_page', 12), 1), 50);

            $artworks = $artist->artworks()
                ->with(['artist.user'])
                ->when($request->status, function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return new ArtworkCollection($artworks);

        } catch (\Exception $e) {
            Log::error('Artist artworks index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artist_id' => $request->artist->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الأعمال الفنية',
                'errors' => [
                    'general' => [config('app.debug') ? $e->getMessage() : 'خطأ غير متوقع']
                ],
            ], 500);
        }
    }

    /**
     * Store a newly created artwork
     */
    public function store(StoreArtworkRequest $request)
    {
        DB::beginTransaction();

        try {
            $artist = $request->artist; // From middleware

            // Process and store images
            $imageUrls = [];
            foreach ($request->images as $imageData) {
                $imageUrl = $this->storeBase64Image($imageData, 'artworks');
                $imageUrls[] = $imageUrl;
            }

            // Create artwork
            $artwork = $artist->artworks()->create([
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
                'category' => $request->category,
                'dimensions' => $request->dimensions,
                'materials' => $request->materials,
                'images' => $imageUrls,
                'status' => Artwork::STATUS_AVAILABLE,
                'views' => 0,
                'likes' => 0,
            ]);

            DB::commit();

            return new ArtworkResource($artwork->load('artist.user'));

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded files
            if (isset($imageUrls)) {
                foreach ($imageUrls as $imageUrl) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $imageUrl));
                }
            }

            Log::error('Artwork store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artist_id' => $request->artist->id,
                'request' => $request->except('images'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة العمل الفني',
                'errors' => [
                    'general' => [config('app.debug') ? $e->getMessage() : 'خطأ غير متوقع']
                ],
            ], 500);
        }
    }

    /**
     * Display the specified artwork
     */
    public function show(Request $request, Artwork $artwork)
    {
        try {
            $artist = $request->artist; // From middleware

            // Check if artwork belongs to the artist
            if ($artwork->artist_id !== $artist->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العمل الفني غير موجود',
                    'errors' => [
                        'artwork' => ['العمل الفني غير موجود أو لا تملك صلاحية لعرضه'],
                    ],
                ], 404);
            }

            return new ArtworkResource($artwork->load('artist.user'));

        } catch (\Exception $e) {
            Log::error('Artist artwork show error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artwork_id' => $artwork->id,
                'artist_id' => $request->artist->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل العمل الفني',
                'errors' => [
                    'general' => [config('app.debug') ? $e->getMessage() : 'خطأ غير متوقع']
                ],
            ], 500);
        }
    }

    /**
     * Update the specified artwork
     */
    public function update(UpdateArtworkRequest $request, Artwork $artwork)
    {
        DB::beginTransaction();

        try {
            $artist = $request->artist; // From middleware

            // Check if artwork belongs to the artist
            if ($artwork->artist_id !== $artist->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العمل الفني غير موجود',
                    'errors' => [
                        'artwork' => ['العمل الفني غير موجود أو لا تملك صلاحية لتعديله'],
                    ],
                ], 404);
            }

            $updateData = $request->only([
                'title', 'description', 'price', 'category', 
                'dimensions', 'materials', 'status'
            ]);

            // Handle images if provided
            if ($request->has('images')) {
                // Store old images for cleanup
                $oldImages = $artwork->images ?? [];

                // Process new images
                $newImageUrls = [];
                foreach ($request->images as $imageData) {
                    $imageUrl = $this->storeBase64Image($imageData, 'artworks');
                    $newImageUrls[] = $imageUrl;
                }

                $updateData['images'] = $newImageUrls;
            }

            // Update artwork
            $artwork->update($updateData);

            // Clean up old images if new ones were uploaded
            if (isset($oldImages) && isset($newImageUrls)) {
                foreach ($oldImages as $oldImage) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $oldImage));
                }
            }

            DB::commit();

            return new ArtworkResource($artwork->fresh()->load('artist.user'));

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up new uploaded files
            if (isset($newImageUrls)) {
                foreach ($newImageUrls as $imageUrl) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $imageUrl));
                }
            }

            Log::error('Artwork update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artwork_id' => $artwork->id,
                'artist_id' => $request->artist->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث العمل الفني',
                'errors' => [
                    'general' => [config('app.debug') ? $e->getMessage() : 'خطأ غير متوقع']
                ],
            ], 500);
        }
    }

    /**
     * Remove the specified artwork (soft delete by changing status)
     */
    public function destroy(Request $request, Artwork $artwork)
    {
        try {
            $artist = $request->artist; // From middleware

            // Check if artwork belongs to the artist
            if ($artwork->artist_id !== $artist->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العمل الفني غير موجود',
                    'errors' => [
                        'artwork' => ['العمل الفني غير موجود أو لا تملك صلاحية لحذفه'],
                    ],
                ], 404);
            }

            // Soft delete by changing status instead of actual deletion
            $artwork->update(['status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف العمل الفني بنجاح',
                'data' => [
                    'artwork_id' => $artwork->id,
                    'status' => 'deleted',
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Artwork destroy error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artwork_id' => $artwork->id,
                'artist_id' => $request->artist->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف العمل الفني',
                'errors' => [
                    'general' => [config('app.debug') ? $e->getMessage() : 'خطأ غير متوقع']
                ],
            ], 500);
        }
    }

    /**
     * Store base64 image to storage
     */
    private function storeBase64Image(string $base64Data, string $directory): string
    {
        // Remove data URL prefix if exists
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
}