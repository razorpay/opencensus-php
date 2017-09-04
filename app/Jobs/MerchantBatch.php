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
class MerchantBatch extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Batch entity id array.
     *
     * @var array
     */
    protected $ids;

    /**
     * Additional parameters from request or query.
     *
     * @var array
     */
    protected $params;

    public function __construct(string $mode, array $ids, array $params = [])
    {
        parent::__construct($mode);

        $this->ids    = $ids;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            foreach ($this->ids as $id)
            {
                $batches[] = $this->repoManager->batch->findOrFail($id);
            }

            $timeStarted = microtime(true);

            $this->trace->debug(
                            TraceCode::BATCH_JOB_RECEIVED,
                            [
                                BatchModel\Entity::ID => $this->ids,
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
