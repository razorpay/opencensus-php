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

            $timeStarted = microtime(true);

            $this->trace->debug(
                            TraceCode::BATCH_JOB_RECEIVED,
                            [
                                BatchModel\Entity::ID => $this->id,
                            ]);

            $batch->getValidator()->validateNotProcessedAlready();

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
