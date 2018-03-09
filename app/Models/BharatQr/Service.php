<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Base;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Gateway;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $gatewayMapping = [
        'icici'   => Gateway::UPI_ICICI,
        'hitachi' => Gateway::HITACHI,
    ];

    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function processPayment($input, string $gateway)
    {
        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST,
            [
                'input'   => $input,
                'gateway' => $gateway,
            ]
        );

        $gateway = $this->gatewayMapping[$gateway];

        $gatewayClass = $this->app['gateway']->gateway($gateway);

        $input = $gatewayClass->preProcessServerCallback($input);

        $qrCodeId = $gatewayClass->getMerchantReferenceForQr($input);

        $this->determineAndSetModeForQr($qrCodeId);

        $gatewayInput = $this->callGatewayQrNotification($gateway, $input);

        $gatewayInput[Entity::GATEWAY] = $gateway;

        $bharatQrInputParams = $this->getBharatQrInputParams($gatewayInput);

        list($valid, $payment) = $this->core->processPayment($bharatQrInputParams, $gatewayInput);

        $input['payment'] = $payment;

        $this->callGatewayQrNotification($gateway, $input);

        $response = $this->getResponse($valid);

        return $response;
    }

    protected function callGatewayQrNotification(string $gateway, array $gatewayInput)
    {
        return $this->app['gateway']->call($gateway, Action::QR_NOTIFICATION, $gatewayInput, null);
    }

    protected function getBharatQrInputParams(array $gatewayInput)
    {
        return [
            Entity::PROVIDER_REFERENCE_ID => $gatewayInput[Entity::PROVIDER_REFERENCE_ID],
            Entity::MERCHANT_REFERENCE    => $gatewayInput[Entity::MERCHANT_REFERENCE],
            Entity::METHOD                => $gatewayInput[Entity::METHOD],
            Entity::AMOUNT                => $gatewayInput[Entity::AMOUNT],
        ];
    }

    protected function getResponse(bool $valid)
    {
        if ($valid === true)
        {
            $xml = '<RESPONSE>OK</RESPONSE>';
        }
        else
        {
            $xml = '<RESPONSE>NOK</RESPONSE>';
        }

        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function determineAndSetModeForQr(string $merchantReference)
    {
        (new QrCode\Entity)->stripSignWithoutValidation($merchantReference);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($merchantReference, 'qr_code');

        if ($mode === null)
        {
            $mode = Mode::TEST;
        }

        $this->app['basicauth']->setModeAndDbConnection($mode);
    }
}
