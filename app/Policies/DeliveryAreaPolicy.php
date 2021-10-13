<?php

namespace App\Policies;

use App\User;
use App\DeliveryArea;
use App\Settings;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryAreaPolicy
{
    use HandlesAuthorization;

    protected function canAccessCityId(User $user, DeliveryArea $area)
    {
        $allow_cities = true;
        if (Settings::getSettings()->multiple_cities)
            $allow_cities = in_array($area->city_id, $user->cities->pluck('id')->all());
        return $user->access_full || ($user->access_delivery_areas && $allow_cities);
    }

    /**
     * Determine whether the user can view the deliveryArea.
     *
     * @param \App\User $user
     * @param \App\DeliveryArea $deliveryArea
     * @return mixed
     */
    public function view(User $user, DeliveryArea $deliveryArea)
    {
        return $this->canAccessCityId($user, $deliveryArea);
    }

    /**
     * Determine whether the user can create deliveryAreas.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_full || $user->access_delivery_areas;
    }

    /**
     * Determine whether the user can update the deliveryArea.
     *
     * @param \App\User $user
     * @param \App\DeliveryArea $deliveryArea
     * @return mixed
     */
    public function update(User $user, DeliveryArea $deliveryArea)
    {
        return $this->canAccessCityId($user, $deliveryArea);
    }

    /**
     * Determine whether the user can delete the deliveryArea.
     *
     * @param \App\User $user
     * @param \App\DeliveryArea $deliveryArea
     * @return mixed
     */
    public function delete(User $user, DeliveryArea $deliveryArea)
    {
        return $this->canAccessCityId($user, $deliveryArea);
    }
}
