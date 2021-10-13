<?php

namespace App;

use Auth;
use App\Settings;
use App\Order;
use App\City;
use App\ApiToken, App\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;

class Customer extends Authenticatable
{
    protected $fillable = ['name', 'email', 'phone', 'city_id', 'password', 'block', 'hold', 'country_id'];
    protected $hidden = ['password'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function vendor()
    {
        return $this->hasMany(Vendor::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function generateToken()
    {
        $token = bin2hex(random_bytes(16));
        while (ApiToken::where('token', $token)->count() > 0) {
            $token = bin2hex(random_bytes(16));
        }
        return ApiToken::create([
            'token' => $token,
            'customer_id' => $this->id
        ]);
    }

    public function saveAddress(Request $request)
    {
        return Address::create([
            'address' => $request->input('address'),
            'customer_id' => $this->id,
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
            'is_default' => $request->input('is_default'),
            'street' => $request->input('street'),
            'building' => $request->input('building'),
            'apartment' => $request->input('apartment'),
            'phone' => $request->input('phone'),
            'special_place' => $request->input('special_place'),
            'mainAddress' => $request->input('mainAddress'),
        ]);
    }

    /**
     * Relation of models accessible by current user
     * @return Relation0
     */
    public static function policyScope()
    {
        $user = auth()->user();
        if ($user->access_full || !Settings::getSettings()->multiple_cities)
            $customers = Customer::orderBy('created_at', 'DESC');
        elseif (!$user->access_full && $user->user_type == 0)
            $customers = Customer::whereIn('city_id', City::policyScope()->pluck('id')->all());
        elseif (!$user->access_full && in_array($user->user_type, [1, 2])) {
            $cust_ids = CustomerVendor::where('vendor_id', $user->vendor_id)->pluck("customer_id");
            $customers = Customer::whereIn('id', $cust_ids);
        } elseif (!$user->access_full && in_array($user->user_type, [4, 5]))
            $customers = Customer::whereIn('created_by', $user->Children());
        elseif (!$user->access_full && in_array($user->user_type, [3]))
            $customers = Customer::whereIn('city_id', City::policyScope()->pluck('id')->all());
        else
            $customers = Customer::orderBy('created_at', 'DESC');
        return $customers;
    }
}
