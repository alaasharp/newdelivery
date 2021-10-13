<?php

namespace App;

use App\Observers\PushMessageObserver;
use Illuminate\Database\Eloquent\Model;
use  Auth;

class PushMessage extends Model
{
    protected $fillable = ['message', 'customer_id', 'created_by'];

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        // non exclusive agent and marketing company
        $user = Auth::user();
        if (in_array($user->user_type, [4, 5, 1, 2]))
            return PushMessage::orderBy('created_at', 'DESC')->whereIn('created_by', $user->Children());
        elseif (in_array($user->user_type, [0, 3]))
            return PushMessage::orderBy('created_at', 'DESC')->whereIn('customer_id', Customer::policyScope()->pluck('id')->all());
        return PushMessage::orderBy('created_at', 'DESC');
    }
}

PushMessage::observe(new PushMessageObserver);
