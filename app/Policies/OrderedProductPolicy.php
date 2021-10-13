<?php

namespace App\Policies;

use App\User;
use App\OrderedProduct;
use App\Settings;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderedProductPolicy
{
    use HandlesAuthorization;

    protected function canAccessCityId(User $user, OrderedProduct $order)
    {
        $allow_cities = true;
        if (Settings::getSettings()->multiple_cities)
            $allow_cities = in_array($order->product->city_id, $user->cities->pluck('id')->all());
        return $user->access_full || ($user->access_orders && $allow_cities);
    }

    /**
     * Determine whether the user can view the order.
     *
     * @param \App\User $user
     * @param \App\Order $order
     * @return mixed
     */
    public function view(User $user, OrderedProduct $order)
    {
        return $this->canAccessCityId($user, $order);
    }

    /**
     * Determine whether the user can create orders.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_full || $user->access_orders;
    }

    /**
     * Determine whether the user can update the order.
     *
     * @param \App\User $user
     * @param \App\Order $order
     * @return mixed
     */
    public function update(User $user, OrderedProduct $order)
    {
        return $this->canAccessCityId($user, $order);
    }

    /**
     * Determine whether the user can delete the order.
     *
     * @param \App\User $user
     * @param \App\Order $order
     * @return mixed
     */
    public function delete(User $user, OrderedProduct $order)
    {
        return $this->canAccessCityId($user, $order);
    }
}
