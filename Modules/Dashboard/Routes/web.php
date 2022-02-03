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

Route::prefix('dashboard')->middleware([BlockInvalidUserMiddleware::class . ':auth-user'])->group(function() {
    Route::get('/', 'DashboardController@index')->name('dashboard.index');
});
