<?php

namespace App\Policies;

use App\User;
use App\Order;
use App\Settings;
use App\City;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    protected function canAccessCityId(User $user, Order $order)
    {
        $allow_cities = true;
        if (in_array($user->user_type, [0, 3]))
            $allow_cities = in_array($order->city_id, City::policyScope()->pluck('id')->all());
        elseif (in_array($user->user_type, [4, 5])) // non exclusive agent and marketing company
        {
            $accessRestaurant = in_array($order->restaurant->created_by, $user->Children());
            return $user->access_full || ($user->access_orders && $accessRestaurant);
        }
        return $user->access_full || ($user->access_orders);
    }

    /**
     * Determine whether the user can view the order.
     *
     * @param \App\User $user
     * @param \App\Order $order
     * @return mixed
     */
    public function view(User $user, Order $order)
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
    public function update(User $user, Order $order)
    {
        if ($order->order_status_id == 15 or $order->is_paid) return false;
        return $this->canAccessCityId($user, $order);
    }

    /**
     * Determine whether the user can delete the order.
     *
     * @param \App\User $user
     * @param \App\Order $order
     * @return mixed
     */
    public function delete(User $user, Order $order)
    {
        return $this->canAccessCityId($user, $order);
    }
}
