<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:customer']);
    }

   public function index(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $favorites = $request->user()->favorites()->with('product')->get();
            
            return response()->json(
                $favorites->map(function ($favorite) {
                    return [
                        'id' => $favorite->product->id,
                        'name' => $favorite->product->name,
                        'price' => $favorite->product->price,
                        'image' => $favorite->image ?? $favorite->product->image,
                    ];
                })
            );
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $product = Product::findOrFail($validated['product_id']);
            
            $favorite = $request->user()->favorites()->firstOrCreate([
                'product_id' => $validated['product_id']
            ], [
                'image' => $product->image 
            ]);

            
            if (!$favorite->wasRecentlyCreated) {
                $favorite->update(['image' => $product->image]);
            }

            return response()->json([
                'message' => 'Товар добавлен в избранное',
                'favorite' => $favorite->load('product')
            ], 201);
        });
    }

    public function destroy(Request $request, $productId)
    {
        return DB::transaction(function () use ($request, $productId) {
            $favorite = $request->user()->favorites()->where('product_id', $productId)->firstOrFail();
            $favorite->delete();
            
            return response()->json([
                'message' => 'Товар удален из избранного'
            ]);
        });
    }
}
