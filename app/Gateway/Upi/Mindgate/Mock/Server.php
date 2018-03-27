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
        Action::COLLECT      => 17,
        Action::VERIFY       => 14,
        Action::REFUND       => 20,
        Action::VALIDATE_VPA => 14,
    ];

    /**
     * Total number of fields in every response
     * including the NA padding
     */
    const RESPONSE_FIELD_COUNT = [
        Action::AUTHORIZE       => 17,
        Action::VALIDATE_VPA    => 14,
        Action::VERIFY          => 21,
        Action::CALLBACK        => 21,
        Action::REFUND          => 21,
    ];

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
        ];

        if ($vpa === 'failedcollect@hdfcbank')
        {
            $content[3] = 'FAILED';
            $content[4] = 'Transaction collect request failed';
        }

        $this->content($content);

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
    protected function makeResponse($data)
    {
        $action = $this->action;

        // There are lots of empty "additional fields" in the response
        // that are currently expected to be filled with NA
        // The number of such fields depends on the request (auth|refund|etc)
        // We calculate the number of such fields and add it as a padding
        // with array_merge

        $paddingCount = self::RESPONSE_FIELD_COUNT[$action] - count($data);

        $data = array_merge($data, array_fill(count($data), $paddingCount, 'NA'));

        $content = implode('|', $data);

        $content = strtoupper(bin2hex($this->encrypt($content)));

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
        return $this->getCipherInstance()
                    ->decrypt(hex2bin($data));
    }

    protected function encrypt($plaintext)
    {
        return $this->getCipherInstance()
                    ->encrypt($plaintext);
    }

    public function getAsyncCallbackContent(array $upiEntity, array $payment)
    {
        $this->action = Action::CALLBACK;

        $content = $this->callbackResponseContent($upiEntity, $payment);

        $this->content($content,'callback');

        $response = $this->makeResponse($content);

        return [
            'meRes' => $response->content()
        ];
    }

    protected function callbackResponseContent(array $upiEntity, array $payment)
    {
        $status = Status::SUCCESS;

        switch ($payment['vpa'])
        {
            case 'failed@hdfcbank':
                $status = Status::FAILED;
                break;
        }

        return [
            $upiEntity['gateway_payment_id'],
            $upiEntity['payment_id'],
            $this->formatAmount($payment['amount']),
            '2017:12:01 00:00:02',
            $status,
            'Transaction success',
            '00',
            // Approval Number
            random_integer(5),
            $payment['vpa'],
            // NPCI Reference Id
            random_integer(16),
            'NA'
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
            'NA'
        ];

        return $this->makeResponse($res, Action::VERIFY);
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
        }

        $this->content($response, 'refund');

        return $this->makeResponse($response, Action::REFUND);
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

    protected function getCipherInstance()
    {
        $cipher = new AES(AES::MODE_ECB);

        $cipher->setKey($this->getEncryptionKey());

        return $cipher;
    }

    protected function getEncryptionKey()
    {
        $key = config('gateway.upi_mindgate.gateway_encryption_key');

        return hex2bin($key);
    }
}
