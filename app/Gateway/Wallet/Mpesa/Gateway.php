<?php

namespace RZP\Gateway\Wallet\Mpesa;

use SoapVar;
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
use RZP\Models\Payment\Entity as Payment;

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
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
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

        $status = $content[ResponseFields::VERIFY_STATUS_CODE];

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

        sd($response);
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
        $wallet = $this->repo->findByPaymentIdAndAction(
            $this->input['payment']['id'], Action::AUTHORIZE);

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
        $wallet = $this->repo->findByPaymentIdAndAction(
            $this->input['payment']['id'], Action::AUTHORIZE);

        $content = $verify->verifyResponseContent;

        $contentToSave = [
            Entity::STATUS_CODE          => $content[ResponseFields::VERIFY_STATUS_CODE],
            Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::REASON],
            Entity::CONTACT              => $content[ResponseFields::MOBILE_NUMBER]
        ];

        if (empty($wallet[Entity::GATEWAY_PAYMENT_ID]) === true)
        {
            $contentToSave[Entity::GATEWAY_PAYMENT_ID] = $content[ResponseFields::VERIFY_TRANS_ID];
        }

        $this->updateGatewayPaymentEntity($wallet, $contentToSave, false);
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
