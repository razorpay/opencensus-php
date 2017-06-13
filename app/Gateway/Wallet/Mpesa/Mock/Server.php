<?php

namespace RZP\Gateway\Wallet\Mpesa\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Wallet\Mpesa\Action;
use RZP\Gateway\Wallet\Mpesa\Status;
use RZP\Gateway\Wallet\Mpesa\SoapAction;
use RZP\Gateway\Wallet\Mpesa\SoapMethod;
use RZP\Gateway\Wallet\Mpesa\StatusCode;
use RZP\Gateway\Wallet\Mpesa\RequestFields;
use RZP\Gateway\Wallet\Mpesa\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $this->validateAuthorizeInput($input);

        $request = $this->parseRequestXml($input);

        $this->validateActionInput($request, RequestFields::GATEWAY_PARAM);

        $response = $this->getAuthResponse($request);

        $this->content($response, Action::AUTHORIZE);

        $url = $request[RequestFields::RETURN_URL];

        $url .= '?' . http_build_query($response);

        return \Redirect::to($url);
    }

    public function validateCustomer(array $input)
    {
        $request = $input[SoapAction::CUSTOMER_API][RequestFields::COMMON_SERVICE_DATA];

        $this->validateActionInput($request, SoapMethod::VALIDATE_CUSTOMER);

        $response = [
            ResponseFields::LC_STATUS       => 'SUCCESS',
            ResponseFields::S2S_STATUS_CODE => StatusCode::SUCCESS,
            ResponseFields::DESCRIPTION     => 'SUCCESS',
            ResponseFields::RESPONSE_ID     => uniqid()
        ];

        $this->content($response, SoapAction::CUSTOMER_API);

        return [ResponseFields::VALIDATE_CUSTOMER => $response];
    }

    public function pgSendOTP(array $input)
    {
        $randInt = strval(random_int(11111111111111, 99999999999999));

        $request = $input[SoapAction::OTP_GENERATE_API][RequestFields::COMMON_SERVICE_DATA];

        $this->validateActionInput($request, SoapMethod::SEND_OTP);

        $mobileNumber = $request[RequestFields::MOBILE_NUMBER];

        $data = [
            ResponseFields::LC_STATUS       => 'SUCCESS',
            ResponseFields::S2S_STATUS_CODE => StatusCode::SUCCESS,
            ResponseFields::DESCRIPTION     => 'Otp Sent Successfully',
            ResponseFields::RESPONSE_ID     => uniqid()
        ];

        $this->content($data, SoapAction::OTP_GENERATE_API);

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

        $this->validateActionInput($request, SoapMethod::OTP_SUBMIT);

        $transId = strval(random_int(11111111111, 99999999999));

        $response = [
            ResponseFields::S2S_TRANS_ID        => $transId,
            ResponseFields::S2S_TRANSACTION_REF => $request[RequestFields::TRANSACTION_REFERENCE],
            ResponseFields::S2S_STATUS_CODE     => StatusCode::SUCCESS,
            ResponseFields::LC_STATUS           => 'SUCCESS',
            ResponseFields::MOBILE_NUMBER       => $request[RequestFields::MOBILE_NUMBER]
        ];

        $this->content($response, SoapAction::OTP_SUBMIT_API);

        return [ResponseFields::UCF_RESPONSE => $response];
    }

    public function queryPaymentTransaction(array $input)
    {
        $request = $input[SoapAction::QUERY_API];

        $this->validateActionInput($request, SoapMethod::QUERY_PAYMENT_TRANSACTION);

        $mobileNumber = strval(random_int(7000000000, 9999999999));

        $transId = strval(random_int(11111111111, 99999999999));

        $response = [
            ResponseFields::S2S_TRANS_ID    => $transId,
            ResponseFields::S2S_REF_NUMBER  => $request[RequestFields::QUERY_TRANSACTION_REF],
            ResponseFields::S2S_STATUS_CODE => StatusCode::SUCCESS,
            ResponseFields::REASON          => 'SUCCESS',
            ResponseFields::MOBILE_NUMBER   => $mobileNumber
        ];

        $this->content($response, SoapAction::QUERY_API);

        return [ResponseFields::UCF_RESPONSE => $response];
    }

    public function refundPaymentTransaction(array $input)
    {
        $request = $input[SoapAction::REFUND_API];

        $this->validateActionInput($request, SoapMethod::REFUND_PAYMENT);

        $mobileNumber = strval(random_int(7000000000, 9999999999));

        $response = [
            ResponseFields::S2S_TRANS_ID        => $request[RequestFields::COM_TRANSACTION_ID],
            ResponseFields::S2S_TRANSACTION_REF => $request[RequestFields::QUERY_TRANSACTION_REF],
            ResponseFields::S2S_STATUS_CODE     => StatusCode::SUCCESS,
            ResponseFields::REASON              => 'SUCCESS',
            ResponseFields::MOBILE_NUMBER       => $mobileNumber
        ];

        $this->content($response, SoapAction::REFUND_API);

        return [ResponseFields::UCF_RESPONSE => $response];
    }

    protected function getAuthResponse(array $request)
    {
        $transId = strval(random_int(11111111111, 99999999999));

        $response = [
            ResponseFields::COM_TRANSACTION_ID    => $transId,
            ResponseFields::TRANSACTION_REFERENCE => $request[RequestFields::TRANSACTION_REFERENCE],
            ResponseFields::STATUS_CODE           => StatusCode::SUCCESS,
            ResponseFields::REASON                => 'SUCCESS',
            ResponseFields::TRANSACTION_AMOUNT    => $request[RequestFields::AMOUNT]
        ];

        return $response;
    }

    protected function parseRequestXml(array $input)
    {
        $xml = $input[RequestFields::GATEWAY_PARAM];

        return (array) simplexml_load_string($xml);
    }
}
