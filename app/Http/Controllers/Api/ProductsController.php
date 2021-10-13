<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Product;
use App\User;
use App\Category;
use App\Settings;

use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('archive', 1)->orderBy('sort', 'ASC');
        $data = $request->all();
        if ($request->input('category_id') != null) {
            $category = Category::find($request->input('category_id'));
            if ($category and $category->restaurant and $category->restaurant->id) {
                $hide_out_stock_qty = Settings::select('hide_out_stock_qty')->where('user_id', $category->restaurant->user_id)->first();
                if ($hide_out_stock_qty and $hide_out_stock_qty->hide_out_stock_qty == 1)
                    $products = $products->where('erp_quantity', '>', 0);
            }
            $products = $products->where('category_id', $request->input('category_id'));
        }
        if ($request->input('vendor_id') != null)
            $products = $products->where('vendor_id', $request->input('vendor_id'));
        if (is_array($data) && isset($data['q'])) {
            $products = $products->where(function ($query) use ($data) {
                $q = '%' . $data['q'] . '%';
                return $query->where('description', 'LIKE', $q)->orWhere('name', 'LIKE', $q);
            });
        }
        if (is_array($data) && isset($data['restaurant_id'])) {
            $category_ids = Category::where('restaurant_id', $data['restaurant_id'])->pluck('id');
            $products = $products->whereIn('category_id', $category_ids);
        }
        if (is_array($data) && isset($data['paginate']))
            $products = $products->paginate(9)->setpath($request->fullUrl());
        else
            $products = $products->get();
        return response()->json($products);
    }
}
