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

Route::prefix('picker')->middleware([
    BlockInvalidUserMiddleware::class . ':auth-user',
    AuthUserRolePathResolver::class . ':auth-user',
])->group(function() {
    Route::get('/', 'PickerController@index')
        ->name('picker.index');
    Route::get('/dashboard', 'PickerController@dashboard')
        ->name('picker.dashboard');
    Route::post('/find-order', 'PickerController@searchOrderByIncrementId')
        ->name('picker.searchOrderByIncrementId');
    Route::post('/filter-order', 'PickerController@searchOrderByFilters')
        ->name('picker.searchOrderByFilters');
    Route::get('/order-view/{orderId}', 'PickerController@viewOrder')
        ->name('picker.viewOrder');
    Route::post('/order-status-change/{orderId}', 'PickerController@orderStatusChange')
        ->name('picker.orderStatusChange');
});
