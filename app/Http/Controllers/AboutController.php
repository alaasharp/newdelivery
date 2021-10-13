<?php

namespace App\Http\Controllers;

use Gate;
use App\About;
use Illuminate\Http\Request;

class AboutController extends BaseController
{
    protected $base = 'aboutUs';
    protected $cls = 'App\About';

    public function index(Request $request)
    {
    	if (!Gate::allows('create', $this->cls) and  false)
            return redirect('/');
        $item = About::find(1);
        return view($this->base . '.form', array_merge(compact('item'), $this->getAdditionalData()));
    }

    public function about()
    {
        
    	if (!Gate::allows('create', $this->cls) and  false) {
            return redirect('/');
        }
        $item = About::find(1);
        return view($this->base . '.form', array_merge(compact('item'), $this->getAdditionalData()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    /*public function store(Request $request)
    {
        dd('dd');
        if (!Gate::allows('create', $this->cls )  and false) {
            return redirect('/');
        }
        return $this->save(new $this->cls, $request);
    }*/
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $item = call_user_func([$this->cls, 'find'], $id);
        if (!Gate::allows('update', $item) and false ) {
            return redirect('/');
        }
        return $this->save($item, $request);
    }

 

}
