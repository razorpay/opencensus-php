<?php

namespace RZP\Gateway\Wallet\Freecharge\Mock;

use Carbon\Carbon;

use RZP\Constants\HashAlgo;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Gateway\Wallet\Freecharge;
use RZP\Gateway\Wallet\Freecharge\ResponseFields;
use RZP\Gateway\Wallet\Freecharge\RequestFields;
use RZP\Models\Payment;

class Server extends Base\Mock\Server
{
    protected $accessToken          = '8c31d80b-83ed-4f52-8377-71301790ccaa';
    protected $accessTokenExpiry    = '2017-09-21T14:18:06';
    protected $refreshToken         = '8c31d80b-83ed-4f52-8377-71301790ccaa';
    protected $refreshTokenExpiry   = '2017-09-21T14:18:06';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateActionInput($input);

        $callbackUrl = $input['callbackUrl'];

        return \Redirect::to($callbackUrl);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input, 'verify');

        // We send Freecharge Transaction ID if it exists,
        // It exists if freecharge acknowledged our TxnId
        // It can mark our transaction as failure later though
        if (isset($input[RequestFields::TXN_ID]) === true)
        {
            $response = [
                ResponseFields::MERCHANT_TXN_ID => $input[RequestFields::MERCHANT_TXN_ID],
                ResponseFields::TXN_ID          => $input[RequestFields::TXN_ID],
                ResponseFields::AMOUNT          => '50000',
                ResponseFields::STATUS          => Freecharge\Status::TRANSACTION_SUCCESS,
            ];

            $response['checksum'] = $this->sortKeysAndGenerateHash($response);
        }
        else
        {
            $errMsg = Freecharge\ResponseCode::getResponseMessage('E008');

            $response = [
                ResponseFields::ERROR_CODE => 'E008',
                ResponseFields::ERROR_MESSAGE => $errMsg,
            ];
        }

        return $this->makeResponse($response);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $response = array(
            ResponseFields::STATUS                 => Freecharge\Status::REFUND_SUCCESS,
            ResponseFields::REFUND_TXN_ID          => $this->getRefundTxnId(),
            ResponseFIelds::REFUND_MERCHANT_TXN_ID => $this->getRefundMerchantTxnId(),
            ResponseFields::REFUNDED_AMOUNT        => '100',
            ResponseFields::ERROR_CODE             => null,
            ResponseFields::ERROR_MESSAGE          => null,
        );

        $response['checksum'] = $this->sortKeysAndGenerateHash($response);

        return $this->makeResponse($response);
    }

    public function otpGenerate($input)
    {
        $this->validateActionInput($input, 'otpGenerate');

        $mobile = $input[RequestFields::MOBILE_NUMBER];

        $response = array(
            ResponseFields::OTP_ID         => '1asda2345',
            ResponseFields::REDIRECT_URL   => '',
            ResponseFields::IS_IVR_ENABLED => 'false',
            ResponseFields::STATUS         => 'VERIFY',
        );

        return $this->makeResponse($response);
    }

    public function otpResend($input)
    {
        $this->validateActionInput($input, 'otpResend');

        $response = [
            ResponseFields::OTP_ID => '12345a',
        ];

        return $this->makeResponse($response);
    }

    public function getBalance($input)
    {
        $this->validateActionInput($input, 'getBalance');

        $response = [
            ResponseFields::WALLET_BALANCE     => '500',
        ];

        return $this->makeResponse($response);
    }

    public function otpSubmit($input)
    {
        $this->validateActionInput($input, 'otpSubmit');

        if ($input[RequestFields::OTP] === Otp::EXPIRED)
        {
            $response = array(
                ResponseFields::ERROR_MESSAGE  => Freecharge\ResponseCode::getResponseMessage('E701'),
                ResponseFields::ERROR_CODE     => 'E701',
            );

            $response = $this->makeResponse($response);

            $response->setStatusCode(202);

            return $response;
        }

        if ($input[RequestFields::OTP] === Otp::INCORRECT)
        {
            $response = array(
                ResponseFields::ERROR_MESSAGE  => Freecharge\ResponseCode::getResponseMessage('E702'),
                ResponseFields::ERROR_CODE => 'E702',
            );

            $response = $this->makeResponse($response);

            $response->setStatusCode(202);

            return $response;
        }

        $response = [
            ResponseFields::ACCESS_TOKEN         => $this->accessToken,
            ResponseFields::ACCESS_TOKEN_EXPIRY  => $this->accessTokenExpiry,
            ResponseFields::REFRESH_TOKEN        => $this->refreshToken,
            ResponseFields::REFRESH_TOKEN_EXPIRY => $this->refreshTokenExpiry,
        ];

        return $this->makeResponse($response);
    }

    public function debitWallet($input)
    {
        $this->validateActionInput($input, 'debitWallet');

        if ($this->mockRequest['content']['accessToken'] === $this->accessToken)
        {
            $response = array(
                ResponseFields::TXN_ID          => $this->getTxnId(),
                ResponseFields::MERCHANT_TXN_ID => $this->getMerchantTxnId(),
                ResponseFields::AMOUNT          => '123',
                ResponseFields::STATUS          => Freecharge\Status::DEBIT_SUCCESS,
                ResponseFields::ERROR_CODE      => null,
                ResponseFields::ERROR_MESSAGE   => null,
            );

            $response[ResponseFields::CHECKSUM] = $this->sortKeysAndGenerateHash($response);

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
        $response = parent::makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function sortKeysAndGenerateHash(array $response)
    {
        foreach ($response as $key => $value)
        {
            if ($value === null or $value === "")
            {
                unset($response[$key]);
            }
        }

        ksort($response);

        $secretKey = $this->app->config['gateway']['wallet_freecharge']['test_hash_secret'];

        $hashString = json_encode($response).$secretKey;

        return hash(HashAlgo::SHA256, $hashString);
    }

    /*
     * Freecharge requires us to create a refund entity and send its Id before it initiates a refund.
     * Mocks presently generates a unique Id/
     */
    protected function getRefundTxnId()
    {
        return uniqid();
    }
}
