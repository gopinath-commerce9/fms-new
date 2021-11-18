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
    Route::post('/login', 'AuthController@generateToken')
        ->name('apiApi.login');
    Route::post('/supervisors/login', 'AuthController@generateSupervisorToken')
        ->name('apiApi.generateSupervisorToken');
    Route::post('/pickers/login', 'AuthController@generatePickerToken')
        ->name('apiApi.generatePickerToken');
    Route::post('/drivers/login', 'AuthController@generateDriverToken')
        ->name('apiApi.generateDriverToken');
    Route::middleware(['auth:sanctum'])->group(function() {
        Route::get('/me', 'AuthController@userDetails')
            ->name('apiApi.userDetails');
        Route::post('/logout', 'AuthController@logout')
            ->name('apiApi.logout');
    });
});
