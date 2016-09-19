<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use Carbon\Carbon;
use Gateway\Upi\Icici;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Base\Entity as UPIEntity;
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
        $input = $this->parseInput($input);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'response'          => $this->getResponseCode(),
            'merchantId'        => $input['merchantId'],
            'subMerchantId'     => isset($input['subMerchantId']) ? $input['subMerchantId'] : null,
            'terminalId'        => isset($input['terminalId']) ? $input['terminalId'] : null,
            'success'           => 'true',
            'message'           => 'Transaction initiated',
            'merchantTranId'    => $input['merchantTranId'],
            'BankRRN'           => '1234567',
        );

        $dontEncrypt = ($this->input['payerVa'] === 'dontencrypt@icici');

        $this->content($content);

        return $this->makeResponse($content, $dontEncrypt);
    }

    public function verify($input)
    {
        $input = $this->parseInput($input);

        parent::verify($input);

        $this->validateActionInput($input);

        $response = array(
            "response"          => "0",
            "merchantId"        => $input['merchantId'],
            "subMerchantId"     => "1234",
            "terminalId"        => "1234",
            "success"           => "true",
            "message"           => "Transaction Successful",
            "merchantTranId"    => $input['merchantTranId'],
            "OriginalBankRRN"   => (string) mt_rand(1111111, 9999999),
            "status"            => "SUCCESS"
        );

        return $this->makeResponse($response, false);
    }

    /**
     * We are testing if our gateway works
     * with all possible values of error codes
     * @return int response code
     * @see ICICI Documentation:
     *
     * >All other values of response codes = Transaction has failed
     */
    protected function getResponseCode()
    {
        switch($this->input['payerVa'])
        {
            // Just make sure that this doesn't return 92
            case 'unknownresponse@icici':
                return mt_rand(93, 500);
                break;
            default:
                return 92;
        }
    }

    protected function makeResponse($data, $dontEncrypt = false)
    {
        $content = json_encode($data);

        // We encrypt content by default
        if ($dontEncrypt === true)
        {
            $encryptedData = $this->encrypt($content);
            assert($encryptedData !== false);

            $content = base64_encode($encryptedData);
        }

        $response = parent::makeResponse($content);

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
        $rsa = $this->getRSAInstance('request');
        return  $rsa->decrypt($ciphertext);
    }

    protected function encrypt($plaintext)
    {
        $rsa = $this->getRSAInstance('response');
        return $rsa->encrypt($plaintext);
    }

    protected function getRSAInstance($mode)
    {
        $rsa = new RSA();

        switch ($mode)
        {
            // Inbound request, decrypt
            case 'request':

                $rsa->setPrivateKey($this->getPrivateKey());
                break;

            // Response, encrypt
            case 'response':

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
        $completeDate = $initDate->copy()->addMinutes(1);

        return [
            'merchantId'        => $entity['gateway_merchant_id'],
            'subMerchantId'     => $payment['merchant_id'],
            'terminalId'        => "1234",
            'BankRRN'           => $entity['gateway_payment_id'],
            'merchantTranId'    => $entity['payment_id'],
            'PayerName'         => "payer name not available",
            'PayerMobile'       => $payment['contact'],
            'PayerVA'           => $entity['vpa'],
            'PayerAmount'       => number_format($payment['amount']/100, 2),
            'TxnStatus'         => "SUCCESS",
            'TxnInitDate'       => $initDate->format('Ymdhis'),
            'TxnCompletionDate' => $completeDate->format('Ymdhis'),
        ];
    }
}
