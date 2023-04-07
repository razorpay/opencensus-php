<?php

namespace RZP\Models\QrPaymentRequest;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

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
                $input[Entity::QR_CODE_ID]            = $qrData['merchant_reference'];
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
