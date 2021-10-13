<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'category_id', 'description', 'price', 'price_old', 'tax_group_id', 'vendor_id', 'archive', 'erp_quantity', 'barcode', 'created_by'];
    protected $appends = ['images', 'formatted_price', 'formatted_old_price', 'tax_value', 'city_id', 'restaurant_id', 'currency_format'];
    protected $hidden = ['erp_id', 'erp_unit', 'erp_type', 'erp_cost', 'erp_quantity', 'erp_subcategory_id', 'erp_code', 'erp_category_id',];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function taxGroup()
    {
        return $this->belongsTo(TaxGroup::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productImages()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function orderedProducts()
    {
        return $this->hasMany(OrderedProduct::class);
    }

    /**
     * Return images array with full URLs
     * @return Array
     */
    public function getImagesAttribute()
    {
        foreach ($this->productImages->pluck('image') as $key => $value) {
            $images[$key] = url(str_replace('thumbs/', '', $value));
        }
        if (count($images) == 0)
            $images[] = "https://smarterp.top/assets/uploads/no_image.png";
        return $images;
    }

    public function getCurrencyFormatAttribute()
    {
        $curncy = null;;
        $user = User::where('vendor_id', $this->vendor_id)->first();
        if ($user) {
            $s = Settings::where('user_id', $user->id)->first();
            if ($s)
                $curncy = explode(':', $s->currency_format)[0];
        }
        if ($curncy == null)
            $curncy = explode(':', \App\Settings::getSettings(2)->currency_format)[0];
        return $curncy;
    }

    public function getFormattedPriceAttribute()
    {
        $id = 2;
        $user = User::where('vendor_id', $this->vendor_id)->first();
        if ($user) $id = $user->id;
        return Settings::currency($this->price, $id);
    }

    public function getPriceAttribute($value)
    {
        $id = 2;
        $user = User::where('vendor_id', $this->vendor_id)->first();
        if ($user) $id = $user->id;
        return number_format($value, Settings::getSettings($id)->decimal_digits, '.', '');
    }

    public function getFormattedOldPriceAttribute()
    {
        $id = 2;
        $user = User::where('vendor_id', $this->vendor_id)->first();
        if ($user) $id = $user->id;
        return Settings::currency($this->price_old, $id);
    }

    public function getTaxValueAttribute()
    {
        $result = $this->taxGroup;
        if ($result == null)
            $result = TaxGroup::getDefaultTaxObject();
        return $result->value;
    }

    public function getCityIdAttribute()
    {
        $result = null;
        if ($this->category != null)
            $result = $this->category->city_id;
        return $result;
    }

    public function getRestaurantIdAttribute()
    {
        $result = null;
        if ($this->category != null)
            $result = $this->category->restaurant_id;
        return $result;
    }

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        return Product::whereIn('category_id', Category::policyScope()->pluck('id')->all());
    }
}
