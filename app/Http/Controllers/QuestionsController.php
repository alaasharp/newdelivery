<?php

namespace App\Http\Controllers;

use App\Question;
use App\Restaurant;
use Validator;
use Gate;
use Illuminate\Http\Request;

class QuestionsController extends BaseController
{
    protected $base = 'Questions';
    protected $cls = 'App\Question';

    public function index(Request $request)
    {
        if (!Gate::allows('create', $this->cls) and false)
            return redirect('/');
        $filter = $request->input('filter');
        $items = $this->getIndexItems($filter);
        $additional = $this->getAdditionalData($request->all());
        return view($this->base . '.index', array_merge(compact('items', 'filter'), $additional));
    }

    protected function getIndexItems($data)
    {
        $resturant_ids = Restaurant::policyScope()->pluck('id')->all();
        if ($data != null) {
            $areas = Question::whereIn('restaurant_id', $resturant_ids)->orderBy('id', 'desc');
            if (is_array($data) && isset($data['q']))
                $areas = $areas->where('text', 'LIKE', '%' . $data['q'] . '%');
            if (is_array($data) && isset($data['restaurant_id']))
                $areas = $areas->where('restaurant_id', $data['restaurant_id']);
            return $areas->paginate(20);
        } else
            return Question::whereIn('restaurant_id', $resturant_ids)->orderBy('id', 'desc')->paginate(20);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!Gate::allows('create', $this->cls) and false)
            return redirect('/');
        $item = new $this->cls;
        return view($this->base . '.form', array_merge(compact('item'), $this->getAdditionalData($request->all())));
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create', $this->cls) and false)
            return redirect('/');
        return $this->save(new $this->cls, $request, $this->opertion);
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
        if (!Gate::allows('update', $item) and false)
            return redirect('/');
        return view($this->base . '.form', array_merge(compact('item'), $this->getAdditionalData($request->all())));
    }

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'text' => 'required',
            'restaurant_id' => 'required',
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = call_user_func([$this->cls, 'find'], $id);
        if (!Gate::allows('update', $item) and false)
            return redirect('/');
        return $this->save($item, $request);
    }

    protected function modifyRequestData($data)
    {
        if (!isset($data['_method']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }
}
