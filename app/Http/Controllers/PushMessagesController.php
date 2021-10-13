<?php

namespace App\Http\Controllers;

use App\PushMessage;
use Illuminate\Http\Request;

class PushMessagesController extends BaseController
{
    protected $base = 'push_messages';
    protected $cls = 'App\PushMessage';

    protected function getIndexItems($data)
    {
        $pushMessage = PushMessage::policyScope();
        if (is_array($data) && isset($data['customer_id']))
            $pushMessage = $pushMessage->where('customer_id', $data['customer_id']);
        return $pushMessage->orderBy($this->orderBy, $this->orderByDir)->paginate(20);

    }

    protected function getAdditionalData($data = null)
    {
        return $data;
    }

    protected function modifyRequestData($data)
    {
        if (!isset($data['_method']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }

    protected function redirectOnCreatePath(Request $request)
    {
        return route($this->base . '.index', ['filter' => ['customer_id' => $request->input('customer_id')]]);
    }
}
