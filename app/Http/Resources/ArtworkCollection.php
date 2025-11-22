<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ArtworkCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ArtworkResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($artwork) use ($request) {
                // Ensure all string values are UTF-8 safe
                $description = $artwork->description ?? '';
                $title = $artwork->title ?? '';
                $dimensions = $artwork->dimensions ?? null;
                $category = $artwork->category ?? '';
                
                // Clean and validate UTF-8 strings
                $description = mb_convert_encoding($description, 'UTF-8', 'UTF-8');
                $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');
                if ($dimensions) {
                    $dimensions = mb_convert_encoding($dimensions, 'UTF-8', 'UTF-8');
                }
                
                $artistName = $artwork->artist->user->name ?? '';
                $artistCity = $artwork->artist->city ?? '';
                $artistName = mb_convert_encoding($artistName, 'UTF-8', 'UTF-8');
                $artistCity = mb_convert_encoding($artistCity, 'UTF-8', 'UTF-8');
                
                return [
                    'id' => $artwork->id,
                    'title' => $title,
                    'description' => $this->truncateText($description, 150),
                    'price' => (int) $artwork->price,
                    'category' => $category,
                    'dimensions' => $dimensions,
                    'main_image' => $artwork->images && count($artwork->images) > 0 ? url($artwork->images[0]) : null,
                    'images_count' => $artwork->images ? count($artwork->images) : 0,
                    'status' => $artwork->status,
                    'views' => (int) $artwork->views,
                    'likes' => (int) $artwork->likes,
                    'created_at' => $artwork->created_at?->toISOString(),
                    
                    // Artist basic info
                    'artist' => [
                        'id' => $artwork->artist->id,
                        'name' => $artistName,
                        'city' => $artistCity,
                        'verified' => (bool) $artwork->artist->verified,
                        'featured' => (bool) $artwork->artist->featured,
                    ],
                ];
            })->values(),
        ];
    }

    /**
     * Get additional data to be returned at the top level.
     */
    public function with(Request $request): array
    {
        $pagination = $this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator 
            ? [
                'pagination' => [
                    'total' => $this->resource->total(),
                    'count' => $this->resource->count(),
                    'per_page' => $this->resource->perPage(),
                    'current_page' => $this->resource->currentPage(),
                    'last_page' => $this->resource->lastPage(),
                    'from' => $this->resource->firstItem(),
                    'to' => $this->resource->lastItem(),
                ]
            ] 
            : [];

        return array_merge([
            'success' => true,
            'message' => 'تم جلب الأعمال الفنية بنجاح',
        ], $pagination);
    }

    /**
     * Truncate text to specified length (UTF-8 safe)
     */
    private function truncateText(?string $text, int $length): ?string
    {
        if (!$text) {
            return null;
        }
        
        // Use mb_strlen and mb_substr for UTF-8 safe operations
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length, 'UTF-8') . '...';
    }
}