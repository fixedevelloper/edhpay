<?php


namespace App\Http\Controllers\Gateway;


use App\CentralLogics\helpers;
use App\Models\Currency;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use function App\CentralLogics\translate;
use function Ramsey\Uuid\Lazy\toUuidV6;

class PaydunyaController
{
    private $base_url;
    private $app_key;
    private $app_secret;
    private $principal_key;
    private $token;
    private $config;

    public function __construct()
    {
        $config = \App\CentralLogics\Helpers::get_business_settings('dunya');
        // You can import it from your Database
        $dunya_app_key = $config['public_key']; // dunya Merchant API APP KEY
        $dunya_app_secret = $config['secret_key']; // dunya Merchant API APP SECRET
        $dunya_principal_key = $config['apikey']; // dunya Merchant API USERNAME
        $dunya_token = $config['token']; // dunya Merchant API PASSWORD
        $dunya_base_url = (env('APP_MODE') == 'live') ? 'https://app.paydunya.com/api/v1/' : 'https://app.paydunya.com/sandbox-api/v1';

        $this->app_key = $dunya_app_key;
        $this->app_secret = $dunya_app_secret;
        $this->principal_key = $dunya_principal_key;
        $this->token = $dunya_token;
        $this->base_url = $dunya_base_url;
    }

    public function callback()
    {
        $status = request()->status;
    }

    public function createOrder()
    {
        $config = Helpers::get_business_settings('dunya');
        $value = session('amount');
        $txnid=toUuidV6();
        $str = "$value|||||||||||$txnid";
        $hash = hash('sha512', $str);
        $data = []; //items will be here

        $paydunya_items[] = [
            "name" => "merchant paid",
            "quantity" => 1,
            "unit_price" => $data['amount'],
            "total_price" => $data['amount'],
            "description" => ""
        ];
        $paydunya_args = [
            "invoice" => [
                "items" => $paydunya_items,
                "total_amount" => $data['amount'],
                "description" => "Paiement de " . $data['amount'] . " FCFA pour article(s) achetés sur " . get_bloginfo("name")
            ], "store" => [
                "name" => $config['dunya'],
                "website_url" => ""
            ], "actions" => [
                "cancel_url" => "",
                "callback_url" => "",
                "return_url" => ""
            ], "custom_data" => [
                "order_id" => 1,
                "trans_id" => $txnid,
                "hash" => $hash
            ]
        ];
        return $paydunya_args;
    }

    public function make_payment()
    {
        $currency_code = Currency::where(['currency_code' => 'EGP'])->first();
        if (isset($currency_code) == false) {
            Toastr::error(translate('paymob_supports_EGP_currency'));
            return back()->withErrors(['error' => 'Failed']);
        }
        try {
            $order = $this->createOrder();
            $response= $this->cURL($this->base_url."checkout-invoice/create",$order);
        }catch (\Exception $exception){
            Toastr::error(translate('country_permission_denied_or_misconfiguration'));
            return back()->withErrors(['error' => 'Failed']);
        }
        return $response;
    }

    protected function cURL($url, $json)
    {

        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = array(
            'Content-Type:application/json',
            "authorization: $this->token",
            "x-app-key: $this->app_key"
        );
        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);

        // Close curl resource to free up system resources
        curl_close($ch);
        return json_decode($output);
    }
}
