<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Batch as BatchModel;

/**
 * Represents asynchronous Batch job.
 */
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

    public $timeout = 5400;

    public function __construct(string $mode, string $id, string $type = null, array $params = [])
    {
        parent::__construct($mode);
        parent::setPassportTokenForJobs();

        $this->id     = $id;
        $this->params = $params;

        $this->setQueueConfigKeyFromBatchType($type);
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
                    BatchModel\Entity::ID   => $this->id,
                    BatchModel\Entity::TYPE => $batch->getType(),
                ]);

            $processor = BatchModel\Processor\Factory::get($batch)
                                                     ->setParams($this->params)
                                                     ->validateAndProcess();

            $timeTaken = microtime(true) - $timeStarted;

            $this->trace->debug(
                TraceCode::BATCH_JOB_HANDLED,
                [
                    BatchModel\Entity::TYPE => $batch->getType(),
                    BatchModel\Entity::ID   => $this->id,
                    'time_taken'            => $timeTaken,
                ]);

            $metricDimensions = $batch->getMetricDimensions(['status' => $batch->getStatus()]);

            $timeTakenMilliSeconds = (int)$timeTaken*1000;

            $this->trace->histogram(BatchModel\Metric::BATCH_REQUEST_PROCESS_TIME_MS, $timeTakenMilliSeconds, $metricDimensions);

            $totalTimeTaken = millitime() - (int)$batch->getCreatedAt()*1000 ;

            $this->trace->histogram(BatchModel\Metric::BATCH_CREATE_TOTAL_PROCESS_TIME_MS, $totalTimeTaken, $metricDimensions);
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
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Setting queue config key based on batch type. Specific batch types will be pushed to other dedicated queues.
     * Rest will go to default batch queue.
     *
     * @param $type
     */
    protected function setQueueConfigKeyFromBatchType(string $type = null)
    {
        $configKey = $type . '_batch';

        $app = App::getFacadeRoot();

        if (isset($app['config']['queue'][$configKey]) === true)
        {
            $this->queueConfigKey = $configKey;
        }
    }
}
