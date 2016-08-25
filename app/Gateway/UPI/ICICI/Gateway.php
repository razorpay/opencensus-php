<?php

namespace RZP\Gateway\UPI\ICICI;

use RZP\Gateway\Base;
use phpseclib\Crypt\RSA;

class Gateway extends Base\Gateway
{
    protected $gateway = 'upi_icici';

    public function __construct()
    {
        /**
         * See http://phpseclib.sourceforge.net/rsa/examples.html
         *
         * We need to run in PCKS 1.5 mode
         */
        define('CRYPT_RSA_PKCS15_COMPAT', true);
        $this->rsa = new RSA();
        $this->rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $this->rsa->loadKey($this->getPublicKey());
    }

    // Unimplemented as of now
    protected function getPublicKey()
    {
    }

    public function authorize(array $input)
    {
        $this->input = $input;
        $this->action = Action::AUTHORIZE;
        $request =  $this->getAuthorizeRequestContent($input);

        $this->sendGatewayRequest($request);
    }

    protected function formatAmount($amount)
    {
        return number_format($amount/100, 2);
    }

    protected function getMerchantId()
    {
        // TODO: Decide between LIVE/TEST
        return $this->config['test_merchant_id'];
    }

    /**
     * Generates request object for the status call
     * @return array
     */
    protected function statusData()
    {
        return [
            "merchantId"        =>  "merchantId",
            "subMerchantId"     =>  "12234",
            "terminalId"        =>  "2342342",
            "merchantTranId"    =>  "612413726581"
        ];
    }

    /**
     * Encrypts data before sending it to ICICI
     * @param  string $data
     * @return string
     */
    protected function encrypt($data)
    {
        return $this->rsa->encrypt($data);
    }

    protected function getAuthorizeRequestContent($input)
    {
        $payment = $input['payment'];

        $data = [
            // Amount and note are lowercase
            // despite being uppercase in docs
            "amount"        =>  $this->formatAmount($payment['amount']),
            "billNumber"    =>  "sdf234234",
            "collectByDate" =>  "15/12/2016 11:01 AM",
            "merchantId"    =>  $this->getMerchantId(),
            "merchantName"  =>  $input['merchant']['billing_label'],
            "merchantTranId"=>  $payment['id'],
            "note"          =>  "collect-pay-request",
            // TODO: talk to icici and ask what all is allowed here
            "payerVa"       =>  "testing1@imobile",
            "subMerchantId" =>  "1234",//$input['merchant']['id'],
            "subMerchantName"=> $input['merchant']['name'],
        ];

        return $this->makeRequest($data);
    }

    protected function makeRequest($data)
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $body = base64_encode($this->encrypt($json));

        // getUrl relies on $this->action, ensure that
        // it is set
        return [
            'url'       =>  $this->getUrl(),
            'content'   =>  $body,
            'method'    =>  'post',
        ];
    }
}
