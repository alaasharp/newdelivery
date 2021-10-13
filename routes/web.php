<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'Auth\LoginController@showLoginForm');

Route::get('/', 'Auth\LoginController@showLoginForm');

Auth::routes();

Route::group(['middleware' => ['auth']], function () {
    Route::get('/home', 'HomeController@index')->name('home');

    Route::get('/Erp_sync', 'GetDataFromERp@index')->name('Erpsync');

    Route::resource('contacts', 'ContactsController');

    Route::resource('aboutUs', 'AboutController');
    Route::resource('terms', 'TermsController');
    Route::resource('instructions', 'instructionsController');

    Route::resource('Questions', 'QuestionsController');

    Route::post('/category/{id}/up', 'CategoriesController@up')->name('category_up');
    Route::post('/category/{id}/down', 'CategoriesController@down')->name('category_down');
    Route::resource('categories', 'CategoriesController');
    Route::get('/products/autocomplete', 'ProductsController@autocomplete');
    Route::get('/products/bulk_upload', 'ProductsController@bulk_upload')->name('products.bulk_upload');
    Route::get('/products/importErp', 'GetDataFromERp@getErpData')->name('products.importErp');
    Route::get('/productsImage/getBarcode', 'ProductsController@getImageByBarCode')->name('products.getBybarcode');

    Route::get('/list/importErp', 'GetDataFromERp@getErpCats')->name('category.ErpCats');
    Route::get('/taxs/importErp', 'GetDataFromERp@getErpTaxs')->name('category.ErpTaxs');
    Route::get('/category/importexcel', 'CategoriesController@bulk_upload')->name('category.getbulk');
    Route::post('/category/bulk', 'CategoriesController@bulk')->name('category.bulk');
    Route::post('/products/bulk', 'ProductsController@bulk')->name('products.bulk');
    Route::resource('products', 'ProductsController');
    Route::post('/product_image/{id}/delete', 'ProductsController@deleteImage')->name('products.delete_image');
    Route::post('/product_status', 'ProductsController@setStatus')->name('product.updateStatus');

    Route::put('/orders/{id}/boy', 'OrdersController@setDeliveryBoy')->name('orders.update_boy');
    Route::resource('orders', 'OrdersController');
    Route::resource('news_items', 'NewsItemsController');
    Route::resource('settings', 'SettingsController');
    Route::resource('push_messages', 'PushMessagesController');
    Route::post('/orders_status', 'OrdersController@setStatus')->name('orders.updateStatus');

    Route::get('delivery_areas/archive/{delivery_area}', [
        'as' => 'delivery_areas.archive',
        'uses' => 'DeliveryAreasController@archivePage'
    ]);

    Route::post('delivery_areas/archive/{delivery_area}', 'DeliveryAreasController@archive');

    Route::get('delivery_areas/noarchive/{delivery_area}', 'DeliveryAreasController@noarchive')->name('delivery_areas.noarchive');

    Route::resource('delivery_areas', 'DeliveryAreasController');
    Route::resource('promo_codes', 'PromoCodesController');
    Route::resource('tax_groups', 'TaxGroupsController');
    Route::resource('cities', 'CitiesController');
    Route::resource('restaurants', 'RestaurantsController');
    Route::post('Appointments', 'RestaurantsController@saveAppointment');
    Route::get('Appointments/{id}', 'RestaurantsController@getAppointment');
    Route::DELETE('Appointments', 'RestaurantsController@saveAppointment');

    Route::resource('customers', 'CustomersController');
    Route::resource('ordered_products', 'OrderedProductsController');
    Route::resource('order_statuses', 'OrderStatusesController');
    Route::resource('users', 'UsersController');
    Route::resource('countries', 'CountryController');

    Route::get('/user/onesignal', 'UsersController@updateToken');
    
    Route::resource('vendors', 'VendorsController');
    Route::get('vendor/ordersendauto', 'VendorsController@changeAutoSend');

    Route::resource('delivery_boys', 'DeliveryBoysController');
    Route::get('/delivery_boy/importErp', 'DeliveryBoysController@getErpDboys')->name('driver.Erp');;
    Route::get('customer/getErp', 'CustomersController@getErpCustomer')->name('customer.Erp');
    Route::get('customer/sentToErp', 'CustomersController@sendCustomerDriver')->name('customer.sendErp');
    // sendCompanySalesReport
    Route::get('order/sentToErp', 'OrdersController@sendCompanySalesReport')->name('order.sendErp');

    Route::resource('delivery_boy_messages', 'DeliveryBoyMessagesController');

    Route::get('customers/delete/{customer}', [
        'as' => 'customers.delete',
        'uses' => 'CustomersController@deletePage'
    ]);

    Route::post('customers/delete/{customer}', 'CustomersController@delete');
});