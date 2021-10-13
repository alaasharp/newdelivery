<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryBoyApiToken extends Model
{
    protected $fillable = ['token', 'delivery_boy_id', 'platform', 'push_token'];

    public function deliveryBoy()
    {
        return $this->belongsTo(DeliveryBoy::class);
    }
}
