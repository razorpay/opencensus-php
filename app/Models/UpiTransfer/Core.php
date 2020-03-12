<?php

namespace RZP\Models\UpiTransfer;

use Config;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Terminal\Entity as Terminal;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processPayment(array $gatewayResponse, $terminals)
    {
        $upiTransferInput = $gatewayResponse['upi_transfer_data'];

        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESS_REQUEST,
            $upiTransferInput
        );

        $this->convertPayeeVpaToLower($upiTransferInput);

        try
        {
            $terminal = $this->filterTerminal($upiTransferInput, $terminals);

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
            return true;
        }
        catch (\Throwable $e)
        {
            $this->alertException($e, $upiTransferInput);

            return false;
        }
    }

    protected function filterTerminal($gatewayResponse, $terminals)
    {
        $payeeVpa = $gatewayResponse[GatewayResponseParams::PAYEE_VPA];

        $selectedTerminals = $terminals->filter(function(Terminal $terminal) use ($payeeVpa) {
            // We will filter the terminals which could possibly be used to make this vpa username.
            return $terminal->isValidVirtualVpaForTerminal($payeeVpa);
        });

        if ($selectedTerminals->count() === 0)
        {
            // Count zero means none of the terminals could have created this vpa
            // and this is an unexpected upi transfer. This case will only happen if we get request
            // for root that is not allotted to us.
            throw new LogicException('Should not have reached here');
        }
        if ($selectedTerminals->count() !== 1)
        {
            // If a VPA matches with 2 terminals that means this can be created from
            // either of these and we should probably change the handle for 1 of the merchant
            // to avoid such future cases.
            $this->trace->error(
                TraceCode::UPI_TRANSFER_TERMINAL_COUNT_GREATER_THEN_ONE,
                [
                    'terminal_ids' => $selectedTerminals->getIds()
                ]
            );
        }

        return $selectedTerminals->first();
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

        // Slack alerts are only for prod
        if ($this->isEnvironmentProduction() === false)
        {
            return;
        }

        $this->app['slack']->queue(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESSING_FAILED,
            array_merge($input, ['message' => $ex->getMessage()]),
            [
                'channel'  => Config::get('slack.channels.upi_transfer_logs'),
                'username' => 'Scrooge',
                'icon'     => ':x:'
            ]
        );
    }

    protected function convertPayeeVpaToLower(array & $input)
    {
        $payeeVpa = $input['payee_vpa'];

        $input['payee_vpa'] = strtolower($payeeVpa);
    }
}
