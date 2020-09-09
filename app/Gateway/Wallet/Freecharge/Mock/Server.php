<?php

namespace RZP\Gateway\Wallet\Freecharge\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Base as WalletBase;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Gateway\Wallet\Freecharge;
use RZP\Gateway\Wallet\Freecharge\RequestFields;
use RZP\Gateway\Wallet\Freecharge\ResponseFields;
use RZP\Models\Payment;

class Server extends Base\Mock\Server
{
    const ACCESS_TOKEN          = '8c31d80b-83ed-4f52-8377-71301790ccaa';
    const ACCESS_TOKEN_EXPIRY   = '2025-09-21T14:18:06';
    const REFRESH_TOKEN         = '8c31d80b-83ed-4f52-8377-71301790ccaa';
    const REFRESH_TOKEN_EXPIRY  = '2025-09-21T14:18:06';
    const INVALID_GATEWAY_TOKEN = 'invalid-gateway-token';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateActionInput($input);

        $content = [
            'status'        => 'COMPLETED',
            'walletBalance' => '1234',
            'errorMessage'  => 'SUCCESS',
            'metadata'      => 'dummy',
        ];

        $content['checksum'] = $this->generateHash($content);

        $callbackUrl = $input['surl'];

        $callbackUrl .= '?' . http_build_query($content);

        return \Redirect::to($callbackUrl);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input, 'verify');

        $merchantTxnId = $input[RequestFields::MERCHANT_TXN_ID];

        $response = [
            ResponseFields::MERCHANT_TXN_ID => $merchantTxnId,
            ResponseFields::STATUS          => 'SUCCESS',
        ];

        // To verify transaction records
        if (($input[RequestFields::TXN_TYPE] === Freecharge\TxnType::CANCELLATION_REFUND))
        {
            $response[ResponseFields::TXN_ID] = 'dummyFreechargeRefundId';

            // dummy amount as we do not validate it for
            // verification of refund records
            $response[ResponseFields::AMOUNT] = 100;

            // To throw failed refund data flow
            switch($merchantTxnId)
            {
                case 'failedRefund12':
                    $response[ResponseFields::STATUS] = 'FAILED';
                    break;

                case 'pendingRefund1':
                    $response[ResponseFields::STATUS] = 'PENDING';
                    break;

                case 'initiatedRfnd1':
                    $response[ResponseFields::STATUS] = 'INITIATED';
                    break;

                case 'failedRefund13':
                    // throw transaction does not exist error
                    $response = $this->getErrorResponse('E008');
                    return $response;

                default:
                    $response[ResponseFields::STATUS] = 'SUCCESS';
            }

            $response['checksum'] = $this->generateHash($response);
        }
        else if (($input[RequestFields::TXN_TYPE] === Freecharge\TxnType::CUSTOMER_PAYMENT))
        {
            if (isset($input[RequestFields::TXN_ID]) === false)
            {
                return $this->getErrorResponse('E008');
            }

            // We send Freecharge Transaction ID if it exists,
            // It exists if freecharge acknowledged our TxnId
            // It can mark our transaction as failure later though
            $response[ResponseFields::TXN_ID] = $input[RequestFields::TXN_ID];

            $wallet = (new WalletBase\Repository)->fetchWalletByPaymentId(
                $merchantTxnId);

            $response[ResponseFields::AMOUNT] = $wallet['amount'];

            $response['checksum'] = $this->generateHash($response);
        }

        $this->content($response,'verify');

        return $this->makeResponse($response);
    }

    public function refund($input)
    {
        $input = json_decode($input, true);

        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $response = [
            ResponseFields::STATUS                 => Freecharge\Status::TRANSACTION_INITIATED,
            ResponseFields::REFUND_TXN_ID          => random_integer(5),
            ResponseFields::REFUND_MERCHANT_TXN_ID => $input[RequestFields::REFUND_MERCHANT_TXN_ID],
            ResponseFields::REFUNDED_AMOUNT        => $input[RequestFields::REFUND_AMOUNT],
            ResponseFields::ERROR_CODE             => null,
            ResponseFields::ERROR_MESSAGE          => null,
        ];

        $response['checksum'] = $this->generateHash($response);

        if ($input['refundAmount'] === '1')
        {
            return $this->getErrorResponse('E018');
        }

        $this->content($response,'refund');

        return $this->makeResponse($response);
    }

    public function otpGenerate($input)
    {
        $input = json_decode($input, true);

        $this->validateActionInput($input, 'otpGenerate');

        $content = [
            ResponseFields::OTP_ID         => '1daea2345',
            ResponseFields::REDIRECT_URL   => '',
            ResponseFields::IS_IVR_ENABLED => 'false',
            ResponseFields::STATUS         => 'VERIFY',
        ];

        $this->content($content);

        return $this->makePostResponse($content);
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

        if ($input[RequestFields::ACCESS_TOKEN] === self::INVALID_GATEWAY_TOKEN)
        {
            return $this->getErrorResponse('E620');
        }

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
            return $this->getErrorResponse('E701');
        }

        if ($input[RequestFields::OTP] === Otp::INCORRECT)
        {
            return $this->getErrorResponse('E702');
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

        if (($input['accessToken'] === self::ACCESS_TOKEN) and
            ($input['amount'] != '199.99'))
        {
            $response = array(
                ResponseFields::TXN_ID          => random_integer(11),
                ResponseFields::MERCHANT_TXN_ID => random_integer(11),
                ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
                ResponseFields::STATUS          => Freecharge\Status::DEBIT_SUCCESS,
                ResponseFields::ERROR_CODE      => null,
                ResponseFields::ERROR_MESSAGE   => null,
            );

            $response[ResponseFields::CHECKSUM] = $this->generateHash($response);

            return $this->makeResponse($response);
        }

        $response = array(
            ResponseFields::TXN_ID          => random_integer(11),
            ResponseFields::MERCHANT_TXN_ID => random_integer(11),
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            ResponseFields::STATUS          => Freecharge\Status::DEBIT_FAILED,
            ResponseFields::ERROR_CODE      => 'E104',
            ResponseFields::ERROR_MESSAGE   => 'Amount not parsable',
        );

        $response[ResponseFields::CHECKSUM] = $this->generateHash($response);

        $response = $this->makeResponse($response);

        $response->setStatusCode(202);

        return $response;
    }

    protected function makeResponse($json)
    {
        $response = parent::makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function getErrorResponse($errCode)
    {
        $errMsg = Freecharge\ResponseCode::getResponseMessage($errCode);

        $response = [
            ResponseFields::ERROR_CODE    => $errCode,
            ResponseFields::ERROR_MESSAGE => $errMsg,
        ];

        $response = $this->makeResponse($response);

        $response->setStatusCode(202);

        return $response;
    }

    protected function makePostResponse($content)
    {
        $response = $this->makeResponse($content);

        if (empty($content[ResponseFields::ERROR_CODE]) === false)
        {
            $response->setStatusCode(202);
        }
        else
        {
            $response->setStatusCode(200);
        }

        return $response;
    }
}
