<?php

Route::group(['namespace' => 'Api', 'middleware' => ['cors']], function () {
    Route::get('/vendors', 'VendorsController@index')->name('vendors.index');
    Route::get('/categories', 'CategoriesController@index')->name('categories.index');
    Route::get('/restaurants', 'RestaurantsController@index')->name('restaurants.index');
    Route::get('/get_next_restaurant_time', 'RestaurantsController@get_next_restaurant_time');
    Route::get('/delivery_areas', 'DeliveryAreasController@index')->name('delivery_areas.index');
    Route::get('/products', 'ProductsController@index')->name('products.index');
    Route::get('/news', 'NewsItemsController@index')->name('news.index');
    Route::post('/order', 'OrdersController@create')->name('orders.create');
    Route::post('/promo_codes/validate', 'PromoCodesController@validate_code')->name('promo_codes.validate');
    Route::get('/settings', 'SettingsController@index')->name('settings.index');
    Route::get('/contact', 'SettingsController@contacts')->name('settings.contacts');
    Route::get('/about', 'SettingsController@aboutus')->name('settings.aboutus');
    Route::get('/privacy_policy', 'SettingsController@trems')->name('settings.trems');
///instructions
    Route::get('/instructions', 'SettingsController@instructions')->name('settings.instructions');

    Route::get('/cities', 'SettingsController@cities')->name('settings.cities');
    Route::get('/countries', 'SettingsController@countries')->name('settings.countries');
    Route::post('forgot_password', 'CustomersController@forgot_password');
    Route::post('forgot_password', 'CustomersController@forgot_password');

    Route::post('/customers', 'CustomersController@create')->name('customers.create');
    Route::post('/login', 'CustomersController@login')->name('customers.login');
    Route::group(['middleware' => ['app_user_auth']], function () {
        Route::get('/tracker', 'OrdersController@tracker');
        Route::post('/push_token_cust', 'CustomersController@push')->name('save_push_token');
        Route::get('/me', 'CustomersController@me')->name('customers.me');
        Route::put('/customers/1', 'CustomersController@update')->name('customers.update');
        Route::get('/orders', 'OrdersController@index')->name('orders.index');
        Route::get('/orders/{orderId}', 'OrdersController@getOrder')->name('orders.getOrder');
        Route::post('/customer/address/create', 'CustomersController@addAddress');
        Route::post('/customer/address/update', 'CustomersController@updateAddress');
        Route::post('/customer/address/delete', 'CustomersController@deleteAddress');
        Route::put('/orders/update', 'OrdersController@update')->name('orders.update');
        Route::get('/customer/cancel/order/{id}', 'OrdersController@caneclOrder')->name('orders.cancel');
        Route::get('/questions', 'RestaurantsController@getQuestion')->name('questions');
        Route::post('/order/rate', 'RestaurantsController@saveRate')->name('saveRate');
    });

    Route::post('/driver_login', 'DriversController@login')->name('drivers.login');
    Route::group(['middleware' => ['drivers_auth']], function () {
        Route::put('/drivers/1', 'DriversController@update')->name('drivers.update');
        Route::put('/statusOn', 'DriversController@statusOn')->name('drivers.statusOn');
        Route::put('/statusOff', 'DriversController@statusOff')->name('drivers.statusOff');
        Route::post('/push_token', 'DriversController@save_push_token')->name('drivers.set_push_token');
        Route::post('/order_status', 'OrdersController@setStatus')->name('drivers.set_order_status');
        Route::get('/driver_orders', 'OrdersController@indexDriver')->name('drivers.driver_index');
        Route::get('/driver_orders_archive', 'OrdersController@archiveDriver')->name('drivers.driver_archive');
        Route::get('/driver_orders_archive_report', 'OrdersController@archiveDriverSummury')->name('drivers.driver_archive_report');
        Route::get('/messages', 'MessagesController@index')->name('messages.index');
        Route::post('/read_message', 'MessagesController@read')->name('messages.read');
    });
});