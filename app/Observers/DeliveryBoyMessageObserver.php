<?php

namespace App\Observers;

use App\Settings;
use Gomoob\Pushwoosh\Client\Pushwoosh;
use Gomoob\Pushwoosh\Model\Request\CreateMessageRequest;
use Gomoob\Pushwoosh\Model\Notification\Notification;

/**
 * Send request to OneSignal once push message were created
 */
class DeliveryBoyMessageObserver
{
    public function saved($model)
    {
        $settings = Settings::getSettings();
        if ($settings->driver_onesignal_id == null || $settings->driver_onesignal_id == '' ||
            $settings->driver_onesignal_token == null || $settings->driver_onesignal_token == '') {
            return;
        }
        $ids = [];
        foreach ($model->deliveryBoy->apiTokens as $token) {
            if (!empty($token->push_token))
                $ids[] = $token->push_token;
        }
        if (count($ids) == 0)
            return;

        $data = array(
            'app_id' => $settings->driver_onesignal_id,
            'contents' => array('en' => $model->message),
            'include_player_ids' => $ids
        );
        $data_string = json_encode($data);
        $ch = curl_init('https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Basic ' . $settings->driver_onesignal_token)
        );
        $result = json_decode(curl_exec($ch));
    }
}
