<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\Restaurant;
use App\Rrestaurantuser;
use App\CustomerVendor;
use App\Settings;
use App\Vendor;
use Gate;
use Illuminate\Support\Facades\Input;
use Validator;
use Illuminate\Http\Request;

// 
// Base controller for admin panels
// To be inherited by other controllers
// 
class BaseController extends Controller
{
    // base folder for views
    protected $base = '';
    // Resource model class name
    protected $cls = '';
    //check  if login user is vendor 
    protected $is_vendor = 0;
    // Field names where images are supposed to be uploaded
    protected $images = [];
    // default sorting
    protected $orderBy = 'created_at';
    protected $orderByDir = 'DESC';
    // Set these attributes as empty strings if they weren't set in request
    protected $setEmpty = [];
    // Handle these attributes as checkboxes (false if not set)
    protected $checkboxes = [];
    // Set many to many relation from these attributes, like
    // 'cities' => 'cities_ids'
    // this will call 'cities' relation 'sync' method
    // using 'cities_ids' request value argument
    protected $manyToMany = [];
    protected $opertion = "not_specified";

    /**
     * Returns relation for current search conditions
     * @param  $data Request 'filter' parameter
     * @return Relation
     */
    protected function getIndexItems($data)
    {
        return call_user_func([$this->cls, 'orderBy'], $this->orderBy, $this->orderByDir)->paginate(20);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!Gate::allows('create', $this->cls)) {
            return redirect('/');
        }
        $filter = $request->input('filter');
        $items = $this->getIndexItems($filter);
        $additional = $this->getAdditionalData($request->all());
        return view($this->base . '.index', array_merge(compact('items', 'filter'), $additional));
    }

    /**
     * Return additional parameters, which should be passed to resource form
     * @param  $data Request data
     * @return array
     */
    protected function getAdditionalData($data = null)
    {
        return [];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!Gate::allows('create', $this->cls)) {
            return redirect('/');
        }
        $item = new $this->cls;
        return view($this->base . '.form', array_merge(compact('item'), $this->getAdditionalData($request->all())));
    }

    /**
     * Modify request data which is used on create/update actions
     * could be used to do some preparations before saving data
     * @param  $data Request data
     * @return array
     */
    protected function modifyRequestData($data)
    {
        return $data;
    }

    /**
     * Create a new model or update the existing,
     * move uploaded images to right location and set relations
     * @param Eloquent $item model
     * @param Request $request request object
     * @return void
     */
    protected function save($item, Request $request)
    {
        $validator = $this->getValidator($request);
        if ($validator->passes()) {
            $data = $this->modifyRequestData($request->all());
            foreach ($this->setEmpty as $key) {
                if (!isset($data[$key])) {
                    $data[$key] = '';
                }
            }
            foreach ($this->checkboxes as $key) {
                if (!isset($data[$key])) {
                    $data[$key] = false;
                }
                if ($data[$key] == '1') {
                    $data[$key] = true;
                } else {
                    $data[$key] = false;
                }
            }
            $item->fill($data);
            foreach ($this->images as $image) {
                $file_name = Input::file($image);
                $img = false;
                if (@is_array(getimagesize($file_name))) {
                    $img = true;
                }
                if (!$img) {
                    continue;
                }
                if ($file_name != null) {
                    $new_file = str_random(10) . '.' . $file_name->getClientOriginalExtension();
                    $file_name->move(public_path('category_images'), $new_file);
                    $item->$image = '/category_images/' . $new_file;
                }
            }
            $item->save();
            if ($this->cls == 'App\Customer') {
                $data = [
                    "name" => $item->name,
                    "email" => $item->email,
                    "phone" => $item->phone,
                    "group_name" => "customer",
                    'created_by' => auth()->user()->id,
                ];
                if ($item->user_type == 1) {
                    $newid = $this->sendCustomerDriver(auth()->user()->companyName, $data);
                    if ($newid != 0) {
                        $item->fresh();
                        $item->erp_id = $newid;
                        $item->companyName = auth()->user()->companyName;
                        $item->save();
                    }
                    $customer_vendor = CustomerVendor::where('vendor_id', auth()->user()->vendor_id)->where('customer_id', $item->id)->get();
                    if (count($customer_vendor) <= 0) {
                        $customer_vendor = new \App\CustomerVendor;
                        $customer_vendor->vendor_id = auth()->user()->vendor_id;
                        $customer_vendor->customer_id = $item->id;
                        $customer_vendor->save();
                    }
                }
            }
            if ($this->cls == 'App\DeliveryBoy') {
                $data = [
                    "name" => $item->name,
                    "email" => $item->email,
                    "phone" => $item->phone,
                    "group_name" => "driver",
                    'created_by' => auth()->user()->id,
                ];
                if ($item->user_type == 1) {
                    $newid = $this->sendCustomerDriver(auth()->user()->companyName, $data);
                    if ($newid != 0) {
                        $item->fresh();
                        $item->erp_id = $newid;
                        $item->companyName = auth()->user()->companyName;
                        $item->save();
                    }
                }
            }
            if ($this->cls == 'App\User' && ($item->user_type == 1 or $item->user_type == 2)) {
                $item->fresh();
                $vendor = Vendor::where('user_id', $item->id)->first();
                $setings = Settings::where('user_id', $item->id)->first();
                if (!$setings) {
                    $setings = new  \App\Settings;
                    $setings->user_id = $item->id;
                    $setings->currency_format = "EGP:value";
                    $setings->save();
                }
                if ($vendor) {
                    //update vendor info
                    $vendor->name = $item->name;
                    $vendor->user_id = $item->id;
                    $vendor->save();
                    $item->vendor_id = $vendor->id;
                    $item->save();
                    // update resturent info based on vendoer  user id
                    $restaurant = Restaurant::where('user_id', $item->id)->first();
                    if ($restaurant != null) {
                        $restaurant->name = $item->name;
                        $restaurant->city_id = $item->city_id;
                        $restaurant->user_id = $item->id;
                        $restaurant->save();
                    }
                } else {
                    // add new vendors
                    $vendor = new \App\Vendor;
                    $vendor->name = $item->name;
                    $vendor->user_id = $item->id;
                    $vendor->created_by = auth()->user()->id;
                    $vendor->save();
                    $item->vendor_id = $vendor->id;
                    $item->save();
                    // add new  restoutrant
                    $restaurant = new  \App\Restaurant;
                    $restaurant->name = $item->name;
                    $restaurant->user_id = $item->id;
                    $restaurant->city_id = $item->city_id;
                    $restaurant->created_by = auth()->user()->id;
                    if ($restaurant->save()){
                        for ($i = 0;$i <= 6 ; $i++){
                            $appointment = new Appointment();
                            $appointment->restaurant_id = $restaurant->id;
                            $appointment->day = $i;
                            $appointment->save();
                        }
                    }
                    // add user rest. nXn relation
                    $prev_rel = Rrestaurantuser::where('restaurant_id', $restaurant->id)->
                    where('user_id', $item->id)->get();
                    if ($prev_rel != null) {
                        foreach ($prev_rel as $prev)
                            $prev->delete();
                    }
                    $rest_user = new  \App\Rrestaurantuser;
                    $rest_user->restaurant_id = $restaurant->id;
                    $rest_user->user_id = $item->id;
                    $rest_user->save();
                }
            }
            foreach ($this->manyToMany as $key => $value) {
                if (isset($data[$value])) {
                    $item->$key()->sync($data[$value]);
                }
            }
            return redirect($this->redirectOnCreatePath($request));
        } else {
            $item->fill($request->all());
            $errors = $validator->messages();
            if ($request->input('user_type')) {
                if (in_array($request->input('user_type'), [0, 3, 4, 5])) {
                    $t = 2;
                    return view($this->base . '.addUSer', array_merge(compact('item', 't', 'errors'), $this->getAdditionalData($request->all())));
                } else {
                    $t = 1;
                    return view($this->base . '.form', array_merge(compact('item', 't', 'errors'), $this->getAdditionalData($request->all())));
                }
            }
            return view($this->base . '.form', array_merge(compact('item', 'errors'), $this->getAdditionalData($request->all())));
        }
    }

    protected function redirectOnCreatePath(Request $request)
    {
        return route($this->base . '.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Gate::allows('create', $this->cls)) {
            return redirect('/');
        }
        return $this->save(new $this->cls, $request, $this->opertion);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!Gate::allows('create', $this->cls)) {
            return redirect('/');
        }
        $item = call_user_func([$this->cls, 'find'], $id);
        return view($this->base . '.show', array_merge(compact('item'), $this->getAdditionalData()));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request)
    {
        $item = call_user_func([$this->cls, 'find'], $id);
        if (!Gate::allows('update', $item)) {
            return redirect('/');
        }
        return view($this->base . '.form', array_merge(compact('item'), $this->getAdditionalData($request->all())));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $item = call_user_func([$this->cls, 'find'], $id);
        if (!Gate::allows('update', $item)) {
            return redirect('/');
        }
        return $this->save($item, $request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = call_user_func([$this->cls, 'find'], $id);
        if (!Gate::allows('delete', $item)) {
            return redirect('/');
        }
        $item->delete();
        return redirect(route($this->base . '.index'));
    }

    /**
     * Create validator to validate the request data,
     * could be customized based on request params or other stuff
     * @param Request $request Request object
     * @return Validator
     */
    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), []);
    }

    /// send customer  to  ERP
    function sendCustomerDriver($company = "laziz", $data)
    {
        $curl = curl_init();
        $ch = curl_init("https://smarterp.top/api/API/addcustomer?api_key=asnvhgk12smartlive20174hfgs587&company=" . $company);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // execute!
        $response = curl_exec($ch);
        $err = curl_error($ch);
        // close the connection, release resources used
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
