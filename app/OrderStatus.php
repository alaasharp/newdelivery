<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    protected $fillable = ['name', 'sort', 'is_default', 'available_to_delivery_boy'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public static function getDefault()
    {
        $res = OrderStatus::where('is_default', true)->first();
        if ($res == null)
            $res = OrderStatus::orderBy('sort', 'ASC')->first();
        return $res;
    }

    public function getNameAttribute($vlaue)
    {
        return app()->getLocale() == "ar" ? $vlaue: $this[app()->getLocale() . '_name'];
    }
}
