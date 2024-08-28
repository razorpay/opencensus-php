<?php

namespace RZP\Jobs\Transfers;

use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Constants\Metric;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;

class TransferReversalCreateTransaction extends Job
{
    protected $reversalAndRefundJournalIds;

    protected  $delaySecs = 900;

    const MAX_RETRY_ATTEMPT = 50;

    protected $queueConfigKey = 'transfer_reversal_transaction_create_process';


    public function __construct(string $mode, $reversalAndRefundJournalIds, $delaySecs = 900)
    {
        parent::__construct($mode);

        $this->reversalAndRefundJournalIds = $reversalAndRefundJournalIds;

        $this->delaySecs = $delaySecs;
    }

    public function handle()
    {
        parent::handle();

        if ((app()->isEnvironmentProduction() === true) and
            ($this->mode === Mode::TEST))
        {
            return;
        }

        try
        {
            app('worker.ctx')->setLedgerDualWriteFlow(true);

            $response   =  (new Transfer\Core())->createTransferReversalTransactions($this->reversalAndRefundJournalIds);

            $this->trace->info(TraceCode::TRANSFER_REVERSAL_TRANSACTION_CREATE_SUCCESS, [
                'response'      => $response
            ]);

            $this->delete();

            return;

        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::TRANSFER_REVERSAL_TRANSACTION_CREATE_FAILURE_QUEUE,
                [
                    'reversalAndRefundJournalIds' => $this->reversalAndRefundJournalIds,
                    'mode' =>  $this->mode,
                ]);

            $this->trace->count(Metric::TRANSFER_REVERSAL_TXN_CREATE_FROM_QUEUE_FAILURE);

            $this->checkRetry($this->delaySecs, $ex);

            return;

        }
    }

    protected function checkRetry($retryTime, \Exception $e): void
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->count(Metric::TRANSFER_REVERSAL_API_TXN_JOB_RETRY_EXHAUSTED);

            $this->trace->error(TraceCode::TRANSFER_REVERSAL_API_TXN_JOB_RETRY_EXHAUSTED, [
                "input" => $this->reversalAndRefundJournalIds,
                "attempts" => $this->attempts()
            ]);

            $this->delete();
        }
        else
        {
            $this->trace->info(TraceCode::TRANSFER_REVERSAL_API_TXN_JOB_DISPTACH_RETRY,
                [
                    "input" => $this->reversalAndRefundJournalIds,
                    "attempts" => $this->attempts()
                ]
            );

            $this->release($retryTime);
        }
    }
}

