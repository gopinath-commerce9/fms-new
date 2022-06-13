<?php

use Illuminate\Http\Request;

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

Route::prefix('V1')->group(function() {
    Route::post('/driver/login', 'ApiController@generateDriverToken')
        ->name('driverApi.generateDriverToken');
    Route::middleware(['auth:sanctum'])->group(function() {
        Route::get('/driver/get-all-orders', 'ApiController@getAllOrders')
            ->name('driverApi.getAllOrders');
        Route::get('/driver/get-recent-orders', 'ApiController@getRecentAssignedOrders')
            ->name('driverApi.getRecentAssignedOrders');
        Route::get('/driver/get-delivery-orders', 'ApiController@getDeliveryOrders')
            ->name('driverApi.getDeliveryOrders');
        Route::get('/driver/get-delivered-orders', 'ApiController@getDeliveredOrders')
            ->name('driverApi.getDeliveredOrders');
        Route::get('/driver/get-order-details', 'ApiController@getOrderDetails')
            ->name('driverApi.getOrderDetails');
        Route::post('/driver/set-order-for-delivery', 'ApiController@setOrderForDelivery')
            ->name('driverApi.setOrderForDelivery');
        Route::post('/driver/set-order-as-delivered', 'ApiController@setOrderAsDelivered')
            ->name('driverApi.setOrderAsDelivered');
        Route::post('/driver/set-order-as-canceled', 'ApiController@setOrderAsCanceled')
            ->name('driverApi.setOrderAsCanceled');
        Route::post('/driver/set-order-amount-collected', 'ApiController@setOrderAmountCollected')
            ->name('driverApi.setOrderAmountCollected');
        Route::post('/driver/set-order-as-assigned', 'ApiController@setOrderAsAssigned')
            ->name('driverApi.setOrderAsAssigned');
    });
});
