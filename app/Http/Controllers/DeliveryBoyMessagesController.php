<?php

namespace App\Http\Controllers;

use Gate;
use Illuminate\Http\Request;
use App\DeliveryBoyMessage;

class DeliveryBoyMessagesController extends BaseController
{
    protected $base = 'delivery_boy_messages';
    protected $cls = 'App\DeliveryBoyMessage';

    protected function getIndexItems($data)
    {
        return DeliveryBoyMessage::where('delivery_boy_id', $data['delivery_boy_id'])
        ->orderBy('id', 'DSC')->paginate(20);
    }

    protected function getAdditionalData($data = null) {
    	if (!isset($data['filter']) || !isset($data['filter']['delivery_boy_id']))
    		return [];
        return ['delivery_boy_id' => $data['filter']['delivery_boy_id']];
    }

    protected function redirectOnCreatePath(Request $request)
    {
        return route($this->base . '.index', ['filter' => ['delivery_boy_id' => $request->input('delivery_boy_id')]]);
    }
}
