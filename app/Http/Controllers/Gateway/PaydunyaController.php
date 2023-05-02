<?php


namespace App\Http\Controllers\Gateway;


use App\CentralLogics\helpers;
use App\Exceptions\TransactionFailedException;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\EMoney;
use Brian2694\Toastr\Facades\Toastr;
use http\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use function App\CentralLogics\translate;

class PaydunyaController extends Controller
{
    private $base_url;
    private $app_key;
    private $app_secret;
    private $principal_key;
    private $token;
    private $config;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $config = \App\CentralLogics\Helpers::get_business_settings('paydunya');
        // You can import it from your Database
        $dunya_app_key = $config['public_key']; // dunya Merchant API APP KEY
        $dunya_app_secret = $config['secret_key']; // dunya Merchant API APP SECRET
        $dunya_principal_key = $config['apikey']; // dunya Merchant API USERNAME
        $dunya_token = $config['token']; // dunya Merchant API PASSWORD
        $dunya_base_url = (env('APP_MODE') == 'live') ? 'https://app.paydunya.com/api/v1/' : 'https://app.paydunya.com/sandbox-api/v1/';

        $this->app_key = $dunya_app_key;
        $this->app_secret = $dunya_app_secret;
        $this->principal_key = $dunya_principal_key;
        $this->token = $dunya_token;
        $this->base_url = $dunya_base_url;
        $this->logger = $logger;
    }

    public function callback(Request $request)
    {
        try {
           // if (isset($_POST['data'])){
                $this->logger->info(">>>>>++++ PAYDUNYA CALLBACK" . json_encode($_POST['data']));
                $status = $_POST['data']['status'];
                if ($status == 'completed') {
                    $transactionID = $_POST['data']['custom_data']['trans_id'];

                    //transaction
                    //add money charge
                    $add_money_charge = \App\CentralLogics\Helpers::get_business_settings('addmoney_charge_percent');
                    if(isset($add_money_charge) && $add_money_charge > 0) {
                        $add_money_charge = (session('amount') * $add_money_charge)/100;
                    } else {
                        $add_money_charge = 0;
                    }

                    //transaction
                    DB::beginTransaction();
                    $data = [];
                    $data['from_user_id'] = Helpers::get_admin_id(); //since admin
                    $data['to_user_id'] = $_POST['data']['custom_data']['to_user_id'];

                    try {
                        //customer transaction
                        $data['user_id'] = $data['to_user_id'];
                        $data['type'] = 'credit';
                        $data['transaction_type'] = ADD_MONEY;
                        $data['ref_trans_id'] = $transactionID;
                        $data['amount'] = $_POST['data']['invoice']['total_amount'];
                        $this->logger->info(json_encode($data));
                        $customer_transaction = Helpers::make_transaction($data);
                        if ($customer_transaction != null) {
                            //admin transaction
                            $data['user_id'] = $data['from_user_id'];
                            $data['type'] = 'debit';
                            $data['transaction_type'] = SEND_MONEY;
                            $data['ref_trans_id'] = $customer_transaction;
                            $data['amount'] = session('amount') + $add_money_charge;
                            if (strtolower($data['type']) == 'debit' && EMoney::where('user_id', $data['from_user_id'])->first()->current_balance < $data['amount']) {
                                DB::rollBack();
                                return \redirect()->route('payment-fail');
                            }
                            $admin_transaction = Helpers::make_transaction($data);
                            Helpers::send_transaction_notification($data['user_id'], $data['amount'], $data['transaction_type']);


                            //admin charge transaction
                            $data['user_id'] = $data['from_user_id'];
                            $data['type'] = 'credit';
                            $data['transaction_type'] = ADMIN_CHARGE;
                            $data['ref_trans_id'] = null;
                            $data['amount'] = $add_money_charge;
                            $data['charge'] = $add_money_charge;
                            if (strtolower($data['type']) == 'debit' && EMoney::where('user_id', $data['from_user_id'])->first()->current_balance < $data['amount']) {
                                DB::rollBack();
                                return \redirect()->route('payment-fail');
                            }
                            $admin_transaction_for_charge = Helpers::make_transaction($data);
                            Helpers::send_transaction_notification($data['user_id'], $data['amount'], $data['transaction_type']);

                        }

                        if ($admin_transaction == null || $admin_transaction_for_charge == null) {
                            //fund record for failed
                            try {
                                $data = [];
                                $data['user_id'] = session('user_id');
                                $data['amount'] = session('amount');
                                $data['payment_method'] = 'paydunya';
                                $data['status'] = 'failed';
                                Helpers::fund_add($data);

                            } catch (\Exception $exception) {
                                throw new TransactionFailedException('Fund record failed');
                            }
                            DB::rollBack();
                            return \redirect()->route('payment-fail');

                        } else {
                            //fund record for success
                            try {
                                $data = [];
                                $data['user_id'] = session('user_id');
                                $data['amount'] = session('amount');
                                $data['payment_method'] = 'paydunya';
                                $data['status'] = 'success';
                                Helpers::fund_add($data);

                            } catch (\Exception $exception) {
                                throw new TransactionFailedException('Fund record failed');
                            }
                            DB::commit();
                            return \redirect()->route('payment-success');
                        }


                    } catch (\Exception $exception) {
                        DB::rollBack();
                        Toastr::error('Something went wrong!');
                        return back();

                    }


                }
                elseif ($status ==  'cancelled'){
                    //Put desired action/code after transaction has been cancelled here
                    //fund record for failed
                    try {
                        $data = [];
                        $data['user_id'] = $_POST['data']['custom_data']['to_user_id'];
                        $data['amount'] = $_POST['data']['invoice']['total_amount'];
                        $data['payment_method'] = 'paydunya';
                        $data['status'] = 'cancel';
                        Helpers::fund_add($data);

                    } catch (\Exception $exception) {
                        Toastr::error('Something went wrong!');
                        return back();
                    }
                    return \redirect()->route('payment-fail');
                }
                else{
                    //fund record for failed
                    try {
                        $data = [];
                        $data['user_id'] = $_POST['data']['custom_data']['to_user_id'];
                        $data['amount'] = $_POST['data']['invoice']['total_amount'];
                        $data['payment_method'] = 'paydunya';
                        $data['status'] = 'failed';
                        Helpers::fund_add($data);

                    } catch (\Exception $exception) {
                        Toastr::error('Something went wrong!');
                        return back();
                    }
                    return \redirect()->route('payment-fail');

                }
         //   }
        } catch (Exception $e) {
            die();
        }
    }

    public function make_payment()
    {
        $this->logger->info(">>>>>++++ PAYDUNYA MAKE PAYEMENT");
        $currency_code = Currency::where(['currency_code' => 'EGP'])->first();
        if (isset($currency_code) == false) {
            Toastr::error(translate('paymob_supports_EGP_currency'));
            return back()->withErrors(['error' => 'Failed']);
        }
        try {
            $order = $this->createOrder();
            $response = $this->cURL($this->base_url . "checkout-invoice/create", $order);
            $this->logger->info(">>>>>++++ PAYDUNYA MAKE PAYEMENT" . json_encode($response));
            $response_decoded = $response;
            if ($response_decoded->response_code && $response_decoded->response_code == "00") {
                Session::put('paydunya_transaction', $order['custom_data']['trans_id']);
                return redirect($response_decoded->response_text);

            } else {
                return \redirect()->route('payment-fail');
            }
        } catch (\Exception $exception) {
            $this->logger->error(">>>>>++++ PAYDUNYA EXCEPTION" . $exception);
            Toastr::error(translate('country_permission_denied_or_misconfiguration'));
            return back()->withErrors(['error' => 'Failed']);
        }
        return $response;
    }

    public function createOrder()
    {
        $this->logger->info(">>>>>++++ PAYDUNYA CREATE ORDER");
        $config = Helpers::get_business_settings('paydunya');
        $value = session('amount');
        $txnid = Uuid::uuid4();
        $str = "$value|||||||||||$txnid";
        $hash = hash('sha512', $str);
        $data = []; //items will be here
        $data['amount'] = $value;
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
                "description" => "Paiement de " . $data['amount'] . " FCFA pour recharge de compte sur " . "EDHPay"
            ], "store" => [
                "name" => "EDHPay",
                "website_url" => "https://edhpay.agensic.com"
            ], "actions" => [
                "cancel_url" => route('payment-fail'),
                "callback_url" => route('paydunya_callback').'?txnid='.$txnid,
                "return_url" => route('payment-success')
            ], "custom_data" => [
                "order_id" => 1,
                "trans_id" => $txnid,
                "to_user_id"=>session('user_id'),
                "hash" => $hash
            ]
        ];
        $this->logger->info(">>>>>++++ PAYDUNYA ORDER" . json_encode($paydunya_args));
        return $paydunya_args;
    }

    protected function cURL($url, $json)
    {

        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = array(
            'Content-Type:application/json',
            "PAYDUNYA-MASTER-KEY: $this->principal_key",
            "PAYDUNYA-PRIVATE-KEY: $this->app_secret",
            "PAYDUNYA-TOKEN: $this->token"
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

    public function make_response($response)
    {

    }
}
