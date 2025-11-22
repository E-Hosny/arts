<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * User roles constants
     */
    public const ROLE_ARTIST = 'artist';
    public const ROLE_BUYER = 'buyer';
    public const ROLE_ADMIN = 'admin';

    /**
     * Check if user is an artist
     */
    public function isArtist(): bool
    {
        return $this->role === self::ROLE_ARTIST;
    }

    /**
     * Check if user is a buyer
     */
    public function isBuyer(): bool
    {
        return $this->role === self::ROLE_BUYER;
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Relationship: User has one artist profile
     */
    public function artist()
    {
        return $this->hasOne(Artist::class);
    }

    /**
     * Relationship: User's orders (as buyer)
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }
}
