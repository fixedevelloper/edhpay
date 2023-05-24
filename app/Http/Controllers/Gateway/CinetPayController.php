<?php


namespace App\Http\Controllers\Gateway;


use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\WithdrawRequest;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Ramsey\Uuid\Nonstandard\Uuid;
use function App\CentralLogics\translate;

class CinetPayController extends Controller
{
    private $base_url = "https://client.cinetpay.com/v1/";
    private $collect_url = "https://api-checkout.cinetpay.com/v2/";
    private $app_key = "18014756426394e0de6b5280.54531191";
    private $app_secret = "CWdhy9WniCX3WfN";
    private $secret_marchant = "625340835646c7c5d2f85b5.65413497";
    private $site_id = "625742";
    private $token;
    private $config;
    private $logger;

    public function make_payment()
    {
        logger(">>>>>++++ CINETPAY MAKE PAYEMENT");
        $currency_code = Currency::where(['currency_code' => 'EGP'])->first();
        if (isset($currency_code) == false) {
            Toastr::error(translate('paymob_supports_EGP_currency'));
            return back()->withErrors(['error' => 'Failed']);
        }
        try {
            $value = session('amount');
            $txnid = Uuid::uuid4();
            $str = "$value|||||||||||$txnid";
            $hash = hash('sha512', $str);
            $order = [

                "apikey" => $this->app_key,
                "site_id" => $this->site_id,
                "transaction_id" => $txnid,
                'lang' => 'fr',
                "amount" => 250,
                "currency" => "USD",
                "alternative_currency"=>"",
                "description" => "TRANSACTION DESCRIPTION",
                "return_url" => route('payment-success'),
                "notify_url" => route('cinetpay_callback') . '?txnid=' . $hash,
                "metadata" => "user001",
                "customer_id" => "001",
                "customer_name" => "John",
                "customer_surname" => "Doe",
                "customer_phone_number" => "",
                "customer_email" => "",
                "customer_address" => "",
                "customer_city" => "",
                "customer_country" => "",
                "customer_zip_code" => "",
                "channels" => "ALL",
            ];
            logger(json_encode($order));
            $response = $this->cURLCollet($this->collect_url . "payment", $order);
            logger(">>>>>++++ CINETPAY MAKE PAYEMENT" . json_encode($response));
            $response_decoded = $response;
            if ($response_decoded->code && $response_decoded->code == "201") {
                Session::put('cinetpay_transaction', $response_decoded->data->payment_token);
                return redirect($response_decoded->data->payment_url);

            } else {
                return \redirect()->route('payment-fail');
            }
        } catch (\Exception $exception) {
            logger(">>>>>++++ CINETPAY EXCEPTION" . $exception);
            Toastr::error(translate('country_permission_denied_or_misconfiguration'));
            return back()->withErrors(['error' => 'Failed']);
        }
        return $response;
    }

    protected function cURL($url, $json)
    {

        // Create curl resource
        $ch = curl_init($url . '&token=' . $this->token);

        // Request headers
        $headers = array(
            'Accept' => 'application/x-www-form-urlencoded',
            'Content-Type' => 'application/x-www-form-urlencoded',
            "token" => $this->token
        );
        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);

        // Close curl resource to free up system resources
        curl_close($ch);
        return json_decode($output);
    }

    public function make_transfert(WithdrawRequest $withdrawRequest)
    {
        $this->authentificate();
        logger($this->token);
        $methods = $withdrawRequest->withdrawal_method_fields;
        $amount = $withdrawRequest->amount;
        $phone = substr($methods['telephone'], 3);
        $data = [
            'prefix' => str_split($methods['telephone'], 3)[0],
            'phone' => $phone,
            'amount' => strval($amount),
            'notify_url' => route('cinetpay_callback') . '?txnid=' . $withdrawRequest->id,

        ];
        logger(json_encode($data));
        $contact = $this->createConctact([
            'prefix' => str_split($methods['telephone'], 3)[0],
            'phone' => $phone,
            'name' => $methods['nom_et_prenom'],
            'surname' => $methods['nom_et_prenom'],
            'email' => "edh@gmail.com",
        ]);
        logger(json_encode($contact));
        logger(">>>>>>>>>>>>>response transfert");
        $resp = $this->cURL($this->base_url . 'transfer/money/send/contact' . '?transaction_id=' . $withdrawRequest->id, ['data' => json_encode($data)]);
        logger(json_encode($resp));
    }

    protected function authentificate()
    {
        $data = [
            'apikey' => $this->app_key,
            'password' => $this->app_secret
        ];
        $resp = $this->cURLAuth($this->base_url . 'auth/login', $data);
        logger(json_encode($resp));
        if ($resp->code == 0) {
            $this->token = $resp->data->token;

        }
    }

    protected function cURLAuth($url, $json)
    {

        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = array(
            'Accept' => 'application/x-www-form-urlencoded',
            'Content-Type' => 'application/x-www-form-urlencoded',
        );
        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);

        // Close curl resource to free up system resources
        curl_close($ch);
        return json_decode($output);
    }
    protected function cURLCollet($url, $json)
    {

        // Create curl resource
      /*  $ch = curl_init($url);

        // Request headers
        $headers = array(
            'Content-Type' => 'application/json',
        );
        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);*/
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($json),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'textuseragent',
            CURLOPT_HTTPHEADER => array(
                "content-type:application/json"
            ),
        ));

        // $output contains the output string
        $output = curl_exec($curl);

        // Close curl resource to free up system resources
        curl_close($curl);
        return json_decode($output);
    }
    protected function createConctact($data)
    {
        $resp = $this->cURL($this->base_url . 'transfer/contact', ['data' => json_encode($data)]);
        return $resp;
    }

    public function callback(Request $request)
    {
        logger(">>>>>>>>>>>>>callback cinetpay");
        $putfp = fopen('php://input', 'r');
        $putdata = '';
        while ($data = fread($putfp, 1024))
            $putdata .= $data;
        fclose($putfp);
        $result = json_decode($putdata);
        logger($result);
    }
}
