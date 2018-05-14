<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\Merchant\Request\Service as MerchantRequestService;

class Service extends Base\Service
{
    public function createBatch(array $input): array
    {
        $batch = $this->core()->create($input, $this->merchant);

        return $batch->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        return $this->core()->fetchWithSettings($input, $this->merchant);
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

        $batch = $this->core()->retryBatchOutputFile($batch);

        return $batch->toArrayPublic();
    }

    public function downloadBatch(string $id): array
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        $signedUrl = $this->core()->downloadBatch($batch);

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
        $batches = $this->core()->processBatches();

        return $batches->toArrayPublic();
    }

    public function processBatch(string $id, array $input = []): array
    {
        $batch = $this->repo->batch->findByPublicId($id);

        $batch = $this->core()->processBatchAsync($batch, $input);

        return $batch->toArrayPublic();
    }

    public function validateFile(array $input): array
    {
        $response = $this->core()->storeAndValidateInputFile($input, $this->merchant);

        return $response;
    }

    public function fetchStatsOfBatch(string $id): array
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core()->fetchStatsOfBatch($batch);

        return $response;
    }

    public function validateToken($input): bool
    {
        $validator = new Validator();
        $validator->validateInput('token', $input);
        $token = $input['token'];

        $merchantRequestService = new MerchantRequestService();
        return $merchantRequestService->isValidOneTimeToken($token);
    }

    public function consumeToken($input)
    {
        $validator = new Validator();
        $validator->validateInput('token', [
            Entity::TOKEN   =>  $input['token']
        ]);
        $token = $input['token'];

        $merchantRequestService = new MerchantRequestService();
        $merchantRequestService->consumeOneTimeToken($token);
    }
}
