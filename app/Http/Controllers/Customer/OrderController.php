<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:customer']);
    }

    public function index(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $orders = $request->user()
                ->orders()
                ->with(['items.product'])
                ->latest()
                ->get();

            return response()->json([
                'orders' => $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'status' => $order->status,
                        'total_price' => $order->total_price,
                        'created_at' => $order->created_at,
                        'tracking_number' => $order->tracking_number,
                        'image' => $order->image,
                        'items' => $order->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product_id' => $item->product_id,
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                                'image' => $item->image,
                                'product' => $item->product ? [
                                    'id' => $item->product->id,
                                    'name' => $item->product->name,
                                    'price' => $item->product->price,
                                    'image' => $item->product->image
                                ] : null
                            ];
                        })
                    ];
                })
            ]);
        });
    }

    public function store(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $cart = $user->cart()->with('items.product')->firstOrFail();

            if ($cart->items->isEmpty()) {
                return response()->json(['message' => 'Корзина пуста'], 422);
            }

            foreach ($cart->items as $item) {
                if ($item->product->stock < $item->quantity) {
                    throw new \Exception("Товар '{$item->product->name}' недоступен в нужном количестве");
                }
            }

            $order = $user->orders()->create([
                'status' => 'принят',
                'total_price' => $cart->total_price,
                'shipping_address' => $request->input('address'),
                'phone' => $request->input('phone')
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'image' => $item->product->image
                ]);

                // Уменьшаем количество товара на складе
                $item->product->decrement('stock', $item->quantity);
            }

            $cart->items()->delete();
            $cart->update(['total_price' => 0]);

            return response()->json([
                'message' => 'Заказ успешно создан',
                'order' => $order->load('items.product')
            ], 201);
        });
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorize('cancel', $order);

        return DB::transaction(function () use ($order) {
            if ($order->status !== 'принят') {
                throw new \Exception("Можно отменять только заказы со статусом 'принят'");
            }

            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'отменен']);

            return response()->json([
                'message' => 'Заказ успешно отменен',
                'order' => $order->fresh()
            ]);
        });
    }
}