<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = ['name', 'code', 'discount', 'discount_in_percent', 'limit_use_count', 'times_used',
        'active_from', 'active_to', 'min_price', 'city_id', 'restaurant_id', 'created_by'
    ];

    protected $dates = ['active_from', 'active_to'];

    public function getPrice($price)
    {
        $result = $price;
        if ($this->discount_in_percent)
            $result = $result * (1 - $this->discount / 100);
        else
            $result = $result - $this->discount;
        return round($result, 2);
    }

    /**
     * Detect if promo code could be used for specified price
     * @param float $price Price to check
     * @return boolean
     */
    public function isAvailableFor($price)
    {
        return ($this->min_price <= $price) && ($this->times_used < $this->limit_use_count || $this->limit_use_count == 0);
    }

    /**
     * Check if promocode could be used for specified product (have the same city and restaurant)
     * @param Product $product
     * @return boolean
     */
    public function isAvailableForProduct($product)
    {
        $result = true;
        if ($this->restaurant_id != null)
            $result = $result && ($this->restaurant_id == $product->restaurant_id);
        return $result;
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
        if ($user->restaurants->count() > 0 and in_array($user->user_type, [1, 2]) && (Settings::getSettings()->multiple_restaurants || Settings::getSettings()->multiple_cities))
            $promo = PromoCode::whereIn('restaurant_id', $user->restaurants->pluck('id')->all())->orderBy('sort', 'ASC');
        else {
            if ($user->access_full || !Settings::getSettings()->multiple_cities)
                $promo = PromoCode::where('id', '>', '0');
            else
                $promo = PromoCode::whereIn('restaurant_id', \App\Restaurant::policyScope()->pluck('id')->all());
        }
        return $promo;
    }
}
