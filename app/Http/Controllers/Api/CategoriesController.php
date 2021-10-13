<?php

namespace App\Http\Controllers\Api;

use App\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        $restaurant_id = $request->input('restaurant_id');
        $data = $request->all();
        $categories = Category::defaultOrder();
        if ($restaurant_id != null)
            $categories = $categories->where('archive', 1)->where('restaurant_id', $restaurant_id);
        else
            $categories = $categories->where('archive', 1);
        if (is_array($data) && isset($data['paginate']))
            $categories = $categories->paginate(10)->setpath($request->fullUrl());
        else
            $categories = $categories->get();
        return response()->json($categories);
    }
}
