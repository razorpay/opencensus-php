<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Batch as BatchModel;

/**
 * Represents asynchronous Batch job for IRCTC.
 *
 * Different from other generic Jobs\Batch, reason to following: In case of IRCTC,
 * we are to execute 2 batches in sequence. And so from elsewhere we push a job
 * containing 2 batch ids in their order of execution which get processed here.
 * processor for refund and delta_refund is same.
 */
class IrctcBatch extends Job
{
    const BATCH_ORDER = [
        BatchModel\Type::IRCTC_REFUND,
        BatchModel\Type::IRCTC_DELTA_REFUND,
        BatchModel\Type::IRCTC_SETTLEMENT
    ];

    /**
     * Associative array with key as batch type and value
     * as Batch entity object.
     *
     * @var array
     */
    protected $batches;

    /**
     * Increasing timeout to 4 hrs to avoid
     * termination of batches.
     * @var int
     */
    public $timeout = 14400;

    public function __construct(string $mode, array $batches)
    {
        parent::__construct($mode);

        $this->batches = $batches;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::IRCTC_BATCH_JOB_RECEIVED, $this->batches);

            foreach (self::BATCH_ORDER as $batchType)
            {
                if (isset($this->batches[$batchType]) === false)
                {
                    continue;
                }

                $batchId = $this->batches[$batchType];

                $batch = $this->repoManager->batch->findOrFail($batchId);

                $timeStarted = microtime(true);

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'message'          => 'To push details of current batch',
                        'batch_id'         => $batchId,
                        'timeStarted'      => $timeStarted,
                        'batches'          => $this->batches,
                        'batchType'        => $batchType
                    ]);

                BatchModel\Processor\Factory::get($batch)->validateAndProcess();

                $timeTaken = microtime(true) - $timeStarted;

                $this->trace->info(
                    TraceCode::BATCH_JOB_HANDLED,
                    [
                        BatchModel\Entity::TYPE => $batchType,
                        BatchModel\Entity::ID   => $batchId,
                        'time_taken'            => $timeTaken,
                    ]);

                // IRCTC batch processing matrix
                $metricDimensions = $batch->getMetricDimensions(['status' => $batch->getStatus()]);

                $timeTakenMilliSeconds = (int)$timeTaken*1000;

                $this->trace->histogram(BatchModel\Metric::BATCH_REQUEST_PROCESS_TIME_MS, $timeTakenMilliSeconds, $metricDimensions);

                $totalTimeTaken = millitime() - (int)$batch->getCreatedAt()*1000 ;

                $this->trace->histogram(BatchModel\Metric::BATCH_CREATE_TOTAL_PROCESS_TIME_MS, $totalTimeTaken, $metricDimensions);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::IRCTC_BATCH_JOB_ERROR,
                [
                    'data' => $this->batches
                ]);
        }
    }
}
