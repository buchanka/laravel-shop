<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:admin']);
    }

    public function show($id)
    {
        return Category::findOrFail($id);
    }
    
    public function index()
    {
        return Category::all();
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
        ]);

        return Category::create($request->only('name'));
    }

    
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
        ]);

        $category->update($request->only('name'));
        return $category;
    }

    
    public function destroy(Category $category)
    {
        $category->delete();
        return response(null, 204);
    }
}