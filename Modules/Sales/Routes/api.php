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
    Route::get('/sales/get-collection-verified-orders', 'ApiController@getCollectionVerifiedOrders')
        ->name('salesApi.getCollectionVerifiedOrders');
    Route::post('/sales/login', 'ApiController@generateAdminToken')
        ->name('salesApi.generateAdminToken');
    /*Route::middleware(['auth:sanctum'])->group(function() {
        Route::post('/sales/create-order', 'ApiController@createOrder')
            ->name('salesApi.createOrder');
    });*/
    Route::post('/sales/create-order', 'ApiController@createOrder')
        ->name('salesApi.createOrder');
});
