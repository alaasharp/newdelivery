<?php

namespace App\Http\Controllers\Api;

use App\Settings;
use App\PromoCode;
use App\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PromoCodesController extends Controller
{
    public function validate_code(Request $request)
    {
        $response = [
            'success' => false
        ];
        $promo_code = PromoCode::where('code', $request->input('code'))
            ->where('active_from', '<=', new Carbon())
            ->where('active_to', '>=', new Carbon())->first();
        if ($promo_code != null) {
            $total = 0;
            $total_with_tax = 0;
            foreach ($request->input('products') as $item) {
                $product = Product::where('id', $item['product']['id'])->first();
                if ($product != null) {
                    $total = $total + $item['count'] * $product->price;
                    $total_with_tax = $total_with_tax + $item['count'] * $product->price * $product->tax_value / 100;
                }
            }
            if (!Settings::getSettings()->tax_included)
                $total_with_tax = $total_with_tax + $total;
            else
                $total_with_tax = $total;
            $product = null;
            if ($request->input('products') != null)
                $product = Product::where('id', $request->input('products')[0]['product']['id'])->first();
            if ($promo_code->isAvailableFor($total) && $promo_code->isAvailableForProduct($product)) {
                $response['success'] = true;
                $response['new_price'] = $promo_code->getPrice($total);
                $response['new_price_tax'] = $promo_code->getPrice($total_with_tax);
            } else
                $response['code'] = 400;
        } else
            $response['code'] = 404;
        return response()->json($response);
    }
}
