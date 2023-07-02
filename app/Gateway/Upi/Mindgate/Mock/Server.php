<?php

namespace RZP\Gateway\Upi\Mindgate\Mock;

use App;
use Carbon\Carbon;
use Gateway\Upi\Mindgate;
use RZP\Gateway\Upi\Mindgate\Action;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Mindgate\Status;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Base\Entity as UPIEntity;
use Models\Payment;

class Server extends Base\Mock\Server
{
    /**
     * How many legit (not "NA") fields
     * are expected to be parsed from the
     * actual incoming request
     */
    const REQUEST_FIELD_COUNT = [
        Action::COLLECT       => 17,
        Action::VERIFY        => 14,
        Action::REFUND        => 20,
        Action::VALIDATE_VPA  => 14,
        Action::VALIDATE_PUSH => 14,
        Action::INTENT_TPV    => 19,
    ];

    /**
     * Total number of fields in every response
     * including the NA padding
     */
    const RESPONSE_FIELD_COUNT = [
        Action::AUTHORIZE     => 17,
        Action::VALIDATE_VPA  => 14,
        Action::VERIFY        => 21,
        Action::CALLBACK      => 21,
        Action::REFUND        => 21,
        Action::VALIDATE_PUSH => 21,
        Action::INTENT_TPV    => 17,
    ];

    const REDIRECT_TO_DARK = 'redirect_to_dark';

    const DEFAULT_VPA = 'default@hdfc';

    public function intentTpv($input)
    {
        $this->input = $input;

        $this->action = Action::INTENT_TPV;

        $input = $this->parseInput($input, Action::INTENT_TPV);

        $content = [
            $input[1],
            Status::SUCCESS,
            'Transaction Initiated Successfully,',
            null,
            null,
            null,
            null,
            null,
            $input[14],
            $input[15],
            null,
            null,
            'NA',
        ];

        $this->content($content, Action::INTENT_TPV);

        return $this->makeResponse($content);
    }

    public function authorize($input)
    {
        parent::authorize($input);

        $input = $this->parseInput($input);

        $vpa = $input[2];

        $this->validateAuthorizeInput($input);

        $content = [
            // Razorpay Payment Id
            $input[1],
            // Bank Payment Id
            random_int(100000, 999999),
            // Amount
            $input[3],
            Status::SUCCESS,
            // Description
            'Transaction Collect request initiated successfully',
            // Payer VA
            $vpa,
            // Payee VA
            'razorpay@hdfcbank',
            $input[7],
            $input[8],
            $input[9],
            $input[10],
            $input[11],
        ];

        if ($vpa === 'failedcollect@hdfcbank')
        {
            $content[3] = 'FAILED';
            $content[4] = 'Transaction collect request failed';
        }

        $this->content($content, __FUNCTION__);

        return $this->makeResponse($content);
    }

    public function validateVpa(string $input)
    {
        $this->action = Action::VALIDATE_VPA;

        $input = $this->parseInput($input, Action::VALIDATE_VPA);

        $this->validateActionInput($input, Action::VALIDATE_VPA);

        $content = [
            // Razorpay Payment Id
            $input[1],
            // Customer VPA
            $input[2],
            // Customer name
            'User Name',
            // Status
            Status::VPA_AVAILABLE,
            // Description
            'Customer vpa is valid',
        ];

        if ($input[2] === 'invalidvpa@hdfcbank')
        {
            $content[3] = Status::VPA_NOT_AVAILABLE;
            $content[4] = 'Customer vpa not valid';
        }

        $this->content($content, 'validate_vpa');

        return $this->makeResponse($content);
    }

    /**
     * See docs link in README.md for response formatting
     */
    protected function makeResponse($data, $key = null)
    {
        $action = $this->action;

        if ($action !== self::REDIRECT_TO_DARK)
        {
            // There are lots of empty "additional fields" in the response
            // that are currently expected to be filled with NA
            // The number of such fields depends on the request (auth|refund|etc)
            // We calculate the number of such fields and add it as a padding
            // with array_merge

            $paddingCount = self::RESPONSE_FIELD_COUNT[$action] - count($data);

            $data = array_merge($data, array_fill(count($data), $paddingCount, 'NA'));

            $content = implode('|', $data);

            $content = $this->encrypt($content, $key);
        }
        else
        {
            $content = $data;
        }

        $response = parent::makeResponse($content);

        $response->headers->set('Content-Type', 'text/plain;charset=ISO-8859-1');
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        $response->headers->set('x-frame-options', 'SAMEORIGIN');

        return $response;
    }

    protected function parseInput($input, $action = Action::COLLECT)
    {
        $input = json_decode($input, true);

        $encryptedInput = $input['requestMsg'];

        $res = $this->decrypt($encryptedInput);

        $arr = explode('|', $res);

        $actualFieldLength = count($arr);
        $expectedFieldLength = self::REQUEST_FIELD_COUNT[$action];

        $message = $actualFieldLength . ' is not equal to expected ' . $expectedFieldLength;

        assertTrue($actualFieldLength === $expectedFieldLength, $message);

        return $arr;
    }

    public function decrypt($data)
    {
        return $this->getCipherInstance(null)
                    ->decrypt(hex2bin($data));
    }

    protected function encrypt($plaintext, $key = null)
    {
        $ciphertext = $this->getCipherInstance($key)
                    ->encrypt($plaintext);

        return strtoupper(bin2hex($ciphertext));
    }

    public function getAsyncCallbackContent(array $upiEntity, array $payment, array $meta = [])
    {
        $this->action = Action::CALLBACK;

        $content = $this->callbackResponseContent($upiEntity, $payment);

        $this->content($content, 'callback');

        $response = $this->makeResponse($content, $meta['key'] ?? null);

        return [
            'pgMerchantId' => $meta['merchant_id'] ?? 'HDFC000000000',
            'meRes'        => $response->content()
        ];
    }

    public function getAsyncCallbackContentForBharatQr($qrCodeId, $amount = 100, $meta = [])
    {
        $this->action = Action::CALLBACK;

        $rrn = random_integer(16);

        if (isset($meta['rrn']) === true)
        {
            $rrn = $meta['rrn'];
        }

        $payerAccountType='NA';

        if (isset($meta['payer_account_type']) === true)
        {
            $payerAccountType = $meta['payer_account_type'];
        }

        $content = [
            random_integer(10),
            'RZP' .$qrCodeId,
            $this->formatAmount($amount),
            '2017:12:01 00:00:02',
            Status::SUCCESS,
            'Transaction success',
            '00',
            // Approval Number
            random_integer(5),
            'sample@icici',
            // NPCI Reference Id
            $rrn,
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'PNB!10000000000!PNBI1111111!8966829290',
            'NA',
            'NA',
            $payerAccountType,
            'NA'
        ];

        $this->content($content,'callback');

        $response = $this->makeResponse($content);

        $request = [
            'url'       => '/payment/callback/bharatqr/upi_hdfc',
            'method'    => 'post',
            'content'   => [
                'pgMerchantId' => 'abcd_bharat_qr',
                'meRes'        => $response->content()
            ]
        ];

        return $request;
    }

    public function getAsyncCallbackContentForBharatQrWithTerminalSecret($qrCodeId, $amount = 100)
    {
        $this->action = Action::CALLBACK;

        $content = [
            random_integer(10),
            'RZP' .$qrCodeId,
            $this->formatAmount($amount),
            '2017:12:01 00:00:02',
            Status::SUCCESS,
            'Transaction success',
            '00',
            // Approval Number
            random_integer(5),
            'sample@icici',
            // NPCI Reference Id
            random_integer(16),
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'PNB!10000000000!PNBI1111111!8966829290'
        ];

        $this->content($content,'callback');

        $key = hex2bin("93158d5892188161a259db660ddb1d0a");

        $response = $this->makeResponse($content, $key);

        $request = [
            'url'       => '/payment/callback/bharatqr/upi_hdfc',
            'method'    => 'post',
            'content'   => [
                'pgMerchantId' => 'abcd_bharat_qr',
                'meRes'        => $response->content()
            ]
        ];

        return $request;
    }

    public function getAsyncFailureCallbackContentForBharatQr($qrCodeId, $amount = 100)
    {
        $this->action = Action::CALLBACK;

        $content = [
            random_integer(10),
            'RZP' .$qrCodeId,
            $this->formatAmount($amount),
            '2017:12:01 00:00:02',
            Status::FAILURE,
            'Transaction failure',
            'NA',
            // Approval Number
            random_integer(5),
            'sample@icici',
            // NPCI Reference Id
            random_integer(16),
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'PNB!10000000000!PNBI1111111!8966829290'
        ];

        $this->content($content,'callback');

        $response = $this->makeResponse($content);

        $request = [
            'url'       => '/payment/callback/bharatqr/upi_hdfc',
            'method'    => 'post',
            'content'   => [
                'pgMerchantId' => 'abcd_bharat_qr',
                'meRes'        => $response->content()
            ]
        ];

        return $request;
    }

    protected function callbackResponseContent(array $upiEntity, array $payment)
    {
        $status = Status::SUCCESS;
        $respCode = '00';

        switch ($payment['vpa'])
        {
            case 'failed@hdfcbank':
                $status = Status::FAILED;
                $respCode = 'ZA';
                break;

            case 'unknownrespcode@hdfcbank':
                $status = Status::FAILED;
                $respCode = 'XXX';
                break;

            case 'numericrespcode@hdfcbank':
                $status = Status::FAILED;
                $respCode = '8';
                break;

            case 'noTerminal@hdfcbank':
                $status = Status::FAILED;
                $respCode = 'noTerminal';
                break;

            case 'decryptionFailed@hdfcbank':
                $status = Status::FAILED;
                $respCode = 'dFailed';
                break;
        }

        return [
            $upiEntity['gateway_payment_id'],
            $upiEntity['payment_id'],
            $this->formatAmount($payment['amount']),
            '2017:12:01 00:00:02',
            $status,
            'Transaction success',
            $respCode,
            // Approval Number
            random_integer(5),
            $payment['vpa'] ?? self::DEFAULT_VPA,
            // NPCI Reference Id
            '910000123456',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'PNB!10000000000!PNBI1111111!8966829290'
        ];
    }

    /**
     * @param  int    $amount amount in paise
     * @return string
     */
    protected function formatAmount(int $amount): string
    {
        return number_format($amount / 100 ,2, '.', '');
    }

    public function verify($input)
    {
        $input = $this->parseInput($input, Action::VERIFY);

        parent::verify($input);

        $this->validateActionInput($input);

        $app = App::getFacadeRoot();

        $paymentId = $input[1];

        $payment = $app['repo']->payment->find($paymentId);

        if ($payment === null)
        {
            /*
                if payment_id is not found, then
                    - load gateway payment by merchant_reference
                    - extract payment_id from gateway payment
                    - load payment by gateway.payment_id
            */

            $gatewayPayment = $app['repo']->upi->fetchByMerchantReference($paymentId);

            if ($gatewayPayment !== null)
            {
                $payment = $app['repo']->payment->find($gatewayPayment['payment_id']);
            }
        }

        $response = $this->getDefaultVerifyResponse($input, $payment);

        $this->content($response,'verify');

        $res = [
            $response['txn_id'],
            $response['payment_id'],
            $response['amount'],
            $response['auth_time'],
            $response['status'],
            $response['message'],
            $response['resp_code'],
            $response['approval_num'],
            $response['payer_va'],
            $response['cust_ref_id'],
            // The Reference Id field always holds NA for now
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            $response['bank_reference'],
        ];

        return $this->makeResponse($res);
    }

    protected function getDefaultVerifyResponse(array $input, $payment): array
    {
        return [
            'status'        => Status::SUCCESS,
            'message'       => 'Transaction success',
            'resp_code'     => '00',
            'npci_txn_id'   => random_int(100000000000, 999999999999),
            'cust_ref_id'   => random_int(100000000000, 999999999999),
            'payment_id'    => $input[1],
            'txn_id'        => $input[2],
            'payer_va'      => $payment['vpa'],
            'approval_num'  => random_int(100000, 999999),
            // "2017:01:19 01:39:03" am/pm is not specified
            // The date is actually not the date of authorization, but the
            // timestamp when the collect request was raised
            'auth_time'     => date('Y:m:d h:i:s', $payment['created_at']),
            'amount'        => ($payment['amount'] / 100),
            'bank_reference' => 'ICICI Bank!004001551691!ICIC0000000!918712929835',
        ];
    }

    public function refund($input)
    {
        parent::refund($input);

        $input = $this->parseInput($input, Action::REFUND);

        $paymentId = $input[2];

        $app = App::getFacadeRoot();

        $payment = $app['repo']->payment->find($paymentId);

        $response = $this->getDefaultRefundResponse($input, $payment);

        if ($payment['vpa'] === 'failedrefund@hdfcbank')
        {
            $response[4] = Status::FAILED;
            $response[6] = 'BT';
        }

        $this->content($response, 'refund');

        return $this->makeResponse($response);
    }

    protected function getDefaultRefundResponse(array $input, $payment)
    {
        return [
            // UPI Txn Id
            random_int(100000, 999999),
            // Refund Id
            $input[1],
            // Amount
            $input[6],
            date('Y:m:d h:i:s', time()),
            // REFUND_SUCCESS is just S
            Status::SUCCESS,
            'Transaction success',
            // response code
            '00',
            // Approval number
            random_integer(12),
            $payment['vpa'],
            // NPCI UPI ID (customer reference number)
            $input[4],
            // Reference Id, currently null
            'NA'
        ];
    }

    protected function getCipherInstance($key)
    {
        $cipher = new AES(AES::MODE_ECB);

        $k = ($key === null ? $this->getEncryptionKey() : $key);

        $cipher->setKey($k);

        return $cipher;
    }

    protected function getEncryptionKey()
    {
        $key = config('gateway.upi_mindgate.gateway_encryption_key');

        return hex2bin($key);
    }

    public function validatePush($input)
    {
        parent::validatePush($input);

        $input = $this->parseInput($input, Action::VALIDATE_PUSH);

        $this->validateActionInput($input);

        $app = App::getFacadeRoot();

        // creating dummy payment array
        $payment = [
            'vpa'        => 'a@b',
            'amount'     => 1300,
            'created_at' => time(),
        ];

        $response = $this->getDefaultVerifyResponse($input, $payment);

        $this->content($response,'verify');

        $res = [
            $response['txn_id'],
            $input[1],
            $response['amount'],
            $response['auth_time'],
            $input[1] === 'payfail123' ? Status::FAILED : Status::SUCCESS,
            $response['message'],
            $response['resp_code'],
            $response['approval_num'],
            $response['payer_va'],
            $response['cust_ref_id'],
            // The Reference Id field always holds NA for now
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            $response['bank_reference'],
        ];

        return $this->makeResponse($res);
    }

    public function redirectToDark($input)
    {
        $this->action = self::REDIRECT_TO_DARK;

        $this->request($input, __FUNCTION__);

        $response = ['success' => true];

        $this->content($response, __FUNCTION__);

        return $this->makeResponse($response);
    }
}
