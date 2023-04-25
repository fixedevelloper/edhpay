<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 180);

use App\CentralLogics\Helpers;
use App\Traits\ActivationClass;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\BusinessSetting;
use Mockery\Exception;

class UpdateController extends Controller
{
    use ActivationClass;

    public function update_software_index()
    {
        return view('update.update-software');
    }

    public function update_software(Request $request)
    {
        Helpers::setEnvironmentValue('SOFTWARE_ID', 'MzczNTQxNDc=');
        Helpers::setEnvironmentValue('BUYER_USERNAME', $request['username']);
        Helpers::setEnvironmentValue('PURCHASE_CODE', $request['purchase_key']);
        Helpers::setEnvironmentValue('APP_MODE', 'live');
        Helpers::setEnvironmentValue('SOFTWARE_VERSION', '3.0');
        Helpers::setEnvironmentValue('APP_NAME', '6cash' . time());

        $data = $this->actch();
        try {
            if (!$data->getData()->active) {
                return redirect(base64_decode('aHR0cHM6Ly82YW10ZWNoLmNvbS9zb2Z0d2FyZS1hY3RpdmF0aW9u'));
            }
        } catch (Exception $exception) {
            Toastr::error('verification failed! try again');
            return back();
        }

        Artisan::call('migrate', ['--force' => true]);
        $previousRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.php');
        $newRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.txt');
        copy($newRouteServiceProvier, $previousRouteServiceProvier);
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        if (!BusinessSetting::where(['key' => 'payment_otp_verification'])->first()) {
            BusinessSetting::insert([
                'key' => 'payment_otp_verification',
                'value' => 1
            ]);
        }
        if (!BusinessSetting::where(['key' => 'hotline_number'])->first()) {
            BusinessSetting::insert([
                'key' => 'hotline_number',
                'value' => '134679'
            ]);
        }
        if (!BusinessSetting::where(['key' => 'merchant_commission_percent'])->first()) {
            BusinessSetting::insert([
                'key' => 'merchant_commission_percent',
                'value' => 10
            ]);
        }
        if (!BusinessSetting::where(['key' => 'payment'])->first()) {
            BusinessSetting::insert([
                'key' => 'payment',
                'value' => '{"status":1,"message":"payment done successfully."}'
            ]);
        }

        return redirect('/admin/auth/login');
    }
}
