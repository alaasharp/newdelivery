<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Vendor extends Model
{
    protected $fillable = ['name', 'sort', 'image', 'created_by'];

    public function deliveryBoy()
    {
        return $this->hasMany(DeliveryBoy::class);
    }

    public function customer()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        $user = Auth::user();
        if ($user->restaurants->count() > 0 and in_array($user->user_type, [1, 2]) && !$user->access_full && (Settings::getSettings()->multiple_restaurants || Settings::getSettings()->multiple_cities))
            $restaurants = Vendor::whereIn('user_id', $user->restaurants->pluck('user_id')->all())->orderBy('sort', 'ASC');
        else {
            if (Settings::getSettings()->multiple_cities && !$user->access_full) {
                $restaurants = Restaurant::policyScope()->pluck('user_id')->all();
                $restaurants = Vendor::whereIn('user_id', $restaurants)->orderBy('sort', 'ASC');
            } else
                $restaurants = Restaurant::orderBy('sort', 'ASC');
        }
        return $restaurants;
    }
}
