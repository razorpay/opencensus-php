<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\Foundation\ScroogeReconciliate;

class ScroogeRefundRecon extends Job
{
    const MAX_JOB_ATTEMPTS = 5;
    const JOB_RELEASE_WAIT = 300;

    //
    // Make sure that this is below 900 (seconds) because
    // SQS doesn't support delay over 15 minutes.
    //
    public $delay = 5;

    protected $data;

    protected $traceData;

    protected $queueConfigKey = 'recon_method_batch';

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        $app = App::getFacadeRoot();

        //
        // Note : Can't make scrooge and batch as instance variable here, as
        // it raises dispatch error : 'Serialization of 'Closure' is not allowed'
        //
        $scrooge = $app['scrooge'];

        // unset 'refunds' as we don't want to trace huge data
        $this->traceData = $this->data;

        unset($this->traceData[ScroogeReconciliate::REFUNDS]);

        $this->trace->info(
            TraceCode::REFUND_RECON_QUEUE_SCROOGE_REQUEST,
            $this->traceData
        );

        $chunkNumber = $this->data[ScroogeReconciliate::CHUNK_NUMBER] ?? 1;

        $batchId = $this->data[ScroogeReconciliate::BATCH_ID] ?? null;

        try
        {
            $response = $scrooge->initiateRefundRecon($this->data, true);

            if (isset($response['body']['response']) === true)
            {
                (new Service)->reconcileRefundsAfterScroogeRecon($response['body']['response'], $this->data[ScroogeReconciliate::SHOULD_UPDATE_BATCH_SUMMARY]);
            }

            $this->trace->info(
                TraceCode::REFUND_RECON_QUEUE_SCROOGE_SUCCESS,
                $this->traceData
            );

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->traceData['job_attempts'] = $this->attempts();

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::RECON_INFO_ALERT,
                [ScroogeReconciliate::INFO_CODE => InfoCode::REFUND_RECON_SCROOGE_JOB_FAILURE_EXCEPTION] + $this->traceData
            );

            $this->handleRefundJobRelease($batchId, $chunkNumber);
        }
    }

    protected function handleRefundJobRelease(string $batchId = null, int $chunkNumber = null)
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::REFUND_RECON_SCROOGE_QUEUE_DELETE,
                [
                    'data'         => $this->traceData,
                    'batch_id'     => $batchId,
                    'chunk_number' => $chunkNumber,
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->delete();
        }
        else
        {
            //
            // When queue_driver is sync, there's no release
            // and hence it's as good as deleting the job.
            //
            $this->release(self::JOB_RELEASE_WAIT);
        }
    }
}
