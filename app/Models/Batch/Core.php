<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Jobs\DispatchRouter;
use RZP\Jobs\Batch as BatchJob;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Core extends Base\Core
{
    use FileHandlerTrait;

    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::BATCH_CREATE_REQUEST, $input);

        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($merchant);

        $processor = Processor\Base::get($batch);

        //
        // We upload the input file to S3 create a filestore entity for the
        // input file via UFH. We then update the batch entity with file meta
        // data if available, follow by save, all inside a transaction.
        //
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
            $this->processBatchInSync();
        }

        return $batches;
    }

    /**
     * Process individual batch by internal auth API call
     *
     * @param Entity $batch
     *
     * @return Entity
     */
    public function processBatchViaApi(Entity $batch)
    {
        $this->trace->info(TraceCode::BATCH_RETRY, $batch->toArrayPublic());

        $this->queueBatchForProcessing($batch);

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

    /**
     * Performs batch processing in sync, by calling the respective batch processor.
     * Used in cron batch processing flow
     *
     * @param  bool     $shouldThrow     flag to indicate if a processing exception
     *                                   should be bubbled up or not
     */
    protected function processBatchInSync(bool $shouldThrow = false)
    {
        try
        {
            Processor\Base::get($batch)
                          ->setParams($this->params)
                          ->validateAndProcess();
        }
        catch (\Throwable $ex)
        {
            if ($shouldThrow === true)
            {
                throw $ex;
            }
        }
    }
}
