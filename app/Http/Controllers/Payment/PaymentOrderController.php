<?php

namespace App\Http\Controllers\Payment;

use App\CentralLogics\helpers;
use App\CentralLogics\SMS_module;
use App\Http\Controllers\Controller;
use App\Models\EMoney;
use App\Models\PaymentRecord;
use App\Models\PhoneVerification;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Stevebauman\Location\Facades\Location;

class PaymentOrderController extends Controller
{
    public function payment_process(Request $request)
    {
        $ip = env('APP_MODE') == 'live' ? $request->ip() : '61.247.180.82';
        $current_user_info = Location::get($ip);

        $payment_id = $request->payment_id;
        $payment_record = PaymentRecord::where(['id' => $payment_id])->first();
        if (isset($payment_record) && $payment_record->expired_at > Carbon::now()) {
            $merchant_user = User::with('merchant')
                ->where(['id' => $payment_record->merchant_user_id])
                ->first();
            return view('payment.phone', compact('payment_id', 'merchant_user', 'current_user_info', 'payment_record'));
        }
        Toastr::warning(translate('Payment time expired'));
        return back();
    }

    public function send_otp(Request $request)
    {
        $request->validate([
            'dial_country_code' => 'required|string',
            'phone' => 'required|min:8|max:20',
        ], [
            'phone.required' => translate('Phone is required'),
            'dial_country_code.required' => translate('Country code is required'),
        ]);

        $phone = $request->dial_country_code . $request->phone;
        $payment_id = $request->payment_id;
        $otp_status = Helpers::get_business_settings('payment_otp_verification');

        if (isset($otp_status) && $otp_status == 1) {
            $otp = mt_rand(1000, 9999);
            if (env('APP_MODE') != LIVE) {
                $otp = '1234'; //hard coded
            }

            $user = User::where(['phone' => $phone, 'type' => CUSTOMER_TYPE])->first();

            if (isset($user)) {

                if ($user->is_kyc_verified != 1) {
                    Toastr::warning(translate('User is not verified, please complete your account verification'));
                    return back();
                }

                session()->put('user_phone', $user->phone);

                DB::table('phone_verifications')->updateOrInsert(['phone' => $phone], [
                    'otp' => $otp,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $response = SMS_module::send($request['phone'], $otp);

                Toastr::success(translate('OTP send !'));
                return redirect()->route('otp', compact('payment_id'));
            }
            Toastr::warning(translate('please enter a valid user phone number'));
            return back();
        }
        return redirect()->route('pin', compact('payment_id'));
    }

    public function otp_index(Request $request)
    {
        $payment_id = $request->payment_id;
        $payment_record = PaymentRecord::where(['id' => $payment_id])->first();
        $frontend_callback = $payment_record->callback;
        return view('payment.otp', compact('payment_id', 'frontend_callback'));
    }

    public function verify_otp(Request $request)
    {
        $request->validate([
            'otp' => 'required|min:4|max:4',
        ], [
            'otp.required' => translate('OTP is required'),
            'otp.min' => translate('OTP must be 4 digit'),
            'otp.max' => translate('OTP must be 4 digit'),
        ]);

        $payment_id = $request->payment_id;
        $verify = PhoneVerification::where(['phone' => session('user_phone'), 'otp' => $request['otp']])->first();

        if (isset($verify)) {
            $verify->delete();
            Toastr::success(translate('OTP verify success !'));
            return redirect()->route('pin', compact('payment_id'));
        }

        Toastr::warning(translate('OTP verify failed !'));
        return back();
    }

    public function resend_otp(Request $request)
    {
        $phone = session('user_phone');

        try {
            $otp = mt_rand(1000, 9999);
            if (env('APP_MODE') != LIVE) {
                $otp = '1234'; //hard coded
            }
            DB::table('phone_verifications')->updateOrInsert(['phone' => $phone], [
                'otp' => $otp,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $response = SMS_module::send($phone, $otp);

            return response()->json(['message' => 'OTP Send'], 200);

        } catch (Exception $e) {
            return response()->json(['message' => 'OTP Send failed'], 404);
        }
    }

    public function pin_index(Request $request)
    {
        $payment_id = $request->payment_id;
        $payment_record = PaymentRecord::where(['id' => $payment_id])->first();
        $frontend_callback = $payment_record->callback;
        return view('payment.pin', compact('payment_id', 'frontend_callback'));
    }

    public function verify_pin(Request $request)
    {
        $request->validate([
            'pin' => 'required|min:4|max:4',
        ], [
            'pin.required' => translate('Pin is required'),
            'pin.min' => translate('Pin must be 4 digit'),
            'pin.max' => translate('Pin must be 4 digit'),
        ]);

        $payment_id = $request->payment_id;
        $user = User::where(['phone' => session('user_phone'), 'type' => CUSTOMER_TYPE])->first();

        //dd($user);

        if (!isset($user)) {
            Toastr::warning(translate('user not found !'));
            return back();
        }

        if (!Hash::check($request->pin, $user->password)) {
            Toastr::warning(translate('pin mismatched !'));
            return back();
        }

        $payment_record = PaymentRecord::where(['id' => $payment_id, 'transaction_id' => null, 'is_paid' => 0])->first();

        if (isset($payment_record) && $payment_record->expired_at > Carbon::now()) {
            $amount = $payment_record->amount;
            $merchant_user = User::where('id', $payment_record->merchant_user_id)->first();
            $admin_user = User::where('type', 0)->first();
            $user_emoney = EMoney::where('user_id', $user->id)->first();
            $merchant_emoney = EMoney::where('user_id', $payment_record->merchant_user_id)->first();
            $admin_emoney = EMoney::where('user_id', $admin_user->id)->first();

            if ($user_emoney->current_balance < $payment_record->amount) {
                Toastr::warning(translate('You do not have enough balance. Please generate eMoney first.'));
                return back();
            }

            $transaction_id = payment_transaction($user, $merchant_user, $user_emoney, $merchant_emoney, $amount, $admin_user, $admin_emoney);
            session()->put('transaction_id', $transaction_id);

            if ($transaction_id != null) {
                $payment_record->user_id = $user->id;
                $payment_record->transaction_id = $transaction_id;
                $payment_record->is_paid = 1;
                $payment_record->save();

                Toastr::success(translate('Payment successful !'));
                return redirect()->route('success', ['payment_id' => $request['payment_id']]);
            }
        }
        Toastr::warning(translate('Payment failed !'));
        return back();
    }

    public function success_index(Request $request)
    {
        $payment_id = $request->payment_id;
        return view('payment.success', compact('payment_id'));
    }

    public function payment_success_callback(Request $request)
    {
        $transaction_id = session('transaction_id');
        $payment_record = PaymentRecord::where(['id' => $request->payment_id])->first();

        $callback = $payment_record['callback']; //db callback
        $url = $callback . '?transaction_id=' . $transaction_id;

        return redirect($url);
    }

    // public function back_to_callback()
    // {
    //     return redirect(session('callback_url'));
    // }
}

