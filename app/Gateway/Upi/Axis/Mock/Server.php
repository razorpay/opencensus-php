<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use App;
use Models\Payment;
use RZP\Gateway\Base;
use Gateway\Upi\Axis;
use phpseclib\Crypt\AES;
use RZP\Gateway\Upi\Axis\Fields;
use RZP\Gateway\Upi\Axis\Status;
use RZP\Gateway\Upi\Axis\Action;

class Server extends Base\Mock\Server
{
    const DEFAULT_VPA = 'default@axis';

    const REQUEST_FIELD_COUNT = [
        Action::COLLECT      => 17,
        Action::VERIFY       => 4,
        Action::REFUND       => 20,
    ];

    const RESPONSE_FIELD_COUNT = [
        Action::AUTHORIZE       => 17,
        Action::VERIFY          => 3,
        Action::CALLBACK        => 21,
        Action::REFUND          => 21,
    ];

    public function authorize($input)
    {
        parent::authorize($input);

        $this->request($input);

        if ((is_string($input) === true) and (str_contains($input, 'creditVpa') === true))
        {
            // to check whether this was an intent request
            $content = [
              Fields::CODE          => '000',
              Fields::RESULT        => 'SUCCESS',
              Fields::CREDIT_VPA    => 'merchant@axis',
              Fields::DATA          => 'rzp_payment_id',
            ];
        }
        else
        {
            $content = [
                Fields::CODE    => '00',
                Fields::RESULT  => 'Accepted Collect Request',
                Fields::DATA    => [
                    Fields::MERCHANT_TRANSACTION_ID => 'PAYMENT_ID',
                    Fields::W_COLLECT_TXN_ID        => str_random(10),
                ]
            ];
        }

        $this->content($content, $this->action);

        // Axis bank sometimes return HTML response, which is not json encoded
        if (is_string($content) === true)
        {
            return parent::makeResponse($content);
        }

        return $this->makeResponse($content);
    }

    public function fetchToken($input)
    {
        $input = $this->parseInput($input, Action::FETCH_TOKEN);

        $this->validateActionInput($input, Action::FETCH_TOKEN);

        $content = [
            Fields::CODE    => '000',
            Fields::RESULT  => 'SUCCESS',
            Fields::DATA    => str_random(30),
        ];

        $this->content($content);

        return $this->makeResponse($content);
    }

    protected function parseInput($input, $action = Action::COLLECT)
    {
        $input = json_decode($input, true);

        return $input;
    }

    /**
     * See docs link in README.md for response formatting
     */
    protected function makeResponse($data)
    {
        $action = $this->action;

        $content = json_encode($data);

        $response = parent::makeResponse($content);

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        $response->headers->set('x-frame-options', 'SAMEORIGIN');

        return $response;
    }

    public function getAsyncCallbackContent(array $upiEntity, array $payment, string $status = '00',
                                            string $result = 'Success')
    {
        $this->action = Action::CALLBACK;

        $content = $this->callbackResponseContent($upiEntity, $payment, $status, $result);

        $this->content($content,'callback');

        $response = $this->makeResponse($content);

        return ['data' => $content];
    }

    protected function callbackResponseContent(array $upiEntity, array $payment, string $status, string $result)
    {
         $data = [
            Fields::CUSTOMER_VPA                => $upiEntity['vpa'] ?? self::DEFAULT_VPA,
            Fields::CALLBACK_MERCHANT_ID        => 'TSTMERCHI',
            Fields::CALLBACK_MERCHANT_CHAN_ID   => 'TSTMERCHIAPP',
            Fields::MERCHANT_TRANSACTION_ID     => $payment['id'],
            Fields::TRANSACTION_TIMESTAMP       => date('j-F-Y'),
            Fields::TRANSACTION_AMOUNT          => $this->formatAmount($upiEntity['amount']),
            Fields::GATEWAY_TRANSACTION_ID      => $upiEntity['upi_txn_id'] ?? 'AXIS00090439839',
            Fields::GATEWAY_RESPONSE_CODE       => $status,
            Fields::GATEWAY_RESPONSE_MESSAGE    => $result,
            Fields::RRN                         => $upiEntity['npci_reference_id'] ?? '714513318376',
            Fields::CHECKSUM                    => 'CHECKSUM NOT REQUIRED'
        ];

        $this->content($data,'callback');

        $json = json_encode($data);

        $aesencrypted = $this->encryptAes($json);

        return $aesencrypted;
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

        $this->request($input, $this->action);

        parent::verify($input);

        $this->validateActionInput($input);

        $app = App::getFacadeRoot();

        $paymentId = $input['tranid'];

        $payment = $app['repo']->payment->find($paymentId);

        $response = $this->getDefaultVerifyResponse($input, $payment);

        $this->content($response,'verify');

        return $this->makeResponse($response, Action::VERIFY);
    }

    public function verifyRefund($input)
    {
        parent::verifyRefund($input);

        $input = $this->parseInput($input, Action::VERIFY);

        $this->request($input, $this->action);

        $app = App::getFacadeRoot();

        $paymentId = $input['unqTxnId'];

        $payment = $app['repo']->payment->find($paymentId);

        $response = $this->getDefaultVerifyRefundResponse($input, $payment);

        $this->content($response,'verify_refund');

        return $this->makeResponse($response, Action::VERIFY);
    }

    protected function getDefaultVerifyRefundResponse(array $input, $payment): array
    {
        return [
            Fields::CODE => '000',
            Fields::RESULT => 'REFUND REQUEST SUCCESSFUL',
            Fields::DATA => [
                Fields::VERIFY_REFUND_ORDER_ID => $payment['id'],
                Fields::TXN_REFUND_ID => $input['txnRefundId']
            ]
        ];
    }

    protected function getDefaultVerifyResponse(array $input, $payment): array
    {
        return [
            Fields::DATA => [
                [
                    Fields::CODE                    => ($input['tranid'] === "IShcnbF6tsOz")?'ML01':'00',
                    Fields::RESULT                  => (($input['merchid'] !== 'RAZORPPROD4264718195' && $payment['vpa'] === 'multipleRRN@axisbank') ||($input['tranid'] === "IShcnbF6tsOz")) ? 'F' :'S',
                    Fields::CHECK_STATUS_UNQ_TXN_ID => $payment['id'] ?? $input[Fields::CHECK_STATUS_UNQ_TXN_ID],
                    Fields::CHECK_STATUS_REF_ID     => '714513318376',
                    Fields::CHECK_STATUS_DATE_TIME  => '25/07/18 17:42:16',
                    Fields::AMOUNT                  => ($payment['vpa'] === 'unexpectedPayment@axisbank' ? 100 :
                        $this->formatAmount($payment['amount'] ?? 60000)),
                    Fields::CHECK_STATUS_DEBIT_VPA  => $payment['vpa'] ?? 'unexpected@axisbank',
                    Fields::CHECK_STATUS_CREDIT_VPA => 'razorpay@axis',
                    Fields::STATUS                  => 'C',
                    Fields::REMARKS                 => 'UPI',
                ]
            ]
        ];
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->request($input, 'refund');

        $input = $this->parseInput($input, Action::REFUND);

        $this->validateActionInput($input, Action::REFUND);

        $paymentId = $input['unqTxnId'];

        $app = App::getFacadeRoot();

        $payment = $app['repo']->payment->find($paymentId);

        $response = $this->getDefaultRefundResponse($input, $payment);

        $this->content($response, 'refund');

        return $this->makeResponse($response, Action::REFUND);
    }

    protected function getDefaultRefundResponse(array $input, $payment)
    {
        return [
            'code'      => '000',
            'result'    => 'REFUND REQUEST SUCCESSFUL',
            'data'      => 'RELIANC34347343',
        ];
    }

    protected function createCryptoIfNotCreated()
    {

        $this->aesCrypto = new AESCrypto(AES::MODE_ECB, $this->getGatewayInstance()->getSecret());
    }

    public function encryptAes(string $stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->encryptString($stringToEncrypt);
    }

    public function decryptAes(string $stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->decryptString($stringToDecrypt);
    }

    protected function getGatewayInstance($bankingType = null)
    {
        $class = 'RZP\Gateway\Upi\Axis\Gateway';

        $gateway = new $class;

        return $gateway;
    }
}
