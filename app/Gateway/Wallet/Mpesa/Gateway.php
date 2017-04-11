<?php

namespace RZP\Gateway\Wallet\Mpesa;

use SoapClient;
use SoapHeader;
use Carbon\Carbon;
use RZP\Exception;
use SimpleXMLElement;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base\Entity;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Entity as Payment;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_mpesa';

    const DATE_FORMAT = 'dmY';

    protected $map = [
        RequestFields::MERCHANT_CODE    => Entity::GATEWAY_MERCHANT_ID,
        RequestFields::AMOUNT           => Entity::AMOUNT,
        RequestFields::TRANSACTION_DATE => Entity::DATE,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestData();

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        $contentToSave = $this->getGatewayParamArray();

        $this->createGatewayPaymentEntity($contentToSave);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $this->assertPaymentId($input['payment']['id'],
                               $input['gateway'][ResponseFields::TRANSACTION_REFERENCE]);

        $this->saveCallbackResponse($input['gateway']);

        $this->checkCallbackStatus($input['gateway']);

        return $this->getCallbackResponseData($input);
    }

    public function otpGenerate(array $input)
    {
        $this->validateCustomer($input);

        $this->action($input, Action::OTP_GENERATE);

        $data = $this->getActionData();

        $response = $this->sendSoapRequest($data,
                                           SoapAction::OTP_GENERATE_API,
                                           SoapMethod::SEND_OTP);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response,
                'payment_id' => $input['payment']['id']
            ]);

        $content = $response['McomOtpResponse']['response'];

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        // Otp generation fails, throw exception
        if (StatusCode::checkIfSuccessStatus($status) === false)
        {
            // TODO: Map statuses to error codes
            $errorCode = ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                $content[ResponseFields::DESCRIPTION]);
        }

        // Create Gateway Payment Entity
        $contentToSave = $this->getOtpGenerateContentToSave($response['McomOtpResponse']);

        $this->createGatewayPaymentEntity($contentToSave);

        return $this->getOtpSubmitRequest($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $data = $this->getActionData();

        $response = $this->sendSoapRequest($data,
                                           SoapAction::OTP_SUBMIT_API,
                                           SoapMethod::OTP_SUBMIT);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_OTP_SUBMIT_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response,
                'payment_id' => $input['payment']['id']
            ]);

        $content = $response['Response'];

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        // Otp submission fails, throw exception
        if (StatusCode::checkIfSuccessStatus($status) === false)
        {
            // TODO: Map statuses to error codes
            $errorCode = ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                $content[ResponseFields::DESCRIPTION]);
        }

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $data = $this->getActionData();

        $response = $this->sendSoapRequest($data,
                                           SoapAction::REFUND_API,
                                           SoapMethod::REFUND_PAYMENT);

        $content = $response['Response'];

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        $attributes = $this->getRefundAttributes($content);

        $this->createGatewayRefundEntity($attributes);

        // response will contain status 100 or 101
        if (StatusCode::checkIfSuccessStatus($status) === false)
        {
            // TODO: Map statuses to error codes
            $errorCode = ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                $content[ResponseFields::REASON]);
        }
    }

    protected function getRefundAttributes(array $content)
    {
        $input = $this->input;

        $attributes = [
            Entity::RECEIVED             => true,
            Entity::PAYMENT_ID           => $input['payment']['id'],
            Entity::WALLET               => Wallet::MPESA,
            Entity::AMOUNT               => $input['refund']['amount'] / 100,
            Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::S2S_TRANS_ID],
            Entity::STATUS_CODE          => $content[ResponseFields::S2S_STATUS_CODE],
            Entity::REFUND_ID            => $input['refund']['id'],
            Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::REASON]
        ];

        return $attributes;
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $data = $this->getActionData();

        $verify->response = $this->sendSoapRequest($data,
                                                   SoapAction::QUERY_API,
                                                   SoapMethod::QUERY_PAYMENT_TRANSACTION);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'    => $verify->response,
                'payment_id' => $verify->input['payment']['id'],
            ]);

        $verify->verifyResponseContent = $verify->response['Response'];
    }

    protected function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContentIfNeeded($verify);
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        if (($verify->input['payment']['status'] === 'created') or
            ($verify->input['payment']['status'] === 'failed'))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        // content will contain status 100 or 101
        if (StatusCode::checkIfSuccessStatus($status) === true)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getAuthorizeRequestData()
    {
        $data = [
            RequestFields::GATEWAY_PARAM => $this->getActionData(),
            RequestFields::CHECKSUM      => $this->getCheckSum(),
        ];

        return $data;
    }

    protected function validateCustomer(array $input)
    {
        $this->action($input, Action::VALIDATE_CUSTOMER);

        $data = $this->getActionData();

        $response = $this->sendSoapRequest($data,
                                           SoapAction::CUSTOMER_API,
                                           SoapMethod::VALIDATE_CUSTOMER);

        $content = $response['MCOMResponseStatus'];

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        // response will contain status 100 or 101
        if (StatusCode::checkIfSuccessStatus($status) === false)
        {
            // TODO: Map statuses to error codes
            $errorCode = ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                $content[ResponseFields::DESCRIPTION]);
        }
    }

    protected function getOtpGenerateContentToSave(array $content)
    {
        $response = $content['response'];

        $attributes = [
            Entity::GATEWAY_PAYMENT_ID2 => $content[ResponseFields::OTP_REF_NUMBER],
            Entity::CONTACT             => $content[ResponseFields::OTP_MOBILE_NUMBER],
            Entity::AMOUNT              => $this->input['payment']['amount'] / 100
        ];

        return $attributes;
    }

    protected function getCheckSum()
    {
        $xml = $this->getActionData();

        return hash_hmac('sha256', $xml, $this->getSecret());
    }

    protected function getActionData()
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $array = $this->getGatewayParamArray();
                $xmlRoot = "<PaymentGatewayRequest />";
                $data = $this->getXmlData($array, $xmlRoot);
                break;

            case Action::VERIFY:
                $data = $this->getQueryData();
                break;

            case Action::VALIDATE_CUSTOMER:
                $data = $this->getValidateCustomerData();
                break;

            case Action::OTP_GENERATE:
                $data = $this->getOtpGenerateData();
                break;

            case Action::OTP_SUBMIT:
                $data = $this->getOtpSubmitData();
                break;

            case Action::REFUND:
                $data = $this->getRefundData();
                break;
        }

        return $data;
    }

    protected function getGatewayParamArray()
    {
        $gatewayParam = [
            RequestFields::MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::TRANSACTION_DATE      => $this->getFormattedDate(),
            RequestFields::TRANSACTION_REFERENCE => $this->input['payment']['id'],
            RequestFields::TRANSACTION_TYPE      => PaymentMethod::WALLET,
            RequestFields::AMOUNT                => $this->input['payment']['amount'] / 100,
            RequestFields::NARRATION             => Constants::NARRATION,
            RequestFields::RETURN_URL            => $this->input['callbackUrl'],
            RequestFields::SURCHARGE             => Constants::SURCHARGE,
        ];

        return $gatewayParam;
    }

    protected function getQueryData()
    {
        $wallet = $this->repo->findByPaymentIdAndActions(
            $this->input['payment']['id'],
            [Action::AUTHORIZE, Action::OTP_GENERATE]);

        $gatewayPaymentId = $wallet->getGatewayPaymentId();

        $paymentId = $this->input['payment']['id'];

        $queryData = [
            RequestFields::MERCHANT_CODE             => $this->getMerchantId(),
            RequestFields::QUERY_TRANSACTION_DATE    => $this->getFormattedDate(),
            RequestFields::COM_TRANSACTION_ID        => $gatewayPaymentId ?? "",
            RequestFields::QUERY_TRANSACTION_REF     => $paymentId,
            RequestFields::PMT_TRANSACTION_REFERENCE => $paymentId,
            RequestFields::AMOUNT                    => $this->input['payment']['amount'] / 100,
        ];

        return $queryData;
    }

    protected function getValidateCustomerData()
    {
        $data = [
            RequestFields::CHANNEL_ID    => Constants::CHANNEL_ID,
            RequestFields::REQUEST_ID    => uniqid(),
            RequestFields::MOBILE_NUMBER => $this->getFormattedPhoneNo(),
        ];

        return [RequestFields::COMMON_SERVICE_DATA => $data];
    }

    protected function getOtpGenerateData()
    {
        $data = [
            RequestFields::REQUEST_ID     => uniqid(),
            RequestFields::CHANNEL_ID     => Constants::CHANNEL_ID,
            RequestFields::ENTITY_TYPE_ID => Constants::ENTITY_TYPE_ID,
            RequestFields::MOBILE_NUMBER  => $this->getFormattedPhoneNo()
        ];

        return [
            RequestFields::COMMON_SERVICE_DATA => $data,
            RequestFields::MERCHANT_ID         => $this->getMerchantId()
        ];
    }

    protected function getOtpSubmitData()
    {
        $wallet = $this->repo->findByPaymentIdAndAction(
            $this->input['payment']['id'], Action::OTP_GENERATE);

        $gatewayPaymentId2 = $wallet->getGatewayPaymentId2();

        $data = [
            RequestFields::MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::TRANSACTION_DATE      => $this->getFormattedDate(),
            RequestFields::TRANSACTION_REFERENCE => $this->input['payment']['id'],
            RequestFields::TRANSACTION_TYPE      => PaymentMethod::WALLET,
            RequestFields::AMOUNT                => $this->input['payment']['amount'] / 100,
            RequestFields::MOBILE_NUMBER         => $this->getFormattedPhoneNo(),
            RequestFields::FROM_ENTITY_TYPE      => Constants::ENTITY_TYPE_ID,
            RequestFields::TO_ENTITY_TYPE        => Constants::TO_ENTITY_TYPE,
            RequestFields::COMMAND_ID            => Constants::COMMAND_ID,
            RequestFields::OTP                   => $this->input['gateway']['otp'],
            RequestFields::OTP_REF_NUMBER        => $gatewayPaymentId2,
            RequestFields::CHANNEL_ID            => Constants::CHANNEL_ID,
        ];

        return [RequestFields::MCOM_PAYMENT_REQ => $data];
    }

    protected function getRefundData()
    {
        $wallet = $this->repo->findByPaymentIdAndActions(
            $this->input['payment']['id'],
            [Action::AUTHORIZE, Action::OTP_GENERATE]);

        $gatewayPaymentId = $wallet->getGatewayPaymentId();

        $data = [
            RequestFields::MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::COM_TRANSACTION_ID    => $gatewayPaymentId ?? "",
            RequestFields::QUERY_TRANSACTION_REF => $this->input['payment']['id'],
            RequestFields::S2S_AMOUNT            => $this->input['payment']['amount'] / 100,
            RequestFields::REFUND_NARRATION      => Constants::REFUND_NARRATION,
            RequestFields::REVERSAL_TYPE         => Constants::REVERSAL_TYPE
        ];

        return $data;
    }

    protected function getFormattedPhoneNo()
    {
        $contact = $this->input['payment']['contact'];

        return explode('+91', $contact)[1];
    }

    protected function getXmlData(array $array, string $xmlRoot)
    {
        $actionParam = array_flip($array);

        $actionParamXml = new SimpleXMLElement($xmlRoot);

        array_walk_recursive($actionParam, [$actionParamXml, 'addChild']);

        $actionParamXml = trim(explode('?>', $actionParamXml->asXML())[1]);

        return $actionParamXml;
    }

    protected function sendSoapRequest($data, $soapRoot, $method)
    {
        $this->trace->info(
            TraceCode::GATEWAY_SOAP_REQUEST,
            [
                'payment_id'  => $this->input['payment']['id'],
                'gateway'     => $this->gateway,
                'soap_method' => $method,
                'request'     => [
                    $soapRoot => $data
                ],
            ]);

        $client = new SoapClient($this->getUrl());

        $headers = $this->getSoapHeaders();

        $client->__setSoapHeaders($headers);

        $response = $client->__soapCall($method, [$soapRoot => $data]);

        return json_decode(json_encode($response), true);
    }

    protected function getSoapHeaders()
    {
        $wsseNs = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

        $headers = [
            new SoapHeader($wsseNs, 'userId', $this->getSoapUserId()),
            new SoapHeader($wsseNs, 'password', $this->getSoapPassword())
        ];

        return $headers;
    }

    protected function checkCallbackStatus(array $content)
    {
        $status = $content[ResponseFields::STATUS_CODE];

        if (StatusCode::checkIfSuccessStatus($status) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content[ResponseFields::STATUS_CODE],
                $content[ResponseFields::REASON]
            );
        }
    }

    protected function saveCallbackResponse(array $content)
    {
        $wallet = $this->repo->findByPaymentIdAndAction(
            $this->input['payment']['id'], Action::AUTHORIZE);

        $contentToSave = [
            Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::COM_TRANSACTION_ID],
            Entity::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::REASON],
        ];

        $this->updateGatewayPaymentEntity($wallet, $contentToSave, false);
    }

    protected function saveVerifyContentIfNeeded(Verify $verify)
    {
        $wallet = $this->repo->findByPaymentIdAndActions(
            $this->input['payment']['id'],
            [Action::AUTHORIZE, Action::OTP_GENERATE]);

        $content = $verify->verifyResponseContent;

        $contentToSave = [
            Entity::STATUS_CODE          => $content[ResponseFields::S2S_STATUS_CODE],
            Entity::CONTACT              => $content[ResponseFields::MOBILE_NUMBER]
        ];

        if (isset($content[ResponseFields::REASON]) === true)
        {
            $contentToSave[Entity::RESPONSE_DESCRIPTION] = $content[ResponseFields::REASON];
        }

        if ((empty($wallet[Entity::GATEWAY_PAYMENT_ID]) === true) and
            (isset($content[ResponseFields::S2S_TRANS_ID]) === true))
        {
            $contentToSave[Entity::GATEWAY_PAYMENT_ID] = $content[ResponseFields::S2S_TRANS_ID];
        }

        $this->updateGatewayPaymentEntity($wallet, $contentToSave, false);
    }

    protected function getMappedAttributes($attributes)
    {
        if ($this->action === Action::OTP_GENERATE)
        {
            return $attributes;
        }
    }

    protected function getFormattedDate()
    {
        return Carbon::now('Asia/Kolkata')->format(self::DATE_FORMAT);
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->terminal['gateway_merchant_id'];
    }

    protected function getSoapUserId()
    {
        return $this->config['test_user_id'];
    }

    protected function getSoapPassword()
    {
        return $this->config['test_password'];
    }
}
