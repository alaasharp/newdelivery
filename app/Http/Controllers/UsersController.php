<?php

namespace App\Http\Controllers;

use App\City;
use App\User;
use Validator;
use Illuminate\Http\Request;
use Auth;
use Gate;

class UsersController extends BaseController
{
    protected $base = 'users';
    protected $cls = 'App\User';
    protected $manyToMany = [
        'cities' => 'cities_ids',
        'restaurants' => 'restaurants_ids',
        'countries' => 'countries_ids'
    ];
    protected $checkboxes = [
        'access_full', 'access_news', 'access_categories', 'access_products',
        'access_orders', 'access_customers', 'access_pushes', 'access_delivery_areas',
        'access_promo_codes', 'access_tax_groups', 'access_cities', 'access_restaurants',
        'access_settings', 'access_users', 'access_delivery_boys', 'access_order_statuses',
        'access_vendors', 'access_countries'
    ];

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!Gate::allows('create', $this->cls))
            return redirect('/');
        $item = new $this->cls;
        $rdata = $request->all();
        $t = 1;
        if (isset($rdata['t'])) {
            if ((in_array(auth()->user()->user_type, [0, 3, 4]) or auth()->user()->access_full) and $rdata['t'] == 2) {
                $t = 2;
                return view($this->base . '.addUSer', array_merge(compact('item', 't'), $this->getAdditionalData($request->all())));
            }
        }
        return view($this->base . '.form', array_merge(compact('item', 't'), $this->getAdditionalData($request->all())));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request)
    {
        $item = call_user_func([$this->cls, 'find'], $id);
        if (!Gate::allows('update', $item))
            return redirect('/');
        $t = 1;
        if ($item) {
            if (in_array($item->user_type, [0, 3, 4, 5])) {
                $t = 2;
                return view($this->base . '.addUSer', array_merge(compact('item', 't'), $this->getAdditionalData($request->all())));
            }
        } else $this->redirectOnCreatePath($request);
        return view($this->base . '.form', array_merge(compact('item', 't'), $this->getAdditionalData($request->all())));
    }

    protected function getIndexItems($data)
    {
        $user = Auth::user();
        $users = User::orderBy($this->orderBy, $this->orderByDir)->where('id', '<>', 2);
        if ($user->user_type == 3 and $user->access_users) // exclusive agent
        {
            $cities = City::whereIn('country_id', $user->countries->pluck('id')->all())->pluck('id')->all();
            $users = $users->whereIn('city_id', $cities)->orwhereIn('country_id', $user->countries->pluck('id')->all());
        } elseif (in_array($user->user_type, [4, 5, 1, 2]) and $user->access_users) // non exclusive agent and marketing company
        {
            $users = $users->whereIn('id', $user->Children());
        } elseif ($user->user_type == 0 and $user->access_users and !$user->access_full) // for admin user not super admin
        {
            $cities = City::whereIn('country_id', $user->countries->pluck('id')->all())->pluck('id')->all();
            $users = $users->whereIn('city_id', $cities)->orwhereIn('country_id', $user->countries->pluck('id')->all());
        }
        if ($data != null) {
            if (is_array($data) && isset($data['q'])) {
                $users = $users->where(function ($query) use ($data) {
                    $q = '%' . $data['q'] . '%';
                    return $query->where('email', 'LIKE', $q)->orWhere('name', 'LIKE', $q);
                });
            }
            if (is_array($data) && isset($data['city_id']))
                $users = $users->where('city_id', $data['city_id']);
            if (is_array($data) && isset($data['user_type']))
                $users = $users->where('user_type', $data['user_type']);
            return $users->where('id', '<>', 2)->paginate(20);
        } elseif (!$user->access_full)
            return $users->paginate(20);
        elseif ($user->access_full and $user->user_type == 0)
            return call_user_func([$this->cls, 'orderBy'], $this->orderBy, $this->orderByDir)->where('id', '<>', 2)->paginate(20);
    }

    protected function modifyRequestData($data)
    {
        if (isset($data['password'])) {
            if (!empty($data['password']))
                $data['password'] = bcrypt($data['password']);
            else
                unset($data['password']);
            unset($data['password_confirmation']);
        }
        if ($data['password'] == null) {
            unset($data['password']);
            unset($data['password_confirmation']);
        }
        if (!isset($data['_method']) and !isset($data['created_by']))
            $data['created_by'] = auth()->user()->id;
        if (!isset($data['country_id']) and isset($data['countries_ids']))
            $data['country_id'] = $data['countries_ids'][0];
        if (!isset($data['city_id']) and isset($data['countries_ids']) and !isset($data['cities_ids'])) {
            $city = \App\City::where('country_id', $data['countries_ids'][0])->first();
            $data['city_id'] = $city->id;
        } elseif (isset($data['cities_ids']))
            $data['city_id'] = $data['cities_ids'][0];
        return $data;
    }

    public function getValidator(Request $request)
    {
        $rules = ['name' => 'required', 'email' => 'required',];
        $data = $request->all();
        if (isset($data['password']) && !empty($data['password']))
            $rules['password'] = 'required|confirmed';
        if (!isset($data['_method']) and isset($data['user_type']) and $data['user_type'] == 3)
            $rules['countries_ids'] = 'sagent';
        if (!isset($data['_method']) and isset($data['user_type']) and !in_array($data['user_type'], [3, 0]))
            $rules['cities_ids'] = 'required';
        return Validator::make($request->all(), $rules);
    }

    public function updateToken(Request $request)
    {
        $item = User::find(auth()->user()->id);
        $item->one_signal_id = $request->input('token');
        $item->save();
    }
}
