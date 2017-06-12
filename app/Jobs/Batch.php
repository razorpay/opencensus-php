<?php

namespace RZP\Jobs;

use App;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Batch as BatchModel;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

/**
 * Represents asynchronous Batch job.
 *
 * Handler:
 * - Calls the batch processor on given batch id.
 *
 */
class Batch extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Mode of the application: TEST|LIVE
     *
     * @var string
     */
    protected $mode;

    /**
     * Batch entity id.
     *
     * @var string
     */
    protected $id;

    public function __construct(string $mode, string $id)
    {
        $this->mode = $mode;
        $this->id   = $id;
    }

    public function handle()
    {
        // Initializes application services

        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $trace = $app['trace'];

        // Sets application and db mode

        $app['rzp.mode'] = $this->mode;

        \Database\DefaultConnection::set($this->mode);

        try
        {
            $batch = $repo->batch->findOrFail($this->id);

            $timeStarted = microtime(true);

            $trace->debug(
                TraceCode::BATCH_JOB_RECEIVED,
                [
                    BatchModel\Entity::ID => $this->id,
                ]);

            (BatchModel\Processor\Base::get($batch))->process();

            $this->delete();

            $timeTaken = microtime(true) - $timeStarted;

            $trace->debug(
                TraceCode::BATCH_JOB_HANDLED,
                [
                    BatchModel\Entity::ID => $this->id,
                    'time_taken'          => $timeTaken,
                ]);
        }
        catch (\Throwable $e)
        {
            // We do not want to retry in case of batch jobs for
            // obvious reasons.

            $this->delete();

            $trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BATCH_JOB_ERROR,
                [
                    BatchModel\Entity::ID => $this->id,
                ]);
        }
    }
}
