<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'artist_id',
        'title',
        'description',
        'price',
        'category',
        'dimensions',
        'materials',
        'images',
        'status',
        'views',
        'likes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'images' => 'array',
            'price' => 'integer',
            'views' => 'integer',
            'likes' => 'integer',
        ];
    }

    /**
     * Artwork status constants
     */
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SOLD = 'sold';

    /**
     * Check if artwork is available
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Check if artwork is sold
     */
    public function isSold(): bool
    {
        return $this->status === self::STATUS_SOLD;
    }

    /**
     * Increment views count
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Increment likes count
     */
    public function incrementLikes(): void
    {
        $this->increment('likes');
    }

    /**
     * Decrement likes count
     */
    public function decrementLikes(): void
    {
        $this->decrement('likes');
    }

    /**
     * Relationship: Artwork belongs to Artist
     */
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * Relationship: Artwork has many orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}