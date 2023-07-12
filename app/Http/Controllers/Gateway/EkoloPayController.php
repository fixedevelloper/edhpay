<?php


namespace App\Http\Controllers\Gateway;


use App\CentralLogics\helpers;
use App\Exceptions\TransactionFailedException;
use App\Models\EMoney;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use KingFlamez\Rave\Facades\Rave as Flutterwave;

class EkoloPayController
{
    private $config;
    /**
     * @var Client
     */
    private $client;
    /**
     * EkoloPayController constructor.
     */
    public function __construct()
    {
        $this->config = \App\CentralLogics\Helpers::get_business_settings('ekolopay');
        $this->client = new Client([
            'base_uri' => $this->config['url'],
        ]);
    }
    function make_payment()
    {
        $user_data = User::find(session('user_id'));
        $endpoint = "/api/v1/gateway/purchase-token?api_client=" . $this->config['apikey'];
        $myuuid = $this->guidv4();
        $product = [
            "label" => "Paiement session",
            "amount" => session('amount'),
            "details" => "",
        ];
        $customer = [
            "uuid" => $myuuid,
            "name" => $user_data['f_name']??'' . ' ' . $user_data['l_name']??'',
            "phone" => $user_data['phone']
        ];
        // $this->logger->info(json_encode($arrayJson));
        $options = [
            'headers' => [
                'Accept' => 'application/x-www-form-urlencoded',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                "customer" => json_encode($customer),
                "product" => json_encode($product),
                "amount" => session('amount'),
                "secret_key" => $this->config['secretkey']
            ],
        ];

        $res = $this->client->post($endpoint, $options);
        // $this->logger->info((string)$options);
        $valresp = json_decode($res->getBody(), true);

        $response = $valresp['response'];
       logger("-------------------------------------------");
        logger($response['API_RESPONSE_CODE']);
        if ($response['API_RESPONSE_CODE'] == 200) {
          /*  return [
                'code' => 200,
                'message' => $response['API_RESPONSE_DATA']['API_DATA']['purchaseToken']
            ];*/

          // $this->sentUserAgent($response['API_RESPONSE_DATA']['API_DATA']['purchaseToken']);
            $url = "https://payment.ekolosoutienscolaire.com/purchase-product/" . $response['API_RESPONSE_DATA']['API_DATA']['purchaseToken'];
           logger($url);
           return redirect($url);
        } else {
           /* return [
                'code' => 0,
                'message' => $response['API_RESPONSE_DATA']['clearMessage']
            ];*/
            return \redirect()->route('payment-fail');
        }

    }

    function sentUserAgent($purchasetoken)
    {
        $url = "https://payment.ekolosoutienscolaire.com/api/v1/gateway/purchase-product=" . $purchasetoken;
        // $res = $this->client->get($endpoint);
       // $url = "/purchase-product=" . $purchasetoken;
        $options = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>"Accept-language: en\r\n" .
                    "User-Agent:".$url // i.e. An iPad
            )
        );

       $context = stream_context_create($options);
        $file = file_get_contents($url, false, $context);
      /*   $res = $this->client->request('GET', $url, [
            'headers' => [
                'User-Agent' => $url,
            ]
        ]);*/
     //   return redirect($url);
    }
    public function callback()
    {
        $status = request()->status;
//        $order = Order::with(['details'])->where(['id' => session('order_id'), 'user_id'=>session('customer_id')])->first();
        //if payment is successful
        if ($status == 'SUCCESSFUL' || $status == 'SUCCESS') {
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
            $data['to_user_id'] = session('user_id');

            try {
                //customer transaction
                $data['user_id'] = $data['to_user_id'];
                $data['type'] = 'credit';
                $data['transaction_type'] = ADD_MONEY;
                $data['ref_trans_id'] = null;
                $data['amount'] = session('amount');

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
                        $data['payment_method'] = 'stripe';
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
                        $data['payment_method'] = 'stripe';
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
        elseif ($status ==  'FAILED' || $status ==  'REJECTED'){
            //Put desired action/code after transaction has been cancelled here
            //fund record for failed
            try {
                $data = [];
                $data['user_id'] = session('user_id');
                $data['amount'] = session('amount');
                $data['payment_method'] = 'ekolopay';
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
                $data['user_id'] = session('user_id');
                $data['amount'] = session('amount');
                $data['payment_method'] = 'ekolopay';
                $data['status'] = 'failed';
                Helpers::fund_add($data);

            } catch (\Exception $exception) {
                Toastr::error('Something went wrong!');
                return back();
            }
            return \redirect()->route('payment-fail');

        }
        // Get the transaction from your DB using the transaction reference (txref)
        // Check if you have previously given value for the transaction. If you have, redirect to your successpage else, continue
        // Confirm that the currency on your db transaction is equal to the returned currency
        // Confirm that the db transaction amount is equal to the returned amount
        // Update the db transaction record (including parameters that didn't exist before the transaction is completed. for audit purpose)
        // Give value for the transaction
        // Update the transaction to note that you have given value for the transaction
        // You can also redirect to your success page from here

    }
    function guidv4($data = null)
    {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
