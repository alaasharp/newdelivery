<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $primaryKey ="id";
    protected $fillable = ['phone','address' ,'comment'];
}
