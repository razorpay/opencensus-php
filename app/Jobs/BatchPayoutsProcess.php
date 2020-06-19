<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class BatchPayoutsProcess extends Job
{
    protected $trace;

    /**
     * Balance Id for which queued payouts have to be processed
     *
     * @var string
     */
    protected $merchantId;

    // Overriding timeout with 300 seconds
    public $timeout = 300;

    public function __construct(string $mode, string $merchantId)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;
    }

    public function handle()
    {
        parent::handle();

        $traceData = ['merchant_id' => $this->merchantId];

        $this->trace->info(
            TraceCode::PAYOUT_BATCH_INITIATE_REQUEST,
            $traceData);

        try
        {
            (new Payout\Core)->initiateProcessingOfBatchSubmittedPayouts($this->merchantId);

            $this->trace->info(
                TraceCode::PAYOUT_BATCH_INITIATE_SUCCESS,
                $traceData);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_BATCH_INITIATE_JOB_FAILED,
                $traceData);
        }
        finally
        {
            $this->delete();
        }
    }
}
