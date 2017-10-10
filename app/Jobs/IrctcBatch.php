<?php

namespace RZP\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Batch as BatchModel;

/**
 * Represents asynchronous Batch job.
 *
 * Handler:
 * - Calls the batch processor on given batch id.
 */
class IrctcBatch extends Job implements ShouldQueue
{
    use InteractsWithQueue;

     const MAX_ALLOWED_ATTEMPTS = 1;

    /**
     * Batches array.
     *
     * @var array
     */
    protected $batches;

    const BATCH_ORDER = [
        BatchModel\Type::IRCTC_REFUND,
        BatchModel\Type::IRCTC_SETTLEMENT
    ];

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
            $this->trace->info(TraceCode::BATCH_JOB_RECEIVED, $this->batches);

            foreach (self::BATCH_ORDER as $batchType)
            {
                if (isset($this->batches[$batchType]) === false)
                {
                    continue;
                }

                $batchId = $this->batches[$batchType];

                $batch = $this->repoManager->batch->findOrFail($batchId);

                $timeStarted = microtime(true);

                BatchModel\Processor\Base::get($batch)->process();

                $timeTaken = microtime(true) - $timeStarted;

                $this->trace->info(
                    TraceCode::BATCH_JOB_HANDLED,
                    [
                        'type'       => $batchType,
                        'batch_id'   => $batchId,
                        'time_taken' => $timeTaken,
                    ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BATCH_JOB_ERROR,
                [
                    'data' => $this->batches
                ]);
        }
    }
}
