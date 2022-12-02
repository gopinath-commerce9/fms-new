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

Route::prefix('cashier')->middleware([
    BlockInvalidUserMiddleware::class . ':auth-user',
    AuthUserRolePathResolver::class . ':auth-user',
])->group(function() {
    Route::get('/', 'CashierController@index')
        ->name('cashier.index');
    Route::get('/dashboard', 'CashierController@dashboard')
        ->name('cashier.dashboard');
    Route::post('/find-order', 'CashierController@searchOrderByIncrementId')
        ->name('cashier.searchOrderByIncrementId');
    Route::post('/find-order-item', 'CashierController@searchOrderItemByBarcode')
        ->name('cashier.searchOrderItemByBarcode');
    Route::post('/prepare-order-status-change/{orderId}', 'CashierController@prepareOrderStatusChange')
        ->name('cashier.prepareOrderStatusChange');
    Route::post('/order-status-change/{orderId}', 'CashierController@orderStatusChange')
        ->name('cashier.orderStatusChange');
});
