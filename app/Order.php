<?php

namespace App;

use Auth;
use App\Services\LoyaltyService;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'lat', 'lng', 'delivery_area_id', 'delivery_price', 'promo_code',
        'promo_code_id', 'promo_discount', 'payment_method', 'stripe_token', 'paypal_id', 'city_id', 'restaurant_id',
        'delivery_boy_id', 'is_paid', 'customer_id', 'comment', 'loyalty', 'vendor_id', 'address_id', 'reference'
    ];

    protected $appends = ['restaurant_data', 'display_price', 'status_text', 'currency_format', 'rated'];

    public function getRatedAttribute()
    {
        $rates_count = Rate::where('order_id', $this->id)->where('customer_id', $this->customer_id)->count();
        return $rates_count > 0 ? 1 : 0;
    }

    public function getCurrencyFormatAttribute()
    {
        $curncy = explode(':', Settings::getSettings(2)->currency_format)[0];
        $user = User::where('vendor_id', $this->vendor_id)->first();
        if ($user) {
            $s = Settings::where('user_id', $user->id)->first();
            if ($s)
                $curncy = explode(':', $s->currency_format)[0];
        }
        return $curncy;
    }

    public function getTaxAttribute()
    {
        $result = $this->attributes['tax'];
        $user = Vendor::find($this->vendor_id);
        $user_id = $user ? $user->user_id : 2;
        $tax_seting = Settings::where('user_id', $user_id)->first();
        $tax_included = $tax_seting ? $tax_seting->tax_included : 1;
        if ($tax_included == 1)
            $result = 0;
        return $result;
    }

    public function getDisplayPriceAttribute()
    {
        $id = 2;
        $user = User::where('vendor_id', $this->vendor_id)->first();
        if ($user) $id = $user->id;
        return Settings::currency($this->total_with_tax + $this->delivery_price, $id);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryBoy()
    {
        return $this->belongsTo(DeliveryBoy::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function getTotalTax()
    {
        $result = 0;
        $user = Vendor::find($this->vendor_id);
        $user_id = $user ? $user->user_id : 2;
        $tax_seting = Settings::where('user_id', $user_id)->first();
        $tax_included = $tax_seting ? $tax_seting->tax_included : 1;
        if ($tax_included == 1)
            $result = 0;
        else {
            foreach ($this->orderedProducts as $op) {
                if ($op->tax_value > 0)
                    $result = $result + $op->tax_value * $op->product->price * $op->count / 100;
            }
        }
        return $result;
    }

    public function orderedProducts()
    {
        return $this->hasMany(OrderedProduct::class);
    }

    public function rates()
    {
        return $this->hasMany(Rate::class, 'order_id');
    }

    public function deliveryArea()
    {
        return $this->belongsTo(DeliveryArea::class);
    }

    public function getStatusTextAttribute()
    {
        $result = '';
        if ($this->orderStatus != null) {
            $result = $this->orderStatus->name;
        }
        return $result;
    }

    public function getGrandTotal()
    {
        return $this->total_with_tax + $this->delivery_price;
    }

    /**
     * Process one of the payment methods (PayPal or Stripe)
     * @return void
     */
    public function pay()
    {
        $settings = Settings::first();
        if ($this->payment_method == 'stripe') {
            \Stripe\Stripe::setApiKey($settings->stripe_private);
            $charge = \Stripe\Charge::create([
                'amount' => (int)round($this->getGrandTotal() * 100),
                'currency' => 'usd',
                'source' => $this->stripe_token
            ]);
            $this->is_paid = true;
            $this->save();
        }
        if ($this->payment_method == 'paypal') {
            // get token
            if ($settings->paypal_production == '1') {
                $ch = curl_init('https://api.paypal.com/v1/oauth2/token');
            } else {
                $ch = curl_init('https://api.sandbox.paypal.com/v1/oauth2/token');
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Accept-Language: en_US'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $settings->paypal_client_id . ':' . $settings->paypal_client_secret);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
            $x = curl_exec($ch);
            curl_close($ch);
            $token = json_decode($x)->access_token;
            // get payment info
            $ch = curl_init('https://api.sandbox.paypal.com/v1/payments/payment/' . $this->paypal_id);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ));
            $result = curl_exec($ch);
            $result = json_decode($result);
            curl_close($ch);
            if (isset($result->transactions) && count($result->transactions) > 0) {
                if ($result->state == "approved" && $result->transactions[0]->amount->total == $this->getGrandTotal()) {
                    $this->is_paid = true;
                    $this->save();
                }
            }
        }
        if ($this->is_paid || ($this->payment_method == 'cash')) {
            $service = new LoyaltyService();
            $service->earnPoints($this->fresh());
        }
    }

    /**
     * Relation of models accessible by current user
     * @return Relation
     */
    public static function policyScope()
    {
        $user = Auth::user();
        if ($user->restaurants->count() > 0 and in_array($user->user_type, [1, 2]) && !$user->access_full && (Settings::getSettings()->multiple_restaurants || Settings::getSettings()->multiple_cities))
            $order = Order::whereIn('restaurant_id', $user->restaurants->pluck('id')->all())->orderBy('created_at', 'DESC');
        else {
            if ($user->access_full || !Settings::getSettings()->multiple_cities)
                $order = Order::orderBy('created_at', 'DESC');
            else
                $order = Order::whereIn('restaurant_id', Restaurant::policyScope()->pluck('id')->all())->orderBy('created_at', 'DESC');
        }
        return $order;
    }

    public function getRestaurantDataAttribute()
    {
        return $this->restaurant;
    }
}
