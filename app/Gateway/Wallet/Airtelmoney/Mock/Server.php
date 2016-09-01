<?php

namespace RZP\Gateway\Wallet\Airtelmoney\Mock;

use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Gateway\Wallet\Airtelmoney;

class Server extends Base\Mock\Server
{
    protected $accessToken = '8c31d80b-83ed-4f52-8377-71301790ccaa';

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($this->mockRequest['content']);

        $response = array(
            'status'    => 0,
            'message'   => 'Transaction status',
            'result'    => array(
                array(
                    'amount'                => 500,
                    'transactionDirection'  => -1,
                    'paymentId'             => 1110561680,
                    'status'                => 'success',
                    'merchantTransactionId' => $this->mockRequest['content']['merchantTransactionId'],
                    'completedOn'           => strtotime('-30 mins')
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

    public function debitWallet($input)
    {
        $this->validateActionInput($input, 'debitWallet');

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
            'error_description'  => 'Invalid access token: ' . $this->mockRequest['headers']['Authorization'],
        );

        return $this->makeResponse($response);
    }

    protected function getArtlTxnId()
    {
        return mt_rand(1000000000, 2567890123);
    }

    protected function getArtlRefundId()
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
