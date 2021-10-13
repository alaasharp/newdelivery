<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude', 'en_name', 'tr_name', 'country_id', 'created_by'];

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }

    public function deliveryAreas()
    {
        return $this->hasMany(DeliveryArea::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        $user = Auth::user();
        if ($user->access_full || !Settings::getSettings()->multiple_cities)
            $city = City::orderBy('sort', 'ASC');
        else {
            // exclusive agent
            if (in_array($user->user_type, [3, 0]))
                $city = City::orderBy('sort', 'ASC')->whereIn('country_id', $user->countries->pluck('id')->all());
            // non exclusive agent and marketing company
            elseif (in_array($user->user_type, [4, 5]) and $user->access_cities)
                $city = City::orderBy('sort', 'ASC')->whereIn('id', $user->cities->pluck('id')->all())->orWhereIn('created_by', $user->Children());
            else
                $city = City::orderBy('sort', 'ASC')->whereIn('id', $user->cities->pluck('id')->all());
        }
        return $city;
    }
}
