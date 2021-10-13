<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    protected $table = "aboutUs";
    protected $fillable = ['ar_text', 'en_text', 'tr_text'];
}
