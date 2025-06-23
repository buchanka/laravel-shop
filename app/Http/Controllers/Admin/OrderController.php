<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:admin']);
    }

    /**
     * Просмотр всех заказов с пользователями и товарами
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $orders = $query->paginate(10);
        
        return response()->json([
            'orders' => $orders->items(),
            'total' => $orders->total()
        ]);
    }

    /**
     * Изменение статуса заказа с валидацией переходов
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['подтвержден', 'отправлен', 'доставлен', 'отменен'])
            ]
        ]);

        // Проверка допустимых переходов статусов
        if ($order->status === 'принят' && !in_array($validated['status'], ['подтвержден', 'отменен'])) {
            return response()->json([
                'message' => 'Заказ со статусом "принят" можно перевести только в "подтвержден" или "отменен"'
            ], 422);
        }

        if ($order->status === 'подтвержден' && !in_array($validated['status'], ['отправлен', 'доставлен', 'отменен'])) {
            return response()->json([
                'message' => 'Заказ со статусом "подтвержден" можно перевести только в "отправлен", "доставлен" или "отменен"'
            ], 422);
        }

        if ($order->status === 'отменен') {
            return response()->json([
                'message' => 'Отмененный заказ нельзя изменить'
            ], 422);
        }

        if (in_array($order->status, ['отправлен', 'доставлен'])) {
            return response()->json([
                'message' => 'Заказ уже завершен (отправлен или доставлен)'
            ], 422);
        }

        
        if ($validated['status'] === 'отменен') {
            return DB::transaction(function () use ($order) {
                
                foreach ($order->items as $item) {
                    $item->product->increment('stock', $item->quantity);
                }

                $order->update(['status' => 'отменен']);

                return response()->json([
                    'message' => 'Заказ отменен, товары возвращены на склад',
                    'order' => $order->fresh(['user', 'items.product'])
                ]);
            });
        }

        // Обновление статуса
        $order->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Статус заказа успешно обновлен',
            'order' => $order->fresh(['user', 'items.product'])
        ]);
    }
}
