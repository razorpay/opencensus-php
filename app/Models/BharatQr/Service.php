<?php

namespace RZP\Models\BharatQr;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Action;

class Service extends Base\Service
{
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
            ]);

        if (Payment\Gateway::isValidBharatQrGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway is invalid',
                'gateway',
                [
                    'gateway' => $gateway
                ]);
        }

        $gatewayClass = $this->app['gateway']->gateway($gateway);

        try
        {
            $gatewayResponse = $gatewayClass->preProcessServerCallback($input, true);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            return $this->getResponse(false);
        }

        $qrData = $gatewayResponse['qr_data'];

        (new Validator)->validateInput('gateway_response', $qrData);

        $qrCodeId = $qrData[GatewayResponseParams::MERCHANT_REFERENCE];

        $this->determineAndSetModeForQr($qrCodeId);

        $gatewayResponse['qr_data'][GatewayResponseParams::GATEWAY] = $gateway;

        $valid = $this->core->processPayment($gatewayResponse);

        $response = $this->getResponse($valid);

        return $response;
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
        // We are not using verifyIdAndSilentlyStripSign here because in case
        // of unexpected payments reference id will be random and this will throw
        // exception.
        (new QrCode\Entity)->stripSignWithoutValidation($merchantReference);

        $mode = $this->repo->determineLiveOrTestModeForEntity($merchantReference, Constants\Entity::QR_CODE);

        if ($mode === null)
        {
            $mode = Mode::LIVE;
        }

        $this->app['basicauth']->setModeAndDbConnection($mode);
    }
}
