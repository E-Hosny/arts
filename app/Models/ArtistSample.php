<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtistSample extends Model
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
        'image_url',
    ];

    /**
     * Relationship: ArtistSample belongs to Artist
     */
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
}