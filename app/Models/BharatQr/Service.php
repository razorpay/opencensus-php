<?php

namespace RZP\Models\BharatQr;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

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

        $this->validateGateway($gateway);

        $gatewayClass = $this->app['gateway']->gateway($gateway);

        try
        {
            $gatewayResponse = $gatewayClass->preProcessServerCallback($input, true);
        }
        catch(Exception\GatewayErrorException $ex)
        {
            $this->trace->traceException($ex);

            return $gatewayClass->getBharatQrResponse(false, $input, $ex);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            return $gatewayClass->getBharatQrResponse(false, $input);
        }

        $qrData = $gatewayResponse['qr_data'];

        (new Validator)->validateInput('gateway_response', $qrData);

        $qrCodeId = $qrData[GatewayResponseParams::MERCHANT_REFERENCE];

        $this->determineAndSetModeForQr($qrCodeId, $gateway);

        $gatewayResponse['qr_data'][GatewayResponseParams::GATEWAY] = $gateway;

        $bharatQrProcessor = $this->getNewProcessor($gatewayResponse);

        $terminal = $bharatQrProcessor->getTerminal();

        try
        {
            // before processing payment, we will call verify callback to check if the
            // notification was sent by the gateway or some other source .
            $gatewayClass->verifyBharatQrCallback($input, $terminal->getGatewayTerminalId());
        }
        catch(Exception\GatewayErrorException $ex)
        {
            $this->traceException($ex, null, TraceCode::GATEWAY_VERIFY_ERROR, ['gateway'   => $gateway]);

            return $gatewayClass->getBharatQrResponse(false, $input, $ex);
        }
        catch(\Exception $ex)
        {
            $this->traceException($ex, null, TraceCode::RUNTIME_ERROR);

            return $gatewayClass->getBharatQrResponse(false, $input);
        }

        $valid = $this->core->processPayment($gatewayResponse);

        $response = $gatewayClass->getBharatQrResponse($valid, $input);

        return $response;
    }

    protected function validateGateway(string $gateway)
    {
        if (Payment\Gateway::isValidBharatQrGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway is invalid',
                'gateway',
                [
                    'gateway' => $gateway
                ]);
        }

        //
        // We throw a url not found exception here
        // because test payments are not allowed
        // on direct auth
        //
        if (($gateway === Payment\Gateway::SHARP) and
            ($this->merchant === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }
    protected function determineAndSetModeForQr(string $merchantReference, string $gateway)
    {
        // We are not using verifyIdAndSilentlyStripSign here because in case
        // of unexpected payments reference id will be random and this will throw
        // exception.
        (new QrCode\Entity)->stripSignWithoutValidation($merchantReference);

        if ($gateway === Payment\Gateway::SHARP)
        {
            $mode = Mode::TEST;
        }
        else
        {
            $mode = $this->repo->determineLiveOrTestModeForEntityByMerchantReference($merchantReference,
                                                                                              Constants\Entity::QR_CODE);

            $mode = $mode ?? Mode::LIVE;
        }

        $this->app['basicauth']->setModeAndDbConnection($mode);
    }

    protected function getNewProcessor($gatewayResponse)
    {
        $processor = (new Processor($gatewayResponse));;

        return $processor;
    }
}
