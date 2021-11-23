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

Route::prefix('sales')->group(function() {
    Route::get('/', 'SalesController@index')
        ->name('sales.index');
    Route::get('/orders', 'SalesController@ordersList')
        ->name('sales.ordersList');
    Route::post('/find-order', 'SalesController@searchOrderByIncrementId')
        ->name('sales.searchOrderByIncrementId');
    Route::post('/filter-order', 'SalesController@searchOrderByFilters')
        ->name('sales.searchOrderByFilters');
    Route::get('/pos', 'SalesController@posView')
        ->name('sales.pos');
    Route::post('/pos/add-cart', 'SalesController@posAddCart')
        ->name('sales.posAddCart');
    Route::post('/pos/create-order', 'SalesController@posCreateOrder')
        ->name('sales.posCreateOrder');
    Route::get('/stock-update', 'SalesController@stockUpdate')
        ->name('sales.stockUpdate');
    Route::post('/update-product-stock-qty', 'SalesController@updateProductStockQty')
        ->name('sales.updateProductStockQty');
    Route::get('/oos-report', 'SalesController@oosReport')
        ->name('sales.oosReport');
    Route::get('/order-items-report', 'SalesController@orderItemsReport')
        ->name('sales.orderItemsReport');
    Route::post('/filter-order-items', 'SalesController@filterOrderItemsReport')
        ->name('sales.filterOrderItemsReport');
});
