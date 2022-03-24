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

use Modules\Base\Http\Middleware\BlockInvalidUserMiddleware;
use Modules\UserRole\Http\Middleware\AuthUserRolePathResolver;

Route::prefix('supervisor')->middleware([
    BlockInvalidUserMiddleware::class . ':auth-user',
    AuthUserRolePathResolver::class . ':auth-user',
])->group(function() {
    Route::get('/', 'SupervisorController@index')
        ->name('supervisor.index');
    Route::get('/dashboard', 'SupervisorController@dashboard')
        ->name('supervisor.dashboard');
    Route::post('/find-order', 'SupervisorController@searchOrderByIncrementId')
        ->name('supervisor.searchOrderByIncrementId');
    Route::post('/filter-order', 'SupervisorController@searchOrderByFilters')
        ->name('supervisor.searchOrderByFilters');
    Route::get('/order-view/{orderId}', 'SupervisorController@viewOrder')
        ->name('supervisor.viewOrder');
    Route::post('/order-status-change/{orderId}', 'SupervisorController@orderStatusChange')
        ->name('supervisor.orderStatusChange');
    Route::post('/prepare-order-status-change/{orderId}', 'SupervisorController@prepareOrderStatusChange')
        ->name('supervisor.prepareOrderStatusChange');
    Route::get('/print-order-items-list/{orderId}', 'SupervisorController@printOrderItemList')
        ->name('supervisor.printOrderItemList');
    Route::get('/print-shipping-label/{orderId}', 'SupervisorController@printShippingLabel')
        ->name('supervisor.printShippingLabel');
    Route::get('/order-resync/{orderId}', 'SupervisorController@orderResync')
        ->name('supervisor.orderResync');
});
