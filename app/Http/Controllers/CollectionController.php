<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * Display a listing of the collections.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $collections = Collection::all();
        return response()->json($collections);
    }

     public function getCollectionsByProduct($productId)
    {
        $product = Product::with('collections')->findOrFail($productId);
        return response()->json($product->collections);
    }
}