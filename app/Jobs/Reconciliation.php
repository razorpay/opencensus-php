<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;
use RZP\Services\KubernetesClient;
use RZP\Models\Batch as BatchModel;

/**
 * Represents asynchronous Reconciliation Batch job.
 */
class Reconciliation extends Job
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

    protected $queueConfigKey = 'reconciliation_batch';

    const MAX_KUBERNETES_JOB_COUNT = 16;

    const KUBERNETES_RECON_JOB_LIST = 'recon_kubernetes_recon_job_list';

    // will release the batch into queue after 15 minutes again.
    const RELEASE_WAIT_SECS = 900;

    const JOB_TO_BE_SCHEDULED = 'job_to_be_scheduled';

    const JOB_RELEASED = 'job_released';

    const JOB_DELETED  = 'job_deleted';

    const NODE_SELECTOR  = 'node_selector';

    const JOB_NAME  = 'job_name';

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

            $this->trace->debug(
                TraceCode::BATCH_JOB_RECEIVED,
                [
                    BatchModel\Entity::ID   => $this->id,
                    BatchModel\Entity::TYPE => $batch->getType(),
                ]);

            $app = App::getFacadeRoot();

            $redis = $app['redis']->Connection('mutex_redis');

            $jobAction = $this->getJobActionForBatch($redis, $batch);

            if ($jobAction === self::JOB_TO_BE_SCHEDULED)
            {
                $this->modifyParams($app, $batch);

                //
                // creating job for K8s client, will spawn a new pod for running this job.
                //
                $jobStatus = $app->k8s_client->createJob($this->mode, $batch->getId(), $this->params, $batch->getType());

                if ($jobStatus === true)
                {
                    $this->addBatchInKubernetesJobList($redis, $batch->getId());
                }
                else
                {
                    //
                    // couldn't create K8s job, deleting the job.
                    //
                    $this->delete();

                    $jobAction = self::JOB_DELETED;
                }
            }

            $this->trace->debug(
                TraceCode::BATCH_JOB_HANDLED,
                [
                    BatchModel\Entity::TYPE => $batch->getType(),
                    BatchModel\Entity::ID   => $this->id,
                    'job_action'            => $jobAction,
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
                TraceCode::BATCH_JOB_ERROR,
                [
                    BatchModel\Entity::ID   => $this->id,

                ]);
        }
    }

    /**
     * @param $redis
     * @param string $batchId
     */
    public function addBatchInKubernetesJobList($redis, string $batchId)
    {
        $redis->hSet(
            self::KUBERNETES_RECON_JOB_LIST,
            $batchId,
            true
        );
    }

    /**
     * @param $redis
     * @param $batch
     * @return bool
     */
    protected function getJobActionForBatch($redis, $batch)
    {
        $data = $redis->hGetAll(self::KUBERNETES_RECON_JOB_LIST);

        $this->trace->debug(
            TraceCode::KUBERNETES_BATCH_JOB_DEBUG,
            [
                'job_list' => $data
            ]);

        $jobAction = null;

        if (isset($data[$batch->getId()]) === true)
        {
            // already scheduled, another request can be deleted
            $this->delete();

            $redis->hDel(Reconciliation::KUBERNETES_RECON_JOB_LIST, $batch->getId());

            $jobAction = self::JOB_DELETED;
        }
        else if (count($data) >= self::MAX_KUBERNETES_JOB_COUNT)
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $jobAction = self::JOB_RELEASED;
        }
        else
        {
            $jobAction = self::JOB_TO_BE_SCHEDULED;
        }

        return $jobAction;
    }

    /**
     * @param $app
     * @param $batch
     */
    protected function modifyParams($app, $batch)
    {
        $this->params[self::JOB_NAME] = ($batch->getAttempts() > 0) ?
                                        ($batch->getId() . $batch->getAttempts()) :
                                        $batch->getId();
        //
        // If its a hitachi recon, spawn the job under hitachi whitelisted node otherwise capture will timedout.
        // also this node selector only works in production.
        //
        if (($batch->getGateway() === Gateway::HITACHI) and ($app['env'] === Environment::PRODUCTION))
        {
            $this->params[self::NODE_SELECTOR] = KubernetesClient::NODE_SELECTOR_HITACHI;
        }
    }
}
