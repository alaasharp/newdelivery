<?php

namespace App\Policies;

use App\User;
use App\City;
use Illuminate\Auth\Access\HandlesAuthorization;

class CityPolicy
{
    use HandlesAuthorization;

    protected function cAccess(User $user, City $city)
    {
        return $user->access_full || $user->access_cities;
    }

    /**
     * Determine whether the user can view the city.
     *
     * @param \App\User $user
     * @param \App\City $city
     * @return mixed
     */
    public function view(User $user, City $city)
    {
        return $this->cAccess($user, $city);
    }

    /**
     * Determine whether the user can create cities.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->cAccess($user, new City);
    }

    /**
     * Determine whether the user can update the city.
     *
     * @param \App\User $user
     * @param \App\City $city
     * @return mixed
     */
    public function update(User $user, City $city)
    {
        $cities = true;
        if (in_array($user->user_type, [4, 5]))
            $cities = in_array($city->created_by, $user->Children()) ? true : false;
        return $this->cAccess($user, $city) and $cities;
    }

    /**
     * Determine whether the user can delete the city.
     *
     * @param \App\User $user
     * @param \App\City $city
     * @return mixed
     */
    public function delete(User $user, City $city)
    {
        $cities = true;
        if (in_array($user->user_type, [4, 5]))
            $cities = in_array($city->created_by, $user->Children()) ? true : false;
        return $this->cAccess($user, $city) and $cities;
    }
}
