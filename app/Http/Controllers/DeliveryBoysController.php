<?php

namespace App\Http\Controllers;

use Gate;
use Validator;
use App\DeliveryBoyMessage;
use Illuminate\Http\Request;
use App\DeliveryBoy;
use Illuminate\Validation\Rule;

class DeliveryBoysController extends BaseController
{
    protected $base = 'delivery_boys';
    protected $cls = 'App\DeliveryBoy';

    public function getValidator(Request $request, $id = null)
    {
        $rules = [
            'name' => 'required',
            'login' => 'required|unique:delivery_boys'
        ];
        $data = $request->all();
        if (isset($data['id']))
            $rules['login'] = [Rule::unique('delivery_boys')->ignore($data['id'])];
        if (isset($data['password']) && !empty($data['password']))
            $rules['password'] = 'required|confirmed';
        return Validator::make($request->all(), $rules);
    }

    protected function getIndexItems($data)
    {
        if (auth()->user()->user_type != 0)
            $data['vendor'] = auth()->user()->vendor_id;
        if ($data != null) {
            $item = DeliveryBoy::policyScope()->orderBy($this->orderBy, $this->orderByDir);
            if (is_array($data) && isset($data['vendor']))
                $item = $item->where('vendor_id', '=', $data['vendor']);
            return $item->paginate(20);
        } else
            return $item = DeliveryBoy::policyScope()->orderBy($this->orderBy, $this->orderByDir)->paginate(20);
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
        foreach (DeliveryBoyMessage::where('delivery_boy_id', $id)->get() as $message) {
            $message->delete();
        }
        $item->delete();
        return redirect(route($this->base . '.index'));
    }

    // get erp boys
    public function getErpDboys()
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
                CURLOPT_HTTPHEADER => array("cache-control: no-cache"),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err)
                $msg = "cURL Error #:" . $err;
            else {
                $response = json_decode($response);
                $drivers = $response->data->Driver;
                $max_id = DeliveryBoy::select('erp_id')->where('vendor_id', auth()->user()->vendor_id)->max('erp_id');
                $i = 0;
                if (is_null($drivers))
                    $msg = "لاتوجد بيانات";
                else
                    foreach ($drivers as $item) {
                        if ($item->id > $max_id) {
                            $driver = new DeliveryBoy;
                            $driver->erp_id = $item->id;
                            $driver->name = $item->name;
                            $driver->login = $item->email;
                            $driver->vendor_id = auth()->user()->vendor_id;
                            $driver->password = bcrypt('123456');
                            $driver->save();
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
}
