<?php

namespace App\Policies;

use App\User;
use App\DeliveryBoy;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryBoyPolicy
{
    use HandlesAuthorization;

    protected function tgAccess(User $user, DeliveryBoy $deliveryBoy)
    {
        return $user->access_full || $user->access_delivery_boys;
    }

    /**
     * Determine whether the user can view the deliveryBoy.
     *
     * @param \App\User $user
     * @param \App\NewsItem $deliveryBoy
     * @return mixed
     */
    public function view(User $user, DeliveryBoy $deliveryBoy)
    {
        return $this->tgAccess($user, $deliveryBoy);
    }

    /**
     * Determine whether the user can create taxGroups.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->tgAccess($user, new DeliveryBoy());
    }

    /**
     * Determine whether the user can update the deliveryBoy.
     *
     * @param \App\User $user
     * @param \App\NewsItem $deliveryBoy
     * @return mixed
     */
    public function update(User $user, DeliveryBoy $deliveryBoy)
    {
        return $this->tgAccess($user, $deliveryBoy);
    }

    /**
     * Determine whether the user can delete the deliveryBoy.
     *
     * @param \App\User $user
     * @param \App\NewsItem $deliveryBoy
     * @return mixed
     */
    public function delete(User $user, DeliveryBoy $deliveryBoy)
    {
        return $this->tgAccess($user, $deliveryBoy);
    }
}
