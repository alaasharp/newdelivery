<?php

namespace App\Http\Controllers;

use App\PushMessage;
use App\Settings;
use Gate;
use Illuminate\Http\Request;
use App\DeliveryBoyMessage;
use App\Order;
use App\CustomerVendor;
use App\Services\OrdersService;

class OrdersController extends BaseController
{
    protected $base = 'orders';
    protected $cls = 'App\Order';
    protected $checkboxes = ['is_paid'];

    protected function getIndexItems($data)
    {
        if (auth()->user()->user_type == 1)
            $data['vendor'] = auth()->user()->vendor_id;
        if ($data != null) {
            $orders = Order::policyScope();
            if (is_array($data) && isset($data['q'])) {
                $orders = $orders->where(function ($query) use ($data) {
                    $q = '%' . $data['q'] . '%';
                    return $query->where('address', 'LIKE', $q)->orWhere('name', 'LIKE', $q)
                        ->orWhere('id', 'LIKE', $q)->orWhere('reference', 'LIKE', $q)->orWhere('phone', 'LIKE', $q);
                });
            }
            if (is_array($data) && isset($data['city_id']))
                $orders = $orders->where('city_id', $data['city_id']);
            if (is_array($data) && isset($data['restaurant_id']))
                $orders = $orders->where('restaurant_id', $data['restaurant_id']);
            if (is_array($data) && isset($data['vendor']))
                $orders = $orders->where('vendor_id', '=', $data['vendor']);
            if (is_array($data) && isset($data['customer_id']))
                $orders = $orders->where('customer_id', $data['customer_id']);
            if (is_array($data) && isset($data['order_status_id']))
                $orders = $orders->where('order_status_id', $data['order_status_id']);
            if (is_array($data) && isset($data['dt']))
                $orders = $orders->whereDate('created_at', '=', $data['dt']);
            return $orders->paginate(20);
        } else
            return Order::policyScope()->paginate(20);
    }

    public function save($item, Request $request)
    {
        $service = new OrdersService();
        $validator = $service->getValidator($request->all());
        if ($validator->passes()) {
            $data = [
                'name' => $request->input('name'),
                'address' => $request->input('address'),
                'phone' => $request->input('phone'),
                'payment_method' => $request->input('payment_method'),
                'stripe_token' => '',
                'paypal_id' => '',
                'delivery_area_id' => $request->input('delivery_area_id'),
                'city_id' => $request->input('city_id'),
                'order_status_id' => $request->input('order_status_id'),
                'restaurant_id' => $request->input('restaurant_id'),
                'is_paid' => ($request->input('is_paid') ? true : false),
                'customer_id' => $request->input('customer_id'),
                'vendor_id' => $request->input('vendor_id')
            ];
            if ($data["order_status_id"] == 15)
                $data["is_paid"] = true;
            $status = $item->order_status_id;
            if ($item->id == null) {
                $response = $service->createOrder($data, [], $request->input('promo_code'));
                $ordery = $response['order'];
                $customer_vendor = CustomerVendor::where('vendor_id', $ordery->vendor_id)->where('customer_id', $ordery->customer_id)->get();
                if (count($customer_vendor) < 0) {
                    $customer_vendor = new CustomerVendor;
                    $customer_vendor->vendor_id = $ordery->vendor_id;
                    $customer_vendor->customer_id = $ordery->customer_id;
                    $customer_vendor->save();
                }
            } else
                $response = $service->updateOrder($item, $data, [], $request->input('promo_code'));
            $item = $response['order'];
            if ($item->order_status_id == 15) {
                if (auth()->user()->order_auto == 1)
                    $this->sendCompanySalesReport($item->id);
            }
            if ($status != $item->order_status_id && Settings::getSettings()->send_push_on_status_change) {
                PushMessage::create([
                    'message' => __('messages.orders.status_changed', ['status' => $item->orderStatus->name]),
                    'customer_id' => $item->customer_id
                ]);
            }
            return redirect(route($this->base . '.index'));
        } else {
            $item->fill($request->all());
            $errors = $validator->messages();
            return view($this->base . '.form', array_merge(compact('item', 'errors'), $this->getAdditionalData()));
        }
    }

    public function setDeliveryBoy($id, Request $request)
    {
        $item = Order::find($id);
        if (!Gate::allows('create', $item))
            return redirect('/');
        $boy_id = $request->input('delivery_boy_id');
        $item->delivery_boy_id = $boy_id;
        $item->save();
        DeliveryBoyMessage::create([
            'delivery_boy_id' => $boy_id,
            'message' => __('messages.delivery_boy_messages.new_order', ['id' => $id])
        ]);
        return redirect(route('orders.show', ['id' => $id]));
    }

    /// send sales report  to  ERP
    function sendCompanySalesReport($id = null)
    {
        if (auth()->user()->user_type == 2) return 0;
        //get all  order not sent to erp yet
        $orders = null;
        if (is_null($id)) {
            if (auth()->user()->user_type == 0 and auth()->user()->access_orders == 1)
                $orders = Order::where('vendor_id', '<>', 0)->where('sendsales', '=', 0)
                    ->where('order_status_id', 15)->get();
            else
                $orders = Order::where('vendor_id', '=', auth()->user()->vendor_id)
                    ->where('sendsales', '=', 0)->where('order_status_id', 15)->get();
        } else {
            if (auth()->user()->user_type == 0 and auth()->user()->access_orders == 1)
                $orders = Order::where('id', $id)->where('vendor_id', '<>', 0)
                    ->where('sendsales', '=', 0)->where('order_status_id', 15)->get();
            else
                $orders = Order::where('id', $id)->where('vendor_id', '=', auth()->user()->vendor_id)
                    ->where('sendsales', '=', 0)->where('order_status_id', 15)->get();
        }
        foreach ($orders as $order) {
            $user = \App\User::where('vendor_id', $order->vendor_id)->first();
            $company = $user->companyName;
            $pos_settings = json_decode($user->pos_settings);//get company name fro order
            ///read defualte  value for company
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
            $customer_id = \App\CustomerVendor::whereNotNull('erp_id')->where('vendor_id', $order->vendor_id)->where('customer_id', $order->customer_id)->first();
            if (!is_null($customer_id)) $customer_id = $customer_id->erp_id;
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
                "user_id" => "1",
                "reference_no" => "SmartD" . $order->id
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
        if (auth()->user()->user_type == 2) return 0;
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
        if ($err)
            return 0;
        else {
            $response = json_decode($response);
            if ($response)
                $newid = $response->status ? $response->status : 0;
            else
                $newid = 0;
            return $newid;
        }
    }

    public function setStatus(Request $request)
    {
        $order = Order::find($request->input('oid'));
        $success['success'] = false;
        $status = \App\OrderStatus::find($request->input('sid'));
        $old_staus = $order->order_status_id;
        if ($status != null) {
            $order->order_status_id = $status->id;
            if ($status->id == 15)
                $order->is_paid = true;
            $order->save();
            if ($status->id != $old_staus &&
                Settings::getSettings()->send_push_on_status_change) {
                PushMessage::create([
                    'message' => __('messages.orders.status_changed', ['status' => $order->orderStatus->name]),
                    'customer_id' => $order->customer_id
                ]);
            }
            if ($status->id == 15) {
                foreach ($order->orderedProducts as $op) {
                    $d_product = \App\Product::find($op->product_id);
                    if ($d_product) {
                        $d_product->erp_quantity = $d_product->erp_quantity - $op->count;
                        $d_product->save();
                    }
                }
                $this->sendCompanySalesReport($order->id);
            }
            $success['success'] = true;
            $success['s_text'] = $status->name;
            $success['is_paid'] = $order->is_paid == 1 ? "<span class='label label-success'>" . trans('messages.common.yes') . "</span>" : "<span class='label label-success'>" . trans('messages.common.no') . "</span>";
            $success['sync'] = $order->sendsales == 1 ? trans('messages.orders.sync_done') : 0;
        }
        return response()->json($success);
    }
}
