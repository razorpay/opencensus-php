<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Batch\Status;
use RZP\Models\Batch as BatchModel;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Represents asynchronous Batch job.
 *
 * Handler:
 * - Calls the batch processor on given batch id.
 */
class Batch extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Batch entity id.
     *
     * @var string
     */
    protected $id;

    /**
     * Additional parameters from request or query.
     *
     * @var array
     */
    protected $params;

    public function __construct(string $mode, string $id, array $params = [])
    {
        parent::__construct($mode);

        $this->id     = $id;
        $this->params = $params;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $batch = $this->repoManager->batch->findOrFail($this->id);

            // Earlier we used to again validate if the batch is in a retriable state
            // I think this is not rquired and once a batch is here, we should set
            // the status to PROCESSING to maintain state consistency
            // TBD- Need to discuss this with reviewers
            $batch->setStatus(Status::PROCESSING);

            $timeStarted = microtime(true);

            $this->trace->debug(
                            TraceCode::BATCH_JOB_RECEIVED,
                            [
                                BatchModel\Entity::ID => $this->id,
                            ]);

            BatchModel\Processor\Base::get($batch)
                                     ->setParams($this->params)
                                     ->process();

            $timeTaken = microtime(true) - $timeStarted;

            $this->trace->debug(
                            TraceCode::BATCH_JOB_HANDLED,
                            [
                                BatchModel\Entity::ID => $this->id,
                                'time_taken'          => $timeTaken,
                            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::BATCH_JOB_ERROR,
                            [
                                BatchModel\Entity::ID => $this->id,
                            ]);
        }
        finally
        {
            $this->delete();
        }
    }
}
