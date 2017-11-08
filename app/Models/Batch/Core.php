<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Jobs\DispatchRouter;
use RZP\Jobs\Batch as BatchJob;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::BATCH_CREATE_REQUEST, $input);

        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($merchant);

        $processor = Processor\Base::get($batch);

        $processor->storeInputFileAndSaveBatch($input);

        $this->trace->info(TraceCode::BATCH_CREATED, $batch->toArrayPublic());

        $this->dispatchOnQueueForProcessingIfApplicable($batch, $input);

        return $batch;
    }

    /**
     * Internal Auth: There are some very rare cases (UFH issues) where output
     * file doesn't get created but the batch is actually processed. This has
     * happened specifically for payment_link type batch. We can't wrap the whole
     * operation under transaction because of few other reasons.
     *
     * TODO: Drop in detail the use case and reasons here.
     *
     * @param Entity $batch
     *
     * @return Entity
     */
    public function retryBatchOutputFile(Entity $batch): Entity
    {
        Processor\Base::get($batch)->retryBatchOutputFile();

        return $batch;
    }

    /**
     * Returns signed URL of the batch's most recent file
     * If batch is processed that will be the output file, else the batch input
     * file is returned.
     *
     * @param Entity $batch
     *
     * @return string
     */
    public function downloadBatch(Entity $batch): string
    {
        $file = $batch->latestFile();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        return $signedUrl;
    }

    /**
     * Process all pending batches. Called via CRON.
     *
     * This CRON runs every 6 hours and is for now only handling REFUND type.
     *
     * @return Base\PublicCollection
     */
    public function processBatches(): Base\PublicCollection
    {
        $this->increaseAllowedSystemLimits();

        $batches = $this->repo->batch->fetchUnprocessedForCron();

        foreach ($batches as $batch)
        {
            try
            {
                Processor\Base::get($batch)->validateAndProcess();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e);
            }
        }

        return $batches;
    }

    public function processBatchAsync(Entity $batch): Entity
    {
        $this->trace->info(TraceCode::BATCH_PROCESS_ASYNC, $batch->toArrayPublic());

        $this->queueBatchForProcessing($batch);

        return $batch;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(1000);
    }

    /**
     * Dispatches new job onto queue for asynchronous processing of it.
     * Only batch entity's of type in Type::QUEUE_GROUP gets pushed onto queue,
     * others are processed via CRON.
     *
     * @param Entity $batch
     * @param array  $input
     */
    protected function dispatchOnQueueForProcessingIfApplicable(Entity $batch, array $input)
    {
        if (Type::isQueueGroup($batch->getType()) === true)
        {
            unset($input[Entity::FILE]);

            $this->queueBatchForProcessing($batch, $input);
        }
    }

    protected function queueBatchForProcessing(Entity $batch, array $input = [])
    {
        $job = new BatchJob($this->mode, $batch->getId(), $input);

        (new DispatchRouter)->dispatchOn($job, DispatchRouter::BATCH);
    }
}
