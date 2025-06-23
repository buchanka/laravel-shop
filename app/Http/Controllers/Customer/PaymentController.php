<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:customer']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'orderId' => 'required|exists:orders,id',
            'cardLastFour' => 'required|string|size:4',
            'amount' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $order = Order::findOrFail($request->orderId);

            // Проверяем, что заказ принадлежит пользователю
            if ($order->user_id !== $request->user()->id) {
                abort(403, 'Unauthorized');
            }

            // Создаем запись о платеже
            $payment = Payment::create([
                'order_id' => $order->id,
                'card_last_four' => $request->cardLastFour,
                'amount' => $request->amount,
                'status' => Payment::STATUS_PENDING
            ]);

            // Обновляем статус заказа
            $order->update(['status' => Payment::STATUS_VERIFIED]);

            return response()->json([
                'message' => 'Платеж успешно обработан',
                'payment' => $payment
            ]);
        });
    }
}