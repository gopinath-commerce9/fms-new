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

use Modules\UserRole\Http\Middleware\AuthUserPermissionResolver;

Route::prefix('userrole')->group(function() {

    Route::get('/', function () {
        return redirect()->route('roles.index');
    });

    Route::get('/roles', 'UserRoleController@index')
        ->name('roles.index')
        ->middleware([AuthUserPermissionResolver::class . ':user-roles.view']);
    Route::get('/roles/view/{roleId}', 'UserRoleController@show')
        ->name('roles.view')
        ->middleware([AuthUserPermissionResolver::class . ':user-roles.view']);
    Route::get('/roles/new', 'UserRoleController@create')
        ->name('roles.new')
        ->middleware([AuthUserPermissionResolver::class . ':user-roles.create']);
    Route::post('/roles/store', 'UserRoleController@store')
        ->name('roles.store')
        ->middleware([AuthUserPermissionResolver::class . ':user-roles.create']);
    Route::get('/roles/edit/{roleId}', 'UserRoleController@edit')
        ->name('roles.edit')
        ->middleware([AuthUserPermissionResolver::class . ':user-roles.update']);
    Route::post('/roles/update/{roleId}', 'UserRoleController@update')
        ->name('roles.update')
        ->middleware([AuthUserPermissionResolver::class . ':user-roles.update']);
    Route::get('/roles/delete/{roleId}', 'UserRoleController@destroy')
        ->name('roles.delete')
        ->middleware([AuthUserPermissionResolver::class . ':user-roles.delete']);

    Route::get('/permissions', 'PermissionController@index')
        ->name('permissions.index')
        ->middleware([AuthUserPermissionResolver::class . ':user-role-permissions.view']);
    Route::get('/permissions/view/{pId}', 'PermissionController@show')
        ->name('permissions.view')
        ->middleware([AuthUserPermissionResolver::class . ':user-role-permissions.view']);
    Route::get('/permissions/new', 'PermissionController@create')
        ->name('permissions.new')
        ->middleware([AuthUserPermissionResolver::class . ':user-role-permissions.create']);
    Route::post('/permissions/store', 'PermissionController@store')
        ->name('permissions.store')
        ->middleware([AuthUserPermissionResolver::class . ':user-role-permissions.create']);
    Route::get('/permissions/edit/{pId}', 'PermissionController@edit')
        ->name('permissions.edit')
        ->middleware([AuthUserPermissionResolver::class . ':user-role-permissions.update']);
    Route::post('/permissions/update/{pId}', 'PermissionController@update')
        ->name('permissions.update')
        ->middleware([AuthUserPermissionResolver::class . ':user-role-permissions.update']);
    Route::get('/permissions/delete/{pId}', 'PermissionController@destroy')
        ->name('permissions.delete')
        ->middleware([AuthUserPermissionResolver::class . ':user-role-permissions.delete']);

});
