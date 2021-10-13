<?php

namespace App\Http\Controllers\Api;

use App\Appointment;
use App\Rate;
use App\Restaurant;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\City;
use DB;
use App\Question;
use Validator;

class RestaurantsController extends Controller
{
    public function get_next_restaurant_time(Request $request)
    {
        $restaurant_id = $request->restaurant_id;
        $day = Carbon::now()->format('l');
        $day = Restaurant::convert_day_name_to_day_number($day);
        $appointment = Appointment::where('restaurant_id', $restaurant_id);
        $restaurant_next_open = 'restaurant is close';
        $data['data'] = $restaurant_next_open;
        $data['status'] = false;
        $data['message'] = 'المطعم مغلق';
        if ($appointment->count() > 0) {
            $appointment = $appointment->where('day', $day);
            if ($appointment->count() > 0) {
                $day = $day == 6 ? 0 : $day + 1;
                $appointment = Appointment::where('restaurant_id', $restaurant_id)->where('day', $day);
                if ($appointment->count() > 0) {
                    $restaurant_next_open = $appointment->first()->open_from;
                    $data['data'] = $restaurant_next_open;
                    $data['status'] = true;
                    $data['message'] = 'تم جلب الوقت التالي للمطعم';
                }
            }
        }
        return response()->json($data);
    }

    public function index(Request $request)
    {
        $city_id = $request->city_id;
        $country_id = $request->country_id;
        $client_lng = $request->client_lng;
        $client_lat = $request->client_lat;
        $data = $request->all();
        $restaurants = Restaurant::with(['DeliveryArea', 'questions'])->where('archive', 1)->orderBy('sort', 'ASC');
        if ($client_lng != null and $client_lat != null) {
            if (is_numeric($client_lng) and is_numeric($client_lat))
                $restaurants = Restaurant::with('DeliveryArea')->where('archive', 1)->select('*',
                    DB::raw('CASE
                    WHEN rlat is null THEN 10000
                    WHEN rlang is null THEN 10000
                    ELSE round(( 3959 * acos( cos( radians(' . $client_lat . ') )* cos( radians(rlat)) * cos(radians(rlang) - radians(' . $client_lng . ') ) + sin( radians(' . $client_lat . ') ) * sin( radians( rlat ) ) ) ),3 ) *1.609344 END	AS distance'))->orderBy('distance', 'asc');
        }
        if ($city_id != null) {
            $restaurants = $restaurants->where('city_id', $request->input('city_id'));
        } elseif ($country_id != null) {
            $cities = City::where('country_id', $country_id)->pluck('id')->all();
            $restaurants = $restaurants->whereIn('city_id', $cities);
        }
        if (is_array($data) && isset($data['restaurant_id']))
            $restaurants = $restaurants->where('id', $data['restaurant_id']);
        if (is_array($data) && isset($data['paginate']))
            $restaurants = $restaurants->paginate(5)->setpath($request->fullUrl());
        else
            $restaurants = $restaurants->get();
        return response()->json($restaurants);
    }

    public function getQuestion(Request $request)
    {
        $restaurant_id = $request->input('restaurant_id');
        $Question = array();
        if ($restaurant_id)
            $Question = Question::where('restaurant_id', $restaurant_id)->get();
        $customer = $request->user;
        $order_id = $request->input('order_id');
        if ($customer and count($Question) > 0 and $order_id) {
            foreach ($Question as $q) {
                $rates_data = Rate::where('order_id', $order_id)->where('customer_id', $customer->id)->where('question_id', $q->id)->first();
                if ($rates_data) {
                    $q['rate_value'] = $rates_data->rate_value;
                    $q['comments'] = $rates_data->comments;
                }
                $q['sutmoer'] = $customer->id;
            }
        }
        return response()->json($Question);
    }

    public function saveRate(Request $request)
    {
        $data = $request->all();
        $response['success'] = false;
        $rules = array(
            'order_id' => "required|numeric",
            'question_id' => "required|numeric",
            'rate_value' => "required|numeric",
        );
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            $response['success'] = false;
            $response['errors'] = $validator->errors()->all();
            return response()->json($response);
        }
        $data['customer_id'] = $request->user->id;
        Rate::updateOrInsert(
            ['customer_id' => $data['customer_id'], 'order_id' => $data['order_id'], 'question_id' => $data['question_id']],
            ['rate_value' => $data['rate_value'], 'comments' => $data['comments']]
        );
        $response['success'] = true;
        $response  ['messages'] = [trans('messages.api.save_rate_success')];
        return response()->json($response);
    }
}
