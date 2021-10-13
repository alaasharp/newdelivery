<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;

class OrderStatusesController extends BaseController
{
    protected $base = 'order_statuses';
    protected $cls = 'App\OrderStatus';
    protected $orderBy = 'sort';
    protected $orderByDir = 'ASC';

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), [
        	'name' => 'required',
        	 'sort' => 'required|integer'
        ]);
    }
}
