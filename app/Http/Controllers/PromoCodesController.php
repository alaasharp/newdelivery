<?php

namespace App\Http\Controllers;

use Validator;
use App\PromoCode;
use Illuminate\Http\Request;

class PromoCodesController extends BaseController
{
    protected $base = 'promo_codes';
    protected $cls = 'App\PromoCode';
    protected $checkboxes = ['discount_in_percent'];

    protected function getIndexItems($data)
    {
        if ($data != null) {
            $codes = PromoCode::policyScope()->
            orderBy($this->orderBy, $this->orderByDir);
            if (is_array($data) && isset($data['city_id']))
                $codes = $codes->where('city_id', $data['city_id']);
            if (is_array($data) && isset($data['restaurant_id'])) {
                $restaurant_id = $data['restaurant_id'];
                $codes = $codes->where('restaurant_id', $restaurant_id);
            }
            return $codes->paginate(20);
        } else {
            return PromoCode::policyScope()->orderBy($this->orderBy, $this->orderByDir)->paginate(20);
        }
    }

    protected function modifyRequestData($data)
    {
        if (!isset($data['_method']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required',
            'code' => 'required',
            'discount' => 'required',
            'limit_use_count' => 'required',
            'active_from' => 'required',
            'active_to' => 'required',
            'min_price' => 'required'
        ]);
    }
}
