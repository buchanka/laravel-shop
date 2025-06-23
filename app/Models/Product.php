<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model

{
    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'image',
        'height',
        'width',
        'length',
        'burn_time',
        'stock',
    ];

        public function reserve(int $quantity, $userOrSession)
    {
        return \DB::transaction(function () use ($quantity, $userOrSession) {
            if ($this->stock < $quantity) {
                throw new \Exception("Недостаточно товара в наличии.");
            }

            
            $this->decrement('stock', $quantity);

            $reservationData = [
                'product_id' => $this->id,
                'quantity' => $quantity,
                'expires_at' => now()->addHours(2),
            ];

            if (is_numeric($userOrSession)) {
                $reservationData['user_id'] = $userOrSession;
            } else {
                $reservationData['session_id'] = $userOrSession;
            }

            return ReservedStock::create($reservationData);
        });
    }


    public function releaseReservation(int $reservationId)
    {
        return \DB::transaction(function () use ($reservationId) {
            $reservation = ReservedStock::findOrFail($reservationId);

            $this->increment('stock', $reservation->quantity);

            $reservation->delete();
        });
    }


    public function confirmReservation(int $reservationId)
    {
        return \DB::transaction(function () use ($reservationId) {
            $reservation = ReservedStock::findOrFail($reservationId);
            $reservation->delete(); 
        });
    }

  
    public function getAvailableStockAttribute()
    {
        $reserved = $this->reservedStocks()->sum('quantity');
        return $this->stock - $reserved;
    }

    
    public function reservedStocks()
    {
        return $this->hasMany(ReservedStock::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
            ->withPivot('quantity', 'price');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }
}
