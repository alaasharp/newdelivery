<?php

namespace App\Policies;

use App\User;
use App\Restaurant;
use App\Settings;
use App\City;
use Illuminate\Auth\Access\HandlesAuthorization;

class RestaurantPolicy
{
    use HandlesAuthorization;

    protected function canAccessCityId(User $user, Restaurant $restaurant)
    {
        $allow_cities = true;
        if (in_array($user->user_type, [0, 3]))
            $allow_cities = in_array($restaurant->city_id, City::policyScope()->pluck('id')->all());
        elseif (in_array($user->user_type, [1, 2]))
            return true;
        elseif (in_array($user->user_type, [4, 5])) // non exclusive agent and marketing company
        {
            $accessRestaurant = in_array($restaurant->created_by, $user->Children());
            return $user->access_full || ($user->access_restaurants && $accessRestaurant);
        }
        return $user->access_full || ($user->access_restaurants && $allow_cities);
    }

    /**
     * Determine whether the user can view the restaurant.
     *
     * @param \App\User $user
     * @param \App\Restaurant $restaurant
     * @return mixed
     */
    public function view(User $user, Restaurant $restaurant)
    {
        return $this->canAccessCityId($user, $restaurant);
    }

    /**
     * Determine whether the user can create restaurants.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_full || $user->access_restaurants;
    }

    /**
     * Determine whether the user can update the restaurant.
     *
     * @param \App\User $user
     * @param \App\Restaurant $restaurant
     * @return mixed
     */
    public function update(User $user, Restaurant $restaurant)
    {
        return $this->canAccessCityId($user, $restaurant);
    }

    /**
     * Determine whether the user can delete the restaurant.
     *
     * @param \App\User $user
     * @param \App\Restaurant $restaurant
     * @return mixed
     */
    public function delete(User $user, Restaurant $restaurant)
    {
        return $this->canAccessCityId($user, $restaurant);
    }
}
