<?php


namespace App\Http\Controllers\Gateway;


use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Support\Facades\Request;
use Psr\Log\LoggerInterface;

class PayCiController extends Controller
{
    private const LOGIN="donald.ebvoundi@agensic.com";
    private const PASSWORD="don84ald";
    private $logger;
    private $config;
    private $base_url;
    private $apikey;
    private $data;
    /**
     * @var mixed
     */
    private $tokencinet;

    public function __construct(LoggerInterface $logger)
    {
        $this->config = \App\CentralLogics\Helpers::get_business_settings('payci');
        $this->base_url = $this->config['url'];
        $this->apikey=$this->config['apikey'];
        $this->logger = $logger;
        $this->data=[];
    }
    public function maketransaction(WithdrawRequest $withdrawRequest){
        $endpoint = '/API/send/';
        $methods=$withdrawRequest->withdrawal_method_fields;
        $amount=$withdrawRequest->amount;
        $user=User::query()->find($withdrawRequest->user_id);
        $country=helpers::getCountyFile($user->dial_country_code);
        logger("----------------------------");

        $txnid="EDHPay-".$withdrawRequest->id;
        logger(route('payci_callback').'?txnid='.$txnid);
        $dataNeste = [
            'apikey' => $this->apikey,
            'full_name' => $user->f_name,
            'amount' => helpers::get_reverse_charge($amount),
            'beneficiary' => $methods['telephone'],
            'description' => "Collet wetransfertcash",
            'method' => "Mobile_money",
            'id_transaction' => $txnid,
            'callback_url' => route('payci_callback').'?txnid='.$txnid,
        ];

        if ($this->makeAuth()==true){
            $res = $this->cURL($endpoint, $dataNeste);
            $response = $res;
            $withdrawRequest->update([
               'admin_note'=>$txnid
            ]);
        }else{
            $response = [
                'comments'=>'eurreur auth'
            ];
        }

        return $response;
    }
    public function callback(Request $request){
        $putfp = fopen('php://input', 'r');
        $putdata = '';
        while($data = fread($putfp, 1024))
            $putdata .= $data;
        fclose($putfp);
        $result = json_decode($putdata);
        $id_transaction = $result->id_payin;
        $operator_id = $result->reference_id;
        $currency = $result->currency;
        $status = $result->status;
        $comment = $result->comments;
        $receiver_name = $result->full_name;
        $transaction_fee = $result->transaction_fee;
        $amount = $result->amount;
        $receiver_account = $result->beneficiary;
        $this->logger->error("notify call payci---post reponse" . $id_transaction);
        $transaction = $id_transaction;
        $this->logger->error("notify call" . $transaction);
    }
    public function makeCollect(WithdrawRequest $withdrawRequest,$notifyurl){
        $endpoint = '/API/redirection/index.php';
        $methods=$withdrawRequest->withdrawal_method_fields;
        $amount=$withdrawRequest->amount;
        $user=User::query()->find($withdrawRequest->user_id);
        $country=helpers::getCountyFile($user->dial_country_code);
        $dataNeste = [
            'apikey' => $this->apikey,
            'full_name' => $user->f_name,
            'amount' => $amount,
            'beneficiary' => $methods['nom_et_prenom'],
            'description' => "Collet wetransfertcash",
            'method' => "Mobile_money",
            'id_transaction' => $withdrawRequest->id,
            'callback_url' => $notifyurl,
        ];
        $data_ = json_encode($dataNeste);

        if ($this->makeAuth()==true){
            $res = $this->cURL($endpoint, $dataNeste);
            $response = $res;
        }else{
            $response = [
                'status'=>"FAILED",
                'comments'=>'eurreur auth'
            ];
        }
        return $response;
    }
    public function makestatus($data){
        $endpoint = 'API/status/';
        $dataNeste = [
            'apikey' => $this->apikey,
            'id_transaction' => $data['id_transaction'],
        ];
        $data = json_encode($dataNeste);

        $response = $this->cURL($endpoint, $dataNeste);

        return json_decode($response->getBody(),true);
    }
    public function makeAuth(){
        $endpoint = 'API/auth/';
        $dataNeste = [
            'apikey' => $this->apikey,
            'login' => self::LOGIN,
            'password' => self::PASSWORD,
        ];
        $data = json_encode($dataNeste);

        $response = $this->cURLAuth($endpoint, $dataNeste);
        logger("test".$response);
        if ($response=="Authentification réussie"){
            return true;
        }else{
            return false;
        }
    }
    public function getBalance(){
        $endpoint = 'API/getbalance/';
        $dataNeste = [
            'apikey' => $this->apikey,
        ];
        $data = json_encode($dataNeste);

        $response = $this->cURL($endpoint, $data);

        return json_decode($response->getBody(),true);
    }
    public function getBalanceAll(){
        $endpoint = 'API/getbalance/all/';
        $dataNeste = [
            'apikey' => $this->apikey,
        ];
        $data = json_encode($dataNeste);

        $response = $this->cURL($endpoint, $data);

        return json_decode($response->getBody(),true);
    }
    protected function cURL($url, $json)
    {
        // Create curl resource
        $ul=$this->base_url.'/'.$url;
        $ch = curl_init($ul);

        // Request headers
        $headers = array(
            'Content-Type:application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->tokencinet,
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
    protected function cURLAuth($url, $json)
    {
        logger($this->base_url.'/'.$url);
        // Create curl resource
        $ch = curl_init($this->base_url.'/'.$url);

        // Request headers
        $headers = array(
            'Content-Type:application/json',
            'Accept' => 'application/json',
        );
        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);
        logger($output);
        // Close curl resource to free up system resources
        curl_close($ch);
        return $output;
    }
}
