<?php

use App\Http\Controllers\Api\V1\Agent\AgentController;
use App\Http\Controllers\Api\V1\Customer\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Customer\WithdrawController;
use App\Http\Controllers\Api\V1\Agent\AgentWithdrawController;
use App\Http\Controllers\Api\V1\Agent\Auth\PasswordResetController as AgentPasswordResetController;
use App\Http\Controllers\Api\V1\Agent\TransactionController as AgentTransactionController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\Customer\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Customer\TransactionController;
use App\Http\Controllers\Api\V1\GeneralController;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OTPController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Payment\Api\PaymentOrderController;
use Illuminate\Support\Facades\Route;


Route::group(['namespace' => 'Api\V1'], function () {

    Route::group(['middleware' => ['deviceVerify']], function () {
        //Check user type
        Route::group(['middleware' => ['inactiveAuthCheck', 'trackLastActiveAt', 'auth:api']], function () {
            Route::post('check-customer', [GeneralController::class, 'check_customer']);
            Route::post('check-agent', [GeneralController::class, 'check_agent']);

        });

        //Customer [Route Group]
        Route::group(['prefix' => 'customer', 'namespace' => 'Auth'], function () {

            Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], function () {
                Route::post('register', [RegisterController::class, 'customer_registration']);
                Route::post('login', [LoginController::class, 'customer_login']);

                Route::post('check-phone', [CustomerAuthController::class, 'check_phone']);
                Route::post('verify-phone', [CustomerAuthController::class, 'verify_phone']);
                Route::post('resend-otp', [CustomerAuthController::class, 'resend_otp']);

                Route::post('forgot-password', [PasswordResetController::class, 'reset_password_request']);
                Route::post('verify-token', [PasswordResetController::class, 'verify_token']);
                Route::put('reset-password', [PasswordResetController::class, 'reset_password_submit']);
            });

            Route::group(['middleware' => ['inactiveAuthCheck', 'trackLastActiveAt', 'auth:api', 'customerAuth', 'checkDeviceId']], function () {
                Route::get('get-customer', [CustomerAuthController::class, 'get_customer']);
                Route::get('get-purpose', [CustomerAuthController::class, 'get_purpose']);
                Route::get('get-banner', [BannerController::class, 'get_customer_banner']);
                Route::get('linked-website', [CustomerAuthController::class, 'linked_website']);
                Route::get('get-notification', [NotificationController::class, 'get_customer_notification']);
                Route::get('get-requested-money', [CustomerAuthController::class, 'get_requested_money']);
                Route::get('get-own-requested-money', [CustomerAuthController::class, 'get_own_requested_money']);
                Route::delete('remove-account', [CustomerAuthController::class, 'remove_account']);
                Route::put('update-kyc-information', [CustomerAuthController::class, 'update_kyc_information']);

                Route::post('check-otp', [OTPController::class, 'check_otp']);
                Route::post('verify-otp', [OTPController::class, 'verify_otp']);

                Route::post('verify-pin', [CustomerAuthController::class, 'verify_pin']);
                Route::post('change-pin', [CustomerAuthController::class, 'change_pin']);

                Route::put('update-profile', [CustomerAuthController::class, 'update_profile']);
                Route::post('update-two-factor', [CustomerAuthController::class, 'update_two_factor']);
                Route::put('update-fcm-token', [CustomerAuthController::class, 'update_fcm_token']);
                Route::post('logout', [CustomerAuthController::class, 'logout']);

                //transactions
                Route::post('send-money', [TransactionController::class, 'send_money']);
                Route::post('cash-out', [TransactionController::class, 'cash_out']);
                Route::post('request-money', [TransactionController::class, 'request_money']);
                Route::post('request-money/{slug}', [TransactionController::class, 'request_money_status']);
                Route::post('add-money', [TransactionController::class, 'add_money']);
                Route::post('withdraw', [TransactionController::class, 'withdraw']);
                Route::get('transaction-history', [TransactionController::class, 'transaction_history']);

                Route::get('withdrawal-methods', [TransactionController::class, 'withdrawal_methods']);

                Route::get('withdrawal-requests', [WithdrawController::class, 'list']);
            });

        });

        //Agents [Route Group]
        Route::group(['prefix' => 'agent', 'namespace' => 'Auth'], function () {

            Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], function () {
                Route::post('login', [LoginController::class, 'agent_login']);

                Route::post('forgot-password', [AgentPasswordResetController::class, 'reset_password_request']);
                Route::post('verify-token', [AgentPasswordResetController::class, 'verify_token']);
                Route::put('reset-password', [AgentPasswordResetController::class, 'reset_password_submit']);
            });
            Route::group(['middleware' => ['inactiveAuthCheck', 'trackLastActiveAt', 'auth:api', 'agentAuth', 'checkDeviceId']], function () {
                Route::get('get-agent', [AgentController::class, 'get_agent']);
                Route::get('get-notification', [NotificationController::class, 'get_agent_notification']);
                Route::get('get-banner', [BannerController::class, 'get_agent_banner']);
                Route::get('linked-website', [AgentController::class, 'linked_website']);
                Route::get('get-requested-money', [AgentController::class, 'get_requested_money']);
                Route::put('update-kyc-information', [CustomerAuthController::class, 'update_kyc_information']);

                Route::post('check-otp', [OTPController::class, 'check_otp']);
                Route::post('verify-otp', [OTPController::class, 'verify_otp']);

                Route::post('verify-pin', [AgentController::class, 'verify_pin']);
                Route::post('change-pin', [AgentController::class, 'change_pin']);

                Route::put('update-profile', [AgentController::class, 'update_profile']);
                Route::post('update-two-factor', [AgentController::class, 'update_two_factor']);
                Route::put('update-fcm-token', [AgentController::class, 'update_fcm_token']);
                Route::post('logout', [AgentController::class, 'logout']);

                //transaction
                Route::post('send-money', [AgentTransactionController::class, 'cash_in']);
                Route::post('cash-out', [AgentTransactionController::class, 'cash_out']);
                Route::post('request-money', [AgentTransactionController::class, 'request_money']);
                Route::post('request-money/{slug}', [AgentTransactionController::class, 'request_money_status']);
                Route::post('add-money', [AgentTransactionController::class, 'add_money']);
                Route::post('withdraw', [AgentTransactionController::class, 'withdraw']);
                Route::get('transaction-history', [AgentTransactionController::class, 'transaction_history']);

                Route::get('withdrawal-methods', [AgentTransactionController::class, 'withdrawal_methods']);

                Route::get('withdrawal-requests', [AgentWithdrawController::class, 'list']);

            });

        });

        //Configuration
        Route::get('/config', [ConfigController::class, 'configuration']);
        //FAQ
        Route::get('/faq', [GeneralController::class, 'faq']);

        //Merchants [Route Group]
        Route::group(['prefix' => 'merchant', 'namespace' => 'Auth'], function () {

        });
    });

    Route::post('/create-payment-order', [PaymentOrderController::class, 'create_payment_order']);
    Route::post('/payment-success', [PaymentOrderController::class, 'payment_success']);
    Route::post('/payment-verification', [PaymentOrderController::class, 'payment_verification']);

});
