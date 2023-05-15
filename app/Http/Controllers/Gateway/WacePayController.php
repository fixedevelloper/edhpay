<?php


namespace App\Http\Controllers\Gateway;


use App\CentralLogics\helpers;
use App\Models\HelpTopic;
use App\Models\User;
use App\Models\WithdrawRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class WacePayController
{
    private $logger;
    private $config;
    private $base_url;
    private $username;
    private $password;
    private $data;
    /**
     * @var mixed
     */
    private $tokencinet;

    public function __construct(LoggerInterface $logger)
    {
        $this->config = \App\CentralLogics\Helpers::get_business_settings('wacepay');
        $this->base_url = $this->config['url'];
        $this->logger = $logger;
        $this->data=[];
    }
    public function authenticate()
    {
        $endpoint = '/api/v1/login';
        $arrayJson = [
            "email" => $this->config['username'],
            "password" => $this->config['password']
        ];
        $this->logger->info(json_encode($arrayJson));

        $response = $this->cURL($this->base_url . $endpoint, $arrayJson);
        if ($response->status === 2000) {
            return [
                "status" => false,
                "token" => $response->access_token,
            ];
        }
        return [
            "status" => false,
            "token" => null,
        ];
    }

    public function sendTransaction(WithdrawRequest $withdrawRequest)
    {
        $endpoint = '/api/v1/transaction/bank/create';
      //  $this->tokencinet=$this->authenticate()['token'];
        $methods=$withdrawRequest->withdrawal_method_fields;
        $amount=$withdrawRequest->amount;
        $user=User::query()->find($withdrawRequest->user_id);
        $this->logger->info("#####----WACE------------");
        $this->logger->info($methods);
        if (!is_null($this->tokencinet)){
            $customerReponse=$this->getCreateSender($user);
            if ($customerReponse["status"] !==2000){
                throw new NotAcceptableHttpException($customerReponse['message']);
            }
            $beneficiaryReponse=$this->createBeneficiary($methods,$customerReponse['sender']['Code']);
            if ($beneficiaryReponse["status"] !==2000){
                throw new NotAcceptableHttpException($beneficiaryReponse['message']);
            }
            $bank = [
                "payoutCountry" => $methods['code_iso'],
                "payoutCity" => "Douala",
                "receiveCurrency" => helpers::currency_code(),
                "amountToPaid" => $amount,
               /* "service" => $transaction->getTypetransaction(),
                "senderCode" => $transaction->getCustomer()->getSenderCode(),
                "beneficiaryCode" => $transaction->getBeneficiare()->getBeneficiaryCode(),
                "sendingCurrency" => $transaction->getCustomer()->getCountry()->getMonaire(),
                "bankAccount" => $transaction->getBeneficiare()->getBankaccountnumber(),
                "bankName" => $transaction->getBeneficiare()->getBankname(),
                "bankSwCode" => $transaction->getBeneficiare()->getBankswiftcode(),
                "bankBranch" => $transaction->getBeneficiare()->getBankbranchnumber(),
                "fromCountry" => $transaction->getCustomer()->getCountry()->getCodeString(),
                "originFund" => "salary",
                "reason" => $transaction->getRaisontransaction(),*/
                "relation" => "brother"
            ];
            $res = $this->cURL($endpoint, json_encode($bank));
            return [
                "status"=>200,
                "data"=>json_decode($res->getBody(), true)
            ];
        }else{
            return [
                "status"=>500,
                "data"=>['status'=>500]
            ];
        }

    }
    public function sendTransactionOM(WithdrawRequest $withdrawRequest,$operateur)
    {
        $endpoint = '/api/v1/transaction/wallet/create';
        $this->tokencinet=$this->authenticate()['token'];
        $methods=$withdrawRequest->withdrawal_method_fields;
        $amount=$withdrawRequest->amount;
        $user=User::query()->find($withdrawRequest->user_id);
        $this->logger->info("#####----WACE------------");
        $this->logger->info($methods);
        if (!is_null($this->tokencinet)){
            $customerReponse=$this->getCreateSender($user);
            if ($customerReponse["status"] !==2000){
                throw new NotAcceptableHttpException($customerReponse['message']);
            }
            $beneficiaryReponse=$this->createBeneficiary($methods,$customerReponse['sender']['Code']);
            if ($beneficiaryReponse["status"] !==2000){
                throw new NotAcceptableHttpException($beneficiaryReponse['message']);
            }
            $wallet = [
                "payoutCountry" => $methods['code_iso'],
                "payoutCity" => $methods['ville'],
                "receiveCurrency" => helpers::currency_code(),
                "amountToPaid" => $amount,
                "service" => $operateur,
                "senderCode" => $customerReponse['sender']['Code'],
                "beneficiaryCode" => $beneficiaryReponse['sender']['Code'],
                "sendingCurrency" => "XAF",
                "mobileReceiveNumber" => $methods['telephone'],
                "fromCountry" => $user->dial_country_code,
                "originFund" => "Salary",
                "reason" => "Family",
                "relation" => "Brother"
            ];
            $this->logger->info("------paybody".json_encode($wallet));

            $res = $this->cURL($endpoint, json_encode($wallet));
            return [
                "status"=>200,
                "data"=>$res
            ];
        }else{
            return [
                "status"=>500,
                "data"=>['status'=>500]
            ];
        }

    }
    public function getStatusTransaction($reference)
    {
        $endpoint = '/api/v1/transaction/status/'.$reference;
        $this->tokencinet=$this->authenticate()['token'];
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->tokencinet,
            ]
        ];
        if (!is_null($this->tokencinet)){
            $res = $this->client->get($endpoint, $options);
            return [
                'status'=>200,
                'data'=>json_decode($res->getBody(), true)
            ];
        }else{
            return [
                'status'=>500,
                'data'=>[]
            ];
        }
    }

    public function getStatusBalance()
    {
        $endpoint = '/api/v1/account/balance';
        $token=$this->authenticate()['token'];
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ]
        ];
        if (!is_null($token)){
            $res = $this->client->get($endpoint, $options);
            return [
                'status'=>500,
                'data'=>json_decode($res->getBody(), true)
            ];
        }else{
            return [
                'status'=>500,
                'data'=>[]
            ];
        }

    }
    public function getBankListCountry($code)
    {
        $endpoint = '/api/v1/transaction/bank/list/'.$code;
        $token=$this->authenticate()['token'];
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ]
        ];
        if (!is_null($token)){
            $res = $this->client->get($endpoint, $options);
            return [
                'status'=>500,
                'data'=>json_decode($res->getBody(), true)
            ];
        }else{
            return [
                'status'=>500,
                'data'=>[]
            ];
        }

    }

    public function getCreateSender($user)
    {
        $endpoint = '/api/v1/sender/create';
        $customer=$user;
        $sender = [
            "firstName" => $customer->f_name,
            "lastName" => $customer->l_name,
            "address" => $this->townRepository->findOneBy(['country'=>$customer->getCountry()])->getLibelle(),
            "phone" => $customer->phone,
            "country" => $customer->getCountry()->getCodeString(),
            "city" => $this->townRepository->findOneBy(['country'=>$customer->getCountry()])->getLibelle(),
            "gender" => "M",
            "civility" => "Maried",
            "idNumber" => $customer->identification_number,
            "idType" => "PP",
            "occupation" => "Develloper",
            "state" => "",
            "nationality" => $customer->getCountry()->getLibelle(),
            "comment" => "new sender",
            "zipcode" => "78952",
            "dateOfBirth" => "1990-03-03",
            "dateExpireId" => $customer->getExpireddatepiece()->format("Y-m-d"),
            "pep" => false,
            "updateIfExist" => true
        ];
        $this->logger->info("##############DataCustomer################");

        $res = $this->cURL($endpoint,json_encode($sender));
        $this->logger->info("##############ResponseCustomer################");
        $this->logger->info($res);
/*        if ($jsonResponse['status']==2000){
            $customer->setSenderCode($jsonResponse['sender']['Code']);
            $this->doctrine->flush();
        }*/

        return $res;
    }

    public function createBeneficiary($beneficiary_,$sendercode)
    {
        $endpoint = '/api/v1/beneficiary/create';
        $beneficiary = [
            "firstName" => $beneficiary_['nom_et_prenom'],
            "lastName" => $beneficiary_['nom_et_prenom'],
            "address" => "non defini",
            "phone" => $beneficiary_['telephone'],
            "country" => $beneficiary_['code_iso'],
            "city" => $beneficiary_['ville'],
            "mobile" => $beneficiary_['telephone'],
            "email" => $beneficiary_['email'],
            "idNumber" => "147852964",
            "idType" => "PP",
            "sender_code" => $sendercode,
            "updateIfExist" => true
        ];
        $this->logger->info("##############DataBeneficiary################");
        $this->logger->info(json_encode($beneficiary));
        $res = $this->cURL($endpoint, json_encode($beneficiary));
        $this->logger->info("##############ResponseBeneficiary################");
        $this->logger->info($res->getBody());

        return $res;
    }
    public function validateTransaction($reference)
    {
        $token=$this->authenticate()['token'];
        $endpoint = '/api/v1/transaction/confirm';
        $body=[
            "reference"=>$reference
        ];
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body' => json_encode($body),
        ];
        $res = $this->client->post($endpoint, $options);
        return json_decode($res->getBody(), true);
    }
    protected function cURL($url, $json)
    {

        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = array(
            'Content-Type:application/json',
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
