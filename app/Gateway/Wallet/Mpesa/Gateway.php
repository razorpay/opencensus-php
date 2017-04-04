<?php

namespace RZP\Gateway\Wallet\Mpesa;

use Carbon\Carbon;
use SimpleXMLElement;
use RZP\Constants\Mode;
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
        RequestFields::MERCHANT_CODE => Base\Entity::GATEWAY_MERCHANT_ID,
        RequestFields::AMOUNT => Base\Entity::AMOUNT,
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

        return $gatewayParamXml->asXML();
    }

    protected function getGatewayParamArray()
    {
        $gatewayParam = [
            RequestFields::MERCHANT_CODE         => '0001000269',
            RequestFields::TRANSACTION_DATE      => $this->getFormattedDate(),
            RequestFields::TRANSACTION_REFERENCE => $this->input['payment']['id'],
            RequestFields::TRANSACTION_TYPE      => 'W',
            RequestFields::AMOUNT                => $this->input['payment']['amount'],
            RequestFields::NARRATION             => 'Razorpay Payments',
            RequestFields::RETURN_URL            => $this->input['callbackUrl'],
            RequestFields::SURCHARGE             => '0.0',
        ];

        return $gatewayParam;
    }

    protected function getCheckSum()
    {
        $xml = $this->getGatewayParam();

        return hash_hmac('sha256', $xml, $this->getSecret());
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
