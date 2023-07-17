<?php

namespace RZP\Models\QrPaymentRequest;

use Carbon\Carbon;

use Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Upi\Yesbank\Fields;
use RZP\Models\Base\UniqueIdEntity;
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

    public function createFailedQRPaymentRequest($input, $type, $gateway = null)
    {
        try
        {
            switch ($gateway)
            {
                case BaseConstants::UPI_YESBANK:
                    $request[Entity::QR_CODE_ID]            = substr($input['data']['upi'][Fields::MERCHANT_REFERENCE], 0, UniqueIdEntity::ID_LENGTH);
                    $request[Entity::TRANSACTION_REFERENCE] = $input['data']['upi'][Fields::NPCI_REFERENCE_ID];
                    break;

                case BaseConstants::UPI_ICICI:
                    $data                                   = json_decode($input, true);
                    $request[Entity::QR_CODE_ID]            = substr($data[ICICIFields::MERCHANT_TRAN_ID], 0, UniqueIdEntity::ID_LENGTH);
                    $request[Entity::TRANSACTION_REFERENCE] = (string) $data[ICICIFields::BANK_RRN];
                    break;

                default:
                    return null;
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
                    Entity::QR_CODE_ID            => $request[Entity::QR_CODE_ID],
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
