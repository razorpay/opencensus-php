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

        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESS_REQUEST,
            [
                'upi_transfer_data' => $this->removePiiForLogging($upiTransferInput),
                'terminal'          => $terminal->getId(),
            ]
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
            $ex,
            Trace::CRITICAL,
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESSING_FAILED,
            $this->removePiiForLogging($input, [Entity::PAYEE_VPA])
        );
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
        ];

        $this->app['diag']->trackUpiTransferRequestEvent(
            EventCode::UPI_TRANSFER_REQUEST,
            $upiTransfer,
            null,
            $properties
        );
    }

    /**
     * Pass fields to $fields that are not to be logged.
     * If nothing is passed, default PII fields will be
     * fetched from Entity class. If any field is not to
     * be completely removed, use the switch-case.
     *
     * @param array $array
     * @param array $fields
     * @return array
     */
    public function removePiiForLogging(array $array, array $fields = [])
    {
        if (empty($fields) === true)
        {
            $fields = (new Entity())->getPii();
        }

        foreach ($fields as $field)
        {
            if (isset($array[$field]) === false)
            {
                continue;
            }

            switch ($field)
            {
                case Entity::PAYEE_VPA:
                    $payeeVpa = $array[Entity::PAYEE_VPA];

                    $array[Entity::PAYEE_VPA . '_root']     = explode('.', $payeeVpa)[0];
                    $array[Entity::PAYEE_VPA . '_dynamic']  = explode('@', explode('.', $payeeVpa)[1])[0];
                    $array[Entity::PAYEE_VPA . '_handle']   = explode('@', $payeeVpa)[1];

                    break;

                default:
                    break;
            }

            unset($array[$field]);
        }

        return $array;
    }
}
