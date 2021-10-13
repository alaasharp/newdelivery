<?php

namespace App\Policies;

use App\User;
use App\NewsItem;
use App\Settings;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsItemPolicy
{
    use HandlesAuthorization;

    protected function canAccessCityId(User $user, NewsItem $newsItem)
    {
        $allow_cities = true;
        if (Settings::getSettings()->multiple_cities)
            $allow_cities = in_array($newsItem->city_id, $user->cities->pluck('id')->all());
        return $user->access_full || ($user->access_news && $allow_cities);
    }

    /**
     * Determine whether the user can view the newsItem.
     *
     * @param \App\User $user
     * @param \App\NewsItem $newsItem
     * @return mixed
     */
    public function view(User $user, NewsItem $newsItem)
    {
        return $this->canAccessCityId($user, $newsItem);
    }

    /**
     * Determine whether the user can create taxGroups.
     *
     * @param \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return ($user->access_full || $user->access_news);
    }

    /**
     * Determine whether the user can update the newsItem.
     *
     * @param \App\User $user
     * @param \App\NewsItem $newsItem
     * @return mixed
     */
    public function update(User $user, NewsItem $newsItem)
    {
        return $this->canAccessCityId($user, $newsItem);
    }

    /**
     * Determine whether the user can delete the newsItem.
     *
     * @param \App\User $user
     * @param \App\NewsItem $newsItem
     * @return mixed
     */
    public function delete(User $user, NewsItem $newsItem)
    {
        return $this->canAccessCityId($user, $newsItem);
    }
}
