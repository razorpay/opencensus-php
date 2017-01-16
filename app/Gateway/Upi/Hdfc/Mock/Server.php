<?php

namespace RZP\Gateway\Upi\Hdfc\Mock;

use App;
use Carbon\Carbon;
use Gateway\Upi\Hdfc;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Base\Entity as UPIEntity;
use Models\Payment;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $input = $this->parseInput($input);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            // Razorpay Payment Id
            $input[1],
            // Bank Payment Id
            random_int(100000, 999999),
            // Amount
            $input[3],
            'SUCCESS',
            'Transaction Collect request initiated successfully',
            'nemomobile@imobile',
            'razorpay@hdfcbank',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
        );

        $this->content($content);

        return $this->makeResponse($content);
    }

    public function verify($input)
    {

    }

    protected function makeResponse($data)
    {
        $content = implode('|', $data);

        $content = strtoupper(bin2hex($this->encrypt($content)));

        $response = parent::makeResponse($content);

        $response->headers->set('Content-Type', 'text/plain;charset=ISO-8859-1');
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        $response->headers->set('x-frame-options', 'SAMEORIGIN');

        return $response;
    }

    protected function parseInput($input)
    {
        $input = json_decode($input, true);

        $encryptedInput = $input['requestMsg'];

        $res = $this->decrypt($encryptedInput);

        $arr = explode('|', $res);

        return array_slice($arr, 0, 7);
    }

    public function decrypt($data)
    {
        $cipher = $this->getAESInstance();

        return $cipher->decrypt(hex2bin($data));
    }

    protected function encrypt($plaintext)
    {
        $cipher = $this->getAESInstance();

        return $cipher->encrypt($plaintext);
    }

    public function getAsyncCallbackContent(array $upiEntity, array $payment)
    {
        $content = $this->S2SRequestContent($upiEntity, $payment);

        $json = json_encode($content, JSON_PRETTY_PRINT);

        $encrypted = $this->encrypt($json);

        return base64_encode($encrypted);
    }

    protected function getCipherInstance()
    {
        $cipher = new AES(AES::MODE_ECB);

        $cipher->setKey($this->getEncryptionKey());

        return $cipher;
    }

    protected function getEncryptionKey()
    {
        $key = config('gateway.upi_hdfc.test_merchant_key');

        return hex2bin($key);
    }
}
