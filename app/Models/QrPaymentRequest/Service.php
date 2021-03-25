<?php

namespace RZP\Models\QrPaymentRequest;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
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

            return $this->core->create($input, $gatewayResponse['callback_data']);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::QR_PAYMENT_SAVE_REQUEST_FAILED,
                [
                    Entity::QR_CODE_ID => $gatewayResponse['qr_data']['merchant_reference'],
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
            $qrPaymentRequest->setExpected($isExpected);

            $qrPaymentRequest->setFailureReason($errorMessage);

            $qrPaymentRequest->setQrPaymentEntity($qrPaymentEntity, $type);

            $qrPaymentRequest->setCreated($qrPaymentEntity);

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

            default:
                throw new LogicException('Invalid QR type');
        }

        return $input;
    }

}
