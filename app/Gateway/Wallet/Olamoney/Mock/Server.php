<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Gateway\Wallet\Olamoney;
use RZP\Gateway\Wallet\Olamoney\Command;
use RZP\Gateway\Wallet\Olamoney\RequestFields;
use RZP\Gateway\Wallet\Olamoney\ResponseFields;


class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $bill = RequestFields::BILL;

        $input[$bill] = json_decode(base64_decode(urldecode($input[$bill])), true);

        $this->validateActionInput($input, $input[$bill][RequestFields::COMMAND]);

        $bill = $input['bill'];

        $content = array(
            ResponseFields::TYPE              => 'credit',
            ResponseFields::STATUS            => 'success',
            ResponseFields::MERCHANT_BILL_ID  => $bill[RequestFields::MERCHANT_REFERENCE_ID],
            ResponseFields::TRANSACTION_ID    => 'ola_txn_id',
            ResponseFields::AMOUNT            => $bill[RequestFields::AMOUNT],
            ResponseFields::COMMENTS          => $bill[RequestFields::COMMENTS],
            ResponseFields::UDF               => $bill[RequestFields::UDF],
            ResponseFields::TIMESTAMP         => time(),
        );

        $content[ResponseFields::HASH] = $this->generateHash($content);

        $paymentId = $input['paymentId'];

        $payment = (new Payment\Repository)->find($paymentId);

        $publicId = $payment->getPublicId();

        $secret = $this->app->config->get('app.key');

        $hash = hash_hmac('sha1', $publicId, $secret);

        $url = Route::getUrlWithPublicCallbackAuth(['id' => $publicId, 'hash' => $hash]);

        $url .= '?' . http_build_query($content);

        return \Redirect::to($url);
    }

    public function otpGenerate($input)
    {
        $this->validateActionInput($input, 'otpGenerate');

        $responseContent = array(
            ResponseFields::STATUS    => 'success',
            ResponseFields::MESSAGE   => '',
        );

        return $this->makeResponse($responseContent);
    }

    public function otpSubmit($input)
    {
        $this->validateActionInput($input, 'otpSubmit');

        $responseContent = array(
            ResponseFields::STATUS          => 'success',
            ResponseFields::MESSAGE         => '',
            ResponseFields::ACCESS_TOKEN    => 'success_access_token',
            ResponseFields::REFRESH_TOKEN   => 'success_refresh_token',
        );

        if ($input[RequestFields::OTP] === Otp::INCORRECT)
        {
            $responseContent = array(
                ResponseFields::STATUS      => 'FAILED',
                ResponseFields::MESSAGE     => 'Invalid OTP',
            );
        }
        else if ($input[RequestFields::OTP] === Otp::INSUFFICIENT_BALANCE)
        {
            $responseContent = array(
                ResponseFields::STATUS          => 'success',
                ResponseFields::MESSAGE         => '',
                ResponseFields::ACCESS_TOKEN    => 'insufficient_balance_access_token',
                ResponseFields::REFRESH_TOKEN   => 'insufficient_balance_refresh_token',
            );
        }

        return $this->makeResponse($responseContent);
    }

    public function getBalance($input)
    {
        $input = json_decode($input, true);

        $this->validateActionInput($input, 'checkBalance');

        $balance = 999999.00;

        if ($input[RequestFields::USER_ACCESS_TOKEN] === 'insufficient_balance_access_token')
        {
            $balance = 0;
        }

        $responseContent = array(
            ResponseFields::STATUS          => 'success',
            ResponseFields::COMMENTS        => 'olaComments',
            ResponseFields::AMOUNT          => $balance,
            ResponseFields::BALANCE_TYPE    => 'olaBalanceType',
        );

        return $this->makeResponse($responseContent);
    }

    public function topupWallet($input)
    {
        $this->validateActionInput($input, 'topupWallet');

        $this->topupRequest = $input;

        $response = array(
            'status' => 'success',
        );

        return $this->makeResponse($response);
    }

    public function debitWallet($input)
    {
        $input = json_decode($input, true);

        $this->validateActionInput($input, Command::DEBIT);

        $udf = json_encode([RequestFields::MERCHANT_DISPLAY_NAME => 'test_merchant_display_name']);

        $responseContent = array(
            ResponseFields::TYPE                    => 'debit',
            ResponseFields::STATUS                  => 'success',
            ResponseFields::MERCHANT_BILL_ID        => $input[RequestFields::UNIQUE_ID],
            ResponseFields::TRANSACTION_ID          => 'olaUniqTxnId',
            ResponseFields::AMOUNT                  => $input[RequestFields::AMOUNT],
            ResponseFields::COMMENTS                => $input[RequestFields::COMMENTS],
            ResponseFields::UDF                     => $input[RequestFields::UDF],
            ResponseFields::IS_CASHBACK_ATTEMPTED   => 'NA',
            ResponseFields::IS_CASHBACK_SUCCESSFUL  => 'NA',
            ResponseFields::TIMESTAMP               => time(),
        );

        $responseContent[ResponseFields::HASH] = $this->generateHash($responseContent);

        $this->content($responseContent);

        return $this->makeResponse($responseContent);
    }

    public function refund($input)
    {
        $input = json_decode($input, true);

        parent::refund($input);

        $this->validateActionInput($input, Command::REFUND);

        $responseContent = array(
            ResponseFields::TYPE              => 'refund',
            ResponseFields::STATUS            => 'success',
            ResponseFields::TRANSACTION_ID    => 'bgho5botne16',
            ResponseFields::MERCHANT_BILL_ID  => 'cd1501cea88e4654898d8b2a266bc467',
            ResponseFields::AMOUNT            => '20.0',
            ResponseFields::TIMESTAMP         => '1439473847354',
            ResponseFields::COMMENTS          => 'test',
            ResponseFields::UDF               => 'test',
        );

        return $this->makeResponse($responseContent);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($this->mockRequest['content']);

        $response = array(
            ResponseFields::STATUS          => 'completed',
            ResponseFields::AMOUNT          => '500.00',
            ResponseFields::TYPE            => 'debit',
            ResponseFields::UNIQUE_BILL_ID  => 'bgho5botne16',
        );

        return $this->makeResponse($response);
    }

    protected function makeResponse($json, $content_type = 'application/json; charset=UTF-8')
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', $content_type);
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
