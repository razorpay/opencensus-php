<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Jobs\DispatchRouter;
use RZP\Jobs\Batch as BatchJob;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Core extends Base\Core
{
    use FileHandlerTrait;

    public function create(array $input): Entity
    {
        $this->trace->info(TraceCode::BATCH_CREATE_REQUEST, $input);

        if (isset($input[Entity::MERCHANT_ID]) === true)
        {
            $this->merchant = $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

            unset($input[Entity::MERCHANT_ID]);
        }

        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($this->merchant);

        $processor = Processor\Base::get($batch);

        // We upload the input file to S3 create a filestore entity for the input file via UFH
        // We then update the batch entity with file metadata if available
        $processor->createInputFileAndUpdateBatch($input);

        $this->trace->info(TraceCode::BATCH_CREATED, $batch->toArrayPublic());

        $this->dispatchOnQueueForProcessingIfApplicable($batch, $input);

        return $batch;
    }

    /**
     * Retry batch entity.
     *
     * - Only sets the status to PROCESSING so it gets picked by
     *   the next cron run.
     *
     * @param Entity $batch
     */
    public function retryBatch(Entity $batch)
    {
        $batch->getValidator()->validateIfProcessable();

        $batch->setProcessing(true);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_RETRY, $batch->toArrayPublic());
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
     * Returns signed url of the batch file: output file if that exists else
     * the input file itself.
     *
     * @param Entity $batch
     *
     * @return string
     */
    public function downloadBatch(Entity $batch): string
    {
        $file = ($batch->getStatus() === Status::CREATED) ?
                    $batch->inputFile() : $batch->outputFile();

        // Backward compatibility:
        // - If file relation exists use that else to handle BC
        //   form the AWS key and get the signed URL as done previously.

        if ($file === null)
        {
            $signedUrl = $this->getSignedUrlOfBatchFile($batch);
        }
        else
        {
            $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);
        }

        $this->trace->info(
            TraceCode::BATCH_DOWNLOAD,
            [
                'batch_id' => $batch->getId(),
                'url'      => $signedUrl,
            ]);

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
            if ($batch->isProcessable() === true)
            {
                $this->processBatch($batch);
            }
        }

        return $batches;
    }

    /**
     * Processes individual batch via API
     *
     * @param Entity $batch
     *
     * @return Entity
     */
    public function processBatchViaApi(Entity $batch)
    {
        $batch->getValidator()->validateIfProcessable();

        return $this->processBatch($batch);
    }

    /**
     * Process a particular batch entity.
     *
     * @param Entity  $batch
     * @param boolean $bubbleEx   - When called iteratively over batch collection
     *                            we don't break execution. But when called via
     *                            API for individual batch we bubble exception
     *                            to response.
     *
     * @return Entity
     * @throws \Throwable
     */
    public function processBatch(Entity $batch): Entity
    {
        $batch->setProcessing(true);

        $this->repo->saveOrFail($batch);

        // TBD - Need to discuss once if all batch types can be retried.
        $job = new BatchJob($this->mode, $batch->getId());

        if (Type::isQueueGroup($batch->getType()) === false)
        {
            Processor\Base::get($batch)->process();
        }
        else
        {
            (new DispatchRouter)->dispatchOn($job, DispatchRouter::BATCH);
        }

        return $batch;
    }

    /**
     * Get signed URL of batch file in old way.
     *
     * @param Entity $batch
     *
     * @return string
     */
    protected function getSignedUrlOfBatchFile(Entity $batch): string
    {
        $awsKey = $batch->getFilePrefix() . $batch->getFileKeyWithExt();

        return $this->getPreSignedUrlFromAws($awsKey);
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
        if (Type::isQueueGroup($batch->getType()) === false)
        {
            return;
        }

        unset($input[Entity::FILE]);

        // For now this is being pushed onto invoice_emails queue only and
        // later we might have a new queue for this purpose only.

        $job = new BatchJob($this->mode, $batch->getId(), $input);

        (new DispatchRouter)->dispatchOn($job, DispatchRouter::BATCH);
    }
}
