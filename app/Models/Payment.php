<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'card_last_four',
        'amount',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the order that owns the payment.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Payment statuses.
     */
    public const STATUS_PENDING = 'принят';
    public const STATUS_VERIFIED = 'подтвержден';
    public const STATUS_COMPLETED = 'доставлен';
    public const STATUS_SHIPPED = 'отправлен';
    public const STATUS_REFUNDED = 'отменен';

    /**
     * Get all available payment statuses.
     *
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_VERIFIED,
            self::STATUS_COMPLETED,
            self::STATUS_SHIPPED,
            self::STATUS_REFUNDED,
        ];
    }
}
