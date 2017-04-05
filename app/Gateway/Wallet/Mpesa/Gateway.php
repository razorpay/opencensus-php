<?php

namespace RZP\Gateway\Wallet\Mpesa;

use Carbon\Carbon;
use RZP\Exception;
use SimpleXMLElement;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Wallet\Base;
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

    public function otpGenerate($input)
    {
        sd('1');
    }

    protected function getAuthorizeRequestData()
    {
        $request = [
            RequestFields::GATEWAY_PARAM => $this->getGatewayParam(),
            RequestFields::CHECKSUM      => $this->getCheckSum(),
        ];

        return $request;
    }

    protected function getGatewayParam()
    {
        $gatewayParam = $this->getGatewayParamArray();

        $gatewayParam = array_flip($gatewayParam);

        $gatewayParamXml = new SimpleXMLElement("<PaymentGatewayRequest />");

        array_walk_recursive($gatewayParam, [$gatewayParamXml, 'addChild']);

        $gatewayParamXml = trim(explode('?>', $gatewayParamXml->asXML())[1]);

        return $gatewayParamXml;
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

    protected function getCheckSum()
    {
        $xml = $this->getGatewayParam();

        return hash_hmac('sha256', $xml, $this->getSecret());
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

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);
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
}
