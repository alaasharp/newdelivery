<?php

namespace App\Http\Controllers;

use App\DeliveryArea;
use Validator;
use Illuminate\Http\Request;

class DeliveryAreasController extends BaseController
{
    protected $base = 'delivery_areas';
    protected $cls = 'App\DeliveryArea';

    protected function getIndexItems($data)
    {
        if ($data != null) {
            $areas = DeliveryArea::policyScope()->orderBy($this->orderBy, $this->orderByDir);
            if (is_array($data) && isset($data['city_id']))
                $areas = $areas->where('city_id', $data['city_id']);
            return $areas->paginate(20);
        } else {
            return DeliveryArea::policyScope()->orderBy($this->orderBy, $this->orderByDir)->paginate(20);
        }
    }

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required',
            'coords' => 'required',
            'price' => 'required|between:0,99999999|numeric'
        ]);
    }

    protected function modifyRequestData($data)
    {
        if (!isset($data['_method']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }
}
