<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['customer_id', 'address', 'lat', 'lng', 'is_default', 'special_place', 'building'
        , 'apartment', 'phone', 'street', 'mainAddress'];
}
