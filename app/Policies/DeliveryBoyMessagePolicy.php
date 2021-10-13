<?php

namespace App\Policies;

use App\User;
use App\DeliveryBoyMessage;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryBoyMessagePolicy
{
    use HandlesAuthorization;

    protected function tgAccess(User $user, DeliveryBoyMessage $deliveryBoy)
    {
        return $user->access_full || $user->access_delivery_boys;
    }

    /**
     * Determine whether the user can view the message.
     *
     * @param \App\User $user
     * @param \App\NewsItem $message
     * @return mixed
     */
    public function view(User $user, DeliveryBoyMessage $message)
    {
        return $this->tgAccess($user, $message);
    }

    /**
     * Determine whether the user can create messages.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->tgAccess($user, new DeliveryBoyMessage());
    }

    /**
     * Determine whether the user can update the message.
     *
     * @param \App\User $user
     * @param \App\NewsItem $message
     * @return mixed
     */
    public function update(User $user, DeliveryBoyMessage $message)
    {
        return $this->tgAccess($user, $message);
    }

    /**
     * Determine whether the user can delete the message.
     *
     * @param \App\User $user
     * @param \App\NewsItem $message
     * @return mixed
     */
    public function delete(User $user, DeliveryBoyMessage $message)
    {
        return $this->tgAccess($user, $message);
    }
}
