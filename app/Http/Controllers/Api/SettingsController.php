<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\About;
use App\OrderStatus;
use App\Settings;
use App\Category;
use App\City;
use App\Contact;
use App\User;
use App\DeliveryArea;
use Illuminate\Http\Request;
use App\Country;

class SettingsController extends Controller
{
    public function cities(Request $request)
    {
        $country_id = $request->input('country_id') ? $request->input('country_id') : null;
        $data = array();
        if ($country_id != null)
            $data['cities'] = City::where('country_id', $country_id)->orderBy('sort', 'ASC')->get();
        else
            $data['cities'] = City::orderBy('sort', 'ASC')->get();
        return response()->json($data);
    }

    public function countries()
    {
        $data['countries'] = Country::orderBy('name', 'ASC')->get();
        return response()->json($data);
    }

    public function index(Request $request)
    {
        $vendor_id = $request->input('vendor_id') ? $request->input('vendor_id') : null;
        if ($vendor_id != null) {
            $user = User::where('vendor_id', $vendor_id)->first();
            $vendor_id = $user ? $user->id : 2;
        } else {
            $vendor_id = 2;
        }
        $data = [
            'settings' => Settings::getSettings($vendor_id),
            'categories' => Category::defaultOrder()->get(),
            'order_statuses' => OrderStatus::orderBy('sort', 'asc')->get(),
            'loyalty' => 0
        ];
        $data['cities'] = City::orderBy('sort', 'ASC')->get();
        $data['countries'] = Country::orderBy('name', 'ASC')->get();
        $data['delivery_areas'] = DeliveryArea::where('archive', 1)->get();
        return response()->json($data);
    }

    public function contacts()
    {
        return response()->json(Contact::first());
    }

    public function aboutus()
    {
        return response()->json(About::find(1));
    }

    public function trems()
    {
        return response()->json(About::find(2));
    }

    public function instructions(Request $request)
    {
        $contact = About::select($request->header('lang') . '_text as text')->find(3);
        return response()->json($contact);
    }

}
