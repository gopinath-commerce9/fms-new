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

use Modules\Base\Http\Middleware\ValidUserDivertMiddleware;
use Modules\UserRole\Http\Middleware\AuthUserPermissionResolver;

Route::prefix('userauth')->group(function() {

    Route::middleware([ValidUserDivertMiddleware::class . ':auth-user'])->group(function() {
        Route::get('/', 'UserAuthController@index')->name('userauth.index');
        Route::get('/login', 'UserAuthController@login')->name('userauth.login');
        Route::post('/authenticate', 'UserAuthController@authenticate')->name('userauth.authenticate');
    });
    Route::get('/logout', 'UserAuthController@logout')->name('userauth.logout');

    Route::get('/users', 'UserCrudController@index')
        ->name('users.index')
        ->middleware([AuthUserPermissionResolver::class . ':users.view']);
    Route::get('/users/view/{userId}', 'UserCrudController@show')
        ->name('users.view')
        ->middleware([AuthUserPermissionResolver::class . ':users.view']);
    Route::get('/users/new', 'UserCrudController@create')
        ->name('users.new')
        ->middleware([AuthUserPermissionResolver::class . ':users.create']);
    Route::post('/users/store', 'UserCrudController@store')
        ->name('users.store')
        ->middleware([AuthUserPermissionResolver::class . ':users.create']);
    Route::get('/users/edit/{userId}', 'UserCrudController@edit')
        ->name('users.edit')
        ->middleware([AuthUserPermissionResolver::class . ':users.update']);
    Route::post('/users/update/{userId}', 'UserCrudController@update')
        ->name('users.update')
        ->middleware([AuthUserPermissionResolver::class . ':users.update']);
    Route::get('/users/delete/{userId}', 'UserCrudController@destroy')
        ->name('users.delete')
        ->middleware([AuthUserPermissionResolver::class . ':users.delete']);

});
