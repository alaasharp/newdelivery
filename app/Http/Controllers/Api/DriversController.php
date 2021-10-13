<?php

namespace App\Http\Controllers\Api;

use Auth;
use Validator;
use Illuminate\Validation\Rule;
use App\DeliveryBoy;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DriversController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->all();
        $result = Auth::guard('drivers')->attempt([
            'login' => $data['login'],
            'password' => $data['password']
        ]);
        $response = [
            'success' => $result
        ];
        if ($result) {
            $driver = Auth::guard('drivers')->user();
            $token = $driver->generateToken();
            $response['token'] = $token->token;
            $response['driver'] = $driver;
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
            if (!empty($data['password']))
                $data['password'] = bcrypt($data['password']);
            else
                unset($data['password']);
            unset($data['password_confirmation']);
            $user->fill($data);
            $user->save();
            $response['driver'] = $user->fresh();
        }
        else {
            $response['success'] = false;
            $response['errors'] = $validator->errors()->all();
        }
        return response()->json($response);
    }

    protected function getValidator($data, $id = null)
    {
        $rules = [
            'login' => 'unique:delivery_boys',
            'name' => 'required'
        ];
        if ($id != null) {
            $rules['login'] = [
                Rule::unique('delivery_boys')->ignore($id)
            ];
            $rules['password'] = 'confirmed';
        }
        else
            $rules['password'] = 'required';
        return Validator::make($data, $rules);
    }

    public function save_push_token(Request $request)
    {
        $token = $request->apiToken;
        $token->push_token = $request->input('push_token');
        $token->save();
        return response()->json([]);
    }

    public function statusOn(Request $request)
    {
        $user = $request->user;
        $deliveroboy=DeliveryBoy::where('id',$user->id);
        $deliveroboy->update(array('status' => 1));
        $response = [
            'success' => true
        ];
        return response()->json($response);
    }

    public function statusOff(Request $request)
    {
        $user = $request->user;
        $deliveroboy=DeliveryBoy::where('id',$user->id);
        $deliveroboy->update(array('status' => 0));
        $response = [
            'success' => true
        ];
        return response()->json($response);
    }
}
