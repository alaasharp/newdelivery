<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaxGroup extends Model
{
    protected $fillable = ['value', 'name', 'is_default', 'companyName', 'user_id', 'created_by'];

    protected static $defaultTax = null;

    public static function getDefaultTax()
    {
        return self::getDefaultTaxObject()->value;
    }


    /**
     * Returns the default tax (which has is_default checkbox set)
     * @return TaxGroup
     */
    public static function getDefaultTaxObject()
    {
        if (self::$defaultTax == null) {
            $tax = TaxGroup::where('is_default', true)->first();
            if ($tax != null)
                self::$defaultTax = $tax;
            else
                self::$defaultTax = new TaxGroup(['value' => 0]);
        }
        return self::$defaultTax;
    }
}
