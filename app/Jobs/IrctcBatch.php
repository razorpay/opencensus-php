<?php

namespace RZP\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Trace\TraceCode;
use RZP\Models\Batch as BatchModel;
use Razorpay\Trace\Logger as Trace;

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
     * Batch entity id array.
     *
     * @var array
     */
    protected $batchData;

    /**
     * Additional parameters from request or query.
     *
     * @var array
     */
    protected $params;

    public function __construct(string $mode, array $batchData, array $params = [])
    {
        parent::__construct($mode);

        $this->batchData = $batchData;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            if (isset ($batchData['irctc_refund']) === true)
            {
                $batch = $this->repoManager->batch->findOrFail($batchData['irctc_refund']);

                $batch->getValidator()->validateNotProcessedAlready();

                BatchModel\Processor\Base::get($batch)
                                         ->process();

                $timeTaken = microtime(true) - $timeStarted;

                $this->trace->debug(
                            TraceCode::BATCH_JOB_HANDLED,
                            [
                                BatchModel\Entity::ID => $batchData['irctc_refund'],
                                'time_taken'          => $timeTaken,
                            ]);
            }

             if (isset ($batchData['irctc_settlement']) === true)
            {
                $batch = $this->repoManager->batch->findOrFail($batchData['irctc_settlement']);

                $batch->getValidator()->validateNotProcessedAlready();

                BatchModel\Processor\Base::get($batch)
                                         ->process();

                $timeTaken = microtime(true) - $timeStarted;

                $this->trace->debug(
                            TraceCode::BATCH_JOB_HANDLED,
                            [
                                BatchModel\Entity::ID => $batchData['irctc_settlement'],
                                'time_taken'          => $timeTaken,
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
                                'data' => $this->batchData,
                            ]);
        }
        finally
        {
            $this->delete();
        }
    }
}
