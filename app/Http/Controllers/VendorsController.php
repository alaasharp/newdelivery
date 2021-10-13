<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Vendor;
use App\User;
use Auth;

class VendorsController extends BaseController
{
    protected $base = 'vendors';
    protected $cls = 'App\Vendor';
    protected $images = ['image'];

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), ['name' => 'required']);
    }

    protected function getIndexItems($data)
    {
        if ($data != null) {
            $restaurants = Vendor::policyScope();
            if (is_array($data) && isset($data['q']))
                $restaurants = $restaurants->where('name', 'LIKE', '%' . $data['q'] . '%');
            if (is_array($data) && isset($data['city_id']))
                $restaurants = $restaurants->where('city_id', $data['city_id']);
            return $restaurants->paginate(20);
        } else
            return Vendor::policyScope()->paginate(20);
    }

    protected function modifyRequestData($data)
    {
        if (!isset($data['_method']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }

    //changeAutoSend
    public function changeAutoSend(Request $request)
    {
        $vendor = User::find($request->input('id'));
        $vendor->order_auto = $request->input('status');
        $vendor->save();
        return "تم التعديل";
    }
}
