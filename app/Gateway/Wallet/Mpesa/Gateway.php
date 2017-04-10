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

        $this->assertPaymentId($input['payment']['id'], $input['gateway']['transrefno']);

        $this->saveCallbackResponse($input['gateway']);

        $this->checkCallbackStatus($input['gateway']);

        return $this->getCallbackResponseData($input);
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

        $soapRoot = "<pay:queryPaymentTransaction />";

        $response = $this->sendSoapRequest($data, $soapRoot)['Response'];

        // sd($response);
    }

    public function otpGenerate($input)
    {
        // sd('1');
    }

    protected function getAuthorizeRequestData()
    {
        $data = [
            RequestFields::GATEWAY_PARAM => $this->getActionData(),
            RequestFields::CHECKSUM      => $this->getCheckSum(),
        ];

        return $data;
    }

    protected function getActionData()
    {
        switch ($this->action)
        {
            case Base\Action::AUTHORIZE:
                $array = $this->getGatewayParamArray();
                $xmlRoot = "<PaymentGatewayRequest />";
                $data = $this->getXmlData($array, $xmlRoot);
                break;

            case Base\Action::VERIFY:
                $data = $this->getQueryData();
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
            $this->input['payment']['id'], Base\Action::AUTHORIZE);

        $gatewayPaymentId = $wallet->getGatewayPaymentId();

        $paymentId = $this->input['payment']['id'];

        $queryData = [
            RequestFields::MERCHANT_CODE             => $this->getMerchantId(),
            RequestFields::QUERY_TRANSACTION_DATE    => $this->getFormattedDate(),
            RequestFields::COM_TRANSACTION_ID        => $gatewayPaymentId ?? "",
            RequestFields::QUERY_TRANSACTION_REF     => $paymentId,
            RequestFields::PMT_TRANSACTION_REFERENCE => strtoupper($paymentId),
            RequestFields::AMOUNT                    => $this->input['payment']['amount'] / 100,
            RequestFields::COMMAND_ID                => Constants::COMMAND_ID
        ];

        return $queryData;
    }

    protected function getCheckSum()
    {
        $xml = $this->getActionData();

        return hash_hmac('sha256', $xml, $this->getSecret());
    }

    protected function getXmlData(array $array, string $xmlRoot)
    {
        $actionParam = array_flip($array);

        $actionParamXml = new SimpleXMLElement($xmlRoot);

        array_walk_recursive($actionParam, [$actionParamXml, 'addChild']);

        $actionParamXml = trim(explode('?>', $actionParamXml->asXML())[1]);

        return $actionParamXml;
    }

    protected function sendSoapRequest($data, $soapRoot)
    {
        $client = new SoapClient($this->getUrl());

        $headers = $this->getSoapHeaders();

        $client->__setSoapHeaders($headers);

        $response = $client->__soapCall(RequestFields::QUERY_PAYMENT_TRANSACTION,
                                        [$soapRoot => $data]);

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
            $this->input['payment']['id'], Base\Action::AUTHORIZE);

        $contentToSave = [
            Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::COM_TRANSACTION_ID],
            Entity::STATUS_CODE          => $content[ResponseFields::STATUS_CODE],
            Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::REASON],
        ];

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
