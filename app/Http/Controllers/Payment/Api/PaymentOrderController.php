<?php

namespace App\Http\Controllers\Payment\Api;

use App\CentralLogics\helpers;
use App\CentralLogics\SMS_module;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\PaymentRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentOrderController extends Controller
{
    public function create_payment_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'public_key' => 'required',
            'secret_key' => 'required',
            'merchant_number' => 'required',
            'amount' => 'required',
        ], [
            'public_key.required' => translate('Public key is required'),
            'secret_key.required' => translate('Secret key is required'),
            'merchant_number.required' => translate('Merchant number is required'),
            'amount.required' => translate('Amount is required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $merchant = Merchant::where(['public_key' => $request->public_key, 'secret_key' => $request->secret_key, 'merchant_number' => $request->merchant_number])->first();

        if (!isset($merchant)){
            return response()->json(['message' => 'merchant not found', 'status'=> 'merchant_not_found'], 401);
        }

        $payment = new PaymentRecord();
        $payment->merchant_user_id = $merchant->user_id;
        $payment->amount = $request->amount;
        $payment->callback = $merchant->callback;
        $payment->is_paid = 0;
        $payment->expired_at = date('Y-m-d H:i:s', strtotime("+5 min"));
        $payment->save();

        $redirect_url = url('').'/payment-process?payment_id='. $payment->id;

        return response()->json(['redirect_url' => $redirect_url, 'status'=> 'payment_created'], 200);
    }

    public function payment_verification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'public_key' => 'required',
            'secret_key' => 'required',
            'merchant_number' => 'required',
            'transaction_id' => 'required',
        ], [
            'public_key.required' => translate('Public key is required'),
            'secret_key.required' => translate('Secret key is required'),
            'merchant_number.required' => translate('Merchant number is required'),
            'transaction_id.required' => translate('Transaction id is required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $payment_record = PaymentRecord::whereHas('merchant_user.merchant', function ($query) use($request){
            $query->where(['public_key' => $request->public_key, 'secret_key' => $request->secret_key, 'merchant_number' => $request->merchant_number]);
            })
            ->where(['transaction_id' => $request->transaction_id])
            ->first();

        if (isset($payment_record)){
            return response()->json(['payment_record' => $payment_record], 200);
        }
        return response()->json(['errors' => 'Payment record not found'], 403);
    }

}

