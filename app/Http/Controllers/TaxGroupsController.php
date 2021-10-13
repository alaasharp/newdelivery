<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\TaxGroup;

class TaxGroupsController extends BaseController
{
    protected $base = 'tax_groups';
    protected $cls = 'App\TaxGroup';

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required',
            'value' => 'required'
        ]);
    }

    protected function getIndexItems($data)
    {
        if (auth()->user()->user_type == 1)
            $data['companyName'] = auth()->user()->companyName;
        if (auth()->user()->user_type == 2)
            $data['user_id'] = auth()->user()->id;
        if (in_array(auth()->user()->user_type, [3, 0, 4, 5]) and auth()->user()->access_tax_groups) {
            $user_ids = \App\Restaurant::policyScope()->pluck('user_id')->all();
            $users = \App\User::whereIn('id', $user_ids)->select('id', 'name', 'companyName')->get();
            $data['user_ids'] = $users->pluck('id')->all();
            $data['companyNames'] = $users->pluck('companyName')->all();
        }

        if ($data != null) {
            $taxs = TaxGroup:: orderBy($this->orderBy, $this->orderByDir);
            if (is_array($data) && isset($data['companyName']))
                $taxs = $taxs->where('companyName', '=', $data['companyName']);
            if (is_array($data) && isset($data['user_id']))
                $taxs = $taxs->where('user_id', '=', $data['user_id']);
            if (is_array($data) && (isset($data['user_ids']) or isset($data['companyNames'])))
                $taxs = $taxs->whereIn('user_id', $data['user_ids'])->orwhereIn('companyName', $data['companyNames']);
            return $taxs->paginate(20);
        } elseif (auth()->user()->access_tax_groups)
            return TaxGroup::orderBy($this->orderBy, $this->orderByDir)->paginate(20);
    }
}
