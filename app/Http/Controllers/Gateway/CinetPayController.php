<?php


namespace App\Http\Controllers\Gateway;


use App\Http\Controllers\Controller;
use App\Models\WithdrawRequest;

class CinetPayController extends Controller
{
    private $base_url="https://client.cinetpay.com/v1/";
    private $app_key="18014756426394e0de6b5280.54531191";
    private $app_secret="CWdhy9WniCX3WfN";
    private $secret_marchant="625340835646c7c5d2f85b5.65413497";
    private $site_id="625742";
    private $token;
    private $config;
    private $logger;
    protected function authentificate(){
        $data=[
          'apikey'=>$this->app_key,
          'password'=>$this->app_secret
        ];
      $resp=  $this->cURLAuth($this->base_url.'auth/login',$data);
        logger(json_encode($resp));
      if ($resp->code==0){
          $this->token=$resp->data->token;

      }
    }
    public function make_transfert(WithdrawRequest $withdrawRequest)
    {
        $this->authentificate();
        logger($this->token);
        $methods=$withdrawRequest->withdrawal_method_fields;
        $amount=$withdrawRequest->amount;
        $phone=substr($methods['telephone'],3);
        $data=[
            'prefix'=>str_split($methods['telephone'],3)[0],
            'phone'=>$phone,
            'amount'=>$amount,
            'name'=>$methods['nom_et_prenom'],
            'surname'=>$methods['nom_et_prenom'],
            'email'=>"edh@gmail.com",
        ];
        logger(json_encode($data));
        $resp=  $this->cURL($this->base_url.'transfer/contact',$data);
        logger(json_encode($resp));
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
    protected function cURL($url, $json)
    {

        // Create curl resource
        $ch = curl_init($url.'?token='.$this->token.'&transaction_id=023658');

        // Request headers
        $headers = array(
            'Accept' => 'application/x-www-form-urlencoded',
            'Content-Type' => 'application/x-www-form-urlencoded',
            "token"=> $this->token
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
}
