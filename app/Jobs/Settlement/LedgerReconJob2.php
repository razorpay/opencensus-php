<?php

namespace RZP\Jobs\Settlement;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;

class LedgerReconJob2 extends Job
{
    public $timeout = 3600; // 1 hour

    protected $queueConfigKey = 'merchant_invoice';

    protected $merchantId;

    protected $startTimestamp;

    public function __construct(string $mode, string $merchantId, int $startTimestamp)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

        $this->startTimestamp = $startTimestamp;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::LEDGER_RECON_JOB_RECEIVED,
            [
                'merchant_id'       => $this->merchantId,
                'start_timestamp'   => $this->startTimestamp,
            ]
        );

        try
        {
            $output = (new Transaction\Service)->prepareIdealLedger($this->merchantId, $this->startTimestamp);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::IDEAL_LEDGER_PROCESS_FAILED,
                [
                    'merchant_id' => $this->merchantId,
                ]
            );
        }
        finally
        {
            $this->delete();
        }
    }
}
