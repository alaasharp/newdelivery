<?php

namespace App\Policies;

use App\User;
use App\Product;
use App\Settings;
use App\City;
use App\Restaurant;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    protected function canAccessCityId(User $user, Product $product)
    {
        $allow_cities = true;
        if (in_array($user->user_type, [0, 3]))
            $allow_cities = in_array($product->category->restaurant_id, Restaurant::policyScope()->pluck('id')->all());
        elseif (in_array($user->user_type, [1, 2]))
            return ($user->vendor_id = $product->vendor_id) ? true : false;
        elseif (in_array($user->user_type, [4, 5])) // non exclusive agent and marketing company
        {
            $accessVendor = $product->vendor ? in_array($product->vendor->created_by, $user->Children()) : false;
            return $user->access_full || ($user->access_products && $accessVendor);
        }
        return $user->access_full || ($user->access_products && $allow_cities);
    }

    /**
     * Determine whether the user can view the product.
     *
     * @param \App\User $user
     * @param \App\Product $product
     * @return mixed
     */
    public function view(User $user, Product $product)
    {
        return $this->canAccessCityId($user, $product);
    }

    /**
     * Determine whether the user can create products.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_full || $user->access_products;
    }

    /**
     * Determine whether the user can update the product.
     *
     * @param \App\User $user
     * @param \App\Product $product
     * @return mixed
     */
    public function update(User $user, Product $product)
    {

        return $this->canAccessCityId($user, $product);


    }

    /**
     * Determine whether the user can delete the product.
     *
     * @param \App\User $user
     * @param \App\Product $product
     * @return mixed
     */
    public function delete(User $user, Product $product)
    {
        return $this->canAccessCityId($user, $product);
    }
}
