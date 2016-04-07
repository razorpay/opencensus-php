<?php

namespace Gateway\Wallet\Payumoney\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Wallet\Payumoney;
use Models\Card;

class Server extends Base\Mock\Server
{
    protected $accessToken = '8c31d80b-83ed-4f52-8377-71301790ccaa';

    protected $authHeader = 'Bearer 8c31d80b-83ed-4f52-8377-71301790ccaa';

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($this->mockRequest['content']);

        $response = array(
            'status'    => 0,
            'message'   => 'Transaction status',
            'result'    => array(
                array(
                    'amount'                => 5,
                    'transactionDirection'  => -1,
                    'paymentId'             => 1110561680,
                    'status'                => 'success',
                    'merchantTransactionId' => $this->mockRequest['content']['merchantTransactionId'],
                    'completedOn'           => 1459219419000
                )
            ),
            'errorCode' => null
        );

        return $this->makeResponse($response);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $refundResponse = array(
            'status'    => 0,
            'rows'      => 0,
            'message'   => 'Refund Initiated',
            'result'    => 13797,
            'guid'      => null,
            'sessionId' => null,
            'errorCode' => null
        );

        return $this->makeResponse($refundResponse);
    }

    public function registerUser($input)
    {
        $this->validateActionInput($input, 'registeruser');

        $mobile = $input['mobile'];

        $response = array(
            'status' => 0,
            'message' => 'SMS sent to ' . substr_replace($mobile, 'xxxxxx', 1, -3),
            'errorCode' => null,
            'guid' => null,
            'result' => null,
            'userVaultDTO' => null
        );

        return $this->makeResponse($response);
    }

    public function otpSubmit($input)
    {
        $this->validateActionInput($input, 'otpsubmit');

        if ($input['otp'] === '123456')
        {
            $response = array(
                'status' => 0,
                'message' => 'access token',
                'errorCode' => null,
                'guid' => null,
                'result' => array(
                    'headers' => array(
                        'Cache-Control' => array(
                            'no-store'
                        ),
                        'Pragma' => array(
                            'no-cache'
                        )
                    ),
                    'body' => array(
                        'access_token' => $this->accessToken,
                        'token_type' => 'bearer',
                        'refresh_token' => 'bfd54a5a-d10a-4e5f-ad51-1d0fd310a4d1',
                        'expires_in' => 7690192,
                        'scope' => 'read trust write'
                    ),
                    'statusCode' => 'OK'
                ),
                'userVaultDTO' => array(
                    'availableAmount' => 22,
                    'minLimit' => null,
                    'maxLimit' => null
                )
            );

            return $this->makeResponse($response);
        }

        $response = array(
            'status'        => -1,
            'message'       => 'Verification code has expired - Please generate a new verification code',
            'errorCode'     => '3010008',
            'guid'          => 'nnhg6878duq7ihb2dtfj6apff',
            'result'        => null,
            'userVaultDTO'  => null
        );

        return $this->makeResponse($response);
    }

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateActionInput($input, 'authorize');

        if (!isset($this->mockRequest['headers']['Authorization']))
        {
            $response = array(
                'error'              => 'unauthorized',
                'error_description'  => 'Full authentication is required to access this resource',
            );

            return $this->makeResponse($response);
        }

        if ($this->mockRequest['headers']['Authorization'] === $this->authHeader)
        {
            $response = array(
                'status'        => 0,
                'message'       => 'Use wallet successful',
                'errorCode'     => null,
                'guid'          => null,
                'result'        => 1110562955,
                'userVaultDTO'  => null
            );

            return $this->makeResponse($response);
        }

        $response = array(
            'error'              => 'invalid_token',
            'error_description'  => 'Invalid access token: ' . $this->makeResponse($response),
        );

        return $this->makeResponse($response);
    }

    protected function getPayuTxnId()
    {
        return mt_rand(1000000000, 2567890123);
    }

    protected function getPayuRefundId()
    {
        return mt_rand(10000, 35000);
    }

    protected function makeResponse($json)
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
