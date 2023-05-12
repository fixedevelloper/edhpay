<?php


namespace App\Http\Controllers\Gateway;


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
    /**
     * @var mixed
     */
    private $tokencinet;

    public function __construct(LoggerInterface $logger)
    {
        $this->config = \App\CentralLogics\Helpers::get_business_settings('wacepay');
        $this->base_url = (env('APP_MODE') == 'live') ? 'https://app.paydunya.com/api/v1/' : 'https://app.paydunya.com/sandbox-api/v1/';
        $this->logger = $logger;
    }
    public function authenticate()
    {
        $endpoint = '/api/v1/login';
        $arrayJson = [
            "email" => $this->config['USERNAME'],
            "password" => $this->config['PASSWORD']
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

    public function sendTransaction(WithdrawRequest $transaction)
    {
        $endpoint = '/api/v1/transaction/bank/create';
        $this->tokencinet=$this->authenticate()['token'];
        if (!is_null($this->tokencinet)){
            $customerReponse=$this->getCreateSender($transaction);
            if ($customerReponse["status"] !==2000){
                throw new NotAcceptableHttpException($customerReponse['message']);
            }
            $beneficiaryReponse=$this->createBeneficiary($transaction);
            if ($beneficiaryReponse["status"] !==2000){
                throw new NotAcceptableHttpException($beneficiaryReponse['message']);
            }
            $bank = [
                "payoutCountry" => $transaction->dial_country_code,
                "payoutCity" => "Douala",
                "receiveCurrency" => $transaction->user()->getMonaire(),
                "amountToPaid" => $transaction->getMontanttotal(),
                "service" => $transaction->getTypetransaction(),
                "senderCode" => $transaction->getCustomer()->getSenderCode(),
                "beneficiaryCode" => $transaction->getBeneficiare()->getBeneficiaryCode(),
                "sendingCurrency" => $transaction->getCustomer()->getCountry()->getMonaire(),
                "bankAccount" => $transaction->getBeneficiare()->getBankaccountnumber(),
                "bankName" => $transaction->getBeneficiare()->getBankname(),
                "bankSwCode" => $transaction->getBeneficiare()->getBankswiftcode(),
                "bankBranch" => $transaction->getBeneficiare()->getBankbranchnumber(),
                "fromCountry" => $transaction->getCustomer()->getCountry()->getCodeString(),
                "originFund" => "salary",
                "reason" => $transaction->getRaisontransaction(),
                "relation" => "brother"
            ];
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->tokencinet,
                ],
                'body' => json_encode($bank),
            ];
            $res = $this->client->post($endpoint, $options);
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
    public function sendTransactionOM(WithdrawRequest $transaction)
    {
        $endpoint = '/api/v1/transaction/wallet/create';
        $this->tokencinet=$this->authenticate()['token'];
        if (!is_null($this->tokencinet)){
            $customerReponse=$this->getCreateSender($transaction);
            if ($customerReponse["status"] !==2000){
                throw new NotAcceptableHttpException($customerReponse['message']);
            }
            $beneficiaryReponse=$this->createBeneficiary($transaction);
            if ($beneficiaryReponse["status"] !==2000){
                throw new NotAcceptableHttpException($beneficiaryReponse['message']);
            }
            $wallet = [
                "payoutCountry" => $transaction->getCountry()->getCodeString(),
                "payoutCity" => $transaction->getTown()->getLibelle(),
                "receiveCurrency" => $transaction->getCountry()->getMonaire(),
                "amountToPaid" => $transaction->getMontanttotal(),
                "service" => $transaction->getOperateur(),
                "senderCode" => $transaction->getCustomer()->getSenderCode(),
                "beneficiaryCode" => $transaction->getBeneficiare()->getBeneficiaryCode(),
                "sendingCurrency" => $transaction->getCustomer()->getCountry()->getMonaire(),
                "mobileReceiveNumber" => $transaction->getBeneficiare()->getPhone(),
                "fromCountry" => $transaction->getCustomer()->getCountry()->getCodeString(),
                "originFund" => "Salary",
                // "reason" => $transaction->getRaisontransaction(),
                "reason" => "Family",
                "relation" => "Brother"
            ];
            $this->logger->info("------paybody".json_encode($wallet));
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->tokencinet,
                ],
                'body' => json_encode($wallet),
            ];
            $res = $this->client->post($endpoint, $options);
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

    public function getCreateSender(WithdrawRequest $transaction)
    {
        $endpoint = '/api/v1/sender/create';
        $customer=$transaction->getCustomer();
        $sender = [
            "firstName" => $customer->getFirstname(),
            "lastName" => $customer->getLastname(),
            "address" => $this->townRepository->findOneBy(['country'=>$customer->getCountry()])->getLibelle(),
            "phone" => $customer->getPhone(),
            "country" => $customer->getCountry()->getCodeString(),
            "city" => $this->townRepository->findOneBy(['country'=>$customer->getCountry()])->getLibelle(),
            "gender" => "M",
            "civility" => "Maried",
            "idNumber" => $customer->getNumeropiece(),
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
        $this->logger->info(json_encode($sender));
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->tokencinet,
            ],
            'body' => json_encode($sender),
        ];
        $res = $this->client->post($endpoint, $options);
        $this->logger->info("##############ResponseCustomer################");
        $this->logger->info($res->getBody());
        $jsonResponse=json_decode($res->getBody(), true);
        if ($jsonResponse['status']==2000){
            $customer->setSenderCode($jsonResponse['sender']['Code']);
            $this->doctrine->flush();
        }

        return $jsonResponse;
    }

    public function createBeneficiary(WithdrawRequest $transaction)
    {
        $endpoint = '/api/v1/beneficiary/create';
        $beneficiary_=$transaction->getBeneficiare();
        $beneficiary = [
            "firstName" => $beneficiary_->getFirstname(),
            "lastName" => $beneficiary_->getLastname(),
            "address" => $transaction->getTown()->getLibelle(),
            "phone" => $beneficiary_->getPhone(),
            "country" => $transaction->getCountry()->getCodeString(),
            "city" => $transaction->getTown()->getLibelle(),
            "mobile" => $beneficiary_->getPhone(),
            "email" => $beneficiary_->getEmail(),
            "idNumber" => "147852964",
            "idType" => "PP",
            "sender_code" => $transaction->getCustomer()->getSenderCode(),
            "updateIfExist" => true
        ];
        $this->logger->info("##############DataBeneficiary################");
        $this->logger->info(json_encode($beneficiary));
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->tokencinet,
            ],
            'body' => json_encode($beneficiary),
        ];
        $res = $this->client->post($endpoint, $options);
        $this->logger->info("##############ResponseBeneficiary################");
        $this->logger->info($res->getBody());
        $jsonResponse=json_decode($res->getBody(), true);
        if ($jsonResponse['status']==2000){
            $beneficiary_->setBeneficiaryCode($jsonResponse['beneficiary']['Code']);
            $this->doctrine->flush();
        }

        return $jsonResponse;
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
}
