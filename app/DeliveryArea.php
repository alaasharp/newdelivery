<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class DeliveryArea extends Model
{
    protected $fillable = ['name', 'coords', 'price', 'city_id', 'archive', 'company_name', 'restaurant_id', 'created_by','min_order_acceptance'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        $user = Auth::user();
        if ($user->access_full || !Settings::getSettings()->multiple_cities)
            return DeliveryArea::where('id', '>', '0');
        elseif (!$user->access_full && in_array($user->user_type, [0, 3]))
            return DeliveryArea:: whereIn('city_id', City::policyScope()->pluck('id')->all());
        elseif (!$user->access_full && in_array($user->user_type, [4, 5]))
            return DeliveryArea:: whereIn('restaurant_id', \App\Restaurant::policyScope()->pluck('id')->all());
        else
            return DeliveryArea::where('company_name', $user->companyName)->whereIn('restaurant_id', $user->restaurants->pluck('id')->all())->whereIn('city_id', $user->cities->pluck('id')->all());
    }
}
