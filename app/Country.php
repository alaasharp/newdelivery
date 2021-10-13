<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['name' ,'latitude', 'longitude', 'en_name', 'tr_name','phone_code','code'];

    public static function policyScope()
    {
        $user = Auth::user();
        if ($user->access_full  )
            return Country::orderBy('name', 'ASC');
        else
            return Country::orderBy('id', 'ASC')->whereIn('id', $user->countries->pluck('id')->all());
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    public static function hasSagent($coutry_ids)
    {
         return 0;
    }
}
