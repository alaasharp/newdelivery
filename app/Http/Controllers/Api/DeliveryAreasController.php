<?php

namespace App\Http\Controllers\Api;

use App\DeliveryArea;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryAreasController extends Controller
{
    public function index(Request $request)
    {
    	$city_id = $request->input('city_id');
    	if ($city_id != null)
			$delivery_areas = DeliveryArea::where('archive', 1)->where('city_id', $city_id)->get();
    	else
			$delivery_areas = DeliveryArea::where('archive', 1)->orderBy('created_at', 'ASC')->get();
        return response()->json($delivery_areas);
    }
}
