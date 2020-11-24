<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

class Core extends Base\Core
{
    const BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT  = 'bvs_validation_processing_attempt_count_';

    const MAX_RETRY_COUNT = 3;

    const BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT_TTL_IN_MIN = 180;

    protected $mutex;

    protected $cache;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->cache = $this->app['cache'];
    }

    /**
     * this function is called to process the response pushed to kafka queue by BVS,
     * here we update the bvs_validation entity status based on the response
     *
     * @param array $payload
     *
     * @return bool
     * @throws \Throwable
     */
    public function Process(array $payload) : bool
    {
        (new Validator())->validateInput('process_kafka_message', $payload);

        $validationObj = $this->getvalidationObject($payload);

        $validationId = $validationObj[Entity::VALIDATION_ID];

        try
        {
            if ($this->isValidationProcessingAttemptExceeded($validationId, $payload) === true)
            {
                return true;
            }

            $this->incrementValidationProcessingAttempt($validationId);

            $this->processValidation($validationId, $validationObj);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ONBOARDING_BVS_VERIFICATION_JOB_ERROR,
                $payload);

            return false;
        }

        return true;
    }

    /**
     * @param array $input
     *
     * @return Entity
     * @throws \RZP\Exception\LogicException
     */
    public function create(array $input): Entity
    {
        $this->trace->info(TraceCode::BVS_CREATE_VALIDATION_PAYLOAD, $input);

        $validation = new Entity();

        $validation->build($input);

        $this->repo->bvs_validation->saveOrFail($validation);

        $verificationMetrics = [
            Constant::ARTEFACT_TYPE                     => $validation->getArtefactType(),
            Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $validation->getValidationStatus()
        ];

        $this->trace->count(Detail\Metric::VALIDATION_STATUS_BY_ARTEFACT_TOTAL, $verificationMetrics);

        return $validation;
    }

    /**
     * convert payload consumed form kafka queue to bvs_validation object
     *
     * @param array $payload
     *
     * @return array
     */
    public function getValidationObject(array $payload): array
    {
        return [
            Entity::VALIDATION_ID     => $payload[Constants::VALIDATION_ID],
            Entity::VALIDATION_STATUS => $payload[Constants::STATUS],
            Entity::ERROR_CODE        => $payload[Constants::ERROR_CODE] ?? null,
            Entity::ERROR_DESCRIPTION => $payload[Constants::ERROR_DESCRIPTION] ?? null,
        ];
    }

    /**
     * Updates Document validation status for merchant
     * (multiple artefact can belongs to same document type for example for poa document type
     * artefact can be aadhaar, passport, voterId)
     *
     * @param string $merchantId
     * @param Entity $validation
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function updateValidationStatusForMerchant(string $merchantId, Entity $validation): void
    {
        [$merchant, $merchantDetails] = (New Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        $statusUpdateFactory = new DocumentStatusUpdater\Factory();

        $statusUpdater = $statusUpdateFactory->getInstance($merchant, $validation);

        $statusUpdater->updateValidationStatus();

        $this->repo->saveOrFail($merchantDetails);
    }

    /**
     * @param string $validationId
     * @param array  $validationObj
     */
    protected function processValidation(string $validationId, array $validationObj): void
    {
        $validation = $this->repo->bvs_validation->findOrFail($validationId);

        $merchantId = $validation->getOwnerId();

        $validation->edit($validationObj);

        $this->mutex->acquireAndRelease(
            $merchantId,
            function() use ($validation, $merchantId) {

                $this->repo->transactionOnLiveAndTest(
                    function() use ($validation, $merchantId) {

                        $this->repo->bvs_validation->saveOrFail($validation);

                        $this->updateValidationStatusForMerchant(
                            $merchantId,
                            $validation);
                    });
            },
            Merchant\Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
            Merchant\Constants::MERCHANT_MUTEX_RETRY_COUNT);
    }

    /**
     * @param string $validationId
     * @param array  $payload
     *
     * @return bool
     */
    protected function isValidationProcessingAttemptExceeded(string $validationId, array $payload): bool
    {
        $retryAttemptsCount = $this->getValidationProcessingAttempts($validationId);

        $retryAttemptMetrics = [
            Constants::RETRY_ATTEMPT_COUNT => $retryAttemptsCount
        ];

        $this->trace->count(Detail\Metric::BVS_VALIDATION_RETRY_ATTEMPT_TOTAL, $retryAttemptMetrics);

        if ($retryAttemptsCount >= self::MAX_RETRY_COUNT)
        {
            $this->trace->info(TraceCode::ONBOARDING_BVS_VERIFICATION_JOB_RETRY_EXCEEDED, $payload);

            return true;
        }

        return false;
    }

    /**
     * @param string $validationId
     * @return int return the retry count for the validationId
     */
    protected function getValidationProcessingAttempts(string $validationId): int
    {
        $bvsValidationProcessingAttemptKey = $this->getbvsValidationProcessingAttemptKey($validationId);

       return $this->cache->get($bvsValidationProcessingAttemptKey) ?? 0;
    }


    /**
     * Increment the retry count.
     *
     * @param string $validationId
     */
    protected function incrementValidationProcessingAttempt(string $validationId) : void
    {
        $bvsValidationProcessingAttempt = $this->getValidationProcessingAttempts($validationId);

        $this->updateBvsValidationProcessingAttempts($validationId, $bvsValidationProcessingAttempt + 1);
    }

    /**
     * Updates the redis key with the retry count
     *
     * @param string $validationId
     * @param int $count
     */
    protected function updateBvsValidationProcessingAttempts(string $validationId, int $count) : void
    {
        $bvsValidationProcessingAttemptRedisKey = $this->getbvsValidationProcessingAttemptKey($validationId);

        $this->cache->put($bvsValidationProcessingAttemptRedisKey, $count, self::BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT_TTL_IN_MIN);
    }

    /**
     * Redis Key for BVS Validation Id retry.
     * @param string $validationId
     * @return string
     */
    protected function getbvsValidationProcessingAttemptKey(string $validationId) : string
    {
        return self::BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT . $validationId;
    }
}
