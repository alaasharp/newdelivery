<?php

namespace App;

use Auth;
use App\Observers\RestaurantObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = ['name', 'description', 'city_id', 'archive', 'cover_image', 'rlang', 'rlat', 'created_by'];
    protected $appends = ['image_url', 'cover_image_url', 'work_hours', 'next_open_time'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function getImageUrlAttribute()
    {
        return url($this->image);
    }

    public function getCoverImageUrlAttribute()
    {
        return url($this->cover_image);
    }

    public function getCoverImageAttribute($value)
    {
        return $value ? url($value) : url('theme/images/logo-side.png');
    }

    public function DeliveryArea()
    {
        return $this->hasMany(DeliveryArea::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function getNextOpenTimeAttribute()
    {
        $restaurant_id = $this->id;
        $day = Carbon::now()->format('l');
        $day = Restaurant::convert_day_name_to_day_number($day);
        $appointment = Appointment::where('restaurant_id',$restaurant_id);
        $restaurant_next_open = 'restaurant is close';
        $data['data'] = $restaurant_next_open;
        $data['status'] = false;
        $data['message'] = 'المطعم مغلق';
        if ($appointment->count() > 0){
            $appointment = $appointment->where('day',$day);
            if ($appointment->count() > 0) {
                $day = $day == 6 ? 0 : $day + 1;
                $appointment = Appointment::where('restaurant_id', $restaurant_id)->where('day', $day);
                if ($appointment->count() > 0)
                    $restaurant_next_open = $appointment->first()->open_from;
            }
        }
        return $restaurant_next_open;
    }

    public function getWorkHoursAttribute()
    {
        $real_data = Appointment::where('restaurant_id', $this->id)->orderBY('day', 'asc')->get()->all();
        $data = array(
            ["open_from" => "08:00:00", "open_to" => "23:00:00", "status" => 0, "day" => 0],
            ["open_from" => "08:00:00", "open_to" => "23:00:00", "status" => 0, "day" => 1],
            ["open_from" => "08:00:00", "open_to" => "23:00:00", "status" => 0, "day" => 2],
            ["open_from" => "08:00:00", "open_to" => "23:00:00", "status" => 0, "day" => 3],
            ["open_from" => "08:00:00", "open_to" => "23:00:00", "status" => 0, "day" => 4],
            ["open_from" => "08:00:00", "open_to" => "23:00:00", "status" => 0, "day" => 5],
            ["open_from" => "08:00:00", "open_to" => "23:00:00", "status" => 0, "day" => 6]
        );
        for ($i = 0; $i < count($data); $i++) {
            if (isset($real_data[$i])) {
                $data[$i]['open_from'] = $real_data[$i]['open_from'];
                $data[$i]['open_to'] = $real_data[$i]['open_to'];
                $data[$i]['day'] = $real_data[$i]['day'];
                $data[$i]['status'] = $real_data[$i]['status'];
            }
        }
        return $data;
    }

    public static function convert_day_name_to_day_number($day_name)
    {
        if ($day_name == 'Saturday'){$day_number = 0;}
        elseif ($day_name == 'Sunday'){$day_number = 1;}
        elseif ($day_name == 'Monday'){$day_number = 2;}
        elseif ($day_name == 'Tuesday'){$day_number = 3;}
        elseif ($day_name == 'Wednesday'){$day_number = 4;}
        elseif ($day_name == 'Thursday'){$day_number = 5;}
        elseif ($day_name == 'Friday'){$day_number = 6;}
        else{$day_number = 'Invalid';}
        return $day_number;
    }

    public static function convert_day_number_to_day_name($day_number)
    {
        if ($day_number == 0){$day_name = 'Saturday';}
        elseif ($day_number == 1){$day_name = 'Sunday';}
        elseif ($day_number == 2){$day_name = 'Monday';}
        elseif ($day_number == 3){$day_name = 'Tuesday';}
        elseif ($day_number == 4){$day_name = 'Wednesday';}
        elseif ($day_number == 5){$day_name = 'Thursday';}
        elseif ($day_number == 6){$day_name = 'Friday';}
        else{$day_name = 'Invalid';}
        return $day_name;
    }

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        $user = Auth::user();
        if ($user->restaurants->count() > 0 and in_array($user->user_type, [1, 2]) && !$user->access_full && (Settings::getSettings()->multiple_restaurants || Settings::getSettings()->multiple_cities))
            return Restaurant::whereIn('id', $user->restaurants->pluck('id')->all())->orderBy('sort', 'ASC');
        else {
            $Restaurant = Restaurant:: orderBy('sort', 'ASC');
            // exclusive agent
            if ($user->user_type == 3 and $user->access_restaurants)
                $Restaurant = $Restaurant->whereIn('city_id', City::policyScope()->pluck('id')->all());
            // non exclusive agent and marketing company
            elseif (in_array($user->user_type, [4, 5]) and $user->access_restaurants)
                $Restaurant = $Restaurant->whereIn('created_by', $user->Children());
            // for admin user not super admin
            elseif ($user->user_type == 0 and $user->access_restaurants and !$user->access_full)
                $Restaurant = $Restaurant->whereIn('city_id', City::policyScope()->pluck('id')->all());
            else
                $Restaurant = Restaurant:: orderBy('sort', 'ASC');
            return $Restaurant;
        }
    }
}
Restaurant::observe(new RestaurantObserver);

