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

        $arr =  explode('/', parse_url($this->mockRequest['url'])['query']);
        $token = $arr[count($arr) - 1];

        $content = [
            Fields::CODE    => '00',
            Fields::RESULT  => 'Accepted Collect Request',
            Fields::DATA    => [
                Fields::MERCHANT_TRANSACTION_ID => 'TESTMERCHANTID:'.$token,
                Fields::W_COLLECT_TXN_ID        => str_random(10),
            ]
        ];

        $this->content($content, $this->action);

        return $this->makeResponse($content);
    }

    public function fetchToken($input)
    {
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

    public function getAsyncCallbackContent(array $upiEntity, array $payment)
    {
        $this->action = Action::CALLBACK;

        $content = $this->callbackResponseContent($upiEntity, $payment);

        $this->content($content,'callback');

        $response = $this->makeResponse($content);

        return ['data' => $content];
    }

    protected function callbackResponseContent(array $upiEntity, array $payment)
    {
         $data = [
            Fields::CUSTOMER_VPA                => $upiEntity['vpa'],
            Fields::MERCH_ID                    => 'RAZAORPAY',
            Fields::MERCH_CHAN_ID               => 'RAZAORPAYAPP',
            Fields::MERCHANT_TRANSACTION_ID     => $upiEntity['payment_id'],
            Fields::TRANSACTION_TIMESTAMP       => date('j-F-Y'),
            Fields::TRANSACTION_AMOUNT          => $this->formatAmount($upiEntity['amount']),
            Fields::GATEWAY_TRANSACTION_ID      => 'AXIS00090439839',
            Fields::GATEWAY_RESPONSE_CODE       => '00',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'Success',
            Fields::RRN                         => '714513318376',
            Fields::CHECKSUM                    => 'CHECKSUM NOT REQUIRED'
        ];

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

    protected function getDefaultVerifyResponse(array $input, $payment): array
    {
        return [
            Fields::DATA => [
                Fields::CODE                    => '00',
                Fields::RESULT                  => 'S',
                Fields::MERCHANT_TRANSACTION_ID => 'CPAGA471420261',
                Fields::W_COLLECT_TXN_ID        => 'AXI91977318751526881521496367600647',
                Fields::MERCH_ID                => 'RAZAORPAY',
                Fields::MERCH_CHAN_ID           => 'RAZAORPAYAPP',
                Fields::CUSTOMER_VPA            => $payment['vpa'],
                Fields::TXN_TIME                => '25-MAY-17 01.59.59.741000 PM',
                Fields::TXN_AMOUNT              => $this->formatAmount($payment['amount']),
                Fields::RRN                     => '714513318376',
                Fields::DEBIT_ACCOUNT_NUM       => '076010100236133',
                Fields::DEBIT_IFSC_CODE         => 'AXIS0000076',
                Fields::CHECKSUM                => 'dc251c30924ec8d2aed7ab0e15dc209e66b3f3efec934484b1b6be822214296d',
            ]
        ];
    }

    public function refund($input)
    {
        parent::refund($input);

        $input = $this->parseInput($input, Action::REFUND);

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
