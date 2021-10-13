<?php

namespace App\Providers;
use Validator;
use Illuminate\Support\ServiceProvider;
use DB;
class CustomValidationServiceProvider extends ServiceProvider
{
   
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('sagent', function ($attribute, $value, $parameters, $validator) {

            
            $country =  DB::table("country_user")->select( "user_id")->whereIn("country_id",$value)->pluck('user_id')->all()  ;
            $users =  DB::table("users")->select( "id")->where('user_type',3)->whereIn("id",$country)->count()  ;
           
             
            if ($users == 0)
               return true;
            else
          return false;
        });

         
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
