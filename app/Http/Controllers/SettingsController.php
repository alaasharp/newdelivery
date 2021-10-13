<?php

namespace App\Http\Controllers;

use Gate;
use App\Settings;
use Illuminate\Http\Request;

class SettingsController extends BaseController
{
    protected $base = 'settings';
    protected $cls = 'App\Settings';
    protected $checkboxes = ['tax_included', 'multiple_restaurants', 'multiple_cities', 'signup_required', 'send_push_on_status_change', 'hide_out_stock_qty'];

    public function index(Request $request)
    {
        if (!Gate::allows('create', $this->cls))
            return redirect('/');
        $item = Settings::getSettings(auth()->user()->id);
        return view($this->base . '.form', array_merge(compact('item'), $this->getAdditionalData()));
    }

    public function update(Request $request, $id)
    {
        $item = call_user_func([$this->cls, 'find'], $id);
        if (!Gate::allows('update', $item))
            return redirect('/');
        return $this->save1($item, $request);
    }

    public function save1($item, Request $request)
    {
        $validator = $this->getValidator($request);
        if ($validator->passes()) {
            $data = $this->modifyRequestData($request->all());
            if ($data['currency_postion'] == 0)
                $data['currency_format'] = $data['currency_format'] . ':value';
            else
                $data['currency_format'] = 'value:' . $data['currency_format'];
            if (auth()->user()->user_type == 0 or auth()->user()->access_full) {
                foreach ($this->setEmpty as $key) {
                    if (!isset($data[$key]))
                        $data[$key] = '';
                }
                foreach ($this->checkboxes as $key) {
                    if (!isset($data[$key]))
                        $data[$key] = false;
                    if ($data[$key] == '1')
                        $data[$key] = true;
                    else
                        $data[$key] = false;
                }
                $item->fill($data);
                $item->save();
            } else {
                $item = null;
                $item = Settings::where('user_id', $data['user_id'])->first();
                if (is_null($item))
                    $item = new Settings;
                foreach ($this->setEmpty as $key) {
                    if (!isset($data[$key]))
                        $data[$key] = '';
                }
                foreach ($this->checkboxes as $key) {
                    if (!isset($data[$key]))
                        $data[$key] = false;
                    if ($data[$key] == '1')
                        $data[$key] = true;
                    else
                        $data[$key] = false;
                }
                $item->fill($data);
                $item->save();
            }
            return redirect($this->redirectOnCreatePath($request));
        } else {
            $item->fill($request->all());
            $errors = $validator->messages();
            return view($this->base . '.form', array_merge(compact('item', 'errors'), $this->getAdditionalData($request->all())));
        }
    }
}
