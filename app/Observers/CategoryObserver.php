<?php

namespace App\Observers;

use App\Category;
use App\Product;

/**
 * Send request to OneSignal once push message were created
 */
class CategoryObserver
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
    public function deleting(Category $Category)
    {
        $p = Product::where('category_id', $Category->id)->delete();
    }
}
