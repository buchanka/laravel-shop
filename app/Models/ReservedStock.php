<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservedStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'session_id',
        'user_id',
        'quantity',
        'expires_at',
    ];

    protected $dates = ['expires_at'];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}