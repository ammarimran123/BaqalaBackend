<?php
/**
 * File name: api.php
 * Last modified: 2020.04.30 at 08:21:08
 * Author: Crosshair Technology Lab - TriCloud Technologies
 * Copyright (c) 2020
 *
 */

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('driver')->group(function () {
    Route::post('login', 'API\Driver\UserAPIController@login');
    Route::post('register', 'API\Driver\UserAPIController@register');
    Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
    Route::get('user', 'API\Driver\UserAPIController@user');
    Route::get('logout', 'API\Driver\UserAPIController@logout');
    Route::get('settings', 'API\Driver\UserAPIController@settings');
});

Route::prefix('manager')->group(function () {
    Route::post('login', 'API\Manager\UserAPIController@login');
    Route::post('register', 'API\Manager\UserAPIController@register');
    Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
    Route::get('user', 'API\Manager\UserAPIController@user');
    Route::get('logout', 'API\Manager\UserAPIController@logout');
    Route::get('settings', 'API\Manager\UserAPIController@settings');
});


Route::post('login', 'API\UserAPIController@login');
Route::post('login1', 'API\UserAPIController@login1');
Route::post('getOrdersOfUser','API\UserAPIController@getOrdersOfUser');
Route::post('getAllAdminOrders','API\UserAPIController@getAllAdminOrders');
Route::post('getOrdersOfUserOne','API\UserAPIController@getOrdersOfUserOne');
Route::post('getMarketProducts','API\UserAPIController@getMarketProducts');
Route::post('getMarkets','API\UserAPIController@getMarkets');
Route::post('getMarketProductsOne','API\UserAPIController@getMarketProductsOne');
Route::post('setEditProduct','API\UserAPIController@setEditProduct');
Route::post('getCategories','API\UserAPIController@getCategories');
Route::post('preRegisterUser','API\UserAPIController@preRegisterUser');
Route::post('deleteProduct','API\UserAPIController@deleteProduct');

Route::post('getTotalVendorOrders','API\UserAPIController@getTotalVendorOrders');
Route::post('getTotalVendorEarning','API\UserAPIController@getTotalVendorEarning');
Route::post('getTotalVendorCash','API\UserAPIController@getTotalVendorCash');
Route::post('getTotalVendorBank','API\UserAPIController@getTotalVendorBank');
Route::post('getVendorActiveOrders','API\UserAPIController@getVendorActiveOrders');
Route::post('getAllOrdersAdmin','API\UserAPIController@getAllOrdersAdmin');

////test for getting products wrt time(search only products whose market is in open time)
Route::get('test','API\ProductAPIController@indexTest');
////test for getting markets wrt time
Route::get('marketsTime','API\MarketAPIController@marketsTime');
////only showing categories in which poducts belong
Route::get('categoriesAndProducts','API\CategoryAPIController@categoriesAndProducts');
Route::post('deleteAllProducts','API\UserAPIController@deleteAllProducts');

Route::post('ifDiscountedPrice','API\UserAPIController@ifDiscountedPrice');

Route::post('changeOrderStatuss', 'API\UserAPIController@changeOrderStatuss');

Route::resource('notifications', 'API\NotificationAPIController');

Route::post('changeOrderStatusTest', 'API\UserAPIController@changeOrderStatusTest');


Route::get('getOrderStatuses','API\UserAPIController@getOrderStatuses');
Route::post('register', 'API\UserAPIController@register');
Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
Route::get('user', 'API\UserAPIController@user');
Route::get('logout', 'API\UserAPIController@logout');
Route::get('settings', 'API\UserAPIController@settings');
Route::post('update_player_id', 'API\UserAPIController@updateOneSignalPlayerId');

Route::resource('fields', 'API\FieldAPIController');
Route::resource('categories', 'API\CategoryAPIController');
Route::resource('markets', 'API\MarketAPIController');

Route::resource('faq_categories', 'API\FaqCategoryAPIController');
Route::get('products/categories', 'API\ProductAPIController@categories');
Route::get('products/searchMarket', 'API\ProductAPIController@searchMarket');
Route::resource('products', 'API\ProductAPIController');
Route::resource('galleries', 'API\GalleryAPIController');
Route::resource('product_reviews', 'API\ProductReviewAPIController');
Route::get('specialOffers', 'API\ProductAPIController@discountedProducts');


Route::resource('faqs', 'API\FaqAPIController');
Route::resource('market_reviews', 'API\MarketReviewAPIController');
Route::resource('currencies', 'API\CurrencyAPIController');

Route::resource('option_groups', 'API\OptionGroupAPIController');

Route::resource('options', 'API\OptionAPIController');

Route::get('serviceFee', 'API\CartAPIController@sendServiceFee');
Route::get('sendSmsNotification', 'API\OrderAPIController@sendSMSNotification');
Route::get('getTotalMarkets', 'API\MarketAPIController@getTotalMarkets');
Route::get('getTotalProducts', 'API\ProductAPIController@getTotalProducts');
Route::get('getProductSearchTotal', 'API\ProductAPIController@getProductSearchTotal');
Route::get('getProductCategorySearchTotal', 'API\ProductAPIController@getProductCategorySearchTotal');

Route::middleware('auth:api')->group(function () {
    Route::group(['middleware' => ['role:driver']], function () {
        Route::prefix('driver')->group(function () {
            Route::resource('orders', 'API\OrderAPIController');
            Route::resource('notifications', 'API\NotificationAPIController');
            Route::post('users/{id}', 'API\UserAPIController@update');
            Route::resource('faq_categories', 'API\FaqCategoryAPIController');
            Route::resource('faqs', 'API\FaqAPIController');
        });
    });
    Route::group(['middleware' => ['role:manager']], function () {
        Route::prefix('manager')->group(function () {
            Route::post('users/{id}', 'API\UserAPIController@update');
            Route::get('users/drivers_of_market/{id}', 'API\Manager\UserAPIController@driversOfMarket');
            Route::get('dashboard/{id}', 'API\DashboardAPIController@manager');
            Route::resource('markets', 'API\Manager\MarketAPIController');
        });
    });
    Route::post('users/{id}', 'API\UserAPIController@update');

    Route::resource('order_statuses', 'API\OrderStatusAPIController');

    Route::get('payments/byMonth', 'API\PaymentAPIController@byMonth')->name('payments.byMonth');
    Route::resource('payments', 'API\PaymentAPIController');

    Route::get('favorites/exist', 'API\FavoriteAPIController@exist');
    Route::resource('favorites', 'API\FavoriteAPIController');

    Route::resource('orders', 'API\OrderAPIController');

    Route::resource('product_orders', 'API\ProductOrderAPIController');

    Route::resource('notifications', 'API\NotificationAPIController');

    Route::get('carts/count', 'API\CartAPIController@count')->name('carts.count');
    Route::resource('carts', 'API\CartAPIController');

    Route::resource('delivery_addresses', 'API\DeliveryAddressAPIController');

    Route::resource('drivers', 'API\DriverAPIController');

    Route::resource('earnings', 'API\EarningAPIController');

    Route::resource('driversPayouts', 'API\DriversPayoutAPIController');

    Route::resource('marketsPayouts', 'API\MarketsPayoutAPIController');
});