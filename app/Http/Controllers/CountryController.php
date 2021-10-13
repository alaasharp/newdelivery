<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Country;

class CountryController extends BaseController
{
    protected $base = 'countries';
    protected $cls = 'App\Country';

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required'
        ]);
    }

    protected function getIndexItems($data)
    {
        return Country::policyScope()->orderBy($this->orderBy, $this->orderByDir)->paginate(20);
    }

}
