<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;
use App\Observers\CategoryObserver;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The database primary key value.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['name', 'parent_id', 'image', 'restaurant_id', 'city_id', 'archive', 'sort'];

    protected $appends = ['has_children', 'image_url'];
    protected $hidden = ['image'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function getHasChildrenAttribute()
    {
        return $this->children()->count();
    }

    public function getImageUrlAttribute()
    {
        return url($this->image);
    }

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        $user = Auth::user();
        if ($user->restaurants->count() > 0 and in_array($user->user_type, [1, 2]) && (Settings::getSettings()->multiple_restaurants || Settings::getSettings()->multiple_cities))
            return Category::withDepth()->whereIn('restaurant_id', $user->restaurants->pluck('id')->all())->defaultOrder();
        else {
            $Category = Category::withDepth()->defaultOrder();
            if ($user->access_full)
                $Category = Category::withDepth()->defaultOrder();
            // exclusive agent
            elseif ($user->user_type == 3 and $user->access_categories)
                $Category = $Category->whereIn('restaurant_id', Restaurant::policyScope()->pluck('id')->all());
            // non exclusive agent and marketing company
            elseif (in_array($user->user_type, [4, 5]) and $user->access_categories)
                $Category = $Category->whereIn('restaurant_id', Restaurant::whereIn('created_by', $user->Children())->pluck('id')->all());
            // for admin user not super admin
            elseif ($user->user_type == 0 and $user->access_categories and !$user->access_full)
                $Category = $Category->whereIn('restaurant_id', Restaurant::policyScope()->pluck('id')->all());
            // for admin user not super admin
            elseif ($user->user_type == 0 and $user->access_full)
                $Category = Category::orderBy('sort', 'ASC');
            return $Category;
        }
    }

    public function scopeOfParentName($query)
    {
        return $query->select('name')->where('id', $this->parent_id)->get();
    }

    public function scopeOfParentCaregory($query)
    {
        $parent_id = $this->parent_id;
        return $query->whereHas('parent', function ($q) use ($parent_id) {
            $q->where('id', $parent_id);
        });
    }

    public function getParent($parent_id)
    {
        return Category::where('categories.id', $this->parent_id)->get();
    }
}

Category::observe(new CategoryObserver);
