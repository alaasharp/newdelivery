<?php

namespace App\Policies;

use App\User;
use App\PromoCode;
use App\Settings;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromoCodePolicy
{
    use HandlesAuthorization;

    protected function canAccessCityId(User $user, PromoCode $promoCode)
    {
        $allow_cities = true;
        if (Settings::getSettings()->multiple_cities)
            $allow_cities = in_array($promoCode->city_id, $user->cities->pluck('id')->all());
        return $user->access_full || ($user->access_promo_codes && $allow_cities);
    }

    /**
     * Determine whether the user can view the promoCode.
     *
     * @param  \App\User  $user
     * @param  \App\PromoCode  $promoCode
     * @return mixed
     */
    public function view(User $user, PromoCode $promoCode)
    {
        return $this->canAccessCityId($user, $promoCode);
    }

    /**
     * Determine whether the user can create promoCodes.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_full || $user->access_promo_codes;
    }

    /**
     * Determine whether the user can update the promoCode.
     *
     * @param  \App\User  $user
     * @param  \App\PromoCode  $promoCode
     * @return mixed
     */
    public function update(User $user, PromoCode $promoCode)
    {
        return $this->canAccessCityId($user, $promoCode);
    }

    /**
     * Determine whether the user can delete the promoCode.
     *
     * @param  \App\User  $user
     * @param  \App\PromoCode  $promoCode
     * @return mixed
     */
    public function delete(User $user, PromoCode $promoCode)
    {
        return $this->canAccessCityId($user, $promoCode);
    }
}
