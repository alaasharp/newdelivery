<?php

namespace App\Services;

use Validator;
use App\Order;
use App\Settings;

/**
 * Service to manage loyalty points
 */
class LoyaltyService
{
    public function earnPoints(Order $order)
    {
        $customer = $order->customer;
        if ($customer == null) {
            return;
        }
        $total_money = $customer->ordered_money_left + $order->total;
        $points_to_add = floor($total_money / Settings::getSettings(2)->loyalty_points_per_amount);
        $total_money = $total_money - $points_to_add * Settings::getSettings(2)->loyalty_points_per_amount;
        $points_to_add = $points_to_add * Settings::getSettings(2)->loyalty_points_per_order;
        $customer->loyalty_points = $customer->loyalty_points + $points_to_add;
        $customer->ordered_money_left = $total_money;
        if ($customer->loyalty_points >= Settings::getSettings(2)->loyalty_points_for_reward) {
            $customer->loyalty_points = 0;
            $customer->loyalty_reward = $customer->loyalty_reward + Settings::getSettings(2)->loyalty_reward_amount;
        }
        $customer->save();
    }
}