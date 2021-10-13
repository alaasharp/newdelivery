<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class NewsItem extends Model
{
    protected $fillable = ['title', 'image', 'announce', 'full_text', 'city_id', 'user_id', 'created_by'];
    protected $appends = ['image_url'];
    protected $hidden = ['image'];

    public function getImageUrlAttribute()
    {
        return url($this->image);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        if (Settings::getSettings()->multiple_cities and in_array(Auth::user()->user_type, [1, 2]) && !Auth::user()->access_full and Auth::user()->user_type != 0)
            $new = NewsItem::whereIn('city_id', City::policyScope()->pluck('id')->all())->where('user_id', auth()->user()->id)->orderBy('created_at', 'DESC');
        elseif (Settings::getSettings()->multiple_cities or Auth::user()->user_type == 0)
            $new = NewsItem::whereIn('city_id', City::policyScope()->pluck('id')->all())->orderBy('created_at', 'DESC');
        else
            $new = NewsItem::orderBy('created_at', 'DESC');
        return $new;
    }
}
