<?php

namespace RZP\Gateway\Mozart\Mock;

use Str;
use RZP\App;
use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;

class Server extends Base\Mock\Server
{

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
        $VerifyObj = new VerifyData();

        return $this->processMockResponse($input, $VerifyObj, 'verify');
    }

    public function refund($input)
    {
        $refundObj = new RefundData();

        return $this->processMockResponse($input, $refundObj, 'refund');
    }

    public function verifyRefund($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        $response = [
            'data' =>
                [
                    'enqinfo' => [
                        '0' => [
                            'DEALID' => 'CS905114097404',
                            'ERRORDESCRIPTION' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                            'Key' => $entities['terminal']['gateway_secure_secret'],
                            'ORDERNO' => '104',
                            'REQUESTID' => '1234',
                            'RESPONSECODE' => '0'
                        ]
                    ],
                    'received' => true,
                    'requeryid' => '1234',
                    'reqid' => 'RZP200219195445345',
                    'rescode' => '00',
                    'rqtype' => 'CAN',
                    'status' => 'verification_successful',
                    'valkey' => $entities['terminal']['gateway_secure_secret'],
                    'errdesc' => 'SUCCESS',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'verify_refund');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
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

    protected function getUpiAirtelSecret()
    {
        return 'u9FDS3hNIQBPVNfb';
    }

}
