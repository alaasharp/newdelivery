<?php

namespace App\Observers;

use App\Category;
use App\Restaurant;

/**
 * Send request to OneSignal once push message were created
 */
class RestaurantObserver
{
    public function saved($model)
    {

    }

    /**
     * Listen to the User deleting event.
     *
     * @param User $user
     * @return void
     */
    public function deleting(Restaurant $Restaurant)
    {
        $p = Category::where('restaurant_id', $Restaurant->id)->delete();
    }
}
