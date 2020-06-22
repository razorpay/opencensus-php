<?php

namespace RZP\Services;

use App;
use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Jobs\Reconciliation;
use RZP\Models\Batch as BatchModel;

class Batch extends Job
{
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

    /**
     * Repository manager
     *
     * @var \RZP\Base\RepositoryManager
     */
    protected $repoManager;

    /**
     * Trace instance
     *
     * @var \RZP\Trace
     */
    protected $trace;


    /**
     * Process the batch Entries
     *
     * @var id
     * @var mode
     * @var params
     *
     * @return mixed
     */
    public function process(string $id, string $mode = null, array $params = [])
    {
        $this->id = $id;
        $this->params = $params;
        $this->mode = $mode;

        parent::__construct($mode);

        parent::handle();

        try
        {

            $batch = $this->repoManager->batch->findOrFail($this->id);

            $timeStarted = microtime(true);

            $this->trace->debug(
                TraceCode::KUBERNETES_BATCH_JOB_RECEIVED,
                [
                    BatchModel\Entity::ID   => $this->id,
                    BatchModel\Entity::TYPE => $batch->getType(),
                ]);

            $processor = BatchModel\Processor\Factory::get($batch)
                ->setParams($this->params)
                ->validateAndProcess();


            $timeTaken = microtime(true) - $timeStarted;

            $this->trace->debug(
                TraceCode::KUBERNETES_BATCH_JOB_HANDLED,
                [
                    BatchModel\Entity::TYPE => $batch->getType(),
                    BatchModel\Entity::ID   => $this->id,
                    'time_taken'            => $timeTaken,
                ]);
        }
        catch (\Throwable $e)
        {
            //
            // At this stage if any exception is thrown, then we just trace it
            // and don't update the batch, as all the error handling is done in
            // the processor class itself and any exception thrown should be
            // ideally handled before this itself.
            //
            $this->trace->traceException(
                $e,
                null,
                TraceCode::KUBERNETES_BATCH_JOB_ERROR,
                [
                    BatchModel\Entity::ID   => $this->id,
                ]);
        }
        finally
        {
            //
            // For reconciliation, maintaining a list of batches scheduled in redis, will delete the batch from list
            // once batch is handled.
            //
            $app = App::getFacadeRoot();

            $redis = $app['redis']->Connection('mutex_redis');

            $this->trace->debug(
                TraceCode::KUBERNETES_BATCH_JOB_DEBUG,
                [
                    BatchModel\Entity::ID   => $this->id,
                ]);

            $redis->hDel(Reconciliation::KUBERNETES_RECON_JOB_LIST, $this->id);
        }
    }
}
