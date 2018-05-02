<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Settings;
use RZP\Models\FileStore;
use RZP\Base\RuntimeManager;
use RZP\Jobs\Batch as BatchJob;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    /**
     * Create flow: Creates new batch entity against given file id or against
     * given file(by first storing it).
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::BATCH_CREATE_REQUEST, $input);

        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($merchant);

        $processor = Processor\Factory::get($batch);

        $processor->storeInputFileAndSaveBatchWithSettings($input);

        $this->trace->info(TraceCode::BATCH_CREATED, $batch->toArrayPublic());

        $this->dispatchOnQueueForProcessingIfApplicable($batch, $input);

        return $batch;
    }

    /**
     * Validate flow: Stores and validates uploaded input file.
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @return array
     */
    public function storeAndValidateInputFile(array $input, Merchant\Entity $merchant): array
    {
        $this->trace->info(TraceCode::BATCH_FILE_VALIDATE_REQUEST, $input);

        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($merchant);

        $processor = Processor\Factory::get($batch);

        $response = $processor->storeAndValidateInputFile($input);

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
        Processor\Factory::get($batch)->retryOutputFile();

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

    public function fetchStatsOfBatch(Entity $batch): array
    {
        switch ($batch->getType())
        {
            case Type::PAYMENT_LINK:
                $stats = (new Invoice\Core)->fetchStatsOfBatch($batch);
                break;

            default:
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_STATS_NOT_SUPPORTED_FOR_TYPE,
                    Entity::TYPE,
                    [Entity::TYPE => $batch->getType()]);
        }

        $response = [
            Entity::ID    => $batch->getPublicId(),
            Entity::TYPE  => $batch->getType(),
            Entity::STATS => $stats,
        ];

        return $response;
    }

    public function fetchWithSettings(array $input, Merchant\Entity $merchant): array
    {
        $withConfig = array_pull($input, Entity::WITH_CONFIG, '0');

        $batches    = $this->repo->batch->fetch($input, $merchant->getId());

        $response   = $batches->toArrayPublic();

        // Conditionally, query and populate settings/config for each batch entity in response.
        if ($withConfig === '1')
        {
            // TODO: This is just temporary and not optimal query.
            $batches->each(function ($batch, $index) use (& $response)
            {
                $settingAccessor = Settings\Accessor::for($batch, Settings\Module::BATCH);

                $response[Base\PublicCollection::ITEMS][$index][Entity::CONFIG] = $settingAccessor->all()->toArray();
            });
        }

        return $response;
    }

    public function processBatchAsync(Entity $batch, array $input = []): Entity
    {
        $this->trace->info(TraceCode::BATCH_PROCESS_ASYNC, [$batch->toArrayPublic(), $input]);

        BatchJob::dispatch($this->mode, $batch->getId(), $input);

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

            BatchJob::dispatch($this->mode, $batch->getId(), $input);
        }
    }
}
