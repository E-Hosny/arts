<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'bio',
        'phone',
        'city',
        'subscription_tier',
        'commission_rate',
        'total_sales',
        'verified',
        'featured',
        'rejection_reason',
        'can_reapply_at',
        'notes_admin',
        'approved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'featured' => 'boolean',
            'can_reapply_at' => 'datetime',
            'total_sales' => 'integer',
            'commission_rate' => 'integer',
        ];
    }

    /**
     * Artist status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Subscription tier constants
     */
    public const TIER_BASIC = 'basic';
    public const TIER_PROFESSIONAL = 'professional';
    public const TIER_ELITE = 'elite';

    /**
     * Check if artist is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if artist is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if artist is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if artist can reapply
     */
    public function canReapply(): bool
    {
        return $this->can_reapply_at && now()->gte($this->can_reapply_at);
    }

    /**
     * Relationship: Artist belongs to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Artist has many samples
     */
    public function samples()
    {
        return $this->hasMany(ArtistSample::class);
    }

    /**
     * Relationship: Artist has many artworks
     */
    public function artworks()
    {
        return $this->hasMany(Artwork::class);
    }

    /**
     * Relationship: Artist has many orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relationship: Artist has many transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Relationship: Artist approved by admin
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}