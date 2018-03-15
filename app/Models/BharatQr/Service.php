<?php

namespace RZP\Models\BharatQr;

use RZP\Constants;
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

        $input = $gatewayClass->preProcessServerCallback($input, true);

        $qrData = $input['qr_data'];

        $qrCodeId = $qrData[GatewayResponseParams::MERCHANT_REFERENCE];

        $this->determineAndSetModeForQr($qrCodeId);

        $qrData[GatewayResponseParams::GATEWAY] = $gateway;

        $bharatQrInputParams = $this->getBharatQrInputParams($qrData);

        list($valid, $bharatQr) = $this->core->processPayment($bharatQrInputParams, $qrData);

        //
        // In case of duplicate notification
        // we don't create new payment
        //
        if ($bharatQr !== null)
        {
            $gatewayInput = $input['gateway_input'];

            $gatewayInput['payment'] = $bharatQr->payment->toArray();

            $this->callGatewayAuthorize($gateway, $gatewayInput);

        }

        $response = $this->getResponse($valid);

        return $response;
    }

    protected function callGatewayAuthorize(string $gateway, array $gatewayInput)
    {
        $gatewayInput['qr_notification'] = true;

        return $this->app['gateway']->call($gateway, Action::AUTHORIZE, $gatewayInput, null);
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

        $mode = $this->repo->determineLiveOrTestModeForEntity($merchantReference, Constants\Entity::QR_CODE);

        if ($mode === null)
        {
            $mode = Mode::LIVE;
        }

        $this->app['basicauth']->setModeAndDbConnection($mode);
    }
}
