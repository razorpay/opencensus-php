<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use App;
use Carbon\Carbon;
use RZP\Models\Payment;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Exception\LogicException;
use RZP\Gateway\Upi\Icici\Fields;
use RZP\Exception\AssertionException;

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

        $this->request($input);

        $this->validateAccountNumberForTPV($input);

        if (isset($input['payerVa']) === false)
        {
            if ((isset($input['payerAccount']) === true) and
                (isset($input['validatePayerAccFlag']) === false))
            {
                throw new AssertionException('validatePayerAccFlag is expected for Intent TPV');
            }
        }
        else
        {
            if ((isset($input['payerAccount']) === true) and
                (isset($input['ValidatePayerAccFlag']) === false))
            {
                throw new AssertionException('ValidatePayerAccFlag is expected for Collect TPV');
            }
        }

        $content = [
            Fields::RESPONSE         => $this->getAuthorizeResponseCode(),
            Fields::MERCHANT_ID      => $input['merchantId'],
            Fields::SUBMERCHANT_ID   => $input['subMerchantId'] ?? null,
            Fields::TERMINAL_ID      => $input['terminalId'] ?? null,
            Fields::SUCCESS          => 'true',
            Fields::MESSAGE          => 'Transaction initiated',
            Fields::MERCHANT_TRAN_ID => $input['merchantTranId'],
            // Intent will not return RRN
            Fields::BANK_RRN         => isset($input['payerVa']) ? random_int(111111111, 999999999) : null,
        ];

        $dontEncrypt = ((isset($input['payerVa']) === true) and
                        ($input['payerVa'] === 'dontencrypt@icici'));

        $this->content($content, 'authorize', $input);

        return $this->makeResponse($content, $dontEncrypt);
    }

    // For certain banks, like SBIN, CBIN account numbers have to be of fixed specific length. We modify the account
    // numbers for these banks. Adding a validation here so that test case added for SBI fails if there is some bug
    // introduced in code.
    protected function validateAccountNumberForTPV($input)
    {
        if ((isset($input['validatePayerAccFlag']) === true) or (isset($input['ValidatePayerAccFlag']) === true))
        {
            if (substr($input['payerIFSC'], 0, 4) === 'SBIN')
            {
                if (strlen($input['payerAccount']) < 17)
                {
                    throw new AssertionException('Invalid Account number');
                }
            }
        }
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

        if ($payment === NULL)
        {
            $bharatQr = $app['repo']->bharat_qr->findByMerchantReference($input['merchantTranId']);

            if ($bharatQr != NULL)
            {
                $payment = $bharatQr->payment;
            }
        }

        $status = 'SUCCESS';
        $message = 'Transaction Successful';

        $amount = number_format($payment['amount'] / 100, 2, '.', '');

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

        if (isset($payment['notes']['amount']) === true)
        {
            if ($payment['notes']['amount'] === 'mismatch')
            {
                $amount = '12';
            }
        }

        $responseCode = $this->getVerifyResponseCode($payment['vpa']);

        if (empty($payment['created_at']) === true)
        {
            $initDate = Carbon::now();
        }
        else
        {
            $initDate = Carbon::createFromTimestampUTC($payment['created_at']);
        }

        $completeDate = $initDate->copy()->addMinutes(1);

        $response = [
            'response'          => $responseCode,
            'merchantId'        => $input['merchantId'],
            'subMerchantId'     => '1234',
            'terminalId'        => '1234',
            'success'           => $this->getSuccess($responseCode),
            'message'           => $message,
            'OriginalBankRRN'   => (string) random_int(1111111111, 9999999999),
            'merchantTranId'    => $input['merchantTranId'],
            'payerVA'           => $payment['vpa'],
            'Amount'            => $amount,
            'status'            => $status,
            'TxnInitDate'       => $initDate->getTimestamp(),
            'TxnCompletionDate' => $completeDate->getTimestamp(),
        ];

        $this->content($response, 'verify');

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
        if (isset($this->input['payerVa']) === true)
        {
            switch ($this->input['payerVa'])
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
            }

            return '92';
        }

        return '0';
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

    public function getAsyncCallbackContentForBharatQr(array $content)
    {
        $json = json_encode($content, JSON_PRETTY_PRINT);

        $encrypted = $this->encrypt($json);

        return base64_encode($encrypted);
    }

    public function getFailedAsyncCallbackContent(array $upiEntity, array $payment)
    {
        $response = $this->getCallbackFailureContentArray($upiEntity,$payment);

        $this->content($response);

        $json = json_encode($response, JSON_PRETTY_PRINT);

        $encrypted = $this->encrypt($json);

        return base64_encode($encrypted);
    }

    protected function S2SRequestContent(array $upiEntity, array $payment)
    {
        // Format is 20160830152240
        $initDate = Carbon::createFromTimestampUTC($upiEntity['created_at']);
        $completeDate = $initDate->copy()->addMinutes(1);

        $response = $this->getCallbackContentArray($upiEntity,$payment);

        $this->content($response);

        return $response;
    }

    public function validateVpa($input)
    {
        $request = json_decode($input, true);

        $response = $this->getValidateVpaResponseArray($request);

        return $this->makeResponse($response);
    }

    private function getValidateVpaResponseArray(array $input)
    {
        $input = $input["entities"];

        $vpa = $input['payment']['vpa'];

        $response = [
            'BankRRN'       => 'Random',
            'MobileAppData' => 'SUCCESS,Mask Name=Rohit',
            'SeqNo'         => random_alpha_string(35),
            'UpiTranlogId'  => '293731967',
            'UserProfile'   => 'random_id',
            'message'       => 'Transaction Successful',
            'response'      => '0',
            'success'       => true,
            'customer_name' => 'Rohit',
        ];

        if ($vpa === 'invalid@sbi')
        {
            $response['response']      = 'ZH';
            $response['MobileAppData'] = null;
            $response['message']       = 'INVALID VIRTUAL ADDRESS';
            $response['success']       = false;
            $response['customer_name'] = null;
        }

        $response["data"] = $response;

        return $response;
    }

    protected function getCallbackContentArray(array $upiEntity, array $payment): array
    {
        // Format is 20160830152240
        $initDate = Carbon::createFromTimestampUTC($upiEntity['created_at']);
        $completeDate = $initDate->copy()->addMinutes(1);

        $content = [
            'merchantId'        => $upiEntity['gateway_merchant_id'],
            'subMerchantId'     => '1234',
            'terminalId'        => '1234',
            'BankRRN'           => $upiEntity['gateway_payment_id'] ?? '12345678987654321',
            'merchantTranId'    => $upiEntity['payment_id'],
            'PayerName'         => 'payer name not available',
            'PayerMobile'       => $payment['contact'],
            'PayerVA'           => $upiEntity['vpa'],
            'PayerAmount'       => number_format($payment['amount'] / 100, 2, '.', ''),
            'TxnStatus'         => 'SUCCESS',
            'TxnInitDate'       => $initDate->format('Ymdhis'),
            'TxnCompletionDate' => $completeDate->format('Ymdhis'),
            'originalBankRRN'   => $payment['status'] === 'created' ? null : '12345678987654321',
        ];

        return $content;
    }

    protected function getCallbackFailureContentArray(array $upiEntity, array $payment): array
    {
        // Format is 20160830152240
        $initDate = Carbon::createFromTimestampUTC($upiEntity['created_at']);
        $completeDate = $initDate->copy()->addMinutes(1);

        $content = [
            'merchantId'        => $upiEntity['gateway_merchant_id'],
            'subMerchantId'     => '1234',
            'terminalId'        => '1234',
            'BankRRN'           => $upiEntity['gateway_payment_id'] ?? '12345678987654321',
            'merchantTranId'    => $upiEntity['payment_id'],
            'PayerName'         => 'payer name not available',
            'PayerMobile'       => $payment['contact'],
            'PayerVA'           => $upiEntity['vpa'],
            'PayerAmount'       => number_format($payment['amount'] / 100, 2, '.', ''),
            'TxnStatus'         => 'FAILURE',
            'TxnInitDate'       => $initDate->format('Ymdhis'),
            'TxnCompletionDate' => $completeDate->format('Ymdhis'),
            'originalBankRRN'   => $payment['status'] === 'created' ? null : '12345678987654321',
        ];

        return $content;
    }

    public function getQueryParams(array $upiEntity, array $payment): string
    {
        $content = $this->getCallbackContentArray($upiEntity,$payment);

        $queryParams = http_build_query($content);

        return $queryParams;
    }

    public function redirectToDark($input)
    {
        $this->request($input, __FUNCTION__);

        $response = ['success' => true];

        $this->content($response, __FUNCTION__);

        return $this->makeResponse($response);
    }
}
