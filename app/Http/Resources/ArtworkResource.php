<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtworkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Helper function to ensure UTF-8 encoding
        $ensureUtf8 = function ($value) {
            if (is_string($value)) {
                return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
            return $value;
        };

        return [
            'id' => $this->id,
            'title' => $ensureUtf8($this->title),
            'description' => $ensureUtf8($this->description),
            'price' => (int) $this->price,
            'category' => $ensureUtf8($this->category),
            'dimensions' => $this->dimensions ? $ensureUtf8($this->dimensions) : null,
            'materials' => $this->materials ? $ensureUtf8($this->materials) : null,
            'images' => $this->images ? array_map(fn($img) => url($img), $this->images) : [],
            'main_image' => $this->images && count($this->images) > 0 ? url($this->images[0]) : null,
            'status' => $this->status,
            'views' => (int) $this->views,
            'likes' => (int) $this->likes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Artist information (when loaded)
            'artist' => $this->whenLoaded('artist', function () use ($ensureUtf8) {
                return [
                    'id' => $this->artist->id,
                    'name' => $ensureUtf8($this->artist->user->name ?? ''),
                    'city' => $ensureUtf8($this->artist->city ?? ''),
                    'bio' => $ensureUtf8($this->artist->bio ?? ''),
                    'subscription_tier' => $this->artist->subscription_tier,
                    'verified' => (bool) $this->artist->verified,
                    'featured' => (bool) $this->artist->featured,
                ];
            }),
            
            // Detailed artist info (for single artwork view)
            'artist_details' => $this->when($this->relationLoaded('artist') && $request->routeIs('artworks.show'), function () use ($ensureUtf8, $request) {
                return [
                    'id' => $this->artist->id,
                    'name' => $ensureUtf8($this->artist->user->name ?? ''),
                    'email' => $this->artist->user->email ?? '', // Only for owner
                    'city' => $ensureUtf8($this->artist->city ?? ''),
                    'bio' => $ensureUtf8($this->artist->bio ?? ''),
                    'phone' => $this->when($this->isOwner($request), $this->artist->phone),
                    'subscription_tier' => $this->artist->subscription_tier,
                    'commission_rate' => $this->when($this->isOwner($request), $this->artist->commission_rate),
                    'verified' => (bool) $this->artist->verified,
                    'featured' => (bool) $this->artist->featured,
                    'total_sales' => (int) $this->artist->total_sales,
                    'samples_count' => $this->artist->samples()->count(),
                    'artworks_count' => $this->artist->artworks()->where('status', 'available')->count(),
                ];
            }),
        ];
    }

    /**
     * Check if the current user is the owner of this artwork
     */
    private function isOwner(Request $request): bool
    {
        return $request->user() && 
               $request->user()->isArtist() && 
               $request->user()->artist &&
               $request->user()->artist->id === $this->artist_id;
    }

    /**
     * Get additional data to be returned at the top level.
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}