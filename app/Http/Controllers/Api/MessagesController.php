<?php

namespace App\Http\Controllers\Api;

use App\DeliveryBoyMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(DeliveryBoyMessage::where('delivery_boy_id', $request->user->id)
            ->orderBy('id', 'DSC')->paginate(50));
    }

    public function read(Request $request)
    {
    	$m = DeliveryBoyMessage::find($request->input('id'));
    	if ($m != null && $m->delivery_boy_id == $request->user->id) {
    		$m->read = true;
    		$m->save();
    	}
    	return response()->json(DeliveryBoyMessage::where('delivery_boy_id', $request->user->id)->paginate(50));
    }
}
