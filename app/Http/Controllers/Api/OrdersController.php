<?php

namespace App\Http\Controllers\Api;

use Mail;
use App\ApiToken;
use App\OrderStatus;
use App\Order;
use App\Vendor;
use App\Customer;
use App\CustomerVendor;
use App\User;
use App\Settings;
use App\Address;
use Carbon\Carbon;
use App\Restaurant;
use App\Services\OrdersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->all();
        $orders = Order::with('orderedProducts')->where('customer_id', $request->user->id)->orderBy('created_at', 'DESC');
        if (is_array($data) && isset($data['paginate']))
            $orders = $orders->paginate(20)->setpath($request->fullUrl());
        else
            $orders = $orders->get();
        return response()->json($orders);
    }

    public function getOrder(Request $request, $orderId)
    {
        $orders = Order::with('orderedProducts')->where('customer_id', $request->user->id)->where('id', $orderId)->get();
        return response()->json($orders);
    }

    public function setStatus(Request $request)
    {
        $order = Order::find($request->input('id'));
        $success = false;
        if ($request->user->id == $order->delivery_boy_id) {
            $status = OrderStatus::find($request->input('order_status_id'));
            if ($status != null && $status->available_to_delivery_boy) {
                $order->order_status_id = $status->id;
                if ($status->id == 15)
                    $order->is_paid = true;

                $order->save();
                if ($status->id == 15) {
                    foreach ($order->orderedProducts as $op) {
                        $d_product = \App\Product::find($op->product_id);
                        if ($d_product) {
                            $d_product->erp_quantity = $d_product->erp_quantity - $op->count;
                            $d_product->save();
                        }
                    }
                    $vendor = Vendor::find($order->vendor_id);
                    $user = User::find($vendor->user_id);
                    if ($user) {
                        if ($user->user_type == 1)
                            $this->sendCompanySalesReport($order->id);
                    }
                }
                $success = true;
            }
        }
        return response()->json(['success' => $success]);
    }

    public function caneclOrder(Request $request, $id = 0)
    {
        $success = false;
        if ($id != 0) {
            $apiToken = ApiToken::where('token', $request->header('token'))->first();
            $customer_id = null;
            if ($apiToken) {
                $customer_id = $apiToken->customer_id;
                $order = Order::find($id);
                if ($order->customer_id == $customer_id) {
                    $order->order_status_id = 16;
                    $order->save();
                    $success = true;
                }
            }
        }
        return response()->json(['success' => $success]);
    }

    public function indexDriver(Request $request)
    {
        $data = $request->all();
        $id = $request->user->id;
        $order_id = $request->input('search');
        $lat = $request->input('lat');
        $lang = $request->input('lang');
        $orders = Order::with('orderedProducts')->where('delivery_boy_id', $id);
        if (!empty($order_id)) $orders = $orders->where('id', $order_id);
        if ($lat and $lang) {
            $orders = Order::with('orderedProducts')->select('*',
                DB::raw('round(( 3959 * acos( cos( radians(' . $lat . ') )* cos( radians(Lat)) * cos(radians(lng) - radians(' . $lang . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat ) ) ) ),3 ) *1.609344  AS distance'))
                ->where('delivery_boy_id', $id);
            if (!empty($order_id)) $orders = $orders->where('id', $order_id);
            if (isset($data['sort_by']) and $data['sort_by'] == "near_by") $orders = $orders->orderBy('distance', 'asc');
        }
        if (isset($data['sort_by']) and $data['sort_by'] == "recent") $orders = $orders->orderBy('id', 'DESC');
        if (isset($data['sort_by']) and $data['sort_by'] == "older") $orders = $orders->orderBy('id', 'asc');
        if (isset($data['order_status'])) $orders = $orders->where('order_status_id', $data['order_status']);
        $orders = $orders->whereNotIn('order_status_id', [15, 16]);
        if (isset($data['from_date']) and !empty($data['from_date'])) $orders = $orders->whereDate('created_at', '>=', $data['from_date']);
        if (isset($data['to_date']) and !empty($data['to_date'])) $orders = $orders->whereDate('created_at', '<=', $data['to_date']);
        $orders = $orders->paginate(10);
        return response()->json($orders);
    }

    public function archiveDriver(Request $request)
    {
        $data = $request->all();
        $id = $request->user->id;
        $order_id = $request->input('search');
        $orders = Order::with('orderedProducts')->where('delivery_boy_id', $id);
        if (!empty($order_id)) $orders = $orders->where('id', $order_id);
        if (isset($data['sort_by']) and $data['sort_by'] == "recent") $orders = $orders->orderBy('id', 'DESC');
        if (isset($data['sort_by']) and $data['sort_by'] == "older") $orders = $orders->orderBy('id', 'asc');
        if (isset($data['order_status'])) $orders = $orders->where('order_status_id', $data['order_status']);
        $orders = $orders->whereIn('order_status_id', [15, 16]);
        if (isset($data['from_date']) and !empty($data['from_date'])) $orders = $orders->whereDate('created_at', '>=', $data['from_date']);
        if (isset($data['to_date']) and !empty($data['to_date'])) $orders = $orders->whereDate('created_at', '<=', $data['to_date']);
        $orders = $orders->paginate(10);
        $jsonData['data'] = $orders;
        $jsonData['total_sum'] = 750;
        $jsonData['orders'] = 20;
        return response()->json($orders);
    }

    public function archiveDriverSummury(Request $request)
    {
        $data = $request->all();
        $id = $request->user->id;
        $order_id = $request->input('search');
        $orders = Order::with('orderedProducts')->where('delivery_boy_id', $id);
        if (!empty($order_id)) $orders = $orders->where('id', $order_id);
        if (isset($data['sort_by']) and $data['sort_by'] == "recent") $orders = $orders->orderBy('id', 'DESC');
        if (isset($data['sort_by']) and $data['sort_by'] == "older") $orders = $orders->orderBy('id', 'asc');
        if (isset($data['order_status'])) $orders = $orders->where('order_status_id', $data['order_status']);
        $orders = $orders->whereIn('order_status_id', [15, 16]);
        if (isset($data['from_date']) and !empty($data['from_date'])) $orders = $orders->whereDate('created_at', '>=', $data['from_date']);
        if (isset($data['to_date']) and !empty($data['to_date'])) $orders = $orders->whereDate('created_at', '<=', $data['to_date']);
        $myorders = $orders;
        $reportData['orders_count'] = $myorders->count();
        $sum = $myorders->sum(DB::raw('total_with_tax + delivery_price'));
        $reportData['total_sum'] = round($sum, 3);
        return response()->json($reportData);
    }

    public function create(Request $request)
    {
        $apiToken = ApiToken::where('token', $request->header('token'))->first();
        $customer_id = null;
        if ($apiToken)
            $customer_id = $apiToken->customer_id;
        $loyalty = 0;
        if ($request->input('loyalty'))
            $loyalty = $request->input('loyalty');
        $new_address = "";
        if ($request->input('address_id')) {
            $add = Address::find($request->input('address_id'));
            if (!is_null($add)) {
                $new_address .= trans('messages.orders.building') . $add->building . trans('messages.orders.apartment') . $add->apartment;
                $new_address .= trans('messages.orders.special_palce') . $add->special_place;
            }
        }
        $data = [
            'name' => $request->input('name'),
            'address' => $request->input('address') . $new_address,
            'phone' => $request->input('phone'),
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
            'payment_method' => $request->input('payment_method'),
            'stripe_token' => $request->input('stripe_token'),
            'paypal_id' => $request->input('paypal_id'),
            'delivery_area_id' => $request->input('delivery_area_id'),
            'customer_id' => $customer_id,
            'city_id' => $request->input('city_id'),
            'loyalty' => $loyalty,
            'restaurant_id' => $request->input('restaurant_id'),
            'comment' => $request->input('comment'),
            'address_id' => $request->input('address_id') ? $request->input('address_id') : NULL,
        ];
        if (isset($data['restaurant_id'])) {
            $rest = Restaurant::find($data['restaurant_id']);
            $user_settings = Settings::getSettings($rest->user_id);
            if (!empty($user_settings->min_order_acceptance)) {
                $actual_price = $this->calcNetTotal($request->input('products'));
                if ($actual_price < $user_settings->min_order_acceptance) {
                    $response['errors'][] = trans('messages.orders.min_amount') . $user_settings->min_order_acceptance;
                    $response['success'] = false;
                    return response()->json($response);
                }
            }
        }
        $service = new OrdersService();
        $response = $service->createOrder($data, $request->input('products'), $request->input('code'));
        if ($response['success']) {
            $order = $response['order']->fresh();
            $customer_vendor = CustomerVendor::whereNotNull('erp_id')->where('vendor_id', $order->vendor_id)->where('customer_id', $order->customer_id)->get();
            if (count($customer_vendor) <= 0) {
                CustomerVendor::whereNull('erp_id')->where('vendor_id', $order->vendor_id)->where('customer_id', $order->customer_id)->delete();
                $cust = Customer::find($order->customer_id);
                if ($cust) {
                    $data = [
                        "name" => $cust->name,
                        "email" => $cust->email,
                        "phone" => $cust->phone,
                        "group_name" => "customer"
                    ];
                    $vendor = Vendor::find($order->vendor_id);
                    $user = User::find($vendor->user_id);
                    if ($user) {
                        if ($user->user_type == 1)
                            $newid = $this->sendCustomerDriver($user->companyName, $data);
                        $customer_vendor = new \App\CustomerVendor;
                        $customer_vendor->vendor_id = $order->vendor_id;
                        $customer_vendor->customer_id = $order->customer_id;
                        if ($user->user_type == 1) $customer_vendor->erp_id = $newid;
                        $customer_vendor->save();
                    }
                }
            }
            $this->sendMessage($order->id, $order->vendor_id);
            $response = [
                'success' => true,
                'order' => $order->load('orderedProducts')->toArray()
            ];
        }
        return response()->json($response);
    }

    public function calcNetTotal($products)
    {
        $sum = 0;
        if (is_null($products)) return $sum;
        foreach ($products as $item) {
            // $product = Product::where('id', $item['product']['price'])->first();
            $sum += $item['count'] * $item['product']['price'];
        }
        return $sum;
    }

    public function update(Request $request)
    {
        $apiToken = ApiToken::where('token', $request->header('token'))->first();
        $customer_id = null;
        if ($apiToken)
            $customer_id = $apiToken->customer_id;
        $loyalty = 0;
        if ($request->input('loyalty'))
            $loyalty = $request->input('loyalty');
        $data = [
            'name' => $request->input('name'),
            'address' => $request->input('address'),
            'phone' => $request->input('phone'),
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
            'payment_method' => $request->input('payment_method'),
            'stripe_token' => $request->input('stripe_token'),
            'paypal_id' => $request->input('paypal_id'),
            'delivery_area_id' => $request->input('delivery_area_id'),
            'customer_id' => $customer_id,
            'city_id' => $request->input('city_id'),
            'loyalty' => $loyalty,
            'restaurant_id' => $request->input('restaurant_id'),
            'comment' => $request->input('comment')
        ];
        //check if resturant  is pause
        $service = new OrdersService();
        $order = Order:: find($request->input('id'));
        if (is_null($order)) {
            $response = [
                'success' => false,
                'order' => null
            ];
            $response['errors'] = ['   الطلب  غير موجود '];
            return $response;
        }
        $response = $service->updateOrder($order, $data, $request->input('products'), $request->input('code'));
        if ($response['success']) {
            $order = $response['order']->fresh();
            Mail::send('emails.order_created', ['item' => $order], function ($m) use ($order) {
                $m->from(Settings::getSettings()->mail_from_mail, Settings::getSettings()->mail_from_name);
                $m->to(
                    explode(',', Settings::getSettings()->notification_email)
                )->subject(Settings::getSettings()->mail_from_new_order_subject);
            });
            $response = [
                'success' => true,
                'message' => 'تم تعديل الطلب',
                'order' => $order->load('orderedProducts')->toArray()
            ];
        }
        return response()->json($response);
    }

    protected function getValidator($data)
    {
        $service = new OrdersService();
        return $service->getValidator();
    }

    public function tracker(Request $request)
    {
        $status = OrderStatus::orderBy('sort', 'asc')->first();
        $orders = null;
        if ($status != null) {
            $order = Order::where('customer_id', $request->user->id)->whereNotNull('order_status_id')
                ->where('order_status_id', '<>', $status->id)->where('created_at', '>', (new Carbon())->subHours(3))
                ->orderBy('created_at', 'DESC')->first();
        }
        return response()->json([
            'order' => $order,
            'statuses' => OrderStatus::orderBy('sort', 'ASC')->get()
        ]);
    }
    /// send sales report  to  ERP
    function sendCompanySalesReport($id = null)
    {
        //get all  order not sent to erp yet
        $orders = null;
        $orders = Order::where('id', $id)->where('sendsales', '=', 0)->where('order_status_id', 15)->get();
        foreach ($orders as $order) {
            $user = \App\User::where('vendor_id', $order->vendor_id)->first();
            $company = $user->companyName;

            $pos_settings = json_decode($user->pos_settings);//get company name fro order
            $product_id = "";
            $product_names = "";
            $real_unit_price = "";
            $quantity = "";
            $product_codes = "";
            $count = 0;
            $product_type = "";
            foreach ($order->orderedProducts as $op) {
                if ($pos_settings->overselling == 0 and $op->product->erp_quantity <= 0) ;
                else {
                    $product_id .= $op->product->erp_id . "/";
                    $product_names .= $op->product->name . "/";
                    $product_codes .= $op->product->erp_code . "/";
                    $real_unit_price .= $op->product->price . "/";
                    $quantity .= $op->count . "/";
                    $product_type .= $op->product->erp_type . '/';
                    $count += $op->count;
                }
            }
            $product_id = rtrim($product_id, '/');
            $product_names = rtrim($product_names, '/');
            $product_codes = str_replace(" ", "", rtrim($product_codes, '/'));
            $real_unit_price = rtrim($real_unit_price, '/');
            $quantity = rtrim($quantity, '/');
            $product_type = rtrim($product_type, '/');
            $customer_id = CustomerVendor::where('vendor_id', $order->vendor_id)->where('customer_id', $order->customer_id)->first();
            if (is_null($customer_id)) $customer_id = $customer_id->erp_id;
            else $customer_id = $pos_settings->default_customer;
            //fill data array
            $payment = \App\Payment::where('companyName', $company)->where('type', 'cash')->first();
            $data = [
                "warehouse" => $pos_settings->default_warehouse,
                "customer" => $customer_id,
                "biller" => $pos_settings->default_biller,
                "total_items" => $count . '',
                "product_id[]" => $product_id,
                "product_name[]" => $product_names,
                "product_type[]" => $product_type,//"standard",
                "product_code[]" => $product_codes,
                "real_unit_price[]" => $real_unit_price,
                "unit_price[]" => $real_unit_price,
                "quantity[]" => $quantity,
                "order_tax" => $pos_settings->default_tax_rate,//$order->tax.'',
                "amount-paid[]" => $order->getGrandTotal() . '',
                "payment_status" => "paid",
                "pos_note" => "/",
                "balance_amount" => $order->total . '',
                "paid_by[]" => $payment->value . '',
                "shipping" => $order->delivery_price,
                "user_id" => "1"
            ];
            echo(json_encode($data));
            $sent = $this->sendSalesReport($company, $data);
            echo $sent;
            if ($sent == 1) {
                $order->sendsales = 1;
                $order->save();
            }
        }
        return redirect('/orders');
    }

    function sendSalesReport($company = "laziz", $data)
    {
        $company = strtolower($company);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://smarterp.top/api/API/sale2?api_key=asnvhgk12smartlive20174hfgs587&company=" . $company,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return 0;
        } else {
            $response = json_decode($response);
            $newid = $response->status ? $response->status : 0;
            return $newid;
        }
    }
    //==========================send notification s=========================
    function sendMessage($id, $user_id = null)
    {
        $content = array(
            "en" => 'New Order#' . $id
        );
        $headings = array(
            "en" => 'New order created plz  check '
        );
        $ids = User::where("vendor_id", $user_id)->first();
        $fields = array(
            'app_id' => "12690dc8-9043-47fc-9a03-a255dbe84ee0",
            'include_player_ids' => array($ids->one_signal_id),
            'contents' => $content,
            'headings' => $headings
        );
        $fields = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ZjlhMDJlM2UtZTQ3ZC00MmI3LWE1NTktM2MxZWEzYjE4ZTcz'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function sendMsgNeworder($id)
    {
        $response = $this->sendMessage($id);
        $data = json_decode($response, true);
        $id = $data['recipients'];
        return $id;
    }

    function sendCustomerDriver($company = "laziz", $data)
    {
        curl_init();
        $ch = curl_init("https://smarterp.top/api/API/addcustomer?api_key=asnvhgk12smartlive20174hfgs587&company=" . $company);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            return 0;
        } else {
            $response = json_decode($response);
            $newid = $response->cid ? $response->cid : 0;
            return $newid;
        }
    }
}
