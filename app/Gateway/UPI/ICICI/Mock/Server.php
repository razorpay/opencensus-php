<?php

namespace RZP\Gateway\UPI\ICICI\Mock;

use Carbon\Carbon;
use Gateway\UPI\ICICI;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\UPI\Base\Entity as UPIEntity;
use Models\Payment;

class Server extends Base\Mock\Server
{

    public function __construct()
    {
        if (defined('CRYPT_RSA_PKCS15_COMPAT') === false)
        {
            define('CRYPT_RSA_PKCS15_COMPAT', true);
        }
    }

    /**
     * Private Key of the mock server
     */
    protected function getPrivateKey()
    {
        return file_get_contents(__DIR__ . '/keys/mockserver.key');
    }

    /**
     * Public key of the client that is connecting
     * to us, in this case, the Mock Gateway
     */
    protected function getPublicKey()
    {
        return file_get_contents(__DIR__ . '/keys/mockclient.pub');
    }

    public function authorize($input)
    {
        parent::authorize($input);
        $input = $this->parseInput($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'response'          =>  '92',
            'merchantId'        =>  $input['merchantId'],
            'subMerchantId'     =>  isset($input['subMerchantId']) ? $input['subMerchantId'] : null,
            'terminalId'        =>  isset($input['terminalId']) ? $input['terminalId'] : null,
            'success'           =>  'true',
            'message'           =>  'Transaction initiated',
            'merchantTranId'    =>  $input['merchantTranId'],
            'BankRRN'           =>  '1234567',
        );

        return $this->makeResponse($content);
    }

    protected function makeResponse($data)
    {
        $json = json_encode($data);

        $res = $this->encrypt($json);

        assert($res !== false);

        $content = base64_encode($res);

        $response = response($content);

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('Content-Language', 'en-US');
        $response->headers->set('Server', 'API Gateway');

        return $response;
    }

    protected function parseInput($input)
    {
        $input = base64_decode($input);
        $input = $this->decrypt($input);
        return json_decode($input, true);
    }

    protected function decrypt($ciphertext)
    {
        $rsa = $this->getRSAInstance('req');
        return  $rsa->decrypt($ciphertext);
    }

    protected function encrypt($plaintext)
    {
        $rsa = $this->getRSAInstance('res');
        return $rsa->encrypt($plaintext);
    }

    protected function getRSAInstance($mode)
    {
        $rsa = new RSA();

        switch ($mode)
        {

            // Inbound request, decrypt
            case 'req':

                $rsa->setPrivateKey($this->getPrivateKey());
                break;

            // Response, encrypt
            case 'res':

                $rsa->loadKey($this->getPublicKey());
                break;
        }

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa;
    }

    public function makeS2SRequest(array $upiEntity, array $payment)
    {
        $data = $this->S2SRequestContent($upiEntity, $payment);

        $json = json_encode($data, JSON_PRETTY_PRINT);

        $encrypted = $this->encrypt($json);

        return base64_encode($encrypted);
    }

    protected function S2SRequestContent(array $entity, array $payment)
    {
        // Format is 20160830152240
        $initDate = Carbon::createFromTimestampUTC($entity['created_at'], 'Asia/Kolkata');
        $completeDate = Carbon::createFromTimestampUTC($entity['created_at'], 'Asia/Kolkata')->addMinutes(1);

        return [
            'merchantId' => $entity['gateway_merchant_id'],
            'subMerchantId' =>  $payment['merchant_id'],
            'terminalId' => "1234",
            'BankRRN' =>  $entity['gateway_payment_id'],
            'merchantTranId' =>  $entity['payment_id'],
            'PayerName' =>  "payer name not available",
            'PayerMobile' =>  $payment['contact'],
            'PayerVA' =>  $entity['vpa'],
            'PayerAmount' => number_format($payment['amount']/100, 2),
            'TxnStatus' => "SUCCESS",
            'TxnInitDate' =>  $initDate->format('Ymdhis'),
            'TxnCompletionDate' =>  $completeDate->format('Ymdhis'),
        ];
    }
}
