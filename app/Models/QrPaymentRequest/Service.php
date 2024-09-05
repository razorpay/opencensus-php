<?php

namespace RZP\Models\QrPaymentRequest;

use Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use RZP\Models\QrCode\Metric;
use RZP\Models\Payment\Method;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Upi\Yesbank\Fields;
use RZP\Constants\Entity as BaseConstants;
use RZP\Gateway\Upi\icici\Fields as ICICIFields;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create($gatewayResponse, $type)
    {
        try
        {
            $input = $this->getInputFromGatewayResponse($gatewayResponse['qr_data'], $type);

            switch ($gatewayResponse['qr_data']['method'])
            {
                case Method::BANK_TRANSFER:
                    $callbackData = $gatewayResponse['original_callback_data'];

                    break;

                default:
                    $callbackData = $gatewayResponse['callback_data'];
            }

            return $this->core->create($input, $callbackData);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::QR_PAYMENT_SAVE_REQUEST_FAILED,
                [
                    Entity::QR_CODE_ID            => $input[Entity::QR_CODE_ID],
                    Entity::TRANSACTION_REFERENCE => $input[Entity::TRANSACTION_REFERENCE]
                ]
            );
        }

        return null;
    }

    public function createForQrPaymentTriggeredViaNewGatewayAdapter($input, $callbackData, $isFailure = false)
    {
        try
        {
            return $this->core->create($input, $callbackData, $isFailure);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::QR_PAYMENT_SAVE_REQUEST_FAILED,
                [
                    Entity::QR_CODE_ID            => $input[Entity::QR_CODE_ID],
                    Entity::TRANSACTION_REFERENCE => $input[Entity::TRANSACTION_REFERENCE]
                ]
            );
        }

        return null;
    }

    public function createForQrPaymentTriggeredViaNewGatewayAdapterDuringRecon($input, $callbackData, $isFailure = false)
    {
        $qrCode = $this->repo->qr_code->findByMerchantReference($input['merchant_reference']);
        $qrpInput = [
            Entity::QR_CODE_ID            => $qrCode->getId() ?? $input['merchant_reference'],
            Entity::TRANSACTION_REFERENCE => $input['provider_reference_id']
        ];

        try
        {
            return $this->core->create($qrpInput, $callbackData, $isFailure);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::QR_PAYMENT_SAVE_REQUEST_FAILED,
                [
                    Entity::QR_CODE_ID            => $input[Entity::QR_CODE_ID],
                    Entity::TRANSACTION_REFERENCE => $input[Entity::TRANSACTION_REFERENCE]
                ]
            );
        }

        return null;
    }

    public function initGatewayCallForQrStatusCheck(string $id): void
    {
        /**
         * @var $qrCode Entity
         */
        $qrCode = $this->repo->qr_code->findOrFail($id) ;
        $this->app['basicauth']->setMerchantById($qrCode->getMerchantId());

        $qrVariant = false;

        // $callbackData shall store the response received from the gateway
        $gatewayData = (new Core())->qrPaymentStatusCheck($qrCode, $qrVariant);

        if ($gatewayData !== null)
        {
            try
            {
                if (($qrVariant === true) or
                    ($gatewayData['terminal']['gateway'] === 'upi_rzpapb'))
                {
                    (new \RZP\Models\QrPayment\Service())->processQrPaymentForNewGatewayFlow(
                        $qrCode, $gatewayData, $gatewayData['terminal']['gateway'], true);
                }
                else
                {
                    (new \RZP\Models\BharatQr\Service())->processPayment($gatewayData['callbackData'], $gatewayData['gateway']);
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::QR_STATUS_CHECK_PAYMENT_CREATION_FAILED,
                    ['id' => $qrCode->getId()]
                );

                $errorMessage = '';
                $gateway = '';
                if ($e->getMessage() !== null)
                {
                    $errorMessage = $e->getMessage();
                }
                if (isset($gatewayData['gateway']) === true)
                {
                    $gateway = $gatewayData['gateway'];
                }
                $dimensions = [
                    'error_message' => $errorMessage,
                    'gateway'       => $gateway
                ];
                $this->trace->count(Metric::QR_STATUS_CHECK_PAYMENT_CREATION_FAILURE, $dimensions);
            }
        }
    }

    public function createFailedQRPaymentRequest($input, $gateway = null)
    {
        $merchantRef = null;
        try
        {
            switch ($gateway)
            {
                case BaseConstants::UPI_YESBANK:
                case BaseConstants::UPI_KOTAK:
                case BaseConstants::UPI_MINDGATE:
                case BaseConstants::UPI_AIRTEL:
                {
                    $input = $input['data'];

                    if (isset($input['meta']) === true)
                    {
                        unset($input['meta']);
                    }

                    if (isset($input['_raw']) === true)
                    {
                        unset($input['_raw']);
                    }

                    $merchantRef                            = $input['upi'][Fields::MERCHANT_REFERENCE];
                    $request[Entity::TRANSACTION_REFERENCE] = $input['upi'][Fields::NPCI_REFERENCE_ID];

                    break;
                }
                case BaseConstants::UPI_ICICI:
                {
                    if (is_array($input) === false)
                    {
                        $input = json_decode($input, true);
                    }

                    $merchantRef                            = $input[ICICIFields::MERCHANT_TRAN_ID];
                    $request[Entity::TRANSACTION_REFERENCE] = (string) $input[ICICIFields::BANK_RRN];

                    break;
                }
                default:
                    return null;
            }

            $gatewayClass = $this->app['gateway']->gateway($gateway);

            if (($merchantRef !== null) and
                (method_exists($gatewayClass, 'getQrPaymentMerchantReference') === true))
            {
                $request[Entity::QR_CODE_ID] = $gatewayClass->getQrPaymentMerchantReference($merchantRef);
            }

            return $this->core->create($request, $input, true);
        }
        catch (Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FAILED_QR_PAYMENT_SAVE_REQUEST_FAILED,
                [
                    'gateway'                     => $gateway,
                    'merchantReference'           => $merchantRef,
                    Entity::TRANSACTION_REFERENCE => $request[Entity::TRANSACTION_REFERENCE]
                ]
            );
        }

        return null;
    }

    public function update($qrPaymentRequest, $isExpected, $qrPaymentEntity, $errorMessage, $type)
    {
        if ($qrPaymentRequest === null)
        {
            $this->trace->info(
                TraceCode::QR_PAYMENT_UPDATE_REQUEST_FAILED,
                [
                    'message' => 'Qr Code payment request update failed, since the request was null'
                ]
            );

            return;
        }

        try
        {
            $qrPaymentRequest->setExpectedIfNotSet($isExpected);

            $qrPaymentRequest->setFailureReasonIfNotSet($errorMessage);

            if ($qrPaymentEntity !== null)
            {
                $qrPaymentRequest->setCreated($qrPaymentEntity->getPaymentId() !== null);
            }

            $qrPaymentRequest->setQrPaymentEntity($qrPaymentEntity, $type);

            $this->core->update($qrPaymentRequest);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::QR_PAYMENT_UPDATE_REQUEST_FAILED,
                [
                    Entity::ID                    => $qrPaymentRequest->getPublicId(),
                    Entity::QR_CODE_ID            => $qrPaymentRequest->getQrCodeId(),
                    Entity::TRANSACTION_REFERENCE => $qrPaymentRequest->getTransactionReference(),
                ]
            );
        }
    }

    private function getInputFromGatewayResponse($qrData, $type)
    {
        switch ($type)
        {
            case Type::BHARAT_QR:
                $input[Entity::QR_CODE_ID] = $qrData['merchant_reference'];
                $input[Entity::TRANSACTION_REFERENCE] = $qrData['provider_reference_id'];
                break;

            case Type::UPI_QR:
                $qrId = $qrData[Entity::QR_CODE_ID];

                if(strlen($qrId) > 14 and starts_with($qrId,'STQ') === true) {
                    $qrId = substr($qrId, 3, 14);
                }

                $input[Entity::QR_CODE_ID]            = $qrId;
                $input[Entity::TRANSACTION_REFERENCE] = $qrData[Entity::TRANSACTION_REFERENCE];
                break;

            default:
                throw new LogicException('Invalid QR type');
        }

        return $input;
    }

}
