<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function createBatch(array $input): array
    {
        $batch = (new Core)->create($input, $this->merchant);

        return $batch->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $batches = $this->repo->batch->fetch($input, $this->merchant->getId());

        return $batches->toArrayPublic();
    }

    public function getBatchById(string $id): array
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        return $batch->toArrayPublic();
    }

    /**
     * Ref: Batch/Core::retryBatchOutputFile
     *
     * @param string $id
     *
     * @return array
     */
    public function retryBatchOutputFile(string $id): array
    {
        $batch = $this->repo->batch->findByPublicId($id);

        $batch = (new Core)->retryBatchOutputFile($batch);

        return $batch->toArrayPublic();
    }

    public function downloadBatch(string $id): array
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        $signedUrl = (new Core)->downloadBatch($batch);

        return [Entity::URL => $signedUrl];
    }

    /**
     * Processes pending batches.
     * Called via cron.
     *
     * @return array
     */
    public function processBatches()
    {
        $batches = (new Core)->processBatches();

        return $batches->toArrayPublic();
    }

    /**
     * Processes particular batch id if not processed already.
     *
     * @param string $id
     *
     * @return array
     */
    public function processBatch(string $id): array
    {
        $batch = $this->repo->batch->findByPublicId($id);

        $batch = (new Core)->processBatchViaApi($batch);

        return $batch->toArrayPublic();
    }
}
