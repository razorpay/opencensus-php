<?php

namespace RZP\Gateway\UPI\ICICI;

use RZP\Gateway\Base;
use Crypt_RSA;

class Gateway extends Base\Gateway
{
    protected $gateway = 'upi_icici';

        const PUBLIC_KEY = <<<EOT
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAmj05pbyW0V0S2LDT5zNc
lAoZevw+2vjyGBQVTBLHJ1PL9zH+TBGe6+uR6QMoF7KG1/yqILaOAmV4K2T00O4I
hp6EoX4EdLt1E/VNpPMOhUbhxwHJ7KD8t4BEGjDRpbdBG+XOsLaXmKRty771ek0V
i8Umbo3IUYoQuC6DIqTCXZmhxnBNd1FAikPoM9mdwFY0/PqQ92XUPmUNTZ7sEzhk
oBrtFTcqnPacPJPa1y6n2YFmUmzv9wnFZ55OGwcvpNiI/GOjmmgemkQp6Vkleo7H
JqoGvsqK1QG54rFhuuTSxGARFhH3wKEB4lGsJ9D1mTGUOnafC4iOC0SAk5mTrKbm
uJdavD1TXAkhXlNs5oVJhQm1UPKtZwqpYlDWz3ybBs26412Nl/wXCshcksA/jPZS
K0sTxEWHjJ7MLyNAoDDV+Gko6BaxURAjX86Ac930tBt2/LIdNUlT+z+uTldsHO1I
dbNHrDYms1ZEIzVV83oN/Hev3Oae+tSWrGQRWvV9rqHByDFlsniwnYhLO6XyHvYq
dPGKC553wEbHtJqPTaupDCY/49d7pVAWGFpVob6ebg8R51yk4mgoEaeg6s9KpMce
RQAfGbcw2gk+LU1nxcgexz0piV0aCTWw1rD+v+O5n1AGOf+5qWUu6H8wqfJtyxGD
N3gj6mi9EFGymEcgFWhhaO0CAwEAAQ==
-----END PUBLIC KEY-----
EOT;

    public function __construct()
    {
        /**
         * See http://phpseclib.sourceforge.net/rsa/examples.html
         *
         * We need to run in PCKS 1.5 mode
         */
        define('CRYPT_RSA_PKCS15_COMPAT', true);
        $this->rsa = @new Crypt_RSA();
        $this->rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $this->rsa->loadKey(self::PUBLIC_KEY);
    }

    public function authorize(array $input)
    {
        $this->input = $input;
        $this->action = Action::AUTHORIZE;
        $request = $this->getAuthorizeRequestContent($input);

        $response = $this->sendGatewayRequest($request);
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
            "terminalId"    =>  "1234",
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
