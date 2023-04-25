<?php

namespace App\Http\Controllers\Merchant\Auth;

use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Gregwar\Captcha\CaptchaBuilder;
use Stevebauman\Location\Facades\Location;



class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:user', ['except' => ['logout']]);
    }

    public function captcha($tmp)
    {

        $phrase = new PhraseBuilder;
        $code = $phrase->build(4);
        $builder = new CaptchaBuilder($code, $phrase);
        $builder->setBackgroundColor(220, 210, 230);
        $builder->setMaxAngle(25);
        $builder->setMaxBehindLines(0);
        $builder->setMaxFrontLines(0);
        $builder->build($width = 100, $height = 40, $font = null);
        $phrase = $builder->getPhrase();

        if(Session::has('default_captcha_code')) {
            Session::forget('default_captcha_code');
        }
        Session::put('default_captcha_code', $phrase);
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type:image/jpeg");
        $builder->output();
    }

    public function login(Request $request)
    {
        $ip = env('APP_MODE') == 'live' ? $request->ip() : '61.247.180.82';
        $current_user_info = Location::get($ip);
        return view('merchant-views.auth.login', compact('current_user_info'));
    }

    public function submit(Request $request)
    {
        $request->validate([
            'phone' => 'required|min:8|max:20',
            'password' => 'required|min:8',
            'country_code' => 'required',
        ]);

        //recaptcha validation
        $recaptcha = Helpers::get_business_settings('recaptcha');
        if (isset($recaptcha) && $recaptcha['status'] == 1) {
            $request->validate([
                'g-recaptcha-response' => [
                    function ($attribute, $value, $fail) {
                        $secret_key = Helpers::get_business_settings('recaptcha')['secret_key'];
                        $response = $value;
                        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response;
                        $response = \file_get_contents($url);
                        $response = json_decode($response);
                        if (!$response->success) {
                            $fail(translate($attribute . ' google reCaptcha failed'));
                        }
                    },
                ],
            ]);
        } else {
            if (strtolower($request->default_captcha_value) != strtolower(Session('default_captcha_code'))) {
                Session::forget('default_captcha_code');
                return back()->withErrors(translate('Captcha Failed'));
            }
        }

        $phone = $request->country_code . $request->phone;

        if (auth('user')->attempt(['phone' => $phone, 'password' => $request->password, 'type' => MERCHANT_TYPE], $request->remember)) {

            return redirect()->route('merchant.dashboard');
        }

        return redirect()->back()->withInput($request->only('email', 'remember'))
            ->withErrors(['Credentials does not match.']);
    }

    public function logout(Request $request)
    {
        auth()->guard('user')->logout();
        return redirect()->route('merchant.auth.login');
    }
}
