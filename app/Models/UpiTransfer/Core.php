<?php

namespace RZP\Models\UpiTransfer;

use Config;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\VirtualAccount;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processPayment(array $gatewayResponse, $terminal)
    {
        $upiTransferInput = $gatewayResponse['upi_transfer_data'];

        $upiTransferTraceInput = $upiTransferInput;

        unset($upiTransferTraceInput['payer_account']);

        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESS_REQUEST,
            $upiTransferTraceInput
        );

        $this->convertPayeeVpaToLower($upiTransferInput);

        $upiTransfer = null;

        $paymentSuccess = false;

        try
        {
            $upiTransfer = (new Entity)->build($upiTransferInput);

            $this->mutex->acquireAndRelease(
                $upiTransferInput[Entity::PROVIDER_REFERENCE_ID],
                function() use($gatewayResponse, $terminal, $upiTransfer)
                {
                    (new Processor($gatewayResponse, $terminal))->process($upiTransfer);
                },
                $ttl = 30,
                $errorCode = ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS,
                // A process will generally not need to do multiple retries at
                // all, since the retry times are adequate for the previous
                // process to complete. Still setting to 3 for freak occurrences.
                $retryCount = 3,
                // 2x and 4x of avg response time for this entire route
                // (not just the process within the lock)
                $minRetryDelay = 600,
                $maxRetryDelay = 1200
            );

            $paymentSuccess = true;

            return true;
        }
        catch (\Throwable $e)
        {
            $paymentSuccess = false;

            $this->alertException($e, $upiTransferInput);

            return false;
        }
        finally
        {
            $isExpected = null;

            if ($upiTransfer !== null)
            {
                $isExpected = $upiTransfer->isExpected();
            }

            (new VirtualAccount\Metric())->pushPaymentMetrics(Constants\Entity::UPI_TRANSFER, $isExpected, $paymentSuccess);

            $this->pushUpiTransferSourceToLake($upiTransfer);
        }
    }

    /**
     * Trace and send an alert to Slack.
     *
     * @param \Throwable $ex
     * @param array      $input
     */
    public function alertException(\Throwable $ex, array $input)
    {
        $input = $input['upi_transfer_data'] ?? $input;

        $this->trace->traceException(
            $ex, Trace::CRITICAL, TraceCode::UPI_TRANSFER_PAYMENT_PROCESSING_FAILED, $input);
    }

    protected function convertPayeeVpaToLower(array & $input)
    {
        $payeeVpa = $input['payee_vpa'];

        $input['payee_vpa'] = strtolower($payeeVpa);
    }

    protected function pushUpiTransferSourceToLake($upiTransfer)
    {
        if ($upiTransfer === null)
        {
            return;
        }

        $properties = [
            'source'        => 'callback',
            'request_from'  => 'bank',
            'gateway'       => $upiTransfer->getGateway(),
        ];

        $this->app['diag']->trackUpiTransferEvent(
            EventCode::UPI_TRANSFER_REQUEST,
            $upiTransfer,
            null,
            $properties
        );
    }
}
