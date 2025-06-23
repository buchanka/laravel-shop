<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:customer']);
    }

    public function index(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $cart = $request->user()->cart()->with('items.product')->first();
            
            return response()->json([
                'cart' => $cart ?? ['items' => []]
            ]);
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'sometimes|integer|min:1'
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $user = $request->user();
            $cart = $user->cart()->firstOrCreate(['user_id' => $user->id]);
            $product = Product::findOrFail($validated['product_id']);

            if ($product->stock < ($validated['quantity'] ?? 1)) {
                throw new \Exception("Недостаточно товара в наличии");
            }

            $cartItem = $cart->items()
                ->where('product_id', $product->id)
                ->first();

            if ($cartItem) {
                $this->authorize('update', $cartItem);
                $cartItem->increment('quantity', $validated['quantity'] ?? 1);
            } else {
                $cartItem = $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $validated['quantity'] ?? 1,
                    'price' => (float) $product->price
                ]);
            }

            // Резервируем товар
            $product->decrement('stock', $validated['quantity'] ?? 1);

            $cart->recalculateTotal();

            return response()->json([
                'message' => 'Товар добавлен в корзину',
                'item' => $cartItem->fresh()
            ], 201);
        });
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorize('update', $cartItem);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        return DB::transaction(function () use ($cartItem, $validated) {
            $product = $cartItem->product;
            $quantityDiff = $validated['quantity'] - $cartItem->quantity;

            // Проверка доступного количества
            if ($product->stock < $quantityDiff) {
                throw new \Exception("Недостаточно товара в наличии");
            }

            $cartItem->update(['quantity' => $validated['quantity']]);
            $product->decrement('stock', $quantityDiff);
            $cartItem->cart->recalculateTotal();

            return response()->json([
                'message' => 'Количество обновлено'
            ]);
        });
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $this->authorize('delete', $cartItem);

        return DB::transaction(function () use ($cartItem) {
            $product = $cartItem->product;
            $cart = $cartItem->cart;

            // Возвращаем товар в запас
            $product->increment('stock', $cartItem->quantity);
            $cartItem->delete();
            $cart->recalculateTotal();

            return response()->json([
                'message' => 'Товар удален из корзины'
            ]);
        });
    }
}
