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
    Route::middleware(['auth:sanctum'])->group(function() {
        Route::get('/me', 'AuthController@userDetails')
            ->name('apiApi.userDetails');
        Route::post('/set-onesignal-player-id', 'AuthController@setOneSignalPlayerId')
            ->name('apiApi.setOneSignalPlayerId');
        Route::post('/set-firebase-token-id', 'AuthController@setFirebaseTokenId')
            ->name('apiApi.setFirebaseTokenId');
        Route::post('/set-user-location-coords', 'AuthController@setUserLocationCoordinates')
            ->name('apiApi.setUserLocationCoordinates');
        Route::post('/logout', 'AuthController@logout')
            ->name('apiApi.logout');
    });
});
