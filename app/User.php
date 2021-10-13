<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Observers\UserObserver;
use DB;
use Auth;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'access_full', 'access_news', 'access_categories', 'access_products',
        'access_orders', 'access_customers', 'access_pushes', 'access_delivery_areas',
        'access_promo_codes', 'access_tax_groups', 'access_cities', 'access_restaurants',
        'access_settings', 'access_users', 'access_delivery_boys', 'access_order_statuses',
        'access_vendors', 'user_type', 'companyName', 'city_id', 'country_id', 'access_countries', 'created_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token',];

    public function cities()
    {
        return $this->belongsToMany(City::class);
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class);
    }


    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'user_id');
    }

    public function Children()
    {
        $Children = DB::select(" select id,name,created_by 
                    from    (select * from users order by created_by, id) myusers, (select @pv := ?) initialisation
                    where   find_in_set(created_by, @pv)
                    and     length(@pv := concat(@pv, ',', id))", [$this->id]);

        return array_merge(array_column($Children, 'id'), [$this->id]);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        $user = Auth::user();
        $users = User::where('id', '<>', 2);
        // exclusive agent
        if ($user->user_type == 3 and $user->access_users)
            $users = $users->whereIn('city_id', $user->cities->pluck('id')->all())->whereIn('country_id', $user->countries->pluck('id')->all());
        // non exclusive agent and marketing company
        elseif (in_array($user->user_type, [4, 5]) and $user->access_users)
            $users = $users->whereIn('id', $user->Children());
        // for admin user not super admin
        elseif ($user->user_type == 0 and $user->access_users and !$user->access_full)
            $users = $users->whereIn('city_id', $user->cities->pluck('id')->all())->whereIn('country_id', $user->countries->pluck('id')->all());
        return $users;
    }
}

User::observe(new UserObserver);