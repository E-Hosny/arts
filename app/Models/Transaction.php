<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'artist_id',
        'amount',
        'commission',
        'net_amount',
        'status',
        'transfer_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'commission' => 'integer',
            'net_amount' => 'integer',
            'transfer_date' => 'datetime',
        ];
    }

    /**
     * Transaction status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Relationship: Transaction belongs to Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship: Transaction belongs to Artist
     */
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
}