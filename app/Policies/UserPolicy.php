<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\City;

class UserPolicy
{
    use HandlesAuthorization;

    protected function can(User $user, User $user2)
    {
        if ($user2->id == 2 and $user->id != 2) return false;// prevent change in supser admin
        if ($user->access_full and $user->user_type == 0) return true; //super admin
        elseif ($user->user_type == 3 and $user->access_users) // exclusive agent
            return in_array($user2->city_id, City::policyScope()->pluck('id')->all()) or in_array($user2->country_id, $user->countries->pluck('id')->all());
        // non exclusive agent and marketing company
        elseif (in_array($user->user_type, [4, 5]) and $user->access_users)
            return in_array($user2->id, $user->Children());
        // for admin user not super admin
        elseif ($user->user_type == 0 and $user->access_users and !$user->access_full)
            return in_array($user2->city_id, City::policyScope()->pluck('id')->all()) || in_array($user2->country_id, $user->countries->pluck('id')->all());
        // non exclusive agent and marketing company
        elseif (in_array($user->user_type, [1, 2]) and $user->access_users)
            return in_array($user2->id, $user->Children());
        return false;
    }

    /**
     * Determine whether the user can view the user.
     *
     * @param \App\User $user
     * @param \App\User $user
     * @return mixed
     */
    public function view(User $user, User $user2)
    {
        return $this->can($user, $user2);
    }

    /**
     * Determine whether the user can create users.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->can($user, new User()) or $user->access_users;
    }

    /**
     * Determine whether the user can update the user.
     *
     * @param \App\User $user
     * @param \App\User $user
     * @return mixed
     */
    public function update(User $user, User $user2)
    {
        return $this->can($user, $user2);
    }

    /**
     * Determine whether the user can delete the user.
     *
     * @param \App\User $user
     * @param \App\User $user
     * @return mixed
     */
    public function delete(User $user, User $user2)
    {
        return $this->can($user, $user2);
    }
}
