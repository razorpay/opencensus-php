<?php

namespace RZP\Models\QrPayment;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BharatQr;
use RZP\Models\BankAccount;
use RZP\Models\QrPayment\Metric;
use RZP\Models\Payment\Gateway;
use RZP\Models\QrPaymentRequest;
use RZP\Gateway\Upi\Icici\Fields;

class Core extends Base\Core
{
    protected $mutex;

    const MUTEX_KEY = 'qr_payment_processing_%s_%s';

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processPayment($gatewayResponse, $terminal, $qrPaymentRequest)
    {
        $input = $this->getQrPaymentInputParams($gatewayResponse['qr_data']);

        $errorMessage = null;

        try
        {
            if(array_key_exists('notes', $input))
            {
                $input['notes'] = substr($input['notes'], 0, Entity::MAX_NOTES_LENGTH);
            }

            $qrPayment = (new Entity)->build($input);

            $mutexKey = sprintf(self::MUTEX_KEY, $input[Entity::MERCHANT_REFERENCE], $input[Entity::PROVIDER_REFERENCE_ID]);

            $this->mutex->acquireAndRelease(
                $mutexKey,
                function() use ($qrPayment, $gatewayResponse, $terminal) {
                    $qrPayment = (new Processor($gatewayResponse, $terminal))->process($qrPayment);

                    // This will be null in case it's a duplicate notification
                    return $qrPayment;
                },
                // Avg response time of this whole route is about 300ms,
                // so 10x of that should be quite safe
                $ttl = 30,
                $errorCode = ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS,
                // A process will generally not need to do multiple retries at
                // all, since the retry times are adequate for the previous
                // process to complete. Still setting to 3 for freak occurrences.
                $retryCount = 3,
                // 2x and 4x of avg response time for this entire route
                // (not just the process within the lock)
                $minRetryDelay = 600,
                $maxRetryDelay = 1200);

            $valid = true;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex);

            $errorMessage = $ex->getMessage();

            switch ($errorMessage)
            {
                case TraceCode::QR_PAYMENT_DUPLICATE_NOTIFICATION:
                    $valid = true;
                    break;

                default:
                    $valid = false;
            }
        }
        finally
        {
            $isExpected = $qrPayment === null ? null : $qrPayment->isExpected();

            $method = $gatewayResponse['qr_data']['method'];

            $gateway = $gatewayResponse['qr_data'][BharatQr\GatewayResponseParams::GATEWAY];

            $requestSource = $qrPayment ? optional($qrPayment->qrCode)->getRequestSource() : null;

            $type = QrPaymentRequest\Type::BHARAT_QR;

            if(isset($qrPayment->qrCode->merchant) === true)
            {
                if($qrPayment->qrCode->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
                    $type = QrPaymentRequest\Type::UPI_QR;
            }

            (new QrPaymentRequest\Service())->update($qrPaymentRequest, $isExpected, $qrPayment, $errorMessage, $type);

            $isSharedTerminalPayment = $terminal->isShared();

            (new Metric())->pushQrV2PaymentsMetrics($isExpected, $valid, $gateway, $method, $errorMessage,
                                                    $requestSource, $isSharedTerminalPayment);
        }

        return $valid;
    }

    protected function getQrPaymentInputParams(array $gatewayInputQrData)
    {
        $input = [
            Entity::PROVIDER_REFERENCE_ID => $gatewayInputQrData[BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID],
            Entity::MERCHANT_REFERENCE    => $gatewayInputQrData[BharatQr\GatewayResponseParams::MERCHANT_REFERENCE],
            Entity::METHOD                => $gatewayInputQrData[BharatQr\GatewayResponseParams::METHOD],
            Entity::AMOUNT                => $gatewayInputQrData[BharatQr\GatewayResponseParams::AMOUNT],
            Entity::GATEWAY               => $gatewayInputQrData[BharatQr\GatewayResponseParams::GATEWAY],
            Entity::PAYER_VPA             => $gatewayInputQrData[BharatQr\GatewayResponseParams::VPA] ?? null,
        ];

        if (array_key_exists(ENTITY::TRANSACTION_TIME, $gatewayInputQrData))
        {
            $input[Entity::TRANSACTION_TIME] = $gatewayInputQrData[BharatQr\GatewayResponseParams::TRANSACTION_TIME];
        }

        if (array_key_exists(Entity::NOTES, $gatewayInputQrData) === true)
        {
            $input[Entity::NOTES] = $gatewayInputQrData[Entity::NOTES];
        }

        if ($gatewayInputQrData[BharatQr\GatewayResponseParams::GATEWAY] === Gateway::SHARP)
        {
            $input[Entity::MERCHANT_REFERENCE] = substr($input[Entity::MERCHANT_REFERENCE], 0, 14);
        }

        return $input;
    }

    public function getAccountForRefund(Entity $qrPayment)
    {
        $payerAccount = $qrPayment->payerBankAccount;

        return [
            BankAccount\Entity::IFSC_CODE        => $payerAccount->getIfscCode(),
            BankAccount\Entity::ACCOUNT_NUMBER   => $payerAccount->getAccountNumber(),
            BankAccount\Entity::BENEFICIARY_NAME => $payerAccount->getBeneficiaryName()
        ];
    }
}
