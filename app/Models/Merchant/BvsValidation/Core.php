<?php

namespace RZP\Models\Merchant\BvsValidation;

use Carbon\Carbon;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Service;
use RZP\Models\Merchant\Consent;
use RZP\Exception\LogicException;
use RZP\Jobs\UpdateMerchantContext;
use RZP\Exception\ExtraFieldsException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
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

    const BVS_RESPONSE_PROCESSING_ATTEMPT_COUNT_TTL_IN_SEC = 10800;

    const BVS_VALIDATION_CUSTOM_CALLBACK_HANDLER_TTL_IN_SEC = 36000;

    const DEFAULT_CALLBACK_HANDLER_FUNCTION = 'updateValidationStatusForMerchant';

    const BVS_LEGAL_DOCUMENT_PROCESSING_ATTEMPT_COUNT = 'bvs_legal_document_processing_attempt_count_';

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
            if ($this->isKafkaMessageProcessingAttemptExceeded($validationId, $payload, self::BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT ) === true)
            {
                return;
            }

            $this->incrementKafkaMessageProcessingAttempt($validationId, self::BVS_VALIDATION_PROCESSING_ATTEMPT_COUNT);

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

    public function processBvsLegalDocuments(array $payload)
    {
        $this->validateInput('process_kafka_message_legal_document', $payload);

        $id = $payload[Constants::ID];

        try
        {
            //If kafka message processing attempt exceeded, retry job will send request to BVS for creation of legal documents again.
            if ($this->isKafkaMessageProcessingAttemptExceeded($id, $payload, self::BVS_LEGAL_DOCUMENT_PROCESSING_ATTEMPT_COUNT) === true)
            {
                return;
            }

            $this->incrementKafkaMessageProcessingAttempt($id, self::BVS_LEGAL_DOCUMENT_PROCESSING_ATTEMPT_COUNT);

            $this->processDocuments($id, $payload);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::BVS_CONSENT_PROCESSING_ERROR,
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
     * @param array                         $input
     *
     * @param Merchant\Document\Entity|null $document
     *
     * @return Entity
     * @throws LogicException
     */
    public function create(array $input,DocumentEntity $document=null): Entity
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

        if(empty($document)===false)
        {
            $document->setValidationId($validation->getValidationId());

            $this->repo->merchant_document->saveOrFail($document);
        }

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

        $this->trace->info(TraceCode::MERCHANT_UPDATE_VALIDATION_STATUS,$merchantDetails->getDirty());

        $this->repo->saveOrFail($merchantDetails);

        $this->trace->info(TraceCode::MERCHANT_UPDATE_VALIDATION_STATUS_DONE,$merchantDetails->getDirty());

        $this->releaseLinkedAccountHoldFundsIfApplicable($merchant, $merchantDetails);

    }

    protected function releaseLinkedAccountHoldFundsIfApplicable($merchant, $merchantDetails)
    {
        if ($merchantDetails->isBankDetailStatusVerified() === true and
            $merchant->isLinkedAccount() === true and
            $merchant->getHoldFunds() === true and
            $merchant->getHoldFundsReason() === Merchant\Constants::LINKED_ACCOUNT_PENNY_TESTING)
        {

            $merchant->setHoldFundsReason();

            $this->repo->saveOrFail($merchant);

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

    public function getMerchantId($validation)
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

        //only edit bvs_validation table if there is any update on the validation object
        if (empty($validationObj) === false)
        {
            $validation->edit($validationObj);

            $this->mutex->acquireAndRelease(
                $validationId,
                function() use ($validation, $merchantId) {
                    $this->repo->bvs_validation->saveOrFail($validation);

                },
                Merchant\Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
                Merchant\Constants::MERCHANT_MUTEX_RETRY_COUNT);
        }

        if (isset($validationObj[Entity::METADATA]) === true)
        {
            $validation->setMetadata($validationObj[Entity::METADATA]);
        }

        $this->mutex->acquireAndRelease(
            $merchantId,
            function() use ($validation, $merchantId) {
                $shouldFireAccountUpdatedWebhook = $this->shouldFireAccountUpdatedWebhook($merchantId);

                $this->repo->transactionOnLiveAndTest(
                    function() use ($validation, $merchantId) {

                        $callbackHandlerFn = $this->getCallbackHandlerFunction($validation);

                        $this->$callbackHandlerFn(
                            $merchantId,
                            $validation);
                    });

                if ($shouldFireAccountUpdatedWebhook === true)
                {
                    (new Merchant\Core())->eventLinkedAccountUpdated($merchantId);
                }
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

    protected function shouldFireAccountUpdatedWebhook(string $merchantId)
    {
        $merchant = $this->repo->merchant->find($merchantId);

        $isLinkedAccount = (is_null($merchant) === false) ? $merchant->isLinkedAccount() : false;

        if($isLinkedAccount === false)
        {
            return false;
        }

        $bankAccountUpdateFeatureEnabled = $merchant->parent->isFeatureEnabled(FeatureConstant::LA_BANK_ACCOUNT_UPDATE);

        $holdFundsReason = $merchant->getHoldFundsReason();

        $this->trace->info(
            TraceCode::SHOULD_FIRE_LINKED_ACCOUNT_UPDATED_WEBHOOK_DATA,
            [
                'is_linked_account'             => $isLinkedAccount,
                'bank_updated_feature_enabled'  => $bankAccountUpdateFeatureEnabled,
                'hold_funds_reason'             => $holdFundsReason,
            ]
        );
        if (($bankAccountUpdateFeatureEnabled === true) and
            ($holdFundsReason === Merchant\Constants::LINKED_ACCOUNT_PENNY_TESTING))
        {
            return true;
        }
        return false;
    }

    /**
     * @param string $validationId
     * @param array  $payload
     *
     * @return bool
     */
    protected function isKafkaMessageProcessingAttemptExceeded(string $validationId, array $payload, string $attribute): bool
    {
        $retryAttemptsCount = $this->getValidationProcessingAttempts($validationId, $attribute);

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
    protected function getValidationProcessingAttempts(string $validationId, string $attribute): int
    {
        $bvsValidationProcessingAttemptKey = $this->getbvsValidationProcessingAttemptKey($validationId, $attribute);

        return $this->cache->get($bvsValidationProcessingAttemptKey) ?? 0;
    }


    /**
     * Increment the retry count.
     *
     * @param string $validationId
     */
    protected function incrementKafkaMessageProcessingAttempt(string $validationId, string $attribute): void
    {
        $bvsValidationProcessingAttempt = $this->getValidationProcessingAttempts($validationId, $attribute);

        $this->updateBvsValidationProcessingAttempts($validationId, $bvsValidationProcessingAttempt + 1, $attribute);
    }

    /**
     * Updates the redis key with the retry count
     *
     * @param string $validationId
     * @param int    $count
     */
    protected function updateBvsValidationProcessingAttempts(string $validationId, int $count,  string $attribute): void
    {
        $bvsValidationProcessingAttemptRedisKey = $this->getbvsValidationProcessingAttemptKey($validationId, $attribute);

        $this->cache->put($bvsValidationProcessingAttemptRedisKey, $count, self::BVS_RESPONSE_PROCESSING_ATTEMPT_COUNT_TTL_IN_SEC);
    }

    /**
     * Redis Key for BVS Validation Id retry.
     *
     * @param string $validationId
     *
     * @return string
     */
    protected function getbvsValidationProcessingAttemptKey(string $validationId, string $attribute): string
    {
        return $attribute . $validationId;
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
            if (isset($input[Constant::CUSTOM_CALLBACK_HANDLER]) === true)
            {
                $customHandler = studly_case($input[Constant::CUSTOM_CALLBACK_HANDLER]);
            }
            else
            {
                $customHandler = self::DEFAULT_CALLBACK_HANDLER_FUNCTION;
            }

            $this->trace->info(TraceCode::BVS_USING_CUSTOM_CALLBACK_PROCESSOR, [
                'validation_id' => $validation->getValidationId(),
                'handler'       => $customHandler,
            ]);

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

    private function processDocuments($id, array $payload)
    {
        $this->trace->info(TraceCode::PROCESS_MERCHANT_CONSENTS, [
            'request_id' => $id,
            'response'   => $payload
        ]);

        $documentsDetail = $payload[Constants::DOCUMENTS_DETAIL];

        foreach ($documentsDetail as $documentDetail)
        {
            $status = $documentDetail['status'];

            $merchantConsentDetail = $this->repo->merchant_consents->getConsentDetailsForRequestId($id, $documentDetail['type']);

            if (empty($merchantConsentDetail) === true)
            {
                // Safety check: If merchant details are still null, return at this point
                return;
            }

            $input = [
                'status'     => $status,
                'updated_at' => Carbon::now()->getTimestamp(),
                'metadata'   => (new Consent\Core())->mergeJson($merchantConsentDetail['metadata'], ['ufh_file_id' => $documentDetail['ufh_file_id']])
            ];

            try
            {
                $merchantConsentDetail->edit($input, 'edit');

                $this->repo->merchant_consents->saveOrFail($merchantConsentDetail);
            }
            catch (\Throwable $e)
            {
                throw new LogicException($e->getMessage(), $e->getCode());
            }

            $retryCount = $merchantConsentDetail->retry_count;

            $this->trace->info(TraceCode::CRON_ATTEMPT_COMPLETE, [
                'merchant_id' => $merchantConsentDetail->merchant_id,
                'count'       => $retryCount
            ]);

            if ($retryCount == self::MAX_RETRY_COUNT)
            {
                $this->trace->count(Constants::API_RETRY_JOB_FAILURE);
            }
        }

    }

    public function savePGOSDataToAPI(array $data)
    {
        $splitzResult = (new Detail\Core)->getSplitzResponse($data[Entity::OWNER_ID], 'pgos_migration_dual_writing_exp_id');

        if ($splitzResult === 'variables')
        {
            $merchant = $this->repo->merchant->find($data[Entity::OWNER_ID]);

            // dual write only for below merchants
            // merchants for whom pgos is serving onboarding requests
            // merchants who are not completely activated
            if ($merchant->getService() === Merchant\Constants::PGOS and
                $merchant->merchantDetail->getActivationStatus()!=Detail\Status::ACTIVATED)
            {
                $validation = $this->repo->bvs_validation->find($data[Entity::VALIDATION_ID]);

                $data[Entity::OWNER_TYPE] = Constant::MERCHANT;

                $data[Entity::PLATFORM] = Constant::PG;

                if (empty($validation) === false)
                {
                    $validation->edit($data);

                    $this->repo->bvs_validation->saveOrFail($validation);
                }
                else
                {
                    $validation = new Entity;

                    $validation->build($data);

                    $this->repo->bvs_validation->saveOrFail($validation);

                }
            }
        }
    }

    /**
     * @param string $operation
     * @param array  $payload
     */
    private function validateInput(string $operation, array $payload): void
    {
        try
        {
            (new Validator())->validateInput($operation, $payload);
        }
        catch (ExtraFieldsException $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::KAFKA_PAYLOAD_CONTAINS_EXTRA_FIELDS,
                $payload);
        }
    }
}
