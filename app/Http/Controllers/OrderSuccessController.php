<?php

namespace App\Http\Controllers;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderSuccessController extends Controller
{
    public function show($orderId)
    {
        $order = Order::find($orderId);
        
        if (!$order) {
            return response()->json([
                'message' => 'Заказ не найден'
            ], 404);
        }

        return response()->json([
            'message' => 'Заказ оформлен',
            'order_id' => $order->id,
            'status' => $order->status,
            'total_price' => $order->total_price,
            'created_at' => $order->created_at
        ]);
    }
}
