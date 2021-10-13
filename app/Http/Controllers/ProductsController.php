<?php

namespace App\Http\Controllers;

use App\ProductImage;
use Illuminate\Support\Facades\Input;
use Validator;
use Gate;
use App\Category, App\TaxGroup;
use App\Product;
use Illuminate\Http\Request;
use App\Services\ProductsImportService;

class ProductsController extends BaseController
{
    protected $base = 'products';
    protected $cls = 'App\Product';
    protected $orderBy = 'sort';
    protected $orderByDir = 'ASC';

    protected function getIndexItems($data)
    {
        if (auth()->user()->user_type != 0)
            $data['vendor'] = auth()->user()->vendor_id;
        if ($data != null) {
            $products = Product::policyScope()->orderBy($this->orderBy, $this->orderByDir);
            if (is_array($data) && isset($data['q'])) {
                $products = $products->where(function ($query) use ($data) {
                    $q = '%' . $data['q'] . '%';
                    return $query->where('description', 'LIKE', $q)->orWhere('name', 'LIKE', $q);
                });
            }
            if (is_array($data) && isset($data['category']))
                $products = $products->where('category_id', '=', $data['category']);
            if (is_array($data) && isset($data['vendor']))
                $products = $products->where('vendor_id', '=', $data['vendor']);
            if (is_array($data) && isset($data['city'])) {
                $category_ids = Category::where('city_id', $data['city'])->pluck('id');
                $products = $products->whereIn('category_id', $category_ids);
            }
            if (is_array($data) && isset($data['restaurant'])) {
                $category_ids = Category::where('restaurant_id', $data['restaurant'])->pluck('id');
                $products = $products->whereIn('category_id', $category_ids);
            }
            if (is_array($data) && isset($data['tax_group']))
                $products = $products->where('tax_group_id', '=', $data['tax_group']);
            return $products->paginate(20);
        } else {
            return Product::policyScope()->orderBy($this->orderBy, $this->orderByDir)->paginate(20);
        }
    }

    protected function getAdditionalData($data = null)
    {
        return ['categories' => Category::policyScope()->get()];
    }

    public function getValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required',
            'price' => 'required|between:0,99999999|numeric',
            'sort' => 'integer',
            'category_id' => 'required'
        ]);
    }

    protected function modifyRequestData($data)
    {
        if (!isset($data['_method']))
            $data['created_by'] = auth()->user()->id;
        return $data;
    }

    protected function save($item, Request $request)
    {
        $validator = $this->getValidator($request);
        if ($validator->passes()) {
            $item->fill($request->all());
            $item->save();
            if (Input::file('image') != null) {
                foreach (Input::file('image') as $image) {
                    if ($image != null) {
                        $new_file = str_random(10) . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('product_images'), $new_file);
                        $pi = new ProductImage([
                            'image' => '/product_images/' . $new_file,
                            'product_id' => $item->id
                        ]);
                        $pi->save();
                    }
                }
            }
            return redirect(route($this->base . '.index'));
        } else {
            $errors = $validator->messages();
            return view($this->base . '.form', array_merge(compact('item', 'errors'), $this->getAdditionalData()));
        }
    }

    public function deleteImage(Request $request, $id)
    {
        $pi = ProductImage::find($id);
        if ($pi)
            $pi->delete();
        return response()->json([]);
    }

    public function autocomplete(Request $request)
    {
        $q = $request->input('query');
        $products = Product::policyScope();
        $city = $request->input('city_id');
        $restaurant_id = $request->input('restaurant_id');
        if (!empty($city)) {
            $category_ids = Category::where('city_id', $city)->pluck('id');
            $products = $products->whereIn('category_id', $category_ids);
        }
        if (!empty($restaurant_id)) {
            $category_ids = Category::where('restaurant_id', $restaurant_id)->pluck('id');
            $products = $products->whereIn('category_id', $category_ids);
        }
        $products = $products->where('name', 'like', '%' . $q . '%')->limit(20)->get();
        $result = [
            'query' => $q,
            'suggestions' => []
        ];
        foreach ($products as $product) {
            $result['suggestions'][] = [
                'data' => $product->id,
                'value' => $product->name
            ];
        }
        return response()->json($result);
    }

    public function bulk_upload()
    {
        if (!Gate::allows('create', $this->cls))
            return redirect('/');
        return view('products.bulk_upload');
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
            $result = $service->import(
                $file_name->getPathName(),
                $request->input('city_id'),
                $request->input('restaurant_id')
            );
        }
        return redirect(route('products.index'))->with('status', __('messages.products.imported', $result));;
    }

    public function getErpData(Request $request)
    {
        if (!Gate::allows('create', $this->cls))
            return redirect('/');
        $company = auth()->user()->companyName;
        $timestamp = Product::select('timestamp')->where('vendor_id', auth()->user()->vendor_id)->whereNotNull('timestamp')->max('timestamp');
        if ($timestamp == null) $timestamp = '2010-08-05 00:00:00';
        if ($company) {
            $url = "https://smarterp.top/api/API/allproducts?api_key=asnvhgk12smartlive20174hfgs587&company=" . $company . "&timestamp=" . str_replace(' ', '%20', $timestamp);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,//"https://smarterp.top/api/API/allproducts?api_key=asnvhgk12smartlive20174hfgs587&company=laziz&timestamp=2019-08-05%2000%3A00%3A00",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "postman-token: 4d2a9383-e37a-e442-b3cd-f4df20596a81"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err)
                $msg = "cURL Error #:" . $err;
            else {
                $response = json_decode($response);
                if ($response->status == 0)
                    $msg = "لايوجد بيانات";
                else {
                    $products = $response->data;
                    $i = 0;
                    foreach ($products as $item) {
                        $product = Product::where('erp_id', $item->id)->where('vendor_id', auth()->user()->vendor_id)->first();
                        if ($product != null) {
                            $product->erp_id = $item->id;
                            $product->name = $item->name;
                            $product->erp_code = $item->code;
                            $product->erp_unit = $item->unit;
                            $product->price = $item->price;
                            $catid = (Category::where('company_name', $company)->where('erp_id', $item->category_id)->first());
                            $catid = $catid ? $catid->id : 0;
                            if ($catid != 0) {
                                $product->category_id = $catid;
                                $product->erp_category_id = $item->category_id;
                                $product->erp_subcategory_id = $item->subcategory_id;
                                $product->vendor_id = auth()->user()->vendor_id;
                                $product->tax_group_id = $item->tax_rate;
                                $product->erp_quantity = $item->quantity ? $item->quantity : 0;
                                $product->erp_cost = $item->cost;
                                $product->erp_unit = $item->unit;
                                $product->erp_type = $item->type;
                                $product->timestamp = $item->timestamp;
                                $product->save();
                            }
                        } else {
                            $product = new Product;
                            $product->erp_id = $item->id;
                            $product->name = $item->name;
                            $product->erp_code = $item->code;
                            $product->erp_unit = $item->unit;
                            $product->price = $item->price;
                            $category_id = empty($item->subcategory_id) ? $item->category_id : $item->subcategory_id;
                            $catid = (Category::where('company_name', $company)->where('erp_id', $category_id)->first());
                            $catid = $catid ? $catid->id : 0;
                            if ($catid != 0) {
                                $product->category_id = $catid;
                                $product->erp_category_id = $item->category_id;
                                $product->erp_subcategory_id = $item->subcategory_id;
                                $product->vendor_id = auth()->user()->vendor_id;
                                $product->tax_group_id = $item->tax_rate;
                                $product->erp_quantity = $item->quantity ? $item->quantity : 0;
                                $product->erp_cost = $item->cost;
                                $product->erp_unit = $item->unit;
                                $product->erp_type = $item->type;
                                $product->timestamp = $item->timestamp;
                                $product->save();
                                $image_names = array();
                                $image_names[] = $item->image;
                                for ($x = 0; $x < count($image_names); $x++) {
                                    $pi = new ProductImage([
                                        'image' => $image_names[$x],
                                        'product_id' => $product->id
                                    ]);
                                    $pi->save();
                                }
                                $i++;
                            }
                        }
                    }
                    $msg = "ok";
                }
            }
        } else {
            $msg = "الشركة غير موجودة";
        }
        return response()->json($msg);
    }

    public function getErpCats(Request $request)
    {
        if (!Gate::allows('create', $this->cls))
            return redirect('/');
        $company = auth()->user()->companyName;
        if ($company) {
            $resturarnt_id = (\App\Restaurant::where('name', $company)->first())->id;
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
                $categorys = array_merge($response->data->category, $response->data->subcategory);
                $max_id = Category::select('erp_id')->where('company_name', $company)->max('erp_id');
                $i = 0;
                if (is_null($categorys))
                    $msg = "لاتوجد بيانات";
                else
                    foreach ($categorys as $item) {
                        if ($item->id > $max_id) {
                            $category = new Category;
                            $category->erp_id = $item->id;
                            $category->name = $item->name;
                            if ($item->parent_id != 0)
                                $category->parent_id = (Category::where('company_name', $company)->where('erp_id', $item->parent_id)->first())->id;
                            $category->restaurant_id = $resturarnt_id;
                            $category->company_name = $company;
                            $category->save();
                            $i++;
                        }
                    }
                $msg = "ok";
            }
        } else
            $msg = "الشركة غير موجودة";
        return response()->json($msg);
    }

    // get taxs
    public function getErpTaxs()
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
                $TaxRates = $response->data->TaxRates;
                $max_id = TaxGroup::select('erp_id')->where('companyName', $company)->max('erp_id');
                $i = 0;
                foreach ($TaxRates as $item) {
                    if ($item->id > $max_id) {
                        $tax = new TaxGroup;
                        $tax->erp_id = $item->id;
                        $tax->name = $item->name;
                        $tax->value = $item->rate;
                        $tax->companyName = $company;
                        $tax->save();
                        $i++;
                    }
                }
                $msg = "ok";
            }
        } else
            $msg = "الشركة غير موجودة";
        return response()->json($msg);
    }

    public function setStatus(Request $request)
    {
        $product = product::find($request->input('pid'));
        $success['success'] = false;
        if ($product != null) {
            $product->archive = $product->archive == 1 ? 0 : 1;
            $product->save();
            $success['success'] = true;
            $success['s_text'] = $product->archive;
        }
        return response()->json($success);
    }

    public function getImageByBarCode(Request $request)
    {
        $vendor_id = $request->input('vendor');
        ///withCount
        $products = product::has('productImages', '=', 0)->where('vendor_id', $vendor_id)->get();
        foreach ($products as $product) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "http://admin.smarterp.top/barcode?q=" . $product->barcode,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array("cache-control: no-cache"),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                $msg = "cURL Error #:" . $err;
            } else {
                $response = json_decode($response);
            }
            if ($response and count($response) > 0)
                foreach ($response as $image) {
                    if ($image->image) {
                        $image_name = explode('/', $image->image);
                        $image_name = $image_name[count($image_name) - 1];
                        $org_name = explode('.', $image_name);
                        if ($org_name[0] == $product->barcode) {
                            copy($image->image, public_path('product_images') . '/' . $image_name);
                            $pi = new ProductImage([
                                'image' => '/product_images/' . $image_name,
                                'product_id' => $product->id
                            ]);
                            $pi->save();
                        }
                    }
                }
        }
        return redirect(url('products?filter[vendor]=' . $vendor_id));
    }
}
