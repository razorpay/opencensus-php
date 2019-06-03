<?php

namespace RZP\Gateway\Mpi\Enstage\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Mpi\Enstage\Url;
use RZP\Gateway\Mpi\Enstage\Field;
use RZP\Gateway\Mpi\Enstage\Gateway;
use RZP\Gateway\Mpi\Enstage\Constant;
use RZP\Gateway\Mpi\Enstage\Action;

class Server extends Base\Mock\Server
{
    protected $gateway;

    public function __construct()
    {
        parent::__construct();

        $this->gateway =  new Gateway();

        $this->gateway->setMode(Mode::TEST);
    }

    public function otpGenerate($input)
    {
        $content = json_decode($input, true);

        $this->setAction(ACTION::OTP_GENERATE);

        $this->validateActionInput($content, $this->action);

        $cardDetails = $this->decryptCardDetails($content[Field::CARD_DETAILS]);

        $this->validateActionInput($cardDetails, 'cardDetails');

        $response = [
            Field::VERSION              => Constant::VERSION,
            Field::MERCHANT_TXN_ID      => $content[Field::MERCHANT_TXN_ID],
            Field::ACS_TXN_ID           => 'TVBJWElENXdYN3ZVOGlQMm1FM2Y',
            Field::RESPONSE_CODE        => '000',
            Field::RES_DESC             => 'Success',
            Field::ACS_VERIFICATION_URL => Url::LIVE_DOMAIN . Url::OTP_SUBMIT,
            Field::ADDITIONAL_DATA_REQ  => [
            ],
            Field::SECRET               => 'random_secret_id',
        ];

        $this->content($response, Action::OTP_GENERATE);

        if ($response[Field::RESPONSE_CODE] === '000')
        {
            $response[Field::MESSAGE_HASH] = $this->getOtpSentResponseMessageHash($response);
        }

        unset($response[Field::SECRET]);

        $jsonResponse = $this->makeJsonResponse($response);

        return $jsonResponse;
    }

    public function otpResend($input)
    {
        $content = json_decode($input, true);

        $this->setAction(ACTION::OTP_RESEND);

        $this->validateActionInput($content, $this->action);

        $response = [
            Field::VERSION                  => Constant::VERSION,
            Field::MERCHANT_TXN_ID          => $content[Field::MERCHANT_TXN_ID],
            Field::ACS_TXN_ID               => $content[Field::ACS_TXN_ID],
            Field::RESPONSE_CODE            => '000',
            Field::RES_DESC                 => 'Success',
            Field::OTP_RESEND_COUNT_LEFT    => 2,
            Field::SECRET                   => 'random_secret_id',
        ];

        $this->content($response, Action::OTP_RESEND);

        $response[Field::MESSAGE_HASH] = $this->getOtpResendResponseMessageHash($response);

        unset($response[Field::SECRET]);

        $jsonResponse = $this->makeJsonResponse($response);

        return $jsonResponse;
    }

    public function otpSubmit($input)
    {
        $content = json_decode($input, true);

        $this->setAction(ACTION::OTP_SUBMIT);

        $this->validateActionInput($content, $this->action);

        $this->validateOtpToken($content[Field::OTP_TOKEN]);

        $response = [
            Field::VERSION              => Constant::VERSION,
            Field::MERCHANT_TXN_ID      => $content[Field::MERCHANT_TXN_ID],
            Field::ACS_TXN_ID           => 'TVBJWElENXdYN3ZVOGlQMm1FM2Y',
            Field::RESPONSE_CODE        => '000',
            Field::RES_DESC             => 'Success',
            Field::ACS_STATUS           => 'Y',
            Field::ACC_ID               => '201611181642092180hE7iE9oZ',
            Field::CAVV                 => 'AAABA5IAAGmTFAYTlAAAAAAAAAA',
            Field::ECI                  => '05',
            Field::XID                  => $content[Field::MERCHANT_TXN_ID],
            Field::SECRET               => 'random_secret_id',
        ];

        $this->content($response, Action::OTP_SUBMIT);

        $response[Field::MESSAGE_HASH] = $this->getOtpValidateResponseHash($response);

        unset($response[Field::SECRET]);

        $jsonResponse = $this->makeJsonResponse($response);

        return $jsonResponse;
    }

    protected function makeJsonResponse($response)
    {
        $json = json_encode($response);

        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function getOtpSentResponseMessageHash($content)
    {
        $hash = [
            Field::MERCHANT_TXN_ID => $content[Field::MERCHANT_TXN_ID],
            Field::ACS_TXN_ID      => $content[Field::ACS_TXN_ID],
            Field::RESPONSE_CODE   => $content[Field::RESPONSE_CODE],
            Field::SECRET          => $content[Field::SECRET],
        ];

        return $this->gateway->generateHash($hash);
    }

    protected function getOtpValidateResponseHash($content)
    {
        $hash = [
            Field::ACS_TXN_ID      => $content[Field::ACS_TXN_ID],
            Field::ACC_ID          => $content[Field::ACC_ID],
            Field::CAVV            => $content[Field::CAVV],
            Field::ECI             => $content[Field::ECI],
            Field::SECRET          => $content[Field::SECRET],
        ];

        return $this->gateway->generateHash($hash);
    }

    protected function getOtpResendResponseMessageHash($content)
    {
        $hash = [
            Field::MERCHANT_TXN_ID => $content[Field::MERCHANT_TXN_ID],
            Field::ACS_TXN_ID      => $content[Field::ACS_TXN_ID],
            Field::RESPONSE_CODE   => $content[Field::RESPONSE_CODE],
            Field::SECRET          => $content[Field::SECRET],
        ];

        return $this->gateway->generateHash($hash);
    }

    protected function validateOtpToken($data)
    {
        $decryptedString = $this->gateway->decrypt($data);

        if ($decryptedString === '999999')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
                null,
                001
            );
        }

        return true;
    }

    protected function decryptCardDetails($cardDetails)
    {
        $decryptedJson = $this->gateway->decrypt($cardDetails);

        $cardDetails = json_decode($decryptedJson, true);

        return $cardDetails;
    }
}
