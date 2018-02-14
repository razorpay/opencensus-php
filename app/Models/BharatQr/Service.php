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

        $bharatQrInputParams = $this->callGatewayFunction($gateway, $input);

        $bharatQrInputParams[Entity::GATEWAY] = $gateway;

        list($valid, $payment) = $this->core->processPayment($bharatQrInputParams);

        $input['payment'] = $payment;

        $this->callGatewayFunction($gateway, $input);

        $response = $this->getResponse($valid);

        return $response;
    }

    protected function callGatewayFunction(string $gateway, array $gatewayInput)
    {
        $action = Action::QR_NOTIFICATION;

        return $this->app['gateway']->call($gateway, $action, $gatewayInput, null);
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
            $mode = Mode::LIVE;
        }

        $this->app['basicauth']->setModeAndDbConnection($mode);
    }
}
