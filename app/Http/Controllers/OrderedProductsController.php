<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Order;
use App\Services\OrdersService;
use App\OrderedProduct;

class OrderedProductsController extends BaseController
{
    protected $base = 'ordered_products';
    protected $cls = 'App\OrderedProduct';

    protected function getIndexItems($data)
    {
        return call_user_func([$this->cls, 'orderBy'], $this->orderBy, $this->orderByDir)
            ->where('order_id', $data['order_id'])->paginate(20);
    }

    public function index(Request $request)
    {
        $filter = $request->input('filter');
        $items = $this->getIndexItems($filter);
        $order = Order::find($filter['order_id']);
        return view($this->base . '.index', compact('items', 'filter', 'order'));
    }

    protected function getAdditionalData($data = null)
    {
        return ['order' => Order::find($data['order_id'])];
    }

    protected function save($item, Request $request)
    {
        $all_products = $request->all();
        unset($all_products['product_id']);
        $validator = $this->getValidator($request);
        if ($validator->passes()) {
            $i = 1;
            $x = 1;
            foreach ($all_products['products'] as $data) {
                if ($data['count'] != null) {
                    $data['order_id'] = $all_products['order_id'];
                    $product = Product::find($data['product_id']);
                    $data['price'] = $product->price;
                    $data['product_data'] = json_encode($product->toArray());
                    $item = new  OrderedProduct;
                    $item->fill($data);
                    $item->save();
                    $x++;
                    $data = null;
                    $service = new OrdersService();
                    $service->setOrderTotals($item->order, $item->order->promo_code);
                }
                $i++;
            }
            return redirect(route($this->base . '.index', ['filter' => ['order_id' => $item->order_id]]));
        } else {
            $item->fill($request->all());
            $errors = $validator->messages();
            return view($this->base . '.form', array_merge(compact('item', 'errors'), $this->getAdditionalData($request->all())));
        }
    }

    public function destroy($id)
    {
        $item = call_user_func([$this->cls, 'find'], $id);
        $item->delete();
        return redirect(route($this->base . '.index', ['filter' => ['order_id' => $item->order_id]]));
    }
}
