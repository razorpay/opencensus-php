<?php

namespace RZP\Jobs\Transfers;

use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Constants\Metric;
use RZP\Models\Ledger\ReverseShadow\Transfers\Reversal\Core as TransferReversalReverseShadowCore;
use RZP\Trace\TraceCode;

class TransferReversalCreateTransaction extends Job
{
    protected $reversalAndRefundJournalIds;
    protected $producerKey;

    public function __construct(string $mode, $reversalAndRefundJournalIds, $producerKey)
    {
        parent::__construct($mode);

        $this->reversalAndRefundJournalIds = $reversalAndRefundJournalIds;
        $this->producerKey = $producerKey;
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
            (new TransferReversalReverseShadowCore())->pushReversalToKafkaForAPITransactionCreation($this->reversalAndRefundJournalIds, $this->producerKey);

            $this->trace->info(TraceCode::TRANSFER_REVERSAL_API_TXN_JOB_PUSH_SUCCESS, [
                'reversalAndRefundJournalIds' =>  $this->reversalAndRefundJournalIds,
                'producerKey' =>  $this->producerKey,
            ]);

        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::TRANSFER_REVERSAL_API_TXN_JOB_PUSH_FAILURE,
                [
                    'message'           => 'transfer reversal api txn job push failed',
                    'reversalAndRefundJournalIds' => $this->reversalAndRefundJournalIds,
                    'producerKey' => $this->producerKey,
                ]);

            $this->trace->count(Metric::TRANSFER_REVERSAL_API_TXN_JOB_PUSH_FAILURE);
        }
    }
}

