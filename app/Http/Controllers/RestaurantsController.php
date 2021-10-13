<?php

namespace App\Http\Controllers;

use App\Restaurant;
use App\Settings;
use Validator;
use Illuminate\Http\Request;
use App\Appointment;

class RestaurantsController extends BaseController
{
    protected $base = 'restaurants';
    protected $cls = 'App\Restaurant';
    protected $orderBy = 'sort';
    protected $orderByDir = 'ASC';
    protected $images = ['image', 'cover_image'];

    public function getValidator(Request $request)
    {
        if (!Settings::getSettings()->multiple_cities)
            return Validator::make($request->all(), ['name' => 'required',]);
        else
            return Validator::make($request->all(), ['name' => 'required', 'city_id' => 'required']);
    }

    protected function getIndexItems($data)
    {
        if ($data != null) {
            $restaurants = Restaurant::policyScope();
            if (is_array($data) && isset($data['q']))
                $restaurants = $restaurants->where('name', 'LIKE', '%' . $data['q'] . '%');
            if (is_array($data) && isset($data['city_id']))
                $restaurants = $restaurants->where('city_id', $data['city_id']);
            return $restaurants->paginate(20);
        } else
            return Restaurant::policyScope()->paginate(20);
    }

    protected function getQuestionItems()
    {
        if ($data != null) {
            $restaurants = Restaurant::policyScope();
            if (is_array($data) && isset($data['q']))
                $restaurants = $restaurants->where('name', 'LIKE', '%' . $data['q'] . '%');
            if (is_array($data) && isset($data['city_id']))
                $restaurants = $restaurants->where('city_id', $data['city_id']);
            return $restaurants->paginate(20);
        } else
            return Restaurant::policyScope()->paginate(20);
    }

    protected function modifyRequestData($data)
    {
        if (!isset($data['_method']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }

    protected function saveAppointment(Request $request)
    {
        $data = $request->all();
        /*if (isset($data['status']))
            $data['status'] = 1; else $data['status'] = 0;*/

        if (isset($data['id']) and isset($data['_method']) and $data['_method'] == "DELETE")
            Appointment::find($data['id'])->delete();
        elseif (isset($data['id']) and !isset($data['_method']))
            Appointment::updateOrinsert(array('id' => $data['id']), $data);
        elseif (!isset($data['id']) and !isset($data['_method']))
            /*Appointment::updateOrinsert(
                array('day' => $data['day'], 'restaurant_id' => $data['restaurant_id'], 'open_from' => $data['open_from']), $data);
        */
        for ($i = 0; $i < 7; $i++) {
            $appointment[] = [
                'restaurant_id' => $request->restaurant_id[$i],
                'day' => $request->day[$i],
                'open_from' => $request->open_from[$i],
                'open_to' => $request->open_to[$i],
                'status' =>$request->status[$i]
            ];
        }
        Appointment::insert($appointment);
        return back();
    }

    protected function getAppointment($id)
    {
        $days = array();
        for ($i = 0; $i < 7; $i++)
            $days[$i] = trans('messages.days.' . $i);
        $restaurants = Restaurant::policyScope()->find($id);
        return view($this->base . '.appointments')->with('item', $restaurants)->with('days', $days);
    }
}
