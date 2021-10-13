<?php

namespace App;

use App\Observers\DeliveryBoyMessageObserver;
use Illuminate\Database\Eloquent\Model;

class DeliveryBoyMessage extends Model
{
    protected $fillable = ['message', 'delivery_boy_id', 'read'];

    public function deliveryBoy()
    {
    	return $this->belongsTo(DeliveryBoy::class);
    }
}
DeliveryBoyMessage::observe(new DeliveryBoyMessageObserver);