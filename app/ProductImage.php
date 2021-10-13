<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image'];
    protected $attributes = ['image' => "https://smarterp.top/assets/uploads/no_image.png"];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageAttribute($value)
    {
        return ($value) ? url(str_replace('thumbs/', '', $value)) : "https://smarterp.top/assets/uploads/no_image.png";
    }
}
