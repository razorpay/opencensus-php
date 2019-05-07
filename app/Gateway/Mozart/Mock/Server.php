<?php

namespace RZP\Gateway\Mozart\Mock;

use phpseclib\Crypt\AES;

use Str;
use RZP\App;
use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $gateway = $input['gateway'];

        unset($input['gateway']);

        return $this->$gateway($input);
    }

    public function payInit($input)
    {
        $payInitObj = new PayInitData();

        return $this->processMockResponse($input, $payInitObj, 'pay_init');
    }

    public function payVerify($input)
    {
        $payVerifyObj = new PayVerifyData();

        return $this->processMockResponse($input, $payVerifyObj, 'pay_verify');
    }

    public function verify($input)
    {
        $verifyObj = new VerifyData();

        return $this->processMockResponse($input, $verifyObj, 'verify');
    }

    public function refund($input)
    {
        $refundObj = new RefundData();

        return $this->processMockResponse($input, $refundObj, 'refund');
    }

    public function verifyRefund($input)
    {
        $verifyRefundObj = new VerifyRefundData();

        return $this->processMockResponse($input, $verifyRefundObj, 'verify_refund');
    }

    protected function makeResponseJson($body)
    {
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function processMockResponse($input, $actionClass, $action)
    {
        $input = json_decode($input, true);

        $entities = $input['entities'];

        $gateway = $entities['payment']['gateway'];

        $response = $actionClass->$gateway($entities);

        $response = json_encode($response);

        $this->content($response, $action);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function getAsyncCallbackContent(array $payment)
    {
        $response = [
            'code'      => "0",
            'errorCode' => "000",
            'messageText' => 'success',
            'rrn' => '987654321',
            'txnStatus' => 'SUCCESS',
            'amount' => $payment['amount'] / 100,
            'hdnOrderID' => ltrim($payment['id'], 'pay_'),
        ];

        $str = implode('#', $response);

        $secret = $this->getUpiAirtelSecret();

        $str .= '#'.$secret;

        $hash = hash(HashAlgo::SHA512, $str);

        $response['hash'] = $hash;

        return [json_encode($response)];
    }

    public function getFailedAsyncCallbackContent(array $payment)
    {
        $response = [
            'code'      => 0,
            'errorCode' => 000,
            'messageText' => 'success',
            'rrn' => '987654321',
            'txnStatus' => 'FAILED',
            'amount' => $payment['amount'] / 100,
            'hdnOrderID' => ltrim($payment['id'], 'pay_'),
        ];

        $str = implode('#', $response);

        $secret = $this->getUpiAirtelSecret();

        $str .= '#'.$secret;

        $hash = hash(HashAlgo::SHA512, $str);

        $response['hash'] = $hash;

        return [json_encode($response)];
    }

    protected function wallet_phonepe($input)
    {
        $content = $input;

        $content['checksum'] = 'randomHash';

        $paymentId = $content['paymentId'];

        $this->content($content);

        $publicId = $this->getSignedPaymentId($paymentId);

        $url = $this->route->getPublicCallbackUrlWithHash($publicId);
        $request = [
            'url'          => $url,
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }

    protected function netbanking_sib($input)
    {
        $aes = new Base\AESCrypto(
                                   AES::MODE_ECB,
                                    $this->app['config']->get('gateway.mozart.netbanking_sib_test_hash_secret')
                                 );

        $queryParams = $aes->decryptString(base64_decode($input['QS']));

        parse_str($queryParams, $queryFields);

        $response = [
            'BankId' => '059',
            'MD'     => 'P',
            'PID'    => $queryFields['ShoppingMallTranFG_PID'],
            'CRN'    => 'INR',
            'PRN'    => $queryFields['ShoppingMallTranFG_PRN'],
            'ITC'    => 'RAZORPAY',
            'AMT'    => $queryFields['ShoppingMallTranFG_TXN_AMT'],
            'BID'    => 999999,
            'PAID'   => 'Y',
        ];

        $this->content($response, 'netbanking_sib_authorize');

        $aes = new Base\AESCrypto(
            AES::MODE_ECB,
            $this->app['config']->get('gateway.mozart.netbanking_sib_test_hash_secret')
        );

        // this encrypted value is never used as the pay_verify response from mozart is mocked
        $content = [
              "ENC_STR" => base64_encode($aes->encryptString(http_build_query($response)))
        ];

        $request = [
            'url'          => $queryFields['ShoppingMallTranFG_RU'],
            'content'      => $content,
            'method'       => 'post',
        ];

        return $this->makePostResponse($request);
    }
}