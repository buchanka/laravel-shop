<?php

namespace App\Http\Controllers\Admin;

use App\Models\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::all();
        return response()->json($collections);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:collections',
        ]);

        $collection = Collection::create($request->only('name'));

        return response()->json($collection, 201);
    }

    public function show(Collection $collection)
    {
        return response()->json($collection);
    }

    public function update(Request $request, Collection $collection)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:collections,name,'.$collection->id,
        ]);

        $collection->update($request->only('name'));

        return response()->json($collection);
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();
        return response()->json(null, 204);
    }
}
