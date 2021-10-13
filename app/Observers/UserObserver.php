<?php

namespace App\Observers;

use App\Restaurant;
use App\User;
use DB;

/**
 * Send request to OneSignal once push message were created
 */
class UserObserver
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
    public function deleting(User $user)
    {
        $p = Restaurant::where('user_id', $user->id)->delete();
        $p = DB::table('city_user')->where('user_id', $user->id)->delete();
        $p = DB::table('country_user')->where('user_id', $user->id)->delete();
    }
}
