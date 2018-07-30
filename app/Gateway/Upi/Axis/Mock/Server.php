<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use App;
use Carbon\Carbon;
use Gateway\Upi\Axis;
use RZP\Gateway\Upi\Axis\Action;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Axis\Fields;
use RZP\Gateway\Upi\Axis\Status;
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
        Action::VERIFY       => 4,
        Action::REFUND       => 20,
    ];

    /**
     * Total number of fields in every response
     * including the NA padding
     */
    const RESPONSE_FIELD_COUNT = [
        Action::AUTHORIZE       => 17,
        Action::VERIFY          => 3,
        Action::CALLBACK        => 21,
        Action::REFUND          => 21,
    ];

    public function authorize($input)
    {
       $arr =  explode('/',parse_url($this->mockRequest['url'])['query']);
       $token = $arr[sizeof($arr)-1];
        parent::authorize($input);

        $content = [
            Fields::CODE => '00',
            Fields::RESULT => 'Accepted Collect Request',
            Fields::DATA => [
                Fields::MERCHANT_TRANSACTION_ID => 'TESTMERCHANTID:'.$token,
                Fields::W_COLLECT_TXN_ID => $this->generateRandomString(10),
            ]
        ];

        $this->content($content);
        return $this->makeResponse($content);
    }

    public function fetchToken($input)
    {
        $content = [
            Fields::CODE => '000',
            Fields::RESULT => 'SUCCESS',
            Fields::DATA => $this->generateRandomString(30),
        ];

        $this->content($content);

        return $this->makeResponse($content);

    }

    protected function parseInput($input, $action = Action::COLLECT)
    {
        $input = json_decode($input, true);

        return $input;
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

    /**
     * See docs link in README.md for response formatting
     */
    protected function makeResponse($data)
    {
        $action = $this->action;

        $content = json_encode($data);

        $response = parent::makeResponse($content);

        $response->headers->set('Content-Type', 'text/plain;charset=ISO-8859-1');
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        $response->headers->set('x-frame-options', 'SAMEORIGIN');

        return $response;
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
        return [
            Fields::CUSTOMER_VPA => $upiEntity['vpa'],
            Fields::MERCH_ID => 'RAZAORPAY',
            Fields::MERCH_CHAN_ID => 'RAZAORPAYAPP',
            Fields::MERCHANT_TRANSACTION_ID => $upiEntity['payment_id'],
            Fields::TRANSACTION_TIMESTAMP => date('j-F-Y'),
            Fields::TRANSACTION_AMOUNT => $this->formatAmount($upiEntity['amount']),
            Fields::GATEWAY_TRANSACTION_ID => $this->generateRandomString(20),
            Fields::GATEWAY_RESPONSE_CODE => '00',
            Fields::GATEWAY_RESPONSE_MESSAGE => 'Success',
            Fields::RRN => "714513318376",
            Fields::CHECKSUM => 'CHECKSUM NOT REQUIRED'
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


    public function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function verify($input)
    {
        $input = $this->parseInput($input, Action::VERIFY);

        parent::verify($input);

        $this->validateActionInput($input);

        $app = App::getFacadeRoot();

        $paymentId = $input['tranid'];

        $payment = $app['repo']->payment->find($paymentId);

        $response = $this->getDefaultVerifyResponse($input, $payment);

        $this->content($response,'verify');

        return $this->makeResponse($response, Action::VERIFY);
    }

    protected function getDefaultVerifyResponse(array $input, $payment): array
    {
        return [
            Fields::CODE => '00',
            Fields::RESULT => 'Successful',
            Fields::DATA => [
            Fields::MERCHANT_TRANSACTION_ID => 'CPAGA471420261',
            Fields::W_COLLECT_TXN_ID => "AXI91977318751526881521496367600647",
            Fields::MERCH_ID =>  "RAZAORPAY",
            Fields::MERCH_CHAN_ID => 'RAZAORPAYAPP',
            Fields::CUSTOMER_VPA =>  $payment['vpa'],
            Fields::TXN_TIME => "25-MAY-17 01.59.59.741000 PM",
            Fields::TXN_AMOUNT => $this->formatAmount($payment['amount']),
            Fields::RRN => "714513318376",
            Fields::DEBIT_ACCOUNT_NUM => "076010100236133",
            Fields::DEBIT_IFSC_CODE => "AXIS0000076",
            Fields::CHECKSUM => "dc251c30924ec8d2aed7ab0e15dc209e66b3f3efec934484b1b6be822214296d",
            ]
        ];
    }

    public function refund($input)
    {
        parent::refund($input);

        s($input);

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
}