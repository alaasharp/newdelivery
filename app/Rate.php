<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    protected $fillable = ['customer_id', 'order_id', 'question_id', 'rate_value', 'comments', 'created_by'];

    public function rates()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
