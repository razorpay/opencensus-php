<?php

namespace RZP\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Trace\TraceCode;
use RZP\Models\Batch as BatchModel;
use Razorpay\Trace\Logger as Trace;

/**
 * Represents asynchronous Batch job for IRCTC.
 *
 * Different from other generic Jobs\Batch, reason to following: In case of IRCTC,
 * we are to execute 2 batches in sequence. And so from elsewhere we push a job
 * containing 2 batch ids in their order of execution which get processed here.
 */
class IrctcBatch extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    const BATCH_ORDER = [
        BatchModel\Type::IRCTC_REFUND,
        BatchModel\Type::IRCTC_SETTLEMENT
    ];

    /**
     * Associative array with key as batch type and value
     * as Batch entity object.
     *
     * @var array
     */
    protected $batches;

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

                BatchModel\Processor\Base::get($batch)->validateAndProcess();

                $timeTaken = microtime(true) - $timeStarted;

                $this->trace->info(
                    TraceCode::BATCH_JOB_HANDLED,
                    [
                        BatchModel\Entity::TYPE => $batchType,
                        BatchModel\Entity::ID   => $batchId,
                        'time_taken'            => $timeTaken,
                    ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::IRCTC_BATCH_JOB_ERROR,
                [
                    'data' => $this->batches
                ]);
        }
    }
}
