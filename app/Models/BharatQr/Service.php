<?php

namespace RZP\Models\BharatQr;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\QrPaymentRequest;
use RZP\Models\Mpan\Entity as MpanEntity;

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
        $inputTrace = $input;

        if (is_array($inputTrace) === true)
        {
            if (isset($inputTrace['mpan']) === true)
            {
                // logs only first 6 and last 4, mask remaining
                $inputTrace['mpan'] =  (new MpanEntity)->getMaskedMpan($inputTrace['mpan']);
            }

            unset($inputTrace['customer_name'], $inputTrace['MERCHANT_PAN']);
        }

        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST,
            [
                'input'   => $inputTrace,
                'gateway' => $gateway,
            ]);

        $this->validateGateway($gateway);

        $gatewayClass = $this->app['gateway']->gateway($gateway);

        $qrPaymentRequest = null;

        try
        {
            $terminalDetails = null;

            // In some cases, for decrypting the s2s callback response, we need to fetch secrets from the terminal and
            // not use the common secret present in config. For such cases, we fetch the corresponding terminal using
            // the details present in the callback response.
            if (method_exists($gatewayClass, 'getTerminalDetailsFromCallbackIfApplicable') === true)
            {
                $terminalDetails = $gatewayClass->getTerminalDetailsFromCallbackIfApplicable($input);
            }

            $terminal = null;

            if ($terminalDetails !== null)
            {
                $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $terminalDetails);

                $gatewayClass->setGatewayParams($input, $this->mode, $terminal);
            }

            $gatewayResponse = $gatewayClass->preProcessServerCallback($input, true);

            $qrData = $gatewayResponse['qr_data'];

            (new Validator)->validateInput('gateway_response', $qrData);

            $qrCodeId = $qrData[GatewayResponseParams::MERCHANT_REFERENCE];

            $this->determineAndSetModeForQr($qrCodeId, $gateway);

            $gatewayResponse['qr_data'][GatewayResponseParams::GATEWAY] = $gateway;

            $qrPaymentRequest = (new QrPaymentRequest\Service())->create($gatewayResponse, QrPaymentRequest\Type::BHARAT_QR);

            $terminal = $terminal ?: $this->getTerminal($gatewayResponse['qr_data']);

            $gatewayClass->setGatewayParams($gatewayResponse, $this->mode, $terminal);

            // before processing payment, we will call verify callback to check if the
            // notification was sent by the gateway or some other source .
            // skipping verification for worldline gateway altogether for Axis-BQR as worldline's verification have some issues
            // Axis-bank has agreed to take responsibility of not having verification
            if ($gateway !== Gateway::WORLDLINE)
            {
                $gatewayClass->verifyBharatQrNotification($gatewayResponse);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            (new QrPaymentRequest\Service())->update($qrPaymentRequest, null, null,
                                                     $ex->getMessage(), QrPaymentRequest\Type::BHARAT_QR);

            return $gatewayClass->getBharatQrResponse(false, $input, $ex);
        }

        $valid = $this->core->processPayment($gatewayResponse, $terminal, $qrPaymentRequest);

        $response = $gatewayClass->getBharatQrResponse($valid, $input);

        return $response;
    }

    protected function getTerminal($gatewayResponse)
    {
        $gateway = $gatewayResponse[GatewayResponseParams::GATEWAY];

        $terminal = null;

        // some gateways allow multiple terminals per mid, so need to find by both mid and mpan
        switch ($gateway)
        {
            case Payment\Gateway::WORLDLINE:
                if (isset($gatewayResponse[GatewayResponseParams::MPAN]) === true)
                {
                    if (isset($gatewayResponse[GatewayResponseParams::GATEWAY_MERCHANT_ID]) === true)
                    {
                        $gatewayMerchantId = $gatewayResponse[GatewayResponseParams::GATEWAY_MERCHANT_ID];

                        $gatewayMpan = $gatewayResponse[GatewayResponseParams::MPAN];

                        // Till the migration cron runs, we need to find terminal by both original mpan and tokenized mpan
                        // after all the terminal mpans are migrated, we can just find the terminal by tokenized mpan
                        $terminal = $this->repo->terminal->findEnabledTerminalByMpanAndGatewayMerchantId($gatewayMerchantId, $gateway, $gatewayMpan);

                        // if terminal is not found using original mpan, than it might have been tokenized in the terminal, finding using tokenized mpan below
                        if ($terminal === null)
                        {
                            $tokenizedMpan =  $this->app['mpan.cardVault']->tokenize(['secret' => $gatewayMpan]);

                            // if push payment request came, it means terminal is already activated on gateway, we need to find enabled terminal on our end.
                            // For worldline, only pending or activated terminal can have enabled true
                            $terminal = $this->repo->terminal->findEnabledTerminalByMpanAndGatewayMerchantId($gatewayMerchantId, $gateway, $tokenizedMpan);
                        }
                    }
                }
                else
                {
                    $terminal = $this->getTerminalDefaultCase($gatewayResponse, $gateway);
                }
                break;
            default:
                $terminal = $this->getTerminalDefaultCase($gatewayResponse, $gateway);
        }

        if ($terminal === null)
        {
            $gatewayResponseTrace = $gatewayResponse;

            if (is_array($gatewayResponseTrace) === true)
            {
                unset($gatewayResponseTrace['mpan']);
            }

            throw new Exception\LogicException(
                'Terminal should not be null here',
                null,
                [
                    'gateway_response'   => $gatewayResponseTrace,
                    'mode'               => $this->mode,
                ]
            );
        }

        return $terminal;
    }

    protected function getTerminalDefaultCase($gatewayResponse, $gateway)
    {
        if (isset($gatewayResponse[GatewayResponseParams::GATEWAY_MERCHANT_ID]) === true)
        {
            $gatewayMerchantId = $gatewayResponse[GatewayResponseParams::GATEWAY_MERCHANT_ID];

            return $this->repo->terminal->findActivatedTerminalByGatewayMerchantId($gatewayMerchantId, $gateway);
        }

        $gatewayMpan = $gatewayResponse[GatewayResponseParams::MPAN];

        // Till the migration cron runs, we need to find terminal by both original mpan and tokenized mpan
        // after all the terminal mpans are migrated, we can just find the terminal by tokenized mpan
        $terminal = $this->repo->terminal->findByGatewayMpan($gatewayMpan, $gateway);

        if ($terminal !== null)
        {
            return $terminal;
        }

        $tokenizedMpan =  $this->app['mpan.cardVault']->tokenize(['secret' => $gatewayMpan]);

        return $this->repo->terminal->findByGatewayMpan($tokenizedMpan, $gateway);
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
            $this->mode = Mode::TEST;
        }
        else
        {
            $mode = $this->repo->qr_code->determineLiveOrTestModeByMerchantReference($merchantReference);

            $this->mode = $mode ?? Mode::LIVE;
        }

        $this->app['basicauth']->setModeAndDbConnection($this->mode);
    }
}
