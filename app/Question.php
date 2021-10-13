<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['text', 'restaurant_id', 'created_by'];
    protected $hidden = ['updated_at', 'created_at',];

    public function rates()
    {
        return $this->hasMany(Rate::class, 'question_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }
}
