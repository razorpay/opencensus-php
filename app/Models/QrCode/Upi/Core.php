<?php

namespace RZP\Models\QrCode\Upi;

use RZP\Models\Base;
use RZP\Services\Mutex;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;
use RZP\Models\QrPaymentRequest;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\QrPaymentRequest\Type as QrType;
use RZP\Models\QrPaymentRequest\Service as QrPaymentRequestService;

class Core extends Base\Core
{
    /**
     * @var Mutex
     */
    protected $mutex;

    protected function init()
    {
        $this->mutex = $this->app['api.mutex'];
    }

    public function processPayment(array $input, string $referenceId, array $data, Terminal\Entity $terminal,
                                   $qrPaymentRequest = null)
    {
        // We can build the entity with data['upi_qr'],
        // Upi Qr Entities are always expected by definition
        $errorMessage = null;
        $payment = null;
        $upi = null;

        try
        {
            $upiQr = (new Entity);

            $processor = new Processor($input, $referenceId, $data, $terminal, $qrPaymentRequest);

            $upiQr = $this->mutex->acquireAndRelease($referenceId,
                function() use ($processor, $upiQr)
                {
                    $upiQr = $processor->process($upiQr);

                    // This will be null in case it's a duplicate notification
                    return $upiQr;
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
            $this->trace->traceException(
                $ex, Trace::CRITICAL, TraceCode::UPI_QR_PAYMENT_PROCESSING_FAILED, $input);

            $errorMessage = $ex->getMessage();

            $valid = false;
        }
        finally
        {
            if ($upiQr !== null and $upiQr->getPayment() !== null)
            {
                $upi = $this->repo->upi->fetchByPaymentId($upiQr->getPayment()->getId());

                if($terminal->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
                {
                    $gatewayResponse['qr_data'] = $this->getQrPaymentParams($input,$data['payment'],
                        $terminal->getGateway(),$upiQr->getPayment()->getId());
                }
            }

            if($terminal->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
            {
                $gatewayResponse['callback_data'] = [];

                (new \RZP\Models\QrPayment\Core())->processPayment($gatewayResponse, $terminal, $qrPaymentRequest);
            }

            (new QrPaymentRequestService())->update($qrPaymentRequest, true, $upi, $errorMessage, QrType::UPI_QR);

            // is expected is always true here, as the callback reaches this point only if it was expected, otherwise
            // the GatewayController would forward to create unexpected payment
            (new VirtualAccount\Metric())->pushPaymentMetrics(QrType::UPI_QR, true, $valid, $terminal->getGateway(),
                                                              $errorMessage);
        }

        return $valid;
    }


    private function getQrPaymentParams($input,$payment,$gateway,$paymentId): array
    {
        return [
            'provider_reference_id' => $input['npci_upi_txn_id'],
            'payer_vpa' => $payment['vpa'],
            'amount' => $payment['amount'],
            'method' => 'upi',
            'gateway' => $gateway,
            'merchant_reference' => $input['payment_id'],
            'payment_id'   =>  $paymentId,
        ];
    }
}
