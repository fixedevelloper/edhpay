<?php


namespace App\Http\Controllers\Gateway;


use App\CentralLogics\helpers;
use App\Exceptions\TransactionFailedException;
use App\Models\EMoney;
use App\Models\User;
use App\Models\WithdrawRequest;
use Brian2694\Toastr\Facades\Toastr;
use Cryptomus\Api\Client;
use Cryptomus\Api\Payment;
use Cryptomus\Api\Payout;
use Cryptomus\Api\RequestBuilderException;
use http\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use function Ramsey\Uuid\Lazy\toUuidV6;

class CryptomusController
{
    /**
     * @var Payment
     */
    public $payment;
    /**
     * @var Payout
     */
    public $payout;
    /**
     * @var string
     */
    public $merchant_uuid;
    CONST KEY="EWtciNg5PJEecINnOwnoiisZ0TC1UcNIjcuxWzPEYolWT81iD7Yg5UctHfgU8YVDvrukzBrOSrUkOt5597BUNDazjfdW1V5Zvx8PM996sYiZLbIU2fhqA2leD5RK1bRG";
    /**
     * CryptomusController constructor.
     */
    public function __construct()
    {
        $config = \App\CentralLogics\Helpers::get_business_settings('cryptomus');
        $this->merchant_uuid = "16ba5351-5cfe-44f0-96b4-4a823caab049";
        $this->payment = Client::payment($config['apikey'], $this->merchant_uuid);
        $payout = Client::payout(env("CRYPTOMUSKEY"), $this->merchant_uuid);
    }
    public function make_payment(){
        try {
            $value = session('amount');
            $txnid = \Ramsey\Uuid\Nonstandard\Uuid::uuid4();
            $payment = $this->payment->create([
                'amount' => $value,
                'currency' => "XAF",
                'order_id' => (string)2,
                'url_return' => route('payment-success'),
                'url_callback' => route('cryptomus_callback',['uuid'=>$this->merchant_uuid]),
                'is_payment_multiple' => true,
                'lifetime' => 7200,
                'subtract' => '1',
            ]);
            return redirect($payment['url']);
        } catch (\Exception $e) {
            logger($e->getMessage());
            return \redirect()->route('payment-fail');
        }
    }
    public function payment_out(WithdrawRequest $withdrawRequest){
        try {
            $methods=$withdrawRequest->withdrawal_method_fields;
            $amount=$withdrawRequest->amount;
            $user=User::query()->find($withdrawRequest->user_id);
            $txnid = rand(500,50000000);
            $payout = $this->payout->create([
                'amount' => $amount,
                'currency' => "XAF",
                'network' => 'TRON',
                'order_id' => $txnid,
                'address' => $methods['address'],
                'url_return' => route('payment-success'),
                'url_callback' => route('cryptomus_callback',['uuid'=>$this->merchant_uuid]),
                'is_payment_multiple' => true,
                'lifetime' => 7200,
                'subtract' => '1',
            ]);
            $withdrawRequest->update([
                'admin_note' =>$txnid
            ]);
           // return redirect($payment['url']);
        } catch (\Exception $e) {
            logger($e->getMessage());
           // return \redirect()->route('payment-fail');
        }
    }
    public function callback_payout(Request $request)
    {
        logger(">>>>>++++ CRYTOMUS CALLBACK" . json_encode($_POST['data']));

        $status = $_POST['status'];
        $data = ["order_id" => "12345"];

        try {
            $result = $this->payout->info($data);
            if ($result['status'] == 'paid'||$result['status'] == 'paid_over') {

            }
        } catch (RequestBuilderException $e) {
        }

    }
    public function callback(Request $request)
    {
        try {
            // if (isset($_POST['data'])){
            logger(">>>>>++++ CRYTOMUS CALLBACK" . json_encode($_POST['data']));

            $status = $_POST['status'];
            if ($status == 'paid'||$status == 'paid_over') {
                $transactionID = $_POST['data']['custom_data']['trans_id'];

                //transaction
                //add money charge
                $add_money_charge = \App\CentralLogics\Helpers::get_business_settings('addmoney_charge_percent');
                if(isset($add_money_charge) && $add_money_charge > 0) {
                    $add_money_charge = ($_POST['data']['invoice']['total_amount'] * $add_money_charge)/100;
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
                    $data['ref_trans_id'] = $transactionID;
                    $data['amount'] = session('amount');
                    logger(json_encode($data));
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
            elseif ($status ==  'cancel' || $status ==  'fail' || $status ==  'system_fail'){
                //Put desired action/code after transaction has been cancelled here
                //fund record for failed
                try {
                    $data = [];
                    $data['user_id'] = $_POST['data']['custom_data']['to_user_id'];
                    $data['amount'] = $_POST['data']['invoice']['total_amount'];
                    $data['payment_method'] = 'cryptomus';
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
}
