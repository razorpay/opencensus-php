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
    const ACCESS_TOKEN          = '8c31d80b-83ed-4f52-8377-71301790ccaa';
    const ACCESS_TOKEN_EXPIRY   = '2025-09-21T14:18:06';
    const REFRESH_TOKEN         = '8c31d80b-83ed-4f52-8377-71301790ccaa';
    const REFRESH_TOKEN_EXPIRY  = '2025-09-21T14:18:06';

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

            $response['checksum'] = $this->generateHash($response);
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
        $input = json_decode($input, true);

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

        $response['checksum'] = $this->generateHash($response);

        return $this->makeResponse($response);
    }

    public function otpGenerate($input)
    {
        $input = json_decode($input, true);

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
        $input = json_decode($input, true);

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
        $input = json_decode($input, true);

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
            ResponseFields::ACCESS_TOKEN         => self::ACCESS_TOKEN,
            ResponseFields::ACCESS_TOKEN_EXPIRY  => self::ACCESS_TOKEN_EXPIRY,
            ResponseFields::REFRESH_TOKEN        => self::REFRESH_TOKEN,
            ResponseFields::REFRESH_TOKEN_EXPIRY => self::REFRESH_TOKEN_EXPIRY,
        ];

        return $this->makeResponse($response);
    }

    public function debitWallet($input)
    {
        $input = json_decode($input, true);

        $this->validateActionInput($input, 'debitWallet');

        if ($input['accessToken'] === self::ACCESS_TOKEN)
        {
            $response = array(
                ResponseFields::TXN_ID          => $this->getTxnId(),
                ResponseFields::MERCHANT_TXN_ID => $this->getMerchantTxnId(),
                ResponseFields::AMOUNT          => '123',
                ResponseFields::STATUS          => Freecharge\Status::DEBIT_SUCCESS,
                ResponseFields::ERROR_CODE      => null,
                ResponseFields::ERROR_MESSAGE   => null,
            );

            $response[ResponseFields::CHECKSUM] = $this->generateHash($response);

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
        return random_integer(11);
    }

    protected function getMerchantTxnId()
    {
        return random_integer(11);
    }

    protected function getRefundMerchantTxnId()
    {
        return random_integer(5);
    }

    protected function makeResponse($json)
    {
        $response = parent::makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
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
