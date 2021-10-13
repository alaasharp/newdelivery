<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerVendor extends Model
{
    protected $table = "vendor_customer";
    protected $fillable = ['vendor_id', 'customer_id', 'erp_id'];
}
