<?php

namespace App\Observers;

use App\Settings;
use App\Customer;

/**
 * Send request to OneSignal once push message were created
 */
class OrderObserver
{
    public function saved($model)
    {
        $settings = Settings::getSettings();
        if ($model->status != 0)
            return;
        if ($settings->pushwoosh_id == null || $settings->pushwoosh_id == '' ||
            $settings->pushwoosh_token == null || $settings->pushwoosh_token == '') {
            $model->status = 3;
            $model->save();
            return;
        }
        $data = array(
            'app_id' => $settings->pushwoosh_id,
            'contents' => array('en' => $model->message)
        );
        if ($model->customer_id == null)
            $data['included_segments'] = ['All'];
        else {
            $user = Customer::find($model->customer_id);
            if ($user != null) {
                $tokens = [];
                foreach ($user->apiTokens as $apiToken) {
                    if (!empty($apiToken->push_token))
                        $tokens[] = $apiToken->push_token;
                }
                $data['include_player_ids'] = $tokens;
            }
        }
        $data_string = json_encode($data);
        $ch = curl_init('https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Basic ' . $settings->pushwoosh_token)
        );
        $result = json_decode(curl_exec($ch));
        if (isset($result->id) && $result->id)
            $model->status = 1;
        else {
            $model->status = 2;
            $model->error = json_encode($result);
        }
        $model->save();
    }
}
