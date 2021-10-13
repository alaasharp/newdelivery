<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    protected $fillable = ['token', 'customer_id'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
