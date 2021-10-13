<?php

namespace App\Policies;

use App\User;
use App\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorPolicy
{
    use HandlesAuthorization;

    protected function tgAccess(User $user, Vendor $vendor)
    {
        return $user->access_full || $user->access_vendors;
    }

    /**
     * Determine whether the user can view the taxGroup.
     *
     * @param \App\User $user
     * @param \App\Vendor $vendor
     * @return mixed
     */
    public function view(User $user, Vendor $vendor)
    {
        return $this->tgAccess($user, $vendor);
    }

    /**
     * Determine whether the user can create taxGroups.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->tgAccess($user, new Vendor());
    }

    /**
     * Determine whether the user can update the taxGroup.
     *
     * @param \App\User $user
     * @param \App\Vendor $vendor
     * @return mixed
     */
    public function update(User $user, Vendor $vendor)
    {
        return $this->tgAccess($user, $vendor);
    }

    /**
     * Determine whether the user can delete the taxGroup.
     *
     * @param \App\User $user
     * @param \App\Vendor $vendor
     * @return mixed
     */
    public function delete(User $user, Vendor $vendor)
    {
        return $this->tgAccess($user, $vendor);
    }
}
