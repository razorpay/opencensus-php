<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Jobs\DispatchRouter;
use RZP\Base\RuntimeManager;
use RZP\Jobs\Batch as BatchJob;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Core extends Base\Core
{
    use FileHandlerTrait;

    public function create(array $input): Entity
    {
        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($this->merchant);

        //
        // Does following inside transaction:
        // - Uploads file to s3 and gets FileStore\Entity created
        // - Validates the file
        // - Updates batch entity with aggregate details of file (if applicable)
        // - Saves batch entity
        //

        $this->repo->transaction(function () use ($batch, $input)
        {
            $file = Processor\Base::get($batch)->saveInputFile($input[Entity::FILE]);

            $entries = $this->parseExcelSheets($file);

            $batch->getValidator()->validateEntries($entries, $this->merchant);

            $this->fillBatchEntityWithInputFileDetails($batch, $entries);

            $this->repo->saveOrFail($batch);
        });

        $this->trace->info(TraceCode::BATCH_CREATED, $batch->toArrayPublic());

        $this->dispatchOnQueueForProcessingIfApplicable($batch);

        return $batch;
    }

    /**
     * Retry batch entity.
     *
     * - Only sets the status to PROCESSING so it gets picked by
     *   the next cron run.
     *
     * @param Entity $batch
     *
     * @return Entity
     */
    public function retryBatch(Entity $batch): Entity
    {
        $batch->getValidator()->validateNotProcessedAlready();

        $batch->setStatus(Status::PROCESSING);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_RETRY, $batch->toArrayPublic());

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
            $this->processBatch($batch);
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
        return $this->processBatch($batch, true);
    }

    /**
     * Process a particular batch entity.
     *
     * @param Entity  $batch
     * @param boolean $bubbleEx - When called iteratively over batch collection
     *                            we don't break execution. But when called via
     *                            API for individual batch we bubble exception
     *                            to response.
     *
     * @return Entity
     */
    public function processBatch(Entity $batch, bool $bubbleEx = false): Entity
    {
        try
        {
            $batch->getValidator()->validateNotProcessedAlready();

            Processor\Base::get($batch)->process();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BATCH_PROCESSING_ERROR,
                [
                    'batch' => $batch->toArrayPublic(),
                ]);

            // Even if there is any error during processing of batch
            // we for now still set the status as PROCESSED.

            $batch->setStatus(Status::PROCESSED);

            $this->repo->saveOrFail($batch);

            if ($bubbleEx === true)
            {
                throw $e;
            }
        }

        return $batch;
    }

    /**
     * Fills Batch entity with details extracted from the input file.
     * Eg.
     * - Total row count
     * - Aggregate sum of amount field
     *
     * @param Entity $batch
     * @param array  $entries
     */
    protected function fillBatchEntityWithInputFileDetails(
        Entity $batch,
        array $entries)
    {
        $totalAmount = array_sum(array_column($entries, Header::AMOUNT));
        $totalCount  = count($entries);

        $batch->setAmount($totalAmount);
        $batch->setTotalCount($totalCount);
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
     */
    protected function dispatchOnQueueForProcessingIfApplicable(Entity $batch)
    {
        if (Type::isQueueGroup($batch->getType()) == false)
        {
            return;
        }

        // For now this is being pushed onto invoice_emails queue only and
        // later we might have a new queue for this purpose only.

        $job = new BatchJob($this->mode, $batch->getId());

        (new DispatchRouter)->dispatchOn($job, DispatchRouter::BATCH);
    }
}
