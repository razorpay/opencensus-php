<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use App;
use Carbon\Carbon;
use RZP\Gateway\Upi\Icici;
use RZP\Models\Payment;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Icici\Fields;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

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

        $content = [
            Fields::RESPONSE         => $this->getAuthorizeResponseCode(),
            Fields::MERCHANT_ID      => $input['merchantId'],
            Fields::SUBMERCHANT_ID   => $input['subMerchantId'] ?? null,
            Fields::TERMINAL_ID      => $input['terminalId'] ?? null,
            Fields::SUCCESS          => 'true',
            Fields::MESSAGE          => 'Transaction initiated',
            Fields::MERCHANT_TRAN_ID => $input['merchantTranId'],
            Fields::BANK_RRN         => random_int(111111111, 999999999),
        ];

        $dontEncrypt = ($this->input['payerVa'] === 'dontencrypt@icici');

        $this->content($content);

        return $this->makeResponse($content, $dontEncrypt);
    }

    public function refund($input)
    {
        $input = $this->parseInput($input);

        parent::refund($input);

        $this->validateRefundInput($input);

        $this->content($input, 'validateRefund');

        $content = $this->getRefundResponseContent($input);

        $this->content($content, 'refund');

        return $this->makeResponse($content);
    }

    protected function getRefundResponseContent(array $input)
    {
        return [
            // Conditional Fields
            Fields::MERCHANT_ID           => $input['merchantId'],
            Fields::SUBMERCHANT_ID        => $input['subMerchantId'],
            Fields::TERMINAL_ID           => $input['terminalId'],
            Fields::ORIGINAL_BANK_RRN_REQ => (string) random_int(1111111111, 9999999999),

            // Mandatory fields
            Fields::MERCHANT_TRAN_ID      => $input['merchantTranId'],
            Fields::STATUS                => 'SUCCESS',
            Fields::RESPONSE              => '0',
            Fields::SUCCESS               => 'true',
            Fields::MESSAGE               => 'Transaction Successful',
        ];
    }

    public function verify($input)
    {
        $input = $this->parseInput($input);

        parent::verify($input);

        $this->validateActionInput($input);

        $app = App::getFacadeRoot();

        $payment = $app['repo']->payment->find($input['merchantTranId']);

        $status = 'SUCCESS';
        $message = 'Transaction Successful';

        if (isset($payment['notes']['status']) === true)
        {
            if ($payment['notes']['status'] === 'created')
            {
                $status = 'PENDING';
                $message = 'Transaction Initiated';
            }
            else if ($payment['notes']['status'] === 'failed')
            {
                $status = 'FAILURE';
                $message = 'Transaction failed';
            }
        }

        $responseCode = $this->getVerifyResponseCode($payment['vpa']);

        $response = [
            'response'          => $responseCode,
            'merchantId'        => $input['merchantId'],
            'subMerchantId'     => '1234',
            'terminalId'        => '1234',
            'success'           => $this->getSuccess($responseCode),
            'message'           => $message,
            'merchantTranId'    => $input['merchantTranId'],
            'OriginalBankRRN'   => (string) random_int(1111111111, 9999999999),
            'status'            => $status
        ];

        $encrypt = (isset($payment['notes']['encrypt']) and ($payment['notes']['encrypt'] === 'true'));

        return $this->makeResponse($response, $encrypt);
    }

    protected function getSuccess($responseCode)
    {
        if ($responseCode === '0')
        {
            return 'true';
        }

        return 'false';
    }

    protected function getVerifyResponseCode($vpa)
    {
        switch($vpa)
        {
            case 'missingpayment@icici':
                return '5006';
            default:
                return '0';
        }
    }

    /**
     * We are testing if our gateway works
     * with all possible values of error codes
     * @return int response code
     * @see ICICI Documentation:
     *
     * >All other values of response codes = Transaction has failed
     */
    protected function getAuthorizeResponseCode()
    {
        switch($this->input['payerVa'])
        {
            // Just make sure that this doesn't return 92
            case 'unknownresponse@icici':
                // Always return 93 error code
                return '93';
            case 'invalidvpa@icici':
                return '5007';
            case 'user@invalidbank':
                return '5008';
            case 'serverdown@icici':
                return '5009';
            default:
                return '92';
        }
    }

    protected function makeResponse($data, $dontEncrypt = false)
    {
        if ((is_string($data) === true) and
            (Utility::isXml($data) === true))
        {
            $response = parent::makeResponse($data);

            return $response;
        }

        $content = json_encode($data);

        // We encrypt content by default
        if ($dontEncrypt === true)
        {
            $encryptedData = $this->encrypt($content);
            assertTrue($encryptedData !== false);

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

        return $rsa->decrypt($ciphertext);
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

    public function getAsyncCallbackContent(array $upiEntity, array $payment)
    {
        $content = $this->S2SRequestContent($upiEntity, $payment);

        $json = json_encode($content, JSON_PRETTY_PRINT);

        $encrypted = $this->encrypt($json);

        return base64_encode($encrypted);
    }

    protected function S2SRequestContent(array $upiEntity, array $payment)
    {
        // Format is 20160830152240
        $initDate = Carbon::createFromTimestampUTC($upiEntity['created_at']);
        $completeDate = $initDate->copy()->addMinutes(1);

        $response = [
            'merchantId'        => $upiEntity['gateway_merchant_id'],
            'subMerchantId'     => '1234',
            'terminalId'        => '1234',
            'BankRRN'           => $upiEntity['gateway_payment_id'],
            'merchantTranId'    => $upiEntity['payment_id'],
            'PayerName'         => 'payer name not available',
            'PayerMobile'       => $payment['contact'],
            'PayerVA'           => $upiEntity['vpa'],
            'PayerAmount'       => number_format($payment['amount'] / 100, 2, '.', ''),
            'TxnStatus'         => 'SUCCESS',
            'TxnInitDate'       => $initDate->format('Ymdhis'),
            'TxnCompletionDate' => $completeDate->format('Ymdhis'),
        ];

        $this->content($response);

        return $response;
    }
}
