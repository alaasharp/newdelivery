<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\City;

class CitiesController extends BaseController
{
    protected $base = 'cities';
    protected $cls = 'App\City';
    protected $orderBy = 'sort';
    protected $orderByDir = 'ASC';

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required'
        ]);
    }

    protected function getIndexItems($data)
    {
        return City::policyScope()->orderBy($this->orderBy, $this->orderByDir)->paginate(20);
    }

    protected function modifyRequestData($data)
    {
        if (!isset($data['_method']) and !isset($data['created_by']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }

}
