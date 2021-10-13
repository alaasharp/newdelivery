<?php

namespace App\Policies;

use App\User;
use App\OrderStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderStatusPolicy
{
    use HandlesAuthorization;

    protected function cAccess(User $user, OrderStatus $order_status)
    {
        return $user->access_full || $user->access_order_statuses;
    }

    /**
     * Determine whether the user can view the order_status.
     *
     * @param \App\User $user
     * @param \App\City $order_status
     * @return mixed
     */
    public function view(User $user, OrderStatus $order_status)
    {
        return $this->cAccess($user, $order_status);
    }

    /**
     * Determine whether the user can create cities.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->cAccess($user, new OrderStatus);
    }

    /**
     * Determine whether the user can update the order_status.
     *
     * @param \App\User $user
     * @param \App\OrderStatus $order_status
     * @return mixed
     */
    public function update(User $user, OrderStatus $order_status)
    {
        return $this->cAccess($user, $order_status);
    }

    /**
     * Determine whether the user can delete the order_status.
     *
     * @param \App\User $user
     * @param \App\OrderStatus $order_status
     * @return mixed
     */
    public function delete(User $user, OrderStatus $order_status)
    {
        return $this->cAccess($user, $order_status);
    }
}
