<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArtworkCollection;
use App\Http\Resources\ArtworkResource;
use App\Models\Artwork;
use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ArtworkController extends Controller
{
    /**
     * Display a listing of public artworks with search and filters
     */
    public function index(Request $request)
    {
        try {
            $perPage = min(max($request->get('per_page', 12), 1), 50);

            $query = Artwork::with(['artist.user'])
                // Only show available artworks from approved artists
                ->where('artworks.status', Artwork::STATUS_AVAILABLE)
                ->whereHas('artist', function ($query) {
                    $query->where('status', Artist::STATUS_APPROVED);
                });

            // Search in title and description
            if ($request->filled('q')) {
                $searchTerm = $request->q;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            // Filter by category
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            // Filter by city (from artist)
            if ($request->filled('city')) {
                $query->whereHas('artist', function ($q) use ($request) {
                    $q->where('city', 'like', "%{$request->city}%");
                });
            }

            // Filter by price range
            if ($request->filled('price_min')) {
                $query->where('price', '>=', $request->price_min);
            }

            if ($request->filled('price_max')) {
                $query->where('price', '<=', $request->price_max);
            }

            // Sorting
            $sort = $request->get('sort', 'latest');
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'highest_price':
                    $query->orderBy('price', 'desc');
                    break;
                case 'lowest_price':
                    $query->orderBy('price', 'asc');
                    break;
                case 'most_viewed':
                    $query->orderBy('views', 'desc');
                    break;
                case 'most_liked':
                    $query->orderBy('likes', 'desc');
                    break;
                case 'latest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $artworks = $query->paginate($perPage);

            // Ensure proper JSON encoding
            $response = new ArtworkCollection($artworks);
            
            // Return with proper headers
            return $response->response()->header('Content-Type', 'application/json; charset=utf-8');

        } catch (\Exception $e) {
            Log::error('Artworks index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
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
     * Display the specified artwork with view counter increment
     */
    public function show(Artwork $artwork)
    {
        try {
            // Check if artwork is available and artist is approved
            if ($artwork->status !== Artwork::STATUS_AVAILABLE) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العمل الفني غير متاح للعرض',
                    'errors' => [
                        'artwork' => ['العمل الفني غير متاح أو تم حذفه'],
                    ],
                ], 404);
            }

            // Load artist relationship
            $artwork->load('artist.user');

            // Check if artist is approved
            if (!$artwork->artist || $artwork->artist->status !== Artist::STATUS_APPROVED) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العمل الفني غير متاح للعرض',
                    'errors' => [
                        'artist' => ['الفنان غير معتمد أو تم إيقاف حسابه'],
                    ],
                ], 404);
            }

            // Increment view counter
            $artwork->incrementViews();

            $response = new ArtworkResource($artwork);
            
            // Return with proper headers
            return $response->response()->header('Content-Type', 'application/json; charset=utf-8');

        } catch (\Exception $e) {
            Log::error('Artwork show error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artwork_id' => $artwork->id,
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
     * Get artwork categories
     */
    public function categories()
    {
        try {
            $categories = [
                [
                    'value' => 'painting',
                    'label_ar' => 'الرسم والتلوين',
                    'label_en' => 'Painting',
                    'description' => 'الرسم الزيتي، الألوان المائية، الأكريليك'
                ],
                [
                    'value' => 'sculpture',
                    'label_ar' => 'النحت',
                    'label_en' => 'Sculpture',
                    'description' => 'النحت في الحجر، الخشب، المعدن'
                ],
                [
                    'value' => 'photography',
                    'label_ar' => 'التصوير الفوتوغرافي',
                    'label_en' => 'Photography',
                    'description' => 'التصوير الفني والطبيعي'
                ],
                [
                    'value' => 'digital_art',
                    'label_ar' => 'الفن الرقمي',
                    'label_en' => 'Digital Art',
                    'description' => 'الرسم الرقمي والتصميم الجرافيكي'
                ],
                [
                    'value' => 'traditional_art',
                    'label_ar' => 'الفن التراثي',
                    'label_en' => 'Traditional Art',
                    'description' => 'الفنون الشعبية والتراثية السعودية'
                ],
                [
                    'value' => 'calligraphy',
                    'label_ar' => 'الخط العربي',
                    'label_en' => 'Arabic Calligraphy',
                    'description' => 'فن الخط العربي والزخرفة الإسلامية'
                ],
                [
                    'value' => 'mixed_media',
                    'label_ar' => 'الوسائط المختلطة',
                    'label_en' => 'Mixed Media',
                    'description' => 'أعمال تجمع بين عدة وسائل فنية'
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'تم جلب فئات الأعمال الفنية بنجاح',
                'data' => [
                    'categories' => $categories,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Categories error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الفئات',
                'errors' => [
                    'general' => [config('app.debug') ? $e->getMessage() : 'خطأ غير متوقع']
                ],
            ], 500);
        }
    }

    /**
     * Get featured artworks
     */
    public function featured(Request $request)
    {
        try {
            $perPage = min(max($request->get('per_page', 8), 1), 20);

            $artworks = Artwork::with(['artist.user'])
                ->where('artworks.status', Artwork::STATUS_AVAILABLE)
                ->whereHas('artist', function ($query) {
                    $query->where('status', Artist::STATUS_APPROVED)
                          ->where('featured', true);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return new ArtworkCollection($artworks);

        } catch (\Exception $e) {
            Log::error('Featured artworks error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الأعمال المميزة',
                'errors' => [
                    'general' => [config('app.debug') ? $e->getMessage() : 'خطأ غير متوقع']
                ],
            ], 500);
        }
    }

    /**
     * Search artworks by artist name
     */
    public function searchByArtist(Request $request)
    {
        try {
            $perPage = min(max($request->get('per_page', 12), 1), 50);
            $artistName = $request->get('artist');

            if (!$artistName) {
                return response()->json([
                    'success' => false,
                    'message' => 'اسم الفنان مطلوب للبحث',
                    'errors' => [
                        'artist' => ['يجب تحديد اسم الفنان للبحث'],
                    ],
                ], 422);
            }

            $artworks = Artwork::with(['artist.user'])
                ->where('artworks.status', Artwork::STATUS_AVAILABLE)
                ->whereHas('artist', function ($query) use ($artistName) {
                    $query->where('status', Artist::STATUS_APPROVED);
                })
                ->whereHas('artist.user', function ($query) use ($artistName) {
                    $query->where('name', 'like', "%{$artistName}%");
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return new ArtworkCollection($artworks);

        } catch (\Exception $e) {
            Log::error('Search by artist error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'artist_name' => $request->get('artist'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث',
                'errors' => [
                    'general' => [config('app.debug') ? $e->getMessage() : 'خطأ غير متوقع']
                ],
            ], 500);
        }
    }
}