<?php

namespace RZP\Models\BharatQr;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Gateway\Sharp;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\QrPayment;
use RZP\Models\Payment\Gateway;
use RZP\Models\QrPaymentRequest;
use RZP\Gateway\Upi\Icici\Fields;
use RZP\Gateway\Hitachi\ResponseFields;
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
            [$terminal, $gatewayResponse] = $this->getTerminalAndGatewayReponse($input, $gatewayClass, $gateway);

            $qrPaymentRequest = (new QrPaymentRequest\Service())->create($gatewayResponse, QrPaymentRequest\Type::BHARAT_QR);

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

        $valid = $this->processQrCodePayment($gatewayResponse, $terminal, $qrPaymentRequest);

        return $gatewayClass->getBharatQrResponse($valid, $input);
    }

    private function processQrCodePayment($gatewayResponse, $terminal, $qrPaymentRequest)
    {
        $isQrCodeV2 = $this->isNonVAQrCodePayment($gatewayResponse);

        if ($isQrCodeV2 === true)
        {
            $valid = (new QrPayment\Core())->processPayment($gatewayResponse, $terminal, $qrPaymentRequest);
        }
        else
        {
            $valid = $this->core->processPayment($gatewayResponse, $terminal, $qrPaymentRequest);
        }

        return $valid;
    }

    private function getTerminalAndGatewayReponse($input, $gatewayClass, $gateway)
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

        $terminal = $terminal ?: $this->getTerminal($gatewayResponse['qr_data']);

        return [$terminal, $gatewayResponse];
    }

    public function processPaymentInternal($input, $gateway)
    {
        $gatewayClass = $this->app['gateway']->gateway($gateway);

        $qrPaymentRequest = null;

        try
        {
            [$terminal, $gatewayResponse] = $this->getTerminalAndGatewayReponse(json_encode($input), $gatewayClass, $gateway);

            $isQrCodeV2 = $this->isNonVAQrCodePayment($gatewayResponse);

            $qrPayment = $this->findQrPayment($gatewayResponse['qr_data'], $isQrCodeV2);

            if ($qrPayment !== null)
            {
                return $this->getQrPaymentResponseInternal($qrPayment);
            }

            $qrPaymentRequest = (new QrPaymentRequest\Service())->create($gatewayResponse, QrPaymentRequest\Type::BHARAT_QR);

            $valid = $this->processQrCodePayment($gatewayResponse, $terminal, $qrPaymentRequest);

            if ($valid === true)
            {
                $qrPayment = $this->findQrPayment($gatewayResponse['qr_data'], $isQrCodeV2);

                if ($qrPayment !== null)
                {
                    return $this->getQrPaymentResponseInternal($qrPayment);
                }
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            if ($qrPaymentRequest !== null)
            {
                (new QrPaymentRequest\Service())->update($qrPaymentRequest, null, null,
                                                         $ex->getMessage(), QrPaymentRequest\Type::BHARAT_QR);

            }

            return $this->getQrPaymentResponseInternal(null, $ex->getMessage());
        }

        return $this->getQrPaymentResponseInternal(null, $qrPaymentRequest->getFailureReason());
    }

    private function getQrPaymentResponseInternal($qrPayment = null, $errorMessage = null)
    {
        if ($qrPayment !== null)
        {
            $payment = $qrPayment->payment;

            $response = ['payment' => $payment->toArrayRecon()];

            $response['refunds'] = $payment->refunds->toArrayRecon();

            return $response;
        }
        else
        {
            throw new Exception\ServerErrorException($errorMessage, ErrorCode::SERVER_ERROR_QR_PAYMENT_PROCESSING_FAILED);
        }
    }

    private function findQrPayment($gatewayQrData, $isQrCodeV2)
    {
        if ($isQrCodeV2 === true)
        {
            return $this->repo->qr_payment->findByProviderReferenceIdAndGatewayAndAmount($gatewayQrData[GatewayResponseParams::PROVIDER_REFERENCE_ID],
                                                                                         $gatewayQrData[GatewayResponseParams::GATEWAY],
                                                                                         $gatewayQrData[GatewayResponseParams::AMOUNT]);
        }
        else
        {
            return $this->repo->bharat_qr->findByProviderReferenceIdAndAmount($gatewayQrData[GatewayResponseParams::PROVIDER_REFERENCE_ID],
                                                                              $gatewayQrData[GatewayResponseParams::AMOUNT]);
        }
    }

    private function isNonVAQrCodePayment($gatewayResponse)
    {
        $tr = $this->getTrFieldForGateway($gatewayResponse);

        $suffixLength = strlen(QrCode\Constants::QR_CODE_V2_TR_SUFFIX);

        if ((strlen($tr) >= ($suffixLength + Entity::ID_LENGTH)) and
            (str_ends_with($tr, QrCode\Constants::QR_CODE_V2_TR_SUFFIX)))
        {
            return true;
        }

        return false;
    }

    private function getTrFieldForGateway($gatewayResponse)
    {
        switch ($gatewayResponse['qr_data'][GatewayResponseParams::GATEWAY])
        {
            case Gateway::UPI_ICICI:
                return $gatewayResponse['callback_data'][Fields::MERCHANT_TRAN_ID];
                break;

            case Gateway::HITACHI:
                return $gatewayResponse['callback_data'][ResponseFields::PURCHASE_ID];
                break;

            case Gateway::SHARP:
                return $gatewayResponse['callback_data'][Sharp\Fields::REFERENCE];
                break;
        }
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
