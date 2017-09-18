<?php

namespace RZP\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

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

    /**
     * Batches array.
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
            $this->trace->info(TraceCode::BATCH_JOB_RECEIVED, $this->batches);

            if (isset($this->batches[BatchModel\Type::IRCTC_REFUND]) === true)
            {
                $batchId = $this->batches[BatchModel\Type::IRCTC_REFUND];

                $batch = $this->repoManager->batch->findOrFail($batchId);

                $batch->getValidator()->validateNotProcessedAlready();

                $timeStarted = microtime(true);

                BatchModel\Processor\Base::get($batch)
                                         ->process();

                $timeTaken = microtime(true) - $timeStarted;

                $this->trace->debug(
                            TraceCode::BATCH_JOB_HANDLED,
                            [
                                'type'       => BatchModel\Type::IRCTC_REFUND,
                                'batch_id'   => $batchId,
                                'time_taken' => $timeTaken,
                            ]);
            }

            if (isset ($this->batches[BatchModel\Type::SETTLEMENT_IRCTC]) === true)
            {
                $batchId = $this->batches[BatchModel\Type::SETTLEMENT_IRCTC];

                $batch = $this->repoManager->batch->findOrFail($batchId);

                $batch->getValidator()->validateNotProcessedAlready();

                $timeStarted = microtime(true);

                BatchModel\Processor\Base::get($batch)
                                         ->process();

                $timeTaken = microtime(true) - $timeStarted;

                $this->trace->debug(
                            TraceCode::BATCH_JOB_HANDLED,
                            [
                                'type'       => BatchModel\Type::SETTLEMENT_IRCTC,
                                'batch_id'   => $batchId,
                                'time_taken' => $timeTaken,
                            ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::BATCH_JOB_ERROR,
                            [
                                'data' => $this->batches,
                            ]);
        }
        finally
        {
            $this->delete();
        }
    }
}
