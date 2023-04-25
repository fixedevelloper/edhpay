<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use App\Models\EMoney;
use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function customer_registration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'image' => 'mimes:jpeg,jpg,png,gif|max:10000', // max 10000kb
            'gender' => 'required',
            'occupation' => 'required',
            'dial_country_code' => 'required',
            'phone' => 'required|unique:users|min:5|max:20',
            'email' => '',
            'password' => 'required|min:4|max:4'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $phone = $request->dial_country_code . $request->phone;
        $customer_phone = User::where(['phone' => $phone])->first();
        if (isset($customer_phone)){
            return response()->json(['errors' => [
                ['code' => 'phone', 'message' => 'This phone number is already taken.']
            ]], 403);
        }

        $verify = null;
        if(Helpers::get_business_settings('phone_verification') == 1) {
            if($request->has('otp')) {
                //$verify = PhoneVerification::where(['phone' => $request['phone'], 'otp' => $request['otp']])->first();
                $verify = PhoneVerification::where(['phone' => $phone, 'otp' => $request['otp']])->first();
                if (!isset($verify)) {
                    return response()->json(['errors' => [
                        ['code' => 'otp', 'message' => 'OTP is not found!']
                    ]], 404);

                }
            }else{
                return response()->json(['errors' => [
                    ['code' => 'otp', 'message' => 'OTP is required.']
                ]], 403);
            }
        }

        DB::transaction(function () use ($request, $verify, $phone) {
            if(isset($verify)) {
                $verify->delete();
            }

            $user = new User();
            $user->f_name = $request->f_name;
            $user->l_name = $request->l_name;
            $user->image = $request->has('image') ? Helpers::upload('customer/', 'png', $request->file('image')) : null;
            $user->gender = $request->gender;
            $user->occupation = $request->occupation;
            $user->dial_country_code = $request->dial_country_code;
            //$user->phone = Helpers::filter_phone($request->phone);
            $user->phone = $phone;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->type = CUSTOMER_TYPE;    //['Admin'=>0, 'Agent'=>1, 'Customer'=>2]
            $user->referral_id = $request->referral_id ?? null;
            $user->save();

            $user->find($user->id);
            $user->unique_id = $user->id . mt_rand(1111, 99999);
            $user->save();

            $emoney = new EMoney();
            $emoney->user_id = $user->id;
            $emoney->save();
        });

        if($request->has('referral_id')) {
            try {
                Helpers::add_refer_commission($request->referral_id);

            } catch (\Exception $e){}
        }

        return response()->json(['message' => 'Registration Successful'], 200);
    }
}
