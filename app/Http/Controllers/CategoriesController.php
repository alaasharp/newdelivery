<?php

namespace App\Http\Controllers;

use App\Settings;
use Validator;
use App\Category;
use Illuminate\Http\Request;
 use Gate;
 use App\Services\ProductsImportService;
 use Illuminate\Support\Facades\Input;
class CategoriesController extends BaseController
{
    protected $base = 'categories';
    protected $cls = 'App\Category';
    protected $images = ['image'];

    protected function getAdditionalData($data = null)
    {
        return [
            'categories' => Category::policyScope()->withDepth()->defaultOrder()->get()
        ];
    }

    public function getValidator(Request $request)
    {
        $rules = [
            'name' => 'required',
            'image' => 'nullable|mimes:jpeg,jpg,png,gif|image'
        ];
        if (Settings::getSettings()->multiple_cities)
            $rules['city_id'] = 'required';
        return Validator::make($request->all(), $rules);
    }

    protected function getIndexItems($data)
    {
        if ($data != null) {
            $categories = Category::policyScope();
            if (is_array($data) && isset($data['city_id']))
                $categories = $categories->where('city_id', $data['city_id']);
            if (is_array($data) && isset($data['restaurant_id']))
                $categories = $categories->where('restaurant_id', $data['restaurant_id']);
            return $categories->paginate(50);
        }
        else
            return Category::policyScope()->paginate(50);
    }

    public function up($id)
    {
        $category = Category::find($id);
        $category->beforeNode($category->getPrevSibling())->save();
        return redirect('/categories');
    }

    public function down(Request $request, $id)
    {
        $category = Category::find($id);
        $category->afterNode($category->getNextSibling())->save();
        return redirect('/categories');
    }
    public function bulk_upload()
    {
        if (!Gate::allows('create', $this->cls))
            return redirect('/');
        return view('categories.bulk_upload');
    }

    public function bulk(Request $request)
    {
        if (!Gate::allows('create', $this->cls))
            return redirect('/');
        $file_name = Input::file('fl');
        $result = [
            'created' => 0,
            'updated' => 0
        ];
        if ($file_name != null) {
            $service = new ProductsImportService();
            $result = $service->importCategories(
                $file_name->getPathName(),
                $request->input('city_id'),
                $request->input('restaurant_id')
            );
        }
        return redirect(route('categories.index'))->with('status', __('messages.products.imported', $result));;
    }
}
