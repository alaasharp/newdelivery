<?php

namespace App\Http\Controllers;

use Validator;
use App\NewsItem;
use Illuminate\Http\Request;

class NewsItemsController extends BaseController
{
    protected $base = 'news_items';
    protected $cls = 'App\NewsItem';
    protected $images = ['image'];
    protected $setEmpty = ['announce'];

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'title' => 'required',
            'full_text' => 'required'
        ]);
    }

    protected function getIndexItems($data)
    {
        if (auth()->user()->user_type == 1)
            $data['user_id'] = auth()->user()->id;
        if ($data != null) {
            $news = NewsItem::policyScope();
            if (is_array($data) && isset($data['q']))
                $news = $news->where('title', 'LIKE', '%' . $data['q'] . '%');
            if (is_array($data) && isset($data['city_id']))
                $news = $news->where('city_id', $data['city_id']);
            if (is_array($data) && isset($data['user_id']))
                $news = $news->where('user_id', $data['user_id']);
            return $news->paginate(20);
        } else
            return NewsItem::policyScope()->paginate(20);
    }

    protected function modifyRequestData($data)
    {
        if (!isset($data['_method']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }
}
