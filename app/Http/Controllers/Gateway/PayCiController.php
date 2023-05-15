<?php


namespace App\Http\Controllers\Gateway;


use App\CentralLogics\helpers;
use App\Models\User;
use App\Models\WithdrawRequest;
use Psr\Log\LoggerInterface;

class PayCiController
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
    public function maketransaction($data){
        $endpoint = '/API/send/';
        $dataNeste = [
            'apikey' => $this->apikey,
            'id_transaction' => $data['id_transaction'],
            'beneficiary' => $data['beneficiary'],
            'amount' => $data['amount'],
            'full_name' => $data['name'],
            'method' => $data['method'],
            'callback_url' => $data['callback_url'],
        ];
        $data = json_encode($dataNeste);

        if ($this->makeAuth()==true){
            $res = $this->cURL($endpoint, $data);
            $response = $res;
        }else{
            $response = [
                'comments'=>'eurreur auth'
            ];
        }

        return $response;
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
