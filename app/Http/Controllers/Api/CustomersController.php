<?php

namespace App\Http\Controllers\Api;

use Auth;
use Validator;
use Illuminate\Validation\Rule;
use App\Customer;
use App\Address;
use App\Settings;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mail;

class CustomersController extends Controller
{
    public function me(Request $request)
    {
        return response()->json($request->user);
    }

    public function forgot_password(Request $request)
    {
        $input = $request->all();
        $response['success'] = false;
        $rules = array(
            'email' => "required|email",
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $response['success'] = false;
            $response['errors'] = $validator->errors()->all();
        } else {
            $Customer = Customer::where('email', $input['email'])->first();
            if (is_null($Customer)) {
                $response = ['success' => false];
                $response  ['errors'] = [trans('passwords.user')];
                return response()->json($response);
            } else {
                $new_password = str_random(10);
                $Customer->password = bcrypt($new_password);
                $Customer->save();
                $emaildata = $Customer->toArray();
                $emaildata['new_pass'] = $new_password;
                $response = ['success' => true];
                $response  ['messages'] = [trans('passwords.api_reset')];
                Mail::send('emails.new_pass', ['item' => $emaildata], function ($m) use ($Customer) {
                    $m->from(Settings::getSettings()->mail_from_mail, Settings::getSettings()->mail_from_name);
                    $m->to($Customer->email)->subject(trans('passwords.reset'));
                });
            }
        }
        return response()->json($response);
    }

    public function login(Request $request)
    {
        $data = $request->all();
        $result = Auth::guard('app_users')->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
            'block' => 0,
            'hold' => 0
        ]);
        $response = [
            'success' => $result
        ];
        if ($result) {
            $customer = Auth::guard('app_users')->user();
            $token = $customer->generateToken();
            $response['token'] = $token->token;
            $response['customer'] = $customer;
            $response['customer']['addresses'] = $customer->addresses;
        }
        return response()->json($response);
    }

    public function create(Request $request)
    {
        $data = $request->all();
        $response = [
            'success' => true
        ];
        $validator = $this->getValidator($data);
        if ($validator->passes()) {
            $data['password'] = bcrypt($data['password']);
            if (empty($data['phone'])) {
                $data['phone'] = '';
            }
            $customer = Customer::create($data);
            $response['customer'] = $customer;
            $token = $customer->generateToken();
            $response['token'] = $token->token;
        } else {
            $response['success'] = false;
            $response['errors'] = $validator->errors()->all();
        }
        return response()->json($response);
    }

    public function addAddress(Request $request)
    {
        $data = $request->all();
        $response = [
            'success' => true
        ];
        if ($data['customer_id']) {
            $customer = Customer::find($data['customer_id']);
            if ($customer != null) {
                if (((int)$data['is_default']) == 1) {
                    Address::where('is_default', 1)->where('customer_id', $customer->id)->update(['is_default' => 0]);
                }
                $customer->saveAddress($request);
                $addresses = Address::where('customer_id', $customer->id)->get();
                $response['addresses'] = $addresses;
            } else {
                $response['errors'] = "Customer not found ";
                $response['success'] = false;

            }

        } else {
            $response['success'] = false;
            $response['errors'] = 'يرجي ادخال رقم العميل';
//            $response['errors'] = $validator->errors()->all();
        }
        return response()->json($response);
    }

    public function updateAddress(Request $request)
    {
        $data = $request->all();
        $response = [
            'success' => true
        ];
        if ($data['customer_id']) {
            $customer = Customer::find($data['customer_id']);
            if ($customer != null) {
                if ($data['is_default'] == 1)
                    Address::where('is_default', 1)->where('customer_id', $customer->id)->update(['is_default' => 0]);
                $address = Address::find($data['id']);
                $address->address = $request->input('address');
                $address->lat = $request->input('lat');
                $address->lng = $request->input('lng');
                $address->street = $request->input('street');
                $address->building = $request->input('building');
                $address->apartment = $request->input('apartment');
                $address->phone = $request->input('phone');
                $address->special_place = $request->input('special_place');
                $address->is_default = $request->input('is_default');
                $address->mainAddress = !empty($request->input('mainAddress')) ? $request->input('mainAddress') : ' ';
                $address->save();
                $addresses = Address::where('customer_id', $customer->id)->get();
                $response['addresses'] = $addresses;
            } else {
                $response['errors'] = "Customer not found ";
                $response['success'] = false;

            }

        } else {
            $response['success'] = false;
            $response['errors'] = 'يرجي ادخال رقم العميل';
//            $response['errors'] = $validator->errors()->all();
        }
        return response()->json($response);
    }

    public function deleteAddress(Request $request)
    {
        $data = $request->all();
        $response = [
            'success' => true
        ];
        if ($data['customer_id']) {
            $customer = Customer::find($data['customer_id']);
            if ($customer != null) {
                $address = Address::find($data['id']);
                if ($address)
                    $address->delete();
                else
                    $response['success'] = false;
                $addresses = Address::where('customer_id', $customer->id)->get();
                $response['addresses'] = $addresses;
            } else {
                $response['success'] = false;
                $response['errors'] = "Customer not found ";
            }
        } else {
            $response['success'] = false;
            $response['errors'] = 'يرجي ادخال رقم العميل';
//            $response['errors'] = $validator->errors()->all();
        }
        return response()->json($response);
    }

    public function update(Request $request)
    {
        $user = $request->user;
        $data = $request->all();
        $validator = $this->getValidator($data, $user->id);
        $response = [
            'success' => true
        ];
        if ($validator->passes()) {
            if (!empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }
            unset($data['password_confirmation']);
            $user = Customer::find($user->id);
            $user->fill($data);
            $user->update();
            $user = $user->fresh();
            $response['customer'] = $user;
        } else {
            $response['success'] = false;
            $response['errors'] = $validator->errors()->all();
        }
        return response()->json($response);
    }

    protected function getValidator($data, $id = null)
    {
        $rules = [
            'phone' => 'unique:customers',
            'email' => 'unique:customers',
            'name' => 'required'
        ];
        if ($id != null) {
            $rules['email'] = [
                Rule::unique('users')->ignore($id)
            ];
            $rules['phone'] = [
                Rule::unique('customers')->ignore($id)
            ];
            $rules['password'] = 'confirmed';
        } else {
            $rules['password'] = 'required';
        }
        if (Settings::getSettings()->multiple_cities) {
            $rules['city_id'] = 'required';
        }
        return Validator::make($data, $rules);
    }

    public function push(Request $request)
    {
        $status = false;
        $apiToken = $request->apiToken;
        $token = $request->input('push_token');
        if ($apiToken != null && !empty($token)) {
            $apiToken->push_token = $token;
            $apiToken->save();
            $status = true;
        }
        return response()->json(['status' => $status]);
    }
}
