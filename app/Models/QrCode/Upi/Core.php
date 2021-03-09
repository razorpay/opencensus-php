<?php

namespace RZP\Models\QrCode\Upi;

use RZP\Models\Base;
use RZP\Services\Mutex;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;
use Razorpay\Trace\Logger as Trace;

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

    public function processPayment(array $input, string $referenceId, array $data, Terminal\Entity $terminal)
    {
        // We can build the entity with data['upi_qr'],
        // Upi Qr Entities are always expected by definition
        $errorMessage = null;

        try
        {
            $upiQr = (new Entity);

            $processor = new Processor($input, $referenceId, $data, $terminal);

            $this->mutex->acquireAndRelease($referenceId,
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
            // is expected is always true here, as the callback reaches this point only if it was expected, otherwise
            // the GatewayController would forward to create unexpected payment
            (new VirtualAccount\Metric())->pushPaymentMetrics('upi_qr', true, $valid,
                                                              $terminal->getGateway(), $errorMessage);
        }

        return $valid;
    }
}
