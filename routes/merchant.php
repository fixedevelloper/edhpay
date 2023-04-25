<?php

use App\Http\Controllers\Merchant\Auth\LoginController;
use App\Http\Controllers\Merchant\DashboardController;
use App\Http\Controllers\Merchant\BusinessSettingsController;
use App\Http\Controllers\Merchant\TransactionController;
use App\Http\Controllers\Merchant\WithdrawController;

Route::group(['namespace' => 'Merchant', 'as' => 'merchant.'], function () {

    //authentication
    Route::group(['namespace' => 'Auth', 'prefix' => 'auth', 'as' => 'auth.'], function () {
        Route::get('/code/captcha/{tmp}', 'LoginController@captcha')->name('default-captcha');
        Route::get('login', [LoginController::class, 'login'])->name('login');
        Route::post('login', [LoginController::class, 'submit']);
        Route::get('logout', [LoginController::class, 'logout'])->name('logout');
    });

    Route::group(['middleware' => ['merchant']], function () {
        //dashboard
        Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('settings', [DashboardController::class, 'settings'])->name('settings');
        Route::post('settings', [DashboardController::class, 'settings_update']);
        Route::post('settings-password', [DashboardController::class, 'settings_password_update'])->name('settings-password');

        Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.'], function () {
            //business setup
            Route::get('shop-settings', [BusinessSettingsController::class, 'shop_index'])->name('shop-settings');
            Route::post('shop-settings-update', [BusinessSettingsController::class, 'shop_update'])->name('shop-settings-update');
            Route::get('integration-settings', [BusinessSettingsController::class, 'integration_index'])->name('integration-settings');
            Route::post('integration-settings-update', [BusinessSettingsController::class, 'integration_update'])->name('integration-settings-update');
        });

        Route::get('/transaction', [TransactionController::class, 'transaction'])->name('transaction');

        Route::group(['prefix' => 'withdraw', 'as' => 'withdraw.'], function () {
            Route::get('/list', [WithdrawController::class, 'list'])->name('list');
            Route::get('/request', [WithdrawController::class, 'withdraw_request'])->name('request');
            Route::post('/request-store', [WithdrawController::class, 'withdraw_request_store'])->name('request-store');
            Route::get('/method-data', [WithdrawController::class, 'withdraw_method'])->name('method-data');
            Route::get('download', [WithdrawController::class, 'download'])->name('download');
        });


    });

});
