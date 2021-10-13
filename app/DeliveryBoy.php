<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Auth;

class DeliveryBoy extends Authenticatable
{
    protected $fillable = ['name', 'login', 'password', 'status', 'vendor_id', 'created_by'];

    protected $hidden = ['password'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function apiTokens()
    {
        return $this->hasMany(DeliveryBoyApiToken::class);
    }

    public function messages()
    {
        return $this->hasMany(DeliveryBoyMessage::class);
    }

    public function generateToken()
    {
        $token = bin2hex(random_bytes(16));
        while (DeliveryBoyApiToken::where('token', $token)->count() > 0) {
            $token = bin2hex(random_bytes(16));
        }
        return DeliveryBoyApiToken::create([
            'token' => $token,
            'delivery_boy_id' => $this->id
        ]);
    }

    public static function policyScope()
    {
        $user = Auth::user();
        if (in_array($user->user_type, [1, 2]) && !$user->access_full) {
            $vendor_ids = Vendor::whereIn('user_id', Restaurant::whereIn('city_id', City::policyScope()->pluck('id')->all())->pluck('user_id')->all())->pluck('id')->all();
            return DeliveryBoy:: whereIn('vendor_id', $vendor_ids);
        }
        if (in_array($user->user_type, [3, 0]) && !$user->access_full) {
            $vendor_ids = Vendor::whereIn('user_id', Restaurant::whereIn('city_id', City::policyScope()->pluck('id')->all())->pluck('user_id')->all())->pluck('id')->all();
            return DeliveryBoy:: whereIn('vendor_id', $vendor_ids);
        } elseif (!$user->access_full && in_array($user->user_type, [3, 4, 5])) {
            return DeliveryBoy:: whereIn('vendor_id', Vendor::policyScope()->pluck('id')->all());
        } elseif ($user->access_full) {
            return DeliveryBoy::orderBy('id', 'desc');
        }
    }
}
