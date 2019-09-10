<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\ServerNotFoundException;
use RZP\Models\Merchant\Request\Service as MerchantRequestService;

class Service extends Base\Service
{
    public function createBatch(array $input): array
    {
        $batch = $this->core()->create($input, $this->merchant, $this->getAuthAdminElseUser());

        return $batch->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $fetchResult = $this->core()->fetchWithSettings($input, $this->merchant);

        if (isset($input['type']) && ($this->app->batchService->isMigratingBatchType($input['type']) === true))
        {
            $fetchResult = $this->app->batchService->getBatchesFromBatchServiceAndMerge($fetchResult, $input, $this->merchant);
        }

        return $fetchResult;
    }

    public function getBatchById(string $id): array
    {
        $responseBatch =  $this->app->batchService->getBatchesFromBatchService($id, $this->merchant);

        if ($responseBatch != null)
        {
            $this->app->batchService->prepareBatchItemResponse($responseBatch);

            return $responseBatch;
        }

        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        return $batch->toArrayPublic();
    }

    /**
     * for admin route
     *
     * @param  string $id
     * @return array
     */
    public function fetchBatchById(string $id): array
    {
        if ($this->auth->isAdminAuth() === false)
        {
            throw (new \Exception(
                PublicErrorDescription::BAD_REQUEST_ERROR,
                PublicErrorCode::BAD_REQUEST_ERROR
            ));
        }

        $responseBatch =  $this->app->batchService->getBatchesFromBatchService($id);

        if ($responseBatch != null)
        {
            $this->app->batchService->prepareBatchItemResponse($responseBatch);

            return $responseBatch;
        }

        $batch = $this->repo->batch->findByPublicId($id);

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
        try
        {
            $signedUrl = $this->app->batchService->downloadS3UrlForBatchOrFileStore($id, 'batch');

            return [Entity::URL => $signedUrl];
        }
        catch (ServerNotFoundException $exception)
        {
            // Either Batch Microservice is down or not found
            // check in DB.
            $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

            $signedUrl = $this->core()->downloadBatch($batch);

            return [Entity::URL => $signedUrl];
        }
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
        $batch =  $this->app->batchService->getBatchesFromBatchService($id, $this->merchant);

        if ($batch != null)
        {
            $responseEntity = (new ResponseEntity());

            $responseEntity->setId($batch['id']);

            $responseEntity->setType($batch['batch_type_id']);

            $responseEntity->setTotalCount($batch['total_count']);

            $batch = $responseEntity;
        }
        else
        {
            $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);
        }

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

    /**
     * @param array $input
     *
     * @return array
     */
    public function sendMail(array $input): array
    {
        $this->trace->info(TraceCode::BATCH_SEND_MAIL_REQUEST, $input);

        $validator = new Validator();

        $validator->validateInput('sendMail', $input);

        return $this->core()->sendMail($input);
    }

    public function getReconBatchesWithFiles(array $input)
    {
        $result = $this->repo->batch->getReconBatchesWithFiles($input);

        return $result->toArray();
    }

    public function getReconFilesCount(array $input)
    {
        $from = $input['from'] ?? null;

        $to   = $input['to'] ?? null;

        $result = $this->repo->batch->getReconFilesCountByGateway($from, $to);

        return $result->toArray();
    }
}
