<?php

namespace App\Http\Controllers;

use App\ProductImage;
use Validator;
use Gate;
use App\Category;
use App\Product;
use Auth;
use App\Customer;
use App\User;
use App\Restaurant;
use App\TaxGroup;
use App\Settings;
use App\Payment;
use App\CustomerVendor;

class GetDataFromERp extends Controller
{
    private $user;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            if ($this->user->user_type != 1) return redirect('/');
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->getErpCats();
        $this->getErpTaxs();
        $this->getErpData();
        $this->getErpCustomer();
        return redirect('/');
    }

    public function getErpData()
    {
        $company = auth()->user()->companyName;
        $company = strtolower($company);
        $user = User::find(auth()->user()->id);
        $overselling = json_decode($user->pos_settings);
        $overselling = $overselling->overselling;
        $companysetting = json_decode($user->company_setting);
        $timestamp = '2010-08-05 00:00:00';
        if ($company) {
            $url = "https://smarterp.top/api/API/allproducts?api_key=asnvhgk12smartlive20174hfgs587&company=" . $company . "&timestamp=" . str_replace(' ', '%20', $timestamp);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,//"https://smarterp.top/api/API/allproducts?api_key=asnvhgk12smartlive20174hfgs587&company=laziz&timestamp=2019-08-05%2000%3A00%3A00",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "postman-token: 4d2a9383-e37a-e442-b3cd-f4df20596a81"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err)
                $msg = "cURL Error #:" . $err;
            else {
                $response = json_decode($response);
                if ($response->status == 0)
                    $msg = "لايوجد بيانات";
                else {
                    $products = $response->data;
                    $i = 0;
                    $resturarnt_id = (Restaurant::where('user_id', auth()->user()->id)->first())->id;
                    foreach ($products as $item) {
                        $product = Product::where('erp_id', $item->id)->where('vendor_id', auth()->user()->vendor_id)->first();
                        if ($product != null) {
                            $product->erp_id = $item->id;
                            $product->name = $item->name;
                            $product->erp_code = $item->code;
                            $product->erp_unit = $item->unit;
                            $price = $item->price;
                            if ($item->tax_method == 1 and $companysetting->default_tax_rate == 1) {
                                $tax = TaxGroup::where('erp_id', $item->tax_rate)->where('companyName', $company)->first();
                                if ($tax)
                                    $price = (($tax->value / 100) * $item->price) + $item->price;
                            } elseif ($item->tax_method == 0 and $companysetting->default_tax_rate2 == 0 and $companysetting->default_tax_rate == 0) {
                                $tax = TaxGroup::where('erp_id', $item->tax_rate)->where('companyName', $company)->first();
                            }
                            $product->price = $price;
                            $category_id = empty($item->subcategory_id) ? $item->category_id : $item->subcategory_id;
                            $catid = (Category::where('company_name', $company)->where('erp_id', $category_id)->first());
                            $catid = $catid ? $catid->id : 0;
                            if ($catid == 0) {
                                $catid = (Category::where('company_name', $company)->where('defualt', 1)->first());
                                if (is_null($catid)) {
                                    $dcat = new Category;
                                    $dcat->name = trans('messages.products.uncategorized');
                                    $dcat->company_name = $company;
                                    $dcat->defualt = 1;
                                    $dcat->restaurant_id = $resturarnt_id;
                                    $dcat->save();
                                    $catid = $dcat->id;
                                } else
                                    $catid = $catid->id;
                            }
                            if ($catid != 0) {
                                $product->category_id = $catid;
                                $product->erp_category_id = $item->category_id;
                                $product->erp_subcategory_id = $item->subcategory_id;
                                $product->vendor_id = auth()->user()->vendor_id;
                                $product->tax_group_id = $item->tax_rate;
                                $product->erp_quantity = $item->quantity ? $item->quantity : 0;
                                $product->erp_cost = $item->cost;
                                $product->erp_unit = $item->unit;
                                $product->erp_type = $item->type;
                                $product->timestamp = $item->timestamp;
                                if ($product->archive == 1) {
                                    if ($overselling == 1 and $item->quantity <= 0)
                                        $product->archive = 1;
                                    elseif ($overselling == 0 and $item->quantity <= 0)
                                        $product->archive = 0;
                                }
                                $product->save();
                                $image_names = array();
                                $image_names[] = $item->image;
                                ProductImage::where('product_id', $product->id)->delete();
                                for ($x = 0; $x < count($image_names); $x++) {
                                    $pi = new ProductImage([
                                        'image' => $image_names[$x],
                                        'product_id' => $product->id
                                    ]);
                                    $pi->save();
                                }
                            }
                        } else {
                            $product = new Product;
                            $product->erp_id = $item->id;
                            $product->name = $item->name;
                            $product->erp_code = $item->code;
                            $product->erp_unit = $item->unit;
                            $price = $item->price;
                            if ($item->tax_method == 1 and $companysetting->default_tax_rate == 1) {
                                $tax = TaxGroup::where('erp_id', $item->tax_rate)->where('companyName', $company)->first();
                                if ($tax)
                                    $price = (($tax->value / 100) * $item->price) + $item->price;
                            } elseif ($item->tax_method == 0 and $companysetting->default_tax_rate2 == 0 and $companysetting->default_tax_rate == 0) {
                                $tax = TaxGroup::where('erp_id', $item->tax_rate)->where('companyName', $company)->first();
                            }
                            $product->price = $price;
                            $category_id = empty($item->subcategory_id) ? $item->category_id : $item->subcategory_id;
                            $catid = (Category::where('company_name', $company)->where('erp_id', $category_id)->first());
                            $catid = $catid ? $catid->id : 0;
                            if ($catid == 0) {
                                $catid = (Category::where('company_name', $company)->where('defualt', 1)->first());
                                if (is_null($catid)) {
                                    $dcat = new Category;
                                    $dcat->name = trans('messages.products.uncategorized');
                                    $dcat->company_name = $company;
                                    $dcat->defualt = 1;
                                    $dcat->restaurant_id = $resturarnt_id;
                                    $dcat->save();
                                    $catid = $dcat->id;
                                } else
                                    $catid = $catid->id;

                            }
                            if ($catid != 0) {
                                $product->category_id = $catid;
                                $product->erp_category_id = $item->category_id;
                                $product->erp_subcategory_id = $item->subcategory_id;
                                $product->vendor_id = auth()->user()->vendor_id;
                                $product->tax_group_id = $item->tax_rate;
                                $product->erp_quantity = $item->quantity ? $item->quantity : 0;
                                $product->erp_cost = $item->cost;
                                $product->erp_unit = $item->unit;
                                $product->erp_type = $item->type;
                                $product->timestamp = $item->timestamp;
                                if ($overselling == 1 and $item->quantity <= 0)
                                    $product->archive = 1;
                                elseif ($overselling == 0 and $item->quantity <= 0)
                                    $product->archive = 0;
                                else
                                    $product->archive = 1;
                                $product->save();
                                $image_names = array();
                                $image_names[] = $item->image;
                                for ($x = 0; $x < count($image_names); $x++) {
                                    $pi = new ProductImage([
                                        'image' => $image_names[$x],
                                        'product_id' => $product->id
                                    ]);
                                    $pi->save();
                                }
                                $i++;
                            }
                        }
                    }
                    $msg = "ok";
                }
            }
        } else {
            $msg = "الشركة غير موجودة";
        }
        return response()->json($msg);
    }

    public function getErpCats()
    {
        $company = auth()->user()->companyName;
        $company = strtolower($company);
        if ($company) {
            $resturarnt_id = (Restaurant::where('user_id', auth()->user()->id)->first())->id;
            $company_setting = null;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://smarterp.top/api/API/settingdesktop?api_key=asnvhgk12smartlive20174hfgs587&company=" . $company,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array("cache-control: no-cache"),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err)
                $msg = "cURL Error #:" . $err;
            else
                $company_setting = json_decode($response);
            ////////////// end get  company setting =======================
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://smarterp.top/api/API/lists2?api_key=asnvhgk12smartlive20174hfgs587&company=" . $company,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array("cache-control: no-cache"),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err)
                $msg = "cURL Error #:" . $err;
            else {
                $response = json_decode($response);
                $payments = $response->data->payment;
                $pos_settings = $response->data->pos_setting;
                $user = User::find(auth()->user()->id);
                $user->pos_settings = json_encode($pos_settings);
                $company_setting = json_encode($company_setting);
                $user->company_setting = $company_setting;
                $user->save();
                $setings = Settings::where('user_id', auth()->user()->id)->first();
                if ($setings) {
                    $setings->currency_format = $response->data->defultcurrency . ":value";
                    $setings->tax_included = json_decode($company_setting)->default_tax_rate2 > 0 ? 0 : 1;
                    $setings->save();
                } else {
                    $setings = new Settings;
                    $setings->user_id = auth()->user()->id;
                    $setings->currency_format = $response->data->defultcurrency . ":value";
                    $setings->tax_included = json_decode($company_setting)->default_tax_rate2 > 0 ? 0 : 1;
                    $setings->save();
                }
                $categorys = array_merge($response->data->category, $response->data->subcategory);
                Category::whereNotIn('erp_id', array_column($categorys, 'id'))->where('company_name', $company)->delete();
                $i = 0;
                if (is_null($categorys))
                    $msg = "لاتوجد بيانات";
                else
                    foreach ($categorys as $item) {
                        $max_id = Category::where('erp_id', $item->id)->where('company_name', $company)->first();
                        if (is_null($max_id)) {
                            $category = new Category;
                            $category->erp_id = $item->id;
                            $category->name = $item->name;
                            if ($item->parent_id != 0)
                                $category->parent_id = (Category::where('company_name', $company)->where('erp_id', $item->parent_id)->first())->id;
                            $category->restaurant_id = $resturarnt_id;
                            $category->company_name = $company;
                            $category->save();
                            $i++;
                        } else {
                            $max_id->name = $item->name;
                            if ($item->parent_id != 0)
                                $max_id->parent_id = (Category::where('company_name', $company)->where('erp_id', $item->parent_id)->first())->id;
                            $max_id->save();
                        }
                    }
                $msg = "ok";
                // get  payment ....
                if (is_null($payments))
                    $msg = "لاتوجد بيانات";
                else
                    foreach ($payments as $payment) {
                        $check = Payment::where('companyName', $company)->where('type', $payment->type)->get();
                        if (count($check) == 0) {
                            $pay = new \App\Payment;
                            $pay->name = $payment->name;
                            $pay->name_en = $payment->name_en;
                            $pay->value = $payment->value;
                            $pay->type = $payment->type;
                            $pay->companyName = $company;
                            $pay->save();
                        }
                    }
                //end get payment
            }
        } else {
            $msg = "الشركة غير موجودة";
        }
        return response()->json($msg);
    }

    // get taxs
    public function getErpTaxs()
    {
        $company = auth()->user()->companyName;
        $company = strtolower($company);
        if ($company) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://smarterp.top/api/API/lists?api_key=asnvhgk12smartlive20174hfgs587&company=" . $company,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array("cache-control: no-cache"),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err)
                $msg = "cURL Error #:" . $err;
            else {
                $response = json_decode($response);
                $TaxRates = $response->data->TaxRates;
                $i = 0;
                foreach ($TaxRates as $item) {
                    $max_id = TaxGroup::select('erp_id')->where('companyName', $company)->where('erp_id', $item->id)->first();
                    if (is_null($max_id)) {
                        $tax = new TaxGroup;
                        $tax->erp_id = $item->id;
                        $tax->name = $item->name;
                        $tax->value = $item->rate;
                        $tax->companyName = $company;
                        $tax->save();
                        $i++;
                    }
                }
                $msg = "ok";
            }
        } else {
            $msg = "الشركة غير موجودة";
        }
        return response()->json($msg);
    }

    /// / get taxs
    public function getErpCustomer()
    {
        $company = auth()->user()->companyName;
        if ($company) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://smarterp.top/api/API/lists?api_key=asnvhgk12smartlive20174hfgs587&company=" . $company,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                $msg = "cURL Error #:" . $err;
            } else {
                $response = json_decode($response);
                $AllCustomerCompanies = $response->data->AllCustomerCompanies;
                $i = 0;
                foreach ($AllCustomerCompanies as $item) {
                    $max_id = Customer::where('name', $item->name)->where('email', $item->email)->first();
                    if (is_null($max_id)) {
                        $tax = new Customer;
                        $tax->erp_id = $item->id;
                        $tax->name = $item->name;
                        $tax->phone = $item->phone;
                        $tax->email = $item->email;
                        $tax->password = \bcrypt('123456');
                        $tax->loyalty_points = $item->award_points;
                        $tax->loyalty_reward = 0;
                        $tax->ordered_money_left = $item->deposit_amount;
                        $tax->ordered_money_left = 0;
                        $tax->companyName = $company;
                        $tax->save();
                        $customer_vendor = CustomerVendor::where('vendor_id', auth()->user()->vendor_id)->where('customer_id', $tax->id)->first();
                        if (is_null($customer_vendor)) {
                            $customer_vendor = new \App\CustomerVendor;
                            $customer_vendor->vendor_id = auth()->user()->vendor_id;
                            $customer_vendor->customer_id = $tax->id;
                            $customer_vendor->erp_id = $item->id;
                            $customer_vendor->save();
                        }
                        $i++;
                    } else {
                        $max_id->erp_id = $item->id;
                        $max_id->name = $item->name;
                        $max_id->phone = $item->phone;
                        $max_id->email = $item->email;
                        $max_id->password = \bcrypt('123456');
                        $max_id->loyalty_points = $item->award_points;
                        $max_id->loyalty_reward = 0;
                        $max_id->ordered_money_left = $item->deposit_amount;
                        $max_id->ordered_money_left = 0;
                        $max_id->companyName = $company;
                        $max_id->save();
                        CustomerVendor::updateOrInsert(array('vendor_id' => auth()->user()->vendor_id, 'customer_id' => $max_id->id),
                            array('vendor_id' => auth()->user()->vendor_id, 'customer_id' => $max_id->id, 'erp_id' => $item->id));
                    }
                }
                $msg = "ok";
            }
        } else {
            $msg = "الشركة غير موجودة";
        }
        return response()->json($msg);
    }
}
