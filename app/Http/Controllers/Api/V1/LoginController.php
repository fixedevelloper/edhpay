<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLogHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function customer_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dial_country_code' => 'required|string',
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, Helpers::error_processor($validator)), 400);
        }

        $phone = $request->dial_country_code . $request->phone;

        $user = User::customer()->where('phone', $phone)->first();


        //availability check
        if (!isset($user)) {
            return response()->json(response_formatter(AUTH_LOGIN_404, null, Helpers::error_processor($validator)), 404);
        }

        //status active check
        if (isset($user->is_active) && $user->is_active == false) {
            return response()->json(response_formatter(AUTH_BLOCK_LOGIN_403, null, Helpers::error_processor($validator)), 403);
        }

        //password check
        if (!Hash::check($request['password'], $user['password'])) {
            return response()->json(response_formatter(AUTH_LOGIN_401, null, Helpers::error_processor($validator)), 401);
        }

        //log user history
        $log_status = self::log_user_history($request, $user->id);
        if(!$log_status) {
            return response()->json(response_formatter(AUTH_LOGIN_400, null, Helpers::error_processor($validator)), 400);
        }

        //if everything is okay
        $user->update(['last_active_at' => now()]);
        $user->AauthAcessToken()->delete();
        $token = $user->createToken('CustomerAuthToken')->accessToken;
        return response()->json(response_formatter(AUTH_LOGIN_200, $token, null), 200);
    }

    public function agent_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dial_country_code' => 'required|string',
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, Helpers::error_processor($validator)), 400);
        }
        $phone = $request->dial_country_code . $request->phone;
       // dd($phone);

        $user = User::agent()->where('phone', $phone)->first();


        //availability check
        if (!isset($user)) {
            return response()->json(response_formatter(AUTH_LOGIN_404, null, Helpers::error_processor($validator)), 404);
        }

        //status active check
        if (isset($user->is_active) && $user->is_active == false) {
            return response()->json(response_formatter(AUTH_BLOCK_LOGIN_403, null, Helpers::error_processor($validator)), 403);
        }

        //password check
        if (!Hash::check($request['password'], $user['password'])) {
            return response()->json(response_formatter(AUTH_LOGIN_401, null, Helpers::error_processor($validator)), 401);
        }

        //log user history
        $log_status = self::log_user_history($request, $user->id);
        if(!$log_status) {
            return response()->json(response_formatter(AUTH_LOGIN_400, null, Helpers::error_processor($validator)), 400);
        }

        //if everything is okay
        $user->update(['last_active_at' => now()]);
        $user->AauthAcessToken()->delete();
        $token = $user->createToken('AgentAuthToken')->accessToken;
        return response()->json(response_formatter(AUTH_LOGIN_200, $token, null), 200);
    }

    public function log_user_history($request, $user_id)
    {
        $ip_address = $request->ip();
        $device_id = $request->header('device-id');
        $browser = $request->header('browser');
        $os = $request->header('os');
        $device_model = $request->header('device-model');

        if($device_id == '' || $os == '' || $device_model == '') {
            return false;
        }

        //History will be logged here
        DB::beginTransaction();
        try {
            UserLogHistory::where('user_id', $user_id)->update(['is_active' => 0]);

            UserLogHistory::create(
                [
                    'ip_address' => $ip_address,
                    'device_id' => $device_id,
                    'browser' => $browser,
                    'os' => $os,
                    'device_model' => $device_model,
                    'user_id' => $user_id,
                ]
            );
            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }

        return true;
    }
}
