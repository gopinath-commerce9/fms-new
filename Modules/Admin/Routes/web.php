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

Route::prefix('admin')->middleware([
    BlockInvalidUserMiddleware::class . ':auth-user',
    AuthUserRolePathResolver::class . ':auth-user',
])->group(function() {
    Route::get('/', 'AdminController@index')
        ->name('admin.index');
    Route::get('/dashboard', 'AdminController@dashboard')
        ->name('admin.dashboard');
    Route::get('/delivery-details', 'AdminController@deliveryDetails')
        ->name('admin.deliveryDetails');
    Route::get('/order-view/{orderId}', 'AdminController@viewOrder')
        ->name('admin.viewOrder');
    Route::post('/get-vendor-status', 'AdminController@getVendorStatus')
        ->name('admin.getVendorStatus');
    Route::post('/fetch-channel-orders', 'AdminController@fetchChannelOrders')
        ->name('admin.fetchChannelOrders');
    Route::post('/fetch-channel-individual-orders', 'AdminController@fetchChannelIndividualOrders')
        ->name('admin.fetchChannelIndividualOrders');
    Route::post('/find-order', 'AdminController@searchOrderByIncrementId')
        ->name('admin.searchOrderByIncrementId');
    Route::post('/filter-orders', 'AdminController@filterOrders')
        ->name('admin.filterOrders');
    Route::get('/download-items-date-csv', 'AdminController@downloadItemsDateCsv')
        ->name('admin.downloadItemsDateCsv');
    Route::get('/download-items-schedule-csv', 'AdminController@downloadItemsScheduleCsv')
        ->name('admin.downloadItemsScheduleCsv');
    Route::post('/export-orderwise-items', 'AdminController@exportOrderWiseItems')
        ->name('admin.exportOrderWiseItems');
    Route::get('/order-resync/{orderId}', 'AdminController@orderResync')
        ->name('admin.orderResync');
});
