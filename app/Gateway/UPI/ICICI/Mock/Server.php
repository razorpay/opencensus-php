<?php

namespace RZP\Gateway\UPI\ICICI\Mock;

use Carbon\Carbon;
use Gateway\UPI\ICICI;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use Models\Payment;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        $this->privateKey = $this->getPrivateKey();
    }

    protected function getPrivateKey()
    {
        return file_get_contents(__DIR__ . '/keys/upi-mock-2.key');
    }

    public function authorize($input)
    {
        parent::authorize($input);
        $input = $this->parseInput($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            "response"          =>  "92",
            "merchantId"        =>  $input['merchantId'],
            "subMerchantId"     =>  isset($input['subMerchantId']) ? $input['subMerchantId'] : null,
            "terminalId"        =>  isset($input['terminalId']) ? $input['terminalId'] : null,
            "success"           =>  "true",
            "message"           =>  "Transaction initiated",
            "merchantTranId"    =>  $input['merchantTranId'],
            "BankRRN"           =>  "1234567",
        );

        return $this->makeResponse($content);
    }

    protected function makeResponse($data)
    {
        $response = \Response::json($data);

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('Content-Language', 'en-US');
        $response->headers->set('Server', 'API Gateway');

        return $response;
    }

    protected function parseInput($input)
    {
        file_put_contents('/tmp/req.txt', $input);
        $input = base64_decode($input);
        $input = $this->decrypt($input);
        return json_decode($input, true);
    }

    protected function decrypt($ciphertext)
    {
        $rsa = new RSA();
        $rsa->setPrivateKey($this->privateKey);
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa->decrypt($ciphertext);
    }
}
