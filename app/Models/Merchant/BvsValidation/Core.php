<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Service;
use RZP\Exception\LogicException;
use RZP\Jobs\UpdateMerchantContext;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\BankAccount\Core as BankAccountCore;
use RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;
use RZP\Models\Merchant\BvsValidation\Entity as ValidationEntity;
use RZP\Models\BankingAccount\Activation\Detail\Entity as BankingAccountActivationEntity;

class Core extends Base\Core
{
    const BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT = 'bvs_validation_processing_attempt_count_';

    const BVS_VALIDATION_CUSTOM_CALLBACK_HANDLER_CACHE_KEY = 'bvs_validation_custom_process_validation_%s';

    const MAX_RETRY_COUNT = 3;

    const BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT_TTL_IN_SEC = 10800;

    const BVS_VALIDATION_CUSTOM_CALLBACK_HANDLER_TTL_IN_SEC = 36000;

    const DEFAULT_CALLBACK_HANDLER_FUNCTION = 'updateValidationStatusForMerchant';

    protected $mutex;

    protected $merchantDetails;

    protected $cache;

    public function __construct($merchantDetails = null)
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->cache = $this->app['cache'];

        $this->merchantDetails = $merchantDetails;
    }

    /**
     * this function is called to process the response pushed to kafka queue by BVS,
     * here we update the bvs_validation entity status based on the response
     *
     * @param array $payload
     *
     * @throws \Throwable
     */
    public function process(array $payload)
    {
        (new Validator())->validateInput('process_kafka_message', $payload);

        $validationObj = $this->getvalidationObject($payload);


        $validationId = $validationObj[Entity::VALIDATION_ID];

        try
        {
            if ($this->isValidationProcessingAttemptExceeded($validationId, $payload) === true)
            {
                return;
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

            throw $e;
        }
    }

    public function getValidation(string $validationId)
    {
        return $this->repo->bvs_validation->findOrFailPublic($validationId);
    }

    public function getLatestArtefactValidation(string $merchantId, string $artefact, string $validationUnit)
    {
        return $this->repo->bvs_validation->getLatestValidationForArtefactAndValidationUnit(
            $merchantId, $artefact, $validationUnit
        );
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
            Entity::VALIDATION_ID       => $payload[Constants::VALIDATION_ID],
            Entity::VALIDATION_STATUS   => $payload[Constants::STATUS],
            Entity::ERROR_CODE          => $payload[Constants::ERROR_CODE] ?? null,
            Entity::ERROR_DESCRIPTION   => $payload[Constants::ERROR_DESCRIPTION] ?? null,
            Entity::RULE_EXECUTION_LIST => $payload[Constants::RULE_EXECUTION_LIST] ?? []
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
        [$merchant, $merchantDetails] = (new Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        $merchantDetails = $this->merchantDetails ?? $merchantDetails;

        $statusUpdateFactory = new DocumentStatusUpdater\Factory();

        $statusUpdater = $statusUpdateFactory->getInstance($merchant, $merchantDetails, $validation);

        $statusUpdater->updateValidationStatus();

        $this->repo->saveOrFail($merchantDetails);

        $this->releaseLinkedAccountHoldFundsIfApplicable($merchant, $merchantDetails);

    }

    protected function releaseLinkedAccountHoldFundsIfApplicable($merchant, $merchantDetails)
    {
        if ($merchantDetails->isBankDetailStatusVerified() === true and
            $merchant->isLinkedAccount() === true and
            $merchant->getHoldFunds() === true and
            $merchant->getHoldFundsReason() === Merchant\Constants::LINKED_ACCOUNT_PENNY_TESTING)
        {
            $releaseFundsInput[MerchantEntity::HOLD_FUNDS] = 0;

            (new Service)->edit($merchant->getMerchantId(), $releaseFundsInput);
        }
    }

    protected function UpdateValidationStatusForBankingAccount(string $merchantId, Entity $validation)
    {
        [$merchant, $merchantDetails] = (new Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        $merchantDetails = $this->merchantDetails ?? $merchantDetails;

        $artefactType = $validation->getArtefactType();

        switch ($artefactType)
        {
            case Constant::BUSINESS_PAN:
                $statusUpdater = new DocumentStatusUpdater\BusinessPanForCA(
                    $merchant,
                    $merchantDetails,
                    BankingAccountActivationEntity::BUSINESS_PAN_VALIDATION,
                    $validation);
                break;
            case Constant::PERSONAL_PAN:
                $statusUpdater = new DocumentStatusUpdater\PersonalPanForCA(
                    $merchant,
                    $merchantDetails,
                    BankingAccountActivationEntity::BUSINESS_PAN_VALIDATION,
                    $validation);
                break;
            default :
                throw new LogicException(
                    ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_TYPE,
                    null,
                    [ValidationEntity::ARTEFACT_TYPE => $artefactType]);
        }

        $statusUpdater->updateValidationStatus();
    }

    /**
     * This updates bvs validation details back to BAS
     * As
     *
     * @param string|null $merchantId
     * @param Entity      $validation
     *
     * @throws LogicException
     */
    protected function UpdateValidationStatusForBAS(?string $merchantId, Entity $validation)
    {
        $statusUpdater = new DocumentStatusUpdater\UpdateStatusForBAS($validation);

        $statusUpdater->updateValidationStatus();
    }

    protected function GstinSelfServeCallbackHandler(string $merchantId, Entity $validation): void
    {
        [$merchant, $merchantDetails] = (new Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        $service = (new Merchant\Detail\Service());

        $service->handleGstinSelfServeCallback($merchantDetails, $validation);
    }

    protected function BankAccountUpdateCallbackHandler(string $merchantId, Entity $validation): void
    {
        [$merchant, $merchantDetails] = (new Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        (new BankAccountCore())->handleBankAccountUpdateCallback($merchant, $merchantDetails, $validation);
    }


    protected function getMerchantId($validation)
    {
        $ownerId = $validation->getOwnerId();

        $validationOwnerType = $validation->getOwnerType();

        if ($validationOwnerType === Constant::BANKING_ACCOUNT)
        {
            $bankingAccount = $this->repo->banking_account->findOrFail($ownerId);

            $merchantId = $bankingAccount->getMerchantId();
        }

        else
        {
            $merchantId = $ownerId;
        }

        return $merchantId;
    }

    /**
     * @param string $validationId
     * @param array  $validationObj
     */
    public function processValidation(string $validationId, array $validationObj): void
    {
        $validation = $this->repo->bvs_validation->findOrFail($validationId);

        $merchantId = $this->getMerchantId($validation);

        $validation->edit($validationObj);

        $this->mutex->acquireAndRelease(
            $validationId,
            function() use ($validation, $merchantId) {
                $this->repo->bvs_validation->saveOrFail($validation);

            },
            Merchant\Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
            Merchant\Constants::MERCHANT_MUTEX_RETRY_COUNT);

        $this->mutex->acquireAndRelease(
            $merchantId,
            function() use ($validation, $merchantId) {

                $this->repo->transactionOnLiveAndTest(
                    function() use ($validation, $merchantId) {


                        $callbackHandlerFn = $this->getCallbackHandlerFunction($validation);

                        $this->$callbackHandlerFn(
                            $merchantId,
                            $validation);
                    });
            },
            Merchant\Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
            Merchant\Constants::MERCHANT_MUTEX_RETRY_COUNT);

        $this->UpdateMerchantContext($merchantId, $validation);
    }

    protected function UpdateMerchantContext($merchantId, $validation)
    {
        try
        {
            $statusUpdateFactory = new DocumentStatusUpdater\Factory();

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $merchantDetails = $this->merchantDetails ?? $merchant->merchantDetail;

            $statusUpdater = $statusUpdateFactory->getInstance($merchant, $merchantDetails, $validation);

            if ($statusUpdater->canUpdateMerchantContext())
            {
                $this->trace->info(TraceCode::MERCHANT_STATUS_UPDATER_TRY, [
                    'merchant_id'   => $merchantId,
                    'validation_id' => $validation->getValidationId(),
                ]);

                $statusUpdater->updateMerchantContext();
            }
        }
        catch (\Exception $e)
        {
            $errorContext = [
                'merchant_id'   => $merchantId,
                'validation_id' => $validation->getValidationId(),
                'message'       => $e->getMessage(),
            ];

            $this->trace->error(TraceCode::MERCHANT_STATUS_UPDATER_FAIL, $errorContext);
        }
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
     *
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
    protected function incrementValidationProcessingAttempt(string $validationId): void
    {
        $bvsValidationProcessingAttempt = $this->getValidationProcessingAttempts($validationId);

        $this->updateBvsValidationProcessingAttempts($validationId, $bvsValidationProcessingAttempt + 1);
    }

    /**
     * Updates the redis key with the retry count
     *
     * @param string $validationId
     * @param int    $count
     */
    protected function updateBvsValidationProcessingAttempts(string $validationId, int $count): void
    {
        $bvsValidationProcessingAttemptRedisKey = $this->getbvsValidationProcessingAttemptKey($validationId);

        $this->cache->put($bvsValidationProcessingAttemptRedisKey, $count, self::BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT_TTL_IN_SEC);
    }

    /**
     * Redis Key for BVS Validation Id retry.
     *
     * @param string $validationId
     *
     * @return string
     */
    protected function getbvsValidationProcessingAttemptKey(string $validationId): string
    {
        return self::BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT . $validationId;
    }

    protected function getCallbackHandlerFunction(Base\Entity $validation)
    {
        $defaultHandlerFunction = self::DEFAULT_CALLBACK_HANDLER_FUNCTION;

        $customCallbackHandlerKey = $this->getCustomHandlerKey($validation);

        $customCallbackHandlerFunction = $this->cache->get($customCallbackHandlerKey);

        if ($customCallbackHandlerFunction === null)
        {
            return $defaultHandlerFunction;
        }

        $this->trace->info(TraceCode::BVS_USING_CUSTOM_CALLBACK_PROCESSOR, [
            'validation_id' => $validation->getValidationId(),
            'handler'       => $customCallbackHandlerFunction,
        ]);

        return $customCallbackHandlerFunction;
    }

    public function setCustomCallbackHandlerIfApplicable(Base\Entity $validation, $input)
    {
        if ($validation->getValidationStatus() == BvsValidationConstant::CAPTURED)
        {
            if (isset($input[Constant::CUSTOM_CALLBACK_HANDLER]) === false)
            {
                return;
            }
            $customHandler = studly_case($input[Constant::CUSTOM_CALLBACK_HANDLER]);

            $this->trace->info(TraceCode::BVS_SET_CUSTOM_CALLBACK_PROCESSOR, [
                'validation_id' => $validation->getValidationId(),
                'handler'       => $customHandler,
            ]);

            $customHandlerKey = $this->getCustomHandlerKey($validation);

            $this->cache->put($customHandlerKey, $customHandler, self::BVS_VALIDATION_CUSTOM_CALLBACK_HANDLER_TTL_IN_SEC);
        }
        else
        {
            if (isset($input[Constant::CUSTOM_CALLBACK_HANDLER]) === false)
            {
                $customHandler = self::DEFAULT_CALLBACK_HANDLER_FUNCTION;
            }
            else
            {
                $customHandler = studly_case($input[Constant::CUSTOM_CALLBACK_HANDLER]);
            }

            $merchantId = $this->getMerchantId($validation);
            $this->$customHandler(
                $merchantId,
                $validation);
        }

    }

    protected function getCustomHandlerKey(Base\Entity $validation)
    {
        return sprintf(self::BVS_VALIDATION_CUSTOM_CALLBACK_HANDLER_CACHE_KEY, $validation->getValidationId());
    }
}
