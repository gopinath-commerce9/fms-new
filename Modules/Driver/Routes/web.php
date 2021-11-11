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

Route::prefix('driver')->middleware([
    BlockInvalidUserMiddleware::class . ':auth-user',
    AuthUserRolePathResolver::class . ':auth-user',
])->group(function() {
    Route::get('/', 'DriverController@index')
        ->name('driver.index');
    Route::get('/dashboard', 'DriverController@dashboard')
        ->name('driver.dashboard');
    Route::post('/find-order', 'DriverController@searchOrderByIncrementId')
        ->name('driver.searchOrderByIncrementId');
    Route::post('/filter-order', 'DriverController@searchOrderByFilters')
        ->name('driver.searchOrderByFilters');
    Route::get('/order-view/{orderId}', 'DriverController@viewOrder')
        ->name('driver.viewOrder');
    Route::post('/order-status-change/{orderId}', 'DriverController@orderStatusChange')
        ->name('driver.orderStatusChange');
    Route::get('/print-shipping-label/{orderId}', 'DriverController@printShippingLabel')
        ->name('driver.printShippingLabel');
});
