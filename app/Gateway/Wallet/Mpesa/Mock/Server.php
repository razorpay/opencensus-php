<?php

namespace RZP\Gateway\Wallet\Mpesa\Mock;

use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;
use RZP\Exception\ServerErrorException;
use RZP\Gateway\Wallet\Mpesa\Constants;
use RZP\Gateway\Wallet\Mpesa\SoapAction;
use RZP\Gateway\Wallet\Mpesa\StatusCode;
use RZP\Gateway\Wallet\Mpesa\RequestFields;
use RZP\Gateway\Wallet\Mpesa\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $this->validateChecksum($input);

        $request = $this->parseRequestXml($input);

        $response = $this->getAuthResponse($request);

        $url = $request[RequestFields::RETURN_URL];

        $url .= '?' . http_build_query($response);

        return \Redirect::to($url);
    }

    public function validateCustomer(array $input)
    {
        $response = [
            ResponseFields::LC_STATUS       => Constants::SUCCESS,
            ResponseFields::S2S_STATUS_CODE => StatusCode::SUCCESS,
            ResponseFields::DESCRIPTION     => Constants::SUCCESS,
            ResponseFields::RESPONSE_ID     => uniqid()
        ];

        return [ResponseFields::VALIDATE_CUSTOMER => $response];
    }

    public function pgSendOTP(array $input)
    {
        $randInt = mt_rand(11111111111111, 99999999999999);

        $mobileNumber = $input[SoapAction::OTP_GENERATE_API]
                              [RequestFields::COMMON_SERVICE_DATA]
                              [RequestFields::MOBILE_NUMBER];

        $data = [
            ResponseFields::LC_STATUS => Constants::SUCCESS,
            ResponseFields::S2S_STATUS_CODE => StatusCode::SUCCESS,
            ResponseFields::DESCRIPTION => 'Otp Sent Successfully',
            ResponseFields::RESPONSE_ID => uniqid()
        ];

        $response = [
            ResponseFields::OTP_GENERATE    => [
                ResponseFields::S2S_REF_NUMBER    => 'mcomOTP' . $randInt,
                ResponseFields::OTP_MOBILE_NUMBER => $mobileNumber,
                ResponseFields::LC_RESPONSE       => $data
            ]
        ];

        return $response;
    }

    public function pgMrchntPymt(array $input)
    {
        $request = $input[SoapAction::OTP_SUBMIT_API][RequestFields::MCOM_PAYMENT_REQ];

        $transId = mt_rand(11111111111, 99999999999);

        $response = [
            ResponseFields::S2S_TRANS_ID        => $transId,
            ResponseFields::S2S_TRANSACTION_REF => $request[RequestFields::TRANSACTION_REFERENCE],
            ResponseFields::S2S_STATUS_CODE     => StatusCode::SUCCESS,
            ResponseFields::LC_STATUS           => Constants::SUCCESS,
            ResponseFields::MOBILE_NUMBER       => $request[RequestFields::MOBILE_NUMBER]
        ];

        return [ResponseFields::UCF_RESPONSE => $response];
    }

    public function queryPaymentTransaction(array $input)
    {
        $request = $input[SoapAction::QUERY_API];

        $mobileNumber = mt_rand(7000000000, 9999999999);

        $response = [
            ResponseFields::S2S_REF_NUMBER  => $request[RequestFields::QUERY_TRANSACTION_REF],
            ResponseFields::S2S_STATUS_CODE => StatusCode::SUCCESS,
            ResponseFields::REASON          => Constants::SUCCESS,
            ResponseFields::MOBILE_NUMBER   => $mobileNumber
        ];

        return [ResponseFields::UCF_RESPONSE => $response];
    }

    public function refundPaymentTransaction(array $input)
    {
        $request = $input[SoapAction::REFUND_API];

        $mobileNumber = mt_rand(7000000000, 9999999999);

        $response = [
            ResponseFields::S2S_TRANS_ID        => $request[RequestFields::COM_TRANSACTION_ID],
            ResponseFields::S2S_TRANSACTION_REF => $request[RequestFields::QUERY_TRANSACTION_REF],
            ResponseFields::S2S_STATUS_CODE     => StatusCode::SUCCESS,
            ResponseFields::REASON              => Constants::SUCCESS,
            ResponseFields::MOBILE_NUMBER       => $mobileNumber
        ];

        return [ResponseFields::UCF_RESPONSE => $response];
    }

    protected function getAuthResponse(array $request)
    {
        $transId = mt_rand(11111111111, 99999999999);

        $response = [
            ResponseFields::COM_TRANSACTION_ID    => $transId,
            ResponseFields::TRANSACTION_REFERENCE => $request[RequestFields::TRANSACTION_REFERENCE],
            ResponseFields::STATUS_CODE           => StatusCode::SUCCESS,
            ResponseFields::REASON                => Constants::SUCCESS,
            ResponseFields::TRANSACTION_AMOUNT    => $request[RequestFields::AMOUNT]
        ];

        return $response;
    }

    protected function parseRequestXml(array $input)
    {
        $xml = $input[RequestFields::GATEWAY_PARAM];

        return (array) simplexml_load_string($xml);
    }

    protected function validateChecksum(array $input)
    {
        $xml = $input[RequestFields::GATEWAY_PARAM];

        $checksum = $input[RequestFields::CHECKSUM];

        $secret = $this->getGatewayInstance()->getSecret();

        $generatedChecksum = hash_hmac(HashAlgo::SHA256, $xml, $secret);

        if (hash_equals($checksum, $generatedChecksum) === false)
        {
            throw new Exception\ServerErrorException('Checksum Validation Failed');
        }
    }
}
