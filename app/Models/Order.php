<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'buyer_id',
        'artwork_id',
        'artist_id',
        'total_amount',
        'commission',
        'artist_earnings',
        'payment_method',
        'payment_status',
        'payment_id',
        'shipping_status',
        'tracking_number',
        'buyer_name',
        'buyer_phone',
        'shipping_address',
        'delivered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'commission' => 'integer',
            'artist_earnings' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Payment status constants
     */
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_COMPLETED = 'completed';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    /**
     * Shipping status constants
     */
    public const SHIPPING_PENDING = 'pending';
    public const SHIPPING_SHIPPED = 'shipped';
    public const SHIPPING_DELIVERED = 'delivered';
    public const SHIPPING_CANCELLED = 'cancelled';

    /**
     * Check if payment is completed
     */
    public function isPaymentCompleted(): bool
    {
        return $this->payment_status === self::PAYMENT_COMPLETED;
    }

    /**
     * Check if order is delivered
     */
    public function isDelivered(): bool
    {
        return $this->shipping_status === self::SHIPPING_DELIVERED;
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->shipping_status === self::SHIPPING_CANCELLED;
    }

    /**
     * Relationship: Order belongs to User (buyer)
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Relationship: Order belongs to Artist
     */
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * Relationship: Order belongs to Artwork
     */
    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    /**
     * Relationship: Order has one transaction
     */
    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
}