<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = ['day', 'open_from', 'open_to', 'status', 'restaurant_id'];
    protected $hidden = ['id', 'restaurant_id', 'created_at', 'updated_at'];
}
