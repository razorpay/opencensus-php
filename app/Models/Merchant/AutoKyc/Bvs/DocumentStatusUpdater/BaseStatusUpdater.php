<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use App;

use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant\Website;
use RZP\Exception\LogicException;
use RZP\Jobs\UpdateMerchantContext;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\Detail\Core;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\BvsValidation\Entity as Validation;
use RZP\Models\Merchant\AutoKyc\Bvs\RuleExecutionResultVerifier;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\BvsValidation\Constants as ValidationConstants;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants as NCConstants;

abstract class BaseStatusUpdater implements StatusUpdater
{
    protected $app;

    protected $repo;

    protected $trace;

    /**
     * @var  DetailEntity
     */
    protected $merchantDetails;

    /**
     * @var  MerchantEntity
     */
    protected $merchant;

    /**
     * @var  string
     */
    protected $merchantId;

    /**
     * @var  string
     */
    protected $artefactType;

    /**
     * @var string
     */
    protected $consumedValidationId;

    /**
     * @var string
     */
    protected $validationUnit;

    /**
     * @var string
     */
    protected $documentTypeStatusKey;

    protected $ruleResultVerifier;

    const VALIDATION_STATUS_FUNCTION_MAPPING = [
        Constants::VERIFIED          => 'getVerifiedStatus',
        Constants::INCORRECT_DETAILS => 'getIncorrectDetailStatus',
        Constants::FAILED            => 'getFailedStatus',
        Constants::NOT_MATCHED       => 'getNotMatchedStatus',
    ];

    /**
     * BaseStatusUpdater constructor.
     *
     * @param MerchantEntity $merchant
     * @param DetailEntity   $merchantDetails
     * @param Validation     $consumedValidation
     */
    public function __construct(MerchantEntity $merchant,
                                DetailEntity $merchantDetails,
                                Entity $consumedValidation)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->merchant = $merchant;

        $this->merchantDetails = $merchantDetails;

        $this->merchantId = $this->merchantDetails->getMerchantId();

        $this->artefactType = $consumedValidation->getArtefactType();

        $this->validationUnit = $consumedValidation->getValidationUnit();

        $this->consumedValidationId = $consumedValidation->getValidationId();

        $ruleResultVerifierFactory = new RuleExecutionResultVerifier\Factory();

        $this->ruleResultVerifier = $ruleResultVerifierFactory->getInstance($consumedValidation);
    }

    /**
     * If existing status is under review then only update activation status means if merchant is already activated or
     * deleted do not update status
     * And if already in needs clarification, just add needs clarification reasons
     * Instant Activation is not covered as merchant context is triggered if form is submitted.
     */
    public function updateMerchantContext(): void
    {
        $partnerActivation = (new PartnerCore())->getPartnerActivation($this->merchant);

        if (($this->merchantDetails->isSubmitted() === false) and
            (empty($partnerActivation) or $partnerActivation->isSubmitted() === false))
        {
            return;
        }

        $merchantId = $this->merchantDetails->getId();

        $isSystemBasedNeedsClarificationEnabled = (new MerchantCore())->isRazorxExperimentEnable(
            $merchantId,
            RazorxTreatment::SYSTEM_BASED_NEEDS_CLARIFICATION);

        $this->trace->info(TraceCode::UPDATE_MERCHANT_CONTEXT_REQUEST, [
            'artefact_type'     => $this->artefactType,
            'experiment_status' => $isSystemBasedNeedsClarificationEnabled,
            'bvs_validation_id' => $this->consumedValidationId,
            'merchant_id'       => $merchantId
        ]);

        if ($isSystemBasedNeedsClarificationEnabled === true)
        {
            UpdateMerchantContext::dispatch(Mode::LIVE, $merchantId, $this->consumedValidationId);

            return;
        }

        $newActivationStatus = $this->getUpdatedActivationStatus();

        $activationStatus = $this->merchantDetails->getActivationStatus();

        if (($activationStatus !== $newActivationStatus) and
            ($activationStatus === Status::UNDER_REVIEW))
        {
            $activationStatusData = [
                DetailEntity::ACTIVATION_STATUS => $newActivationStatus
            ];

            (new Core())->updateActivationStatus($this->merchant, $activationStatusData, $this->merchant);
        }
    }

    protected function getVerifiedStatus()
    {
        return Constants::VERIFIED;
    }

    protected function getFailedStatus()
    {
        return Constants::FAILED;
    }

    protected function getIncorrectDetailStatus()
    {
        return Constants::INCORRECT_DETAILS;
    }

    protected function getNotMatchedStatus()
    {
        return Constants::NOT_MATCHED;
    }

    protected function getNotInitiatedStatus()
    {
        return Constants::NOT_INITIATED;
    }

    /**
     * Returns document validation status for a validation entity
     *
     * @param Validation $validation
     *
     * @return null|string
     * @throws LogicException
     */
    public function getDocumentValidationStatus(Validation $validation): ?string
    {
        $ruleExecutionResult = $this->ruleResultVerifier->verifyAndReturnRuleResult($this->merchant, $validation);

        if ($ruleExecutionResult[NCConstants::IS_ARTEFACT_VALIDATED] === true)
        {
            return $this->getVerifiedStatus();
        }

        if ($validation->getValidationStatus() === Constants::FAILED)
        {
            foreach (Constants::ERROR_MAPPING as $artifactValidationStatus => $error_codes)
            {
                if (array_search($validation->getErrorCode(), $error_codes, true) !== false)
                {
                    $func = self::VALIDATION_STATUS_FUNCTION_MAPPING[$artifactValidationStatus] ?? '';

                    if (method_exists($this, $func) === true)
                    {
                        return $this->$func();
                    }

                    throw new LogicException(
                        ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_VALIDATION_STATUS,
                        null,
                        [Constant::STATUS => $artifactValidationStatus]);
                }
            }

            //
            // If no error code is mapped then consider it as failed status
            //
            return $this->getFailedStatus();
        }

        //
        // If validation is still in pending status
        //

        return null;
    }


    public function getArtefactSignatoryVerificationStatus(Validation $validation): ?string
    {
        $ruleExecutionResult = $this->ruleResultVerifier->verifyAndReturnRuleResult($this->merchant, $validation);

        if ($ruleExecutionResult[NCConstants::IS_ARTEFACT_VALIDATED] === false )
        {
            return $this->getNotInitiatedStatus();
        }

        if ($ruleExecutionResult[NCConstants::IS_SIGNATORY_VALIDATED] === true)
        {
            return $this->getVerifiedStatus();
        }

        if ($validation->getValidationStatus() === Constants::FAILED)
        {
            foreach (Constants::ERROR_MAPPING as $artifactValidationStatus => $error_codes)
            {
                if (array_search($validation->getErrorCode(), $error_codes, true) !== false)
                {
                    $func = self::VALIDATION_STATUS_FUNCTION_MAPPING[$artifactValidationStatus] ?? '';

                    if (method_exists($this, $func) === true)
                    {
                        return $this->$func();
                    }

                    throw new LogicException(
                        ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_VALIDATION_STATUS,
                        null,
                        [Constant::STATUS => $artifactValidationStatus]);
                }
            }

            //
            // If no error code is mapped then consider it as failed status
            //
            return $this->getFailedStatus();
        }

        //
        // If validation is still in pending status
        //

        return null;

    }

    public function handleMerchantSignatory(): void
    {
        $merchantSignatoryStatus = $this->getMerchantSignatoryVerificationStatus($this->merchant);

        $this->app['trace']->info(TraceCode::MERCHANT_SIGNATORY,[
            "merchant_id"               => $this->merchant->getId(),
            "merchant_signatory_status" => $merchantSignatoryStatus
        ]);

        $input = [
            MVD\Entity::MERCHANT_ID         => $this->merchant->getId(),
            MVD\Entity::ARTEFACT_TYPE       => Constant::SIGNATORY_VALIDATION,
            MVD\Entity::ARTEFACT_IDENTIFIER => MVD\Constants::NUMBER,
            MVD\Entity::STATUS              => $merchantSignatoryStatus
        ];

        (new MVD\Core)->createOrEditVerificationDetail($this->merchantDetails, $input);
    }

    public function getMerchantSignatoryVerificationStatus(MerchantEntity $merchant)
    {
        $verificationDetails = $this->repo->merchant_verification_detail->getDetailsForMerchant($this->merchant->getId());

        if (empty($verificationDetails) === false)
        {
            $notMatchedCount = 0;

            foreach ($verificationDetails as $verificationDetail)
            {
                // only checking for artefacts allowed for signatory validation for a particular business type
                $allowedArtefactsForBusinessType = Constant::SIGNATORY_ARTEFACTS_BUSINESS_TYPE_MAPPING[$merchant->merchantDetail->getBusinessType()] ?? [];

                if (in_array($verificationDetail->getArtefactType() . '-' . $verificationDetail->getArtefactIdentifier(), $allowedArtefactsForBusinessType) === true)
                {
                    $signatoryValidationStatus = $verificationDetail['metadata']['signatory_validation_status']?? null;

                    switch ($signatoryValidationStatus)
                    {
                        case ValidationConstants::VERIFIED:
                            return ValidationConstants::VERIFIED;

                        case ValidationConstants::NOT_MATCHED:
                            $notMatchedCount++;
                            break;

                        default:
                            break;

                    }
                }
            }

            return $notMatchedCount >= 1 ? ValidationConstants::NOT_MATCHED : ValidationConstants::NOT_INITIATED;

        }

        return BvsValidationConstants::NOT_INITIATED;
    }




    /**
     * @return string
     */
    public function getUpdatedActivationStatus(): string
    {
        return (new Core())->getApplicableActivationStatus($this->merchantDetails);
    }

    public function updateStatusToPending(): void
    {
        throw new LogicException(
            ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_TYPE,
            null
        );

    }
    public function canUpdateMerchantContext(): bool
    {
        return true;
    }

    /**
     * @throws LogicException
     */
    public function sendConsumedValidationResultEvent()
    {
        $validation = $this->repo->bvs_validation->findOrFail($this->consumedValidationId);

        $documentValidationStatus = $this->getDocumentValidationStatus($validation);

        $properties = $this->getValidationEventProperties($validation, $documentValidationStatus);

        $this->app['diag']->trackOnboardingEvent(
            EventCode::BVS_CONSUMED_VALIDATION_DOCUMENT_VERIFICATION_RESULTS,
            $this->merchant,
            null,
            $properties
        );
    }

    protected function updateStakeholderStatusIfApplicable($status)
    {
        switch ($this->documentTypeStatusKey)
        {
            case DetailEntity::PERSONAL_PAN_DOC_VERIFICATION_STATUS:
                (new Stakeholder\Core)->createOrFetchStakeholder($this->merchantDetails);
                $this->merchantDetails->stakeholder->setPanDocStatus($status);
                break;

            case DetailEntity::POI_VERIFICATION_STATUS:
                (new Stakeholder\Core)->createOrFetchStakeholder($this->merchantDetails);
                $this->merchantDetails->stakeholder->setPoiStatus($status);
                break;

            case DetailEntity::POA_VERIFICATION_STATUS:
                (new Stakeholder\Core)->createOrFetchStakeholder($this->merchantDetails);
                $this->merchantDetails->stakeholder->setPoaStatus($status);
                break;
        }
    }

    /**
     * @param Validation $validation
     * @param string     $documentValidationStatus
     *
     * @return array
     */
    protected function getValidationEventProperties(Validation $validation, string $documentValidationStatus): array
    {
        $responseTimeInMilliseconds = millitime() - ($validation->getCreatedAt() * 1000);

        return [
            Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $documentValidationStatus,
            Constants::DOCUMENT_VERIFICATION_STATUS_KEY => $this->documentTypeStatusKey,
            Constants::RESPONSE_TIME_MILLI_SECONDS      => $responseTimeInMilliseconds,
            Entity::ARTEFACT_TYPE                       => $validation->getArtefactType(),
            Entity::VALIDATION_STATUS                   => $validation->getValidationStatus(),
            Entity::ERROR_CODE                          => $validation->getErrorCode(),
            Entity::ERROR_DESCRIPTION                   => $validation->getErrorDescription(),
        ];
    }
}
