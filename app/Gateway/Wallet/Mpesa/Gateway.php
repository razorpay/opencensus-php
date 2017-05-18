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
use RZP\Constants\HashAlgo;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use libphonenumber\PhoneNumberUtil;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Processor\Wallet;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_mpesa';

    const DATE_FORMAT = 'dmY';

    protected $map = [
        RequestFields::MERCHANT_CODE    => Base\Entity::GATEWAY_MERCHANT_ID,
        RequestFields::AMOUNT           => Base\Entity::AMOUNT,
        RequestFields::TRANSACTION_DATE => Base\Entity::DATE,
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

        $content = $input['gateway'];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $content);

        $this->assertPaymentId($input['payment']['id'],
                               $content[ResponseFields::TRANSACTION_REFERENCE]);

        $this->saveCallbackResponse($content);

        $this->checkGatewayResponseStatus($content[ResponseFields::STATUS_CODE]);

        return $this->getCallbackResponseData($input);
    }

    public function otpGenerate(array $input)
    {
        $this->validateCustomer($input);

        $this->action($input, Action::OTP_GENERATE);

        $data = $this->getOtpGenerateData();

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

        $content = $response[ResponseFields::OTP_GENERATE][ResponseFields::LC_RESPONSE];

        // Create Gateway Payment Entity
        $contentToSave = $this->getOtpGenerateContentToSave($response[ResponseFields::OTP_GENERATE]);

        $this->createGatewayPaymentEntity($contentToSave);

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        // Otp generation fails, throw exception
        $this->checkGatewayResponseStatus($status);

        return $this->getOtpSubmitRequest($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $data = $this->getOtpSubmitData();

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

        $content = $response[ResponseFields::UCF_RESPONSE];

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        $this->saveOtpCallbackContent($content);

        // Otp submission fails, throw exception
        $this->checkGatewayResponseStatus($status);

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

        $data = $this->getRefundData();

        $response = $this->sendSoapRequest($data,
                                           SoapAction::REFUND_API,
                                           SoapMethod::REFUND_PAYMENT);

        $content = $response[ResponseFields::UCF_RESPONSE];

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        $attributes = $this->getRefundAttributes($content);

        $this->createGatewayRefundEntity($attributes);

        // response will contain status 100 or 101
        $this->checkGatewayResponseStatus($status);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $data = $this->getVerifyRequestData();

        $verify->verifyResponse = $this->sendSoapRequest($data,
                                                   SoapAction::QUERY_API,
                                                   SoapMethod::QUERY_PAYMENT_TRANSACTION);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $verify->verifyResponse,
                'payment_id' => $verify->input['payment']['id'],
            ]);

        $verify->verifyResponseContent = $verify->verifyResponse[ResponseFields::UCF_RESPONSE];
    }

    protected function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $status = $this->getVerifyStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    protected function getVerifyStatus(Verify $verify)
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
        if ($status === StatusCode::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function validateCustomer(array $input)
    {
        $this->action($input, Action::VALIDATE_CUSTOMER);

        $data = $this->getValidateCustomerData();

        $response = $this->sendSoapRequest($data,
                                           SoapAction::CUSTOMER_API,
                                           SoapMethod::VALIDATE_CUSTOMER);

        $this->trace->info(
            TraceCode::GATEWAY_VALIDATE_CUSTOMER_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response,
                'payment_id' => $this->input['payment']['id'],
            ]);

        $content = $response[ResponseFields::VALIDATE_CUSTOMER];

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        // response will contain status 100 or 101
        $this->checkGatewayResponseStatus($status);
    }

    protected function getAuthorizeRequestData()
    {
        $xml = $this->getGatewayRequestArray();

        $data = [
            RequestFields::GATEWAY_PARAM => $xml,
            RequestFields::CHECKSUM      => $this->getChecksum($xml),
        ];

        return $data;
    }

    protected function getChecksum(string $xml)
    {
        return hash_hmac(HashAlgo::SHA256, $xml, $this->getSecret());
    }

    protected function getGatewayRequestArray()
    {
        $array = $this->getGatewayParamArray();

        $xmlRoot = "<PaymentGatewayRequest />";

        //
        // Simple XML Element takes the values of the associate array
        // as the XML elements. Therefore, we need to flip the array
        // to ensure that the keys are selected instead.
        //
        $gatewayParam = array_flip($array);

        $gatewayParamXml = new SimpleXMLElement($xmlRoot);

        //
        // Recursively walks through the array and adds each entry in $gatewayParam
        // into $gatewayParamXml as an XML child of the origin XML root.
        //
        array_walk_recursive($gatewayParam, [$gatewayParamXml, 'addChild']);

        $gatewayParamXml = trim(explode('?>', $gatewayParamXml->asXML())[1]);

        return $gatewayParamXml;
    }

    protected function getGatewayParamArray()
    {
        $amount = $this->input['payment']['amount'] / 100;

        $gatewayParam = [
            RequestFields::MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::TRANSACTION_DATE      => $this->getFormattedDate(),
            RequestFields::TRANSACTION_REFERENCE => $this->input['payment']['id'],
            RequestFields::TRANSACTION_TYPE      => Constants::WALLET,
            RequestFields::AMOUNT                => $amount,
            RequestFields::RETURN_URL            => $this->input['callbackUrl'],
        ];

        return $gatewayParam;
    }

    protected function getVerifyRequestData()
    {
        $wallet = $this->repo->findByPaymentIdAndActions(
            $this->input['payment']['id'],
            [Action::AUTHORIZE, Action::OTP_GENERATE]);

        $gatewayPaymentId = $wallet->getGatewayPaymentId() ?? "";

        $paymentId = $this->input['payment']['id'];

        $amount = $this->input['payment']['amount'] / 100;

        $queryData = [
            RequestFields::MERCHANT_CODE             => $this->getMerchantId(),
            RequestFields::QUERY_TRANSACTION_DATE    => $this->getFormattedDate(),
            RequestFields::COM_TRANSACTION_ID        => $gatewayPaymentId,
            RequestFields::QUERY_TRANSACTION_REF     => $paymentId,
            RequestFields::PMT_TRANSACTION_REFERENCE => $paymentId,
            RequestFields::AMOUNT                    => $amount,
        ];

        if ($wallet->getAction() === Action::OTP_GENERATE)
        {
            $queryData[RequestFields::CMDID] = Constants::CMDID;
        }

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

        $amount = $this->input['payment']['amount'] / 100;

        $data = [
            RequestFields::MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::TRANSACTION_DATE      => $this->getFormattedDate(),
            RequestFields::TRANSACTION_REFERENCE => $this->input['payment']['id'],
            RequestFields::TRANSACTION_TYPE      => Constants::WALLET,
            RequestFields::AMOUNT                => $amount,
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

        $amount = $this->input['refund']['amount'] / 100;

        $data = [
            RequestFields::MERCHANT_CODE         => $this->getMerchantId(),
            RequestFields::COM_TRANSACTION_ID    => $gatewayPaymentId ?? "",
            RequestFields::QUERY_TRANSACTION_REF => $this->input['payment']['id'],
            RequestFields::S2S_AMOUNT            => $amount,
            RequestFields::REFUND_NARRATION      => Constants::REFUND_NARRATION,
            RequestFields::REVERSAL_TYPE         => $this->getReversalType()
        ];

        return $data;
    }

    protected function getOtpGenerateContentToSave(array $content)
    {
        $response = $content[ResponseFields::LC_RESPONSE];

        $attributes = [
            Base\Entity::GATEWAY_PAYMENT_ID2  => $content[ResponseFields::S2S_REF_NUMBER],
            Base\Entity::CONTACT              => $content[ResponseFields::OTP_MOBILE_NUMBER],
            Base\Entity::AMOUNT               => $this->input['payment']['amount'],
            Base\Entity::STATUS               => $response[ResponseFields::LC_STATUS],
            Base\Entity::STATUS_CODE          => $response[ResponseFields::S2S_STATUS_CODE],
            Base\Entity::RESPONSE_DESCRIPTION => $response[ResponseFields::DESCRIPTION],
            Base\Entity::REFERENCE1           => $response[ResponseFields::RESPONSE_ID]
        ];

        return $attributes;
    }

    protected function saveOtpCallbackContent(array $content)
    {
        $wallet = $this->repo->findByPaymentIdAndAction(
            $this->input['payment']['id'], Action::OTP_GENERATE);

        $attributes = [
            Base\Entity::RECEIVED            => true,
            Base\Entity::GATEWAY_PAYMENT_ID  => $content[ResponseFields::S2S_TRANS_ID],
            Base\Entity::STATUS              => $content[ResponseFields::LC_STATUS],
            Base\Entity::STATUS_CODE         => $content[ResponseFields::S2S_STATUS_CODE],
        ];

        $this->updateGatewayPaymentEntity($wallet, $attributes, false);
    }

    protected function getRefundAttributes(array $content)
    {
        $input = $this->input;

        $attributes = [
            Base\Entity::RECEIVED             => true,
            Base\Entity::PAYMENT_ID           => $input['payment']['id'],
            Base\Entity::WALLET               => Wallet::MPESA,
            Base\Entity::AMOUNT               => $input['refund']['amount'],
            Base\Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::S2S_TRANS_ID],
            Base\Entity::STATUS_CODE          => $content[ResponseFields::S2S_STATUS_CODE],
            Base\Entity::REFUND_ID            => $input['refund']['id'],
            Base\Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::REASON]
        ];

        return $attributes;
    }

    /**
     * If refund amount is less than payment amount, it is a partial refund
     *
     * @return string $reversalType
     */
    protected function getReversalType()
    {
        $refund = $this->input['payment']['amount'] - $this->input['refund']['amount'];

        if ($refund === 0)
        {
            return Constants::FULL_REVERSAL;
        }

        return Constants::PARTIAL_REVERSAL;
    }

    /**
     * Converts Indian numbers into mpesa acceptable format
     * Example: +91-1234567899 returns 1234567899
     *
     * @return number
     */
    protected function getFormattedPhoneNo()
    {
        $contact = $this->input['payment']['contact'];

        $phoneUtil = PhoneNumberUtil::getInstance();

        return $phoneUtil->parse($contact, 'IN')->getNationalNumber();
    }

    protected function sendSoapRequest(array $data, string $soapRoot, string $method)
    {
        $this->trace->info(
            TraceCode::GATEWAY_SOAP_REQUEST,
            [
                'payment_id'  => $this->input['payment']['id'],
                'gateway'     => $this->gateway,
                'soap_method' => $method,
                'request'     => [
                    'soap_root' => $soapRoot,
                    'data'      => $data
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
        $headers = [
            new SoapHeader(Url::WSDL, Constants::USER_ID, $this->getSoapUserId()),
            new SoapHeader(Url::WSDL, Constants::PASSWORD, $this->getSoapPassword())
        ];

        return $headers;
    }

    protected function checkGatewayResponseStatus(string $status)
    {
        if ($status !== StatusCode::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                $status,
                StatusCode::getErrorMessage($status)
            );
        }
    }

    protected function saveCallbackResponse(array $content)
    {
        $wallet = $this->repo->findByPaymentIdAndAction(
            $this->input['payment']['id'], Action::AUTHORIZE);

        $contentToSave = [
            Base\Entity::RECEIVED             => true,
            Base\Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::COM_TRANSACTION_ID],
            Base\Entity::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            Base\Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::REASON],
        ];

        $this->updateGatewayPaymentEntity($wallet, $contentToSave, false);
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $wallet = $this->repo->findByPaymentIdAndActions(
            $this->input['payment']['id'],
            [Action::AUTHORIZE, Action::OTP_GENERATE]);

        $content = $verify->verifyResponseContent;

        $errorMessage = StatusCode::getErrorMessage($content[ResponseFields::S2S_STATUS_CODE]);

        $contentToSave = [
            Base\Entity::STATUS_CODE          => $content[ResponseFields::S2S_STATUS_CODE],
            Base\Entity::CONTACT              => $content[ResponseFields::MOBILE_NUMBER],
            Base\Entity::RESPONSE_DESCRIPTION => $errorMessage
        ];

        if (empty($wallet[Base\Entity::GATEWAY_PAYMENT_ID]) === true)
        {
            $contentToSave[Base\Entity::GATEWAY_PAYMENT_ID] = $content[ResponseFields::S2S_TRANS_ID];
        }

        $this->updateGatewayPaymentEntity($wallet, $contentToSave, false);
    }

    protected function getMappedAttributes($attributes)
    {
        if ($this->action === Action::OTP_GENERATE)
        {
            return $attributes;
        }

        return parent::getMappedAttributes($attributes);
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
        // TODO: Add live user id
        return $this->config['test_user_id'];
    }

    protected function getSoapPassword()
    {
        // TODO: Add live password
        return $this->config['test_password'];
    }

    protected function getPaymentToVerify($verify)
    {
        $actions = [Action::AUTHORIZE, Action::OTP_GENERATE];

        $gatewayPayment = $this->repo->findByPaymentIdAndActions(
                    $verify->input['payment']['id'], $actions);

        $verify->payment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function getRepository()
    {
        $gateway = $this->gateway;

        return $this->app['repo']->$gateway;
    }
}
