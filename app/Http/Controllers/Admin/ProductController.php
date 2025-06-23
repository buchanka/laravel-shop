<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:admin']);
    }

    public function index(Request $request)
    {
        $query = Product::with(['category', 'collections']);
        
        if ($request->has('sort')) {
            $query->orderBy('name', $request->sort);
        }
        $perPage = $request->per_page ?? 5;
        $products = $query->paginate($perPage);
        
        return response()->json([
            'products' => $products->items(),
            'total' => $products->total(),
            'total_pages' => $products->lastPage(),
            'current_page' => $products->currentPage()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|string',
            'height' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'burn_time' => 'nullable|numeric',
            'stock' => 'required|integer|min:0',
            'collection_ids' => 'nullable|array',
            'collection_ids.*' => 'exists:collections,id'
        ]);

        $product = Product::create($data);

        if (!empty($data['collection_ids'])) {
            $product->collections()->sync($data['collection_ids']);
        }

        return response()->json(['message' => 'Товар добавлен'], 201);
    }

    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255', 
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'category_id' => 'sometimes|exists:categories,id',
            'image' => 'nullable|string',
            'height' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'burn_time' => 'nullable|numeric',
            'stock' => 'required|integer|min:0',
            'collection_ids' => 'nullable|array',
            'collection_ids.*' => 'exists:collections,id'
        ]);
        
         $product->update($validatedData);

        if (array_key_exists('collection_ids', $validatedData)) {
            $product->collections()->sync($validatedData['collection_ids']);
        }
        
        return response()->json(['message' => 'Товар изменен', 'product' => $product], 200);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->noContent();
    }

    public function show(Product $product)
    {
        return response()->json([
            'product' => $product->load(['category', 'collections'])
        ]);
    }

    public function uploadImage(Request $request, Product $product)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $file = $request->file('image');

        // Генерируем имя файла
        $filename = 'product_' . $product->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'product_img/' . $filename;

        try {
            Log::info("Attempting to upload product image to: " . $path);
            
            // Загружаем с явным указанием видимости
            Storage::disk('yandex')->put($path, file_get_contents($file), 'public');
            
            // Проверяем существование файла
            $exists = Storage::disk('yandex')->exists($path);
            Log::info("File exists after upload: " . ($exists ? 'yes' : 'no'));
            
            // Получаем URL
            $imageUrl = Storage::disk('yandex')->url($path);
            Log::info("File URL: " . $imageUrl);

            // Обновляем продукт
            $product->update(['image' => $imageUrl]);

            return response()->json([
                'message' => 'Изображение товара успешно загружено',
                'url' => $imageUrl
            ], 200, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache'
            ]);

        } catch (\Exception $e) {
            Log::error("Product image upload error: " . $e->getMessage());
            return response()->json(['message' => 'Ошибка загрузки изображения: ' . $e->getMessage()], 500);
        }
    }

    public function uploadImageForNewProduct(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $file = $request->file('image');
        $filename = 'product_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'product_img/' . $filename;

        try {
            Log::info("Попытка загрузки изображения", [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'path' => $path
            ]);

            if (!Storage::disk('yandex')) {
                throw new \Exception("Хранилище недоступно");
            }

            $uploaded = Storage::disk('yandex')->put($path, file_get_contents($file), 'public');
            
            if (!$uploaded) {
                throw new \Exception("Не получилось загрузить изображение");
            }

            $exists = Storage::disk('yandex')->exists($path);
            Log::info("Файл доступен: " . ($exists ? 'yes' : 'no'));
            
            $imageUrl = Storage::disk('yandex')->url($path);
            Log::info("Ссылка на загруженный файл: " . $imageUrl);

            if (empty($imageUrl)) {
                throw new \Exception("Не удалось создать url для загруженного файла");
            }

            return response()->json([
                'message' => 'Изображение успешно загружено',
                'url' => $imageUrl
            ], 200);

        } catch (\Exception $e) {
            Log::error("Ошибка загрузки: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Ошибка загрузки: ' . $e->getMessage(),
                'error_details' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function stats(Request $request)
    {
        \Log::info('Admin stats called by user: ', [auth()->user()]);
        $validated = $request->validate([
            'period' => 'sometimes|in:week,month'
        ]);

        $period = $validated['period'] ?? 'week';
        
        $query = Product::query();
        
        if ($period === 'week') {
            $startDate = Carbon::now()->subWeek();
            $query->where('created_at', '>=', $startDate);
            $groupBy = DB::raw('DATE(created_at) as date');
        } else {
            $startDate = Carbon::now()->subMonth();
            $query->where('created_at', '>=', $startDate);
            $groupBy = DB::raw('DATE(created_at) as date');
        }
        
        $stats = $query->select([
                $groupBy,
                DB::raw('COUNT(*) as count')
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        return response()->json($stats);
    }

}
