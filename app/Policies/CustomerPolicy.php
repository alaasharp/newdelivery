<?php

namespace App\Policies;

use App\User;
use App\Settings;
use App\Customer;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    protected function canAccessCityId(User $user, Customer $customer)
    {
        $allow_cities = true;
        if (Settings::getSettings()->multiple_cities)
            $allow_cities = in_array($customer->city_id, $user->cities->pluck('id')->all());
        return $user->access_full || ($user->access_customers && $allow_cities);
    }

    /**
     * Determine whether the user can view the customer.
     *
     * @param \App\User $user
     * @param \App\Customer $customer
     * @return mixed
     */
    public function view(User $user, Customer $customer)
    {
        return $this->canAccessCityId($user, $customer);
    }

    /**
     * Determine whether the user can create customers.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_full || $user->access_customers;
    }

    /**
     * Determine whether the user can update the customer.
     *
     * @param \App\User $user
     * @param \App\Customer $customer
     * @return mixed
     */
    public function update(User $user, Customer $customer)
    {
        return $this->canAccessCityId($user, $customer);
    }

    /**
     * Determine whether the user can delete the customer.
     *
     * @param \App\User $user
     * @param \App\Customer $customer
     * @return mixed
     */
    public function delete(User $user, Customer $customer)
    {
        return $this->canAccessCityId($user, $customer);
    }
}
