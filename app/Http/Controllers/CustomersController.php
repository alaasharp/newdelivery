<?php

namespace App\Http\Controllers;

use Gate;
use App\Customer;
use App\Settings;
use App\CustomerVendor;
use App\PushMessage;
use Validator;
use Illuminate\Http\Request;

class CustomersController extends BaseController
{
    protected $base = 'customers';
    protected $cls = 'App\Customer';

    protected function getIndexItems($data)
    {
        if ($data != null) {
            $customers = Customer::policyScope()->orderBy($this->orderBy, $this->orderByDir);
            if (is_array($data) && isset($data['q'])) {
                $customers = $customers->where(function ($query) use ($data) {
                    $q = '%' . $data['q'] . '%';
                    return $query->where('email', 'LIKE', $q)->orWhere('name', 'LIKE', $q)->orWhere('phone', 'LIKE', $q);
                });
            }
            if (is_array($data) && isset($data['city']))
                $customers = $customers->where('city_id', $data['city']);
            return $customers->paginate(20);
        } else
            return Customer::policyScope()->orderBy($this->orderBy, $this->orderByDir)->paginate(20);
    }

    protected function getAdditionalData($data = null)
    {
        return [];
    }

    public function getValidator(Request $request)
    {
        $rules = [
            'name' => 'required',
            'phone' => 'required'
        ];
        if (!Settings::getSettings()->multiple_cities)
            $rules['city_id'] = 'required';
        $data = $request->all();
        if (isset($data['password']) && !empty($data['password']))
            $rules['password'] = 'required|confirmed';
        return Validator::make($request->all(), $rules);
    }

    protected function modifyRequestData($data)
    {
        if (isset($data['password'])) {
            if (!empty($data['password']))
                $data['password'] = bcrypt($data['password']);
            else
                unset($data['password']);
            unset($data['password_confirmation']);
        }
        if ($data['password'] == null) {
            unset($data['password']);
            unset($data['password_confirmation']);
        }
        if (!isset($data['_method']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }

    public function destroy($id)
    {
        $item = call_user_func([$this->cls, 'find'], $id);
        if (!Gate::allows('delete', $item))
            return redirect('/');
        $item->delete();
        return redirect(route($this->base . '.index'));
    }

    public function deletePage(Customer $customer)
    {
        $this->guardCustomerNotExists($customer);
        return view($this->base . '.delete', compact('customer'));
    }

    public function delete(Customer $customer)
    {
        $this->guardCustomerNotExists($customer);
        if (auth()->user()->access_full)
            $customer->delete();
        else
            CustomerVendor::where('customer_id', $customer->id)->where('vendor_id', auth()->user()->vendor_id)->delete();
        return redirect(route($this->base . '.index'));
    }

    protected function guardCustomerNotExists(Customer $customer)
    {
        if (!$customer->exists) {
            abort(404);
        }
    }

    /// / get taxs 
    public function getErpCustomer()
    {
        if (!Gate::allows('create', $this->cls))
            return redirect('/');
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
            if ($err)
                $msg = "cURL Error #:" . $err;
            else {
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
                        $customer_vendor = \App\CustomerVendor::where('vendor_id', auth()->user()->vendor_id)->where('customer_id', $tax->id)->first();
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
