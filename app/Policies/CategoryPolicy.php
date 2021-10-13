<?php

namespace App\Policies;

use App\User;
use App\Category;
use App\City;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    protected function canAccessCityId(User $user, Category $category)
    {
        $allow_cities = true;
        if (in_array($user->user_type, [0, 3]))
            $allow_cities = in_array($category->city_id, City::policyScope()->pluck('id')->all());
        elseif (in_array($user->user_type, [1, 2]))
            return in_array($category->restaurant_id, $user->restaurants->pluck('id')->all());
        elseif (in_array($user->user_type, [4, 5])) // non exclusive agent and marketing company
        {
            $accessRestaurant = $category->restaurant ? in_array($category->restaurant->created_by, $user->Children()) : false;
            return $user->access_full || ($user->access_categories && $accessRestaurant);
        }
        return $user->access_full || ($user->access_categories && $allow_cities);
    }

    /**
     * Determine whether the user can view the category.
     *
     * @param \App\User $user
     * @param \App\Category $category
     * @return mixed
     */
    public function view(User $user, Category $category)
    {
        return $this->canAccessCityId($user, $category);
    }

    /**
     * Determine whether the user can create categories.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_full || $user->access_categories;
    }

    /**
     * Determine whether the user can update the category.
     *
     * @param \App\User $user
     * @param \App\Category $category
     * @return mixed
     */
    public function update(User $user, Category $category)
    {
        if ($user->companyName = $category->company_name)
            return true; //$this->canAccessCityId($user, $category);
        elseif ($this->canAccessCityId($user, $category)) return true;
        else
            return false;
    }

    /**
     * Determine whether the user can delete the category.
     *
     * @param \App\User $user
     * @param \App\Category $category
     * @return mixed
     */
    public function delete(User $user, Category $category)
    {
        if ($user->companyName = $category->company_name)
            return true;
        elseif ($this->canAccessCityId($user, $category)) return true;
        else
            return false;
    }
}
