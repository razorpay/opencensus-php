<?php

namespace RZP\Gateway\Wallet\Freecharge\Mock;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Gateway\Wallet\Freecharge;
use RZP\Models\Base\UniqueIdentity;
use RZP\Models\Payment;

class Server extends Base\Mock\Server
{
    protected $accessToken          = '8c31d80b-83ed-4f52-8377-71301790ccaa';
    protected $accessTokenExpiry    = '3600';
    protected $refreshToken         = '8c31d80b-83ed-4f52-8377-71301790ccaa';
    protected $refreshTokenExpiry   = '3600';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateActionInput($input);

        $this->topupRequest = $input;

        $callbackUrl = $input['callbackUrl'];

        $request = array(
            'status'        => 'COMPLETED',
            'metadata'      => '',
            'walletBalance' => '1232',
        );

        $request['checksum'] = $this->sortKeysAndGenerateHash($request);

        return \Redirect::to($callbackUrl);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($this->mockRequest['content'], 'verify');
        $content = $this->mockRequest['content'];

        $response = [
            'merchantTxnId'     => $content['merchantTxnId'],
            'txnId'             => $content['txnId'],
            'amount'            => '50000',
            'status'            => Freecharge\Status::TRANSACTION_SUCCESS,
        ];

        $response['checksum'] = $this->sortKeysAndGenerateHash($response);

        return $this->makeResponse($response);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $response = array(
            'status'                => Freecharge\Status::REFUND_SUCCESS,
            'refundTxnId'           => $this->getRefundTxnId(),
            'refundMerchantTxnId'   => $this->getRefundMerchantTxnId(),
            'refundedAmount'        => '100',
            'errorCode'             => null,
            'errorMessage'          => null,
        );

        $response['checksum'] = $this->sortKeysAndGenerateHash($response);

        return $this->makeResponse($response);
    }

    public function otpGenerate($input)
    {
        $this->validateActionInput($input, 'otpGenerate');

        $mobile = $input['mobileNumber'];

        $response = array(
            'otpId'         => '1asda2345',
            'redirectUrl'   => '',
            'isIvrEnabled'  => 'false',
            'status'        => 'VERIFY',
        );

        return $this->makeResponse($response);
    }

    public function otpResend($input)
    {
        $this->validateActionInput($input, 'otpResend');

        $response = [
            'otpId' => '12345a',
        ];

        return $this->makeResponse($response);
    }

    public function getBalance($input)
    {
        $this->validateActionInput($input, 'getBalance');

        $response = [
            'walletBalance'     => '500',
        ];

        return $this->makeResponse($response);
    }

    public function otpSubmit($input)
    {
        $this->validateActionInput($input, 'otpSubmit');

        if ($input['otp'] === Otp::EXPIRED)
        {
            $response = array(
                'errorMessage'  => Freecharge\ResponseCode::getResponseMessage('E701'),
                'errorCode'     => 'E701',
            );
            $response = $this->makeResponse($response);
            $response->setStatusCode(202);
            return $response;
        }

        if ($input['otp'] === Otp::INCORRECT)
        {
            $response = array(
                'errorMessage'  => Freecharge\ResponseCode::getResponseMessage('E702'),
                'errorCode'     => 'E702',
            );
            $response = $this->makeResponse($response);
            $response->setStatusCode(202);
            return $response;
        }

        $response = [
            'accessToken'           => $this->accessToken,
            'accessTokenExpiry'     => $this->accessTokenExpiry,
            'refreshToken'          => $this->refreshToken,
            'refreshTokenExpiry'    => $this->refreshTokenExpiry,
        ];

        return $this->makeResponse($response);
    }

    public function topupRedirect($input)
    {
        $this->validateActionInput($input, 'topupRedirect');

        $this->topupRequest = $input;

        $callbackUrl = $input['callbackUrl'];

        $response = array(
            'status'        => 'COMPLETED',
            'metadata'      => 'dummy',
            'walletBalance' => '1232',
        );

        $response['checksum'] = $this->sortKeysAndGenerateHash($response);

        return $this->makeResponse($response);
    }

    public function debitWallet($input)
    {
        $this->validateActionInput($input, 'debitWallet');

        if ($this->mockRequest['content']['accessToken'] === $this->accessToken)
        {
            $response = array(
                'txnId'         => $this->getTxnId(),
                'merchantTxnId' => $this->getMerchantTxnId(),
                'amount'        => '123',
                'status'        => 'COMPLETED',
                'errorCode'     => null,
                'errorMessage'  => null,
            );

            $response['checksum'] = $this->sortKeysAndGenerateHash($response);

            return $this->makeResponse($response);
        }

        $response = array(
            'error'              => 'invalid_token',
            'error_description'  => 'Invalid access token: ' . $this->mockRequest['headers']['Authorization'],
        );

        return $this->makeResponse($response);
    }

    protected function getTxnId()
    {
        return mt_rand(1000000000, 2567890123);
    }

    protected function getMerchantTxnId()
    {
        return mt_rand(10000, 35000);
    }

    protected function getRefundMerchantTxnId()
    {
        return mt_rand(5000, 10000);
    }

    protected function makeResponse($json)
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function sortKeysAndGenerateHash(array $response)
    {
        foreach ($response as $key => $value)
        {
            if($value === null || $value === "")
            {
                unset($response[$key]);
            }
        }
        ksort($response);

        $secretKey = $this->app->config['gateway']['wallet_freecharge']['test_hash_secret'];

        $hashString = json_encode($response).$secretKey;

        return hash('sha256', $hashString);
    }

    /*
     * Freecharge requires us to create a refund entity and send its Id before it initiates a refund.
     * Mocks presently generates a unique Id/
     *
     */
    protected function getRefundTxnId()
    {
        return UniqueIdentity::generateUniqueId();
    }
}
