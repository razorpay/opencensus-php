<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Jobs\DispatchRouter;
use RZP\Jobs\Batch as BatchJob;
use RZP\Models\Batch\Header as BatchHeaders;

class Core extends Base\Core
{
    public function create(Merchant\Entity $merchant, array $input): Entity
    {
        $this->trace->info(TraceCode::BATCH_CREATE_REQUEST, array_except($input, Entity::FILE));

        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($merchant);

        $processor = Processor\Factory::get($batch);

        $processor->storeInputFileAndSaveBatchWithSettings($input);

        $this->trace->info(TraceCode::BATCH_CREATED, $batch->toArrayPublic());

        $this->dispatchOnQueueForProcessingIfApplicable($batch, $input);

        return $batch;
    }

    public function storeAndValidateBatchFile(Merchant\Entity $merchant, array $input): array
    {
        $this->trace->info(TraceCode::BATCH_FILE_VALIDATE_REQUEST, array_except($input, Entity::FILE));

        $entries = [];

        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($merchant);

        $processor = Processor\Factory::get($batch);

        $processor->getStoredInputFileAndValidateBatchEntries($input, null, $entries);

        $response = $processor->getValidatedEntriesStatsAndSampleData($entries);

        // The error file to be created and saved is supposed to be used in batch create api.
        // Hence it must be saved as an input file and to be saved inside batch/upload folder
        // This error file_store instance has no entity associated with it as any input file
        // and should have the type as `batch_input`. For backward compatibility.
        $response += $processor->createSetOutputFileAndSave($entries, BatchHeaders::ERROR);

        return $response;
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
        Processor\Factory::get($batch)->retryBatchOutputFile();

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
                Processor\Factory::get($batch)->validateAndProcess();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e);
            }
        }

        return $batches;
    }

    public function processBatchAsync(Entity $batch, array $input = []): Entity
    {
        $this->trace->info(TraceCode::BATCH_PROCESS_ASYNC, [$batch->toArrayPublic(), $input]);

        $this->queueBatchForProcessing($batch, $input);

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
