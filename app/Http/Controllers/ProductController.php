<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'categories' => 'sometimes|string',
                'collections' => 'sometimes|string',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|min:0',
                'sort' => 'sometimes|in:price_asc,price_desc,name_asc,name_desc',
                'search' => 'sometimes|string'
            ]);

            DB::enableQueryLog();

            $query = Product::with(['category', 'collections']);

            // Фильтрация по категориям
            if ($request->has('categories') && !empty($request->categories)) {
                $categories = explode(',', $request->categories);
                $query->whereHas('category', function($q) use ($categories) {
                    $q->whereIn('categories.id', $categories);
                });
            }

            // Фильтрация по коллекциям
            if ($request->has('collections') && !empty($request->collections)) {
                $collections = explode(',', $request->collections);
                $query->whereHas('collections', function($q) use ($collections) {
                    $q->whereIn('collections.id', $collections);
                });
            }

            // Фильтрация по цене
            if ($request->has('min_price') && $request->min_price !== '') {
                $query->where('products.price', '>=', (float)$request->min_price);
            }

            if ($request->has('max_price') && $request->max_price !== '') {
                $query->where('products.price', '<=', (float)$request->max_price);
            }

            // Поиск по названию товара, категории или коллекции
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                
                $query->where(function($q) use ($searchTerm) {
                    $q->where('products.name', 'like', $searchTerm)
                    ->orWhereHas('category', function($q) use ($searchTerm) {
                        $q->where('categories.name', 'like', $searchTerm);
                    })
                    ->orWhereHas('collections', function($q) use ($searchTerm) {
                        $q->where('collections.name', 'like', $searchTerm);
                    });
                });
            }

            // Сортировка
            if ($request->has('sort')) {
                switch ($request->sort) {
                    case 'price_asc':
                        $query->orderBy('products.price', 'asc');
                        break;
                    case 'price_desc':
                        $query->orderBy('products.price', 'desc');
                        break;
                    case 'name_asc':
                        $query->orderBy('products.name', 'asc');
                        break;
                    case 'name_desc':
                        $query->orderBy('products.name', 'desc');
                        break;
                }
            }

            $products = $query->get();
            
            \Log::info('SQL Query:', DB::getQueryLog());
            
            return response()->json($products);
            
        } catch (\Exception $e) {
            \Log::error('Error in ProductController: ' . $e->getMessage());
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    public function show($id)
    {
        try {
            $product = Product::with(['category', 'collections'])->findOrFail($id);
            return response()->json($product);
        } catch (\Exception $e) {
            \Log::error('Error fetching product: ' . $e->getMessage());
            return response()->json([
                'error' => 'Product not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}