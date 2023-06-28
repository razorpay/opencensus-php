<?php

namespace RZP\Models\Batch;

use RZP\Constants\Product;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Exception\ServerNotFoundException;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Models\Merchant\Request\Service as MerchantRequestService;

class Service extends Base\Service
{
    public function createBatch(array $input): array
    {
        $this->validateBatchTypeForUserRole($input);

        $batch = $this->core()->create($input, $this->merchant, $this->getAuthAdminElseUser());

        return $batch->toArrayPublic();
    }

    private function validateBatchTypeForUserRole($input)
    {
        if (($this->auth->isProxyAuth() === true) and
            (empty($this->auth->getAdmin()) === true) and
            $this->auth->getProduct() !== Product::BANKING)
        {
            $mode = $this->mode ?? 'live';

            $variant = $this->app->razorx->getTreatment($this->merchant->getId(),
                                                        Merchant\RazorxTreatment::SELLER_APP_PL_BATCH_UPLOAD_EXPERIMENT,
                                                        $mode);

            $role = $this->auth->getUserRole();

            (new Validator)->validateBatchTypeForUserRole($role, $variant, $input['type'] ?? Constants::DEFAULT);
        }
    }

    public function fetchMultiple(array $input): array
    {
        $this->validateBatchTypeForUserRole($input);

        $fetchResult = $this->core()->fetchWithSettings($input, $this->merchant);

        $input['types'] = $this->validateBatchTypes($input);

        $isTypePayout = false;

        if (isset($input['type']) && $input['type'] == 'payout')
        {
            $input['types'] = $this->getBulkPayoutTypes();

            unset($input['type']);

            $isTypePayout = true;
        }

        if ((isset($input['type']) and
            ($this->app->batchService->isMigratingBatchType($input['type']) === true)) or
            (empty($input['types']) === false))
        {
            $fetchResult = $this->app->batchService->getBatchesFromBatchServiceAndMerge($fetchResult, $input, $this->merchant);
        }

        if ($isTypePayout)
        {
            $this->appendUserDetails($fetchResult);
        }

        return $fetchResult;
    }

    private function getBulkPayoutTypes(): array
    {
        return [
            'payouts_bank_transfer_bene_id_process',
            'payouts_bank_transfer_bene_details_process',
            'payouts_upi_bene_details_process',
            'payouts_upi_bene_id_process',
            'payouts_amazonpay_bene_id_process',
            'payouts_amazonpay_bene_details_process',
            'payout'
        ];
    }

    private function appendUserDetails(array &$fetchResult): void
    {
        if (array_key_exists('items', $fetchResult) === true)
        {
            foreach ($fetchResult['items'] as &$item)
            {
                if (array_key_exists('creator_id', $item))
                {
                    $user = $this->repo->user->getUserFromId($item['creator_id']);

                    $item['creator_name'] = $user->getName();

                    $item['creator_email'] = $user->getEmail();
                }
            }
        }
    }

    public function validateBatchTypes($input): array
    {
        if(isset($input['types']) == false)
        {
            return [];
        }
        $types = [];
        foreach ($input['types'] as $type)
        {
            if($this->app->batchService->isMigratingBatchType($type)===true)
            {
                array_push($types, $type);
            }
        }
        return $types;
    }

    public function getBatchById(string $id, Merchant\Entity $merchant = null): array
    {
        if ((empty($this->merchant) === false) and
            (empty($merchant) === true))
        {
            $merchant = $this->merchant;
        }

        $responseBatch =  $this->app->batchService->getBatchesFromBatchService($id, $merchant);

        if ($responseBatch !== null)
        {
            $this->app->batchService->prepareBatchItemResponse($responseBatch);

            $input = [Entity::TYPE => $responseBatch[Entity::TYPE]];

            $this->validateBatchTypeForUserRole($input);

            return $responseBatch;
        }

        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $merchant);

        $input = [Entity::TYPE => $batch->getAttribute(Entity::TYPE) ];

        $this->validateBatchTypeForUserRole($input);

        return $batch->toArrayPublic();
    }

    public function validateFileName(array $queryParams)
    {
        (new Validator())->validateInput(Validator::VALIDATE_FILE_NAME, $queryParams);

        return $this->app->batchService->validateFileName($queryParams, $this->merchant);
    }

    /**
     * for admin route
     *
     * @param  string $id
     * @return array
     */
    public function fetchBatchById(string $id): array
    {
        $responseBatch =  $this->app->batchService->getBatchesFromBatchService($id);

        if ($responseBatch !== null)
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
            $signedUrl = $this->app->batchService->downloadS3UrlForBatchOrFileStore($id, 'batch', $this->merchant->getId());

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
            Entity::TOKEN => $input['token']
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

    /**
     * @param array $input
     *
     * @return array
     */
    public function sendSMS(array $input): array
    {
        $this->trace->info(TraceCode::BATCH_SEND_SMS_REQUEST, $input);

        $validator = new Validator();

        $validator->validateInput('sendSMS', $input);

        return $this->core()->sendSMS($input);
    }

    public function getReconBatchesWithFiles(array $input)
    {
        $result = $this->repo->batch->getReconBatchesWithFiles($input);

        return $result->toArray();
    }

    public function getReconFilesCount(array $input)
    {

        $result = $this->repo->batch->getReconFilesCountByGateway($input);

        return $result->toArray();
    }

    public function isStoppingRequired(string $batchStatus)
    {
        return $batchStatus !== Status::PROCESSED and $batchStatus !== Status::CANCELLED;
    }

    public function stopBatchProcess(array $batch)
    {
        $batchId = $batch[Entity::ID];

        if ($batch[Entity::STATUS] === Status::PROCESSED)
        {
            $this->trace->info(
                TraceCode::STOP_BATCH_PROCESS_NOT_REQUIRED,
                [
                    'batch_id'  => $batchId,
                    'status'    => $batch[Entity::STATUS],
                ]
            );

            return;
        }

        $this->app->batchService->cancelBatchInBatchService($batchId);
    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForFetchingBatchDetailsFromBatchId()
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Batch Refund Details Searched';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForBatchUpload()
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Batch refund File Uploaded';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    private function pushSelfServeSuccessEventsToSegment()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        return [$segmentEventName, $segmentProperties];
    }
}
