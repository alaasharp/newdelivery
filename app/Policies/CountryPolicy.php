<?php

namespace App\Policies;

use App\User;
use App\Country;
use Illuminate\Auth\Access\HandlesAuthorization;

class CountryPolicy
{
    use HandlesAuthorization;

    protected function tgAccess(User $user, Country $Country)
    {
        return $user->access_full || $user->access_countries;
    }

    /**
     * Determine whether the user can view the taxGroup.
     *
     * @param \App\User $user
     * @param \App\Country $Country
     * @return mixed
     */
    public function view(User $user, Country $Country)
    {
        return $this->tgAccess($user, $Country);
    }

    /**
     * Determine whether the user can create taxGroups.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->tgAccess($user, new Country());
    }

    /**
     * Determine whether the user can update the taxGroup.
     *
     * @param \App\User $user
     * @param \App\Country $Country
     * @return mixed
     */
    public function update(User $user, Country $Country)
    {
        if (in_array($user->user_type, [1, 2, 3, 4, 5]))
            return false;
        return $this->tgAccess($user, $Country);
    }

    /**
     * Determine whether the user can delete the taxGroup.
     *
     * @param \App\User $user
     * @param \App\Country $Country
     * @return mixed
     */
    public function delete(User $user, Country $Country)
    {
        if (in_array($user->user_type, [1, 2, 3, 4, 5]))
            return false;
        return $this->tgAccess($user, $Country);
    }
}
