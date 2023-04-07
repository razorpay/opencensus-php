<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification;

use Throwable;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Document;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Feature\Core as FeatureCore;
use RZP\Models\DeviceDetail\Constants as DDConstants;
use RZP\Models\ClarificationDetail\Service as ClarificationService;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Constants as MerchantConstant;
use RZP\Models\Merchant\Detail\SelectiveRequiredFields;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Type as DocumentType;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;
use RZP\Models\Merchant\Detail\NeedsClarificationMetaData;
use RZP\Models\Merchant\Detail\Constants as DetailConstant;
use RZP\Models\Merchant\Detail\RetryStatus as RetryStatus;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList;
use RZP\Models\Merchant\Detail\ActivationFields as ActivationFields;
use RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer\Factory;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

/**
 * This class contains logic specific to kyc clarification
 *
 * @package RZP\Models\Merchant\Detail\NeedClarification
 */
class Core extends Base\Core
{
    /**
     * Check whether a given entity (merchant/partner) should go under needs clarification
     *
     * @param Base\PublicEntity $entity
     *
     * @return bool
     */
    public function shouldTriggerNeedsClarification(Base\PublicEntity $entity): bool
    {
        if ($entity->merchant->isSignupCampaign(DDConstants::EASY_ONBOARDING) === true)
        {
            return false;
        }

        $statusChangeLogs = ($entity->getEntityName() === E::PARTNER_ACTIVATION) ? $entity->getActivationStatusChangeLog() :
            (new MerchantCore)->getActivationStatusChangeLog($entity->merchant);

        $needsClarificationCount = (new MerchantDetailCore())->getStatusChangeCount($statusChangeLogs, Status::NEEDS_CLARIFICATION);

        /*
           If we have already raised needs clarification flow once then don't raise it again
        */
        if (($entity->merchant->isNoDocOnboardingEnabled() === false) and
            ($needsClarificationCount >= 1) and
            ($entity->merchant->isLinkedAccount() === false))
        {
            return false;
        }

        /*
           If all statuses are verified then don't trigger needs clarification request
        */

        return (new UpdateContextRequirements())->shouldTriggerNeedsClarification($entity);
    }

    /**
     * Remove No doc feature if retry exhausted for dedupe or verification
     * @param Merchant\Entity $merchant
     * @param DetailEntity    $merchantDetail
     * @param string|null     $reasonCode
     *
     * @throws Throwable
     */
    public function removeNoDocFeatureIfApplicable(Merchant\Entity $merchant, DetailEntity $merchantDetail)
    {

        if ($merchant->isNoDocOnboardingEnabled() === false)
        {
            return;
        }

        $merchantDetailCore = (new MerchantDetailCore());
        $noDocData          = $merchantDetailCore->fetchNoDocData($merchantDetail);

        [$isRemoveNoDocFeature, $reasonCode] = $this->shouldRemoveNoDocFeature ($noDocData, $merchant, $merchantDetail);
        if ($isRemoveNoDocFeature === true)
        {
            $featureCore = (new FeatureCore());

            $featureCore->removeFeature(FeatureConstants::NO_DOC_ONBOARDING, true);

            if (empty($reasonCode) === false)
            {
                $this->updateActivationStatusForNoDoc($merchant, $merchantDetail, $reasonCode);
            }
        }
    }

    private function shouldRemoveNoDocFeature(array $noDocData, Merchant\Entity $merchant, DetailEntity $merchantDetail )
    {
        $isRemoveNoDocFeature = false;

        $failedStatus = [BvsValidationConstants::NOT_MATCHED, BvsValidationConstants::INCORRECT_DETAILS, BvsValidationConstants::FAILED];

        $verificationConfig = $noDocData[DEConstants::VERIFICATION];

        $dedupeConfig = $noDocData[DetailConstant::DEDUPE];

        if ($this->isDedupeInProgressForXpressOnboarding($dedupeConfig) === true)
        {
            return $isRemoveNoDocFeature;
        }

        foreach ($verificationConfig as $artefact => $value)
        {

            if (($artefact === DetailEntity::GSTIN and in_array($merchantDetail->getGstinVerificationStatus(), $failedStatus, true) === true)
                or ($verificationConfig[$artefact][DEConstants::RETRY_COUNT] > 1))
            {
                return [true, $verificationConfig[$artefact][DEConstants::FAILURE_REASON_CODE]];
            }
        }

        return [$isRemoveNoDocFeature, null];
    }

    private function isDedupeInProgressForXpressOnboarding(array $dedupeConfig) :bool
    {
        $isDedupeConfigInProgress = false;
        foreach ($dedupeConfig as $artefact => $value)
        {
            if ($dedupeConfig[$artefact][DEConstants::RETRY_COUNT] > 0 and $dedupeConfig[$artefact][DEConstants::STATUS] === RetryStatus::PENDING)
            {
                $isDedupeConfigInProgress = true;
                break;
            }
        }

        return $isDedupeConfigInProgress;
    }


    /**
     * Compose needs clarification for a given entity (merchant/partner)
     *
     * @param Base\PublicEntity $entity
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     */
    public function composeNeedsClarificationReason(Base\PublicEntity $entity): array
    {
        $clarificationKeys = (new UpdateContextRequirements())->getClarificationKeys($entity);

        $kycClarificationReasons = [];

        $merchantDetail = ($entity->getEntityName() === E::PARTNER_ACTIVATION) ? $entity->merchantDetail : $entity;

        $merchantDetailCore = (new MerchantDetailCore());
        $noDocData          = $merchantDetailCore->fetchNoDocData($merchantDetail);


        $factory = new Factory($merchantDetail);

        $isLinkedAccount = (is_null($this->merchant) === false) ? $this->merchant->isLinkedAccount() : false;

        $clarificationMetadataArray = ($isLinkedAccount === true) ? NeedsClarificationMetaData::getLinkedAccountSystemBasedNeedsClarificationMetaData()
                                                                  : NeedsClarificationMetaData::SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA;


        foreach ($clarificationKeys as $clarificationKey)
        {
            $clarificationMetadata = $clarificationMetadataArray[$clarificationKey] ?? [];

            //
            // If clarification metadata is not defined then continue
            //
            if (empty($clarificationMetadata) === true)
            {
                continue;
            }

            $reason = $factory->getClarificationReasonComposer($clarificationMetadata, $noDocData)
                              ->getClarificationReason();

            $kycClarificationReasons = $this->mergeKycClarificationReasons(
                $kycClarificationReasons ?? [],
                $reason[DetailEntity::CLARIFICATION_REASONS] ?? [],
                $reason[DetailEntity::ADDITIONAL_DETAILS] ?? []);
        }

        return $kycClarificationReasons;
    }

    /**
     * Merge clarification reason and additional details with existing kyc clarification reasons
     *
     * @param  $kycClarificationReason
     * @param  $clarificationReason
     * @param  $additionalDetails
     *
     * @return array Merged kyc clarification
     */
    public function mergeKycClarificationReasons(
        array $kycClarificationReason,
        $clarificationReason,
        $additionalDetails)
    {
        $kycClarificationReason = $kycClarificationReason ?? [];

        if ((empty($clarificationReason) === false) and
            (is_array($clarificationReason) === true))
        {
            $currentClarificationReason =
                $kycClarificationReason[DetailEntity::CLARIFICATION_REASONS] ?? [];

            $kycClarificationReason[DetailEntity::CLARIFICATION_REASONS] =
                array_merge($currentClarificationReason,
                                      $clarificationReason);
        }

        if ((empty($additionalDetails) === false) and
            (is_array($additionalDetails)) === true)
        {
            $currentAdditionalDetails =
                $kycClarificationReason[DetailEntity::ADDITIONAL_DETAILS] ?? [];

            $kycClarificationReason[DetailEntity::ADDITIONAL_DETAILS] =
                array_merge_recursive($currentAdditionalDetails,
                                      $additionalDetails);
        }

        return $kycClarificationReason;
    }

    /**
     * Returns Formatted Kyc clarification reason
     *
     * This formatted response will be sent as part of api response and email template
     *
     * @param $kycClarificationReason
     *
     * @return array
     */
    public function getFormattedKycClarificationReasons($kycClarificationReason)
    {

        if (empty($kycClarificationReason) === true)
        {
            return [];
        }
        $requirements = [];

        $clarificationReasons = $kycClarificationReason[DetailEntity::CLARIFICATION_REASONS] ?? [];

        $additionalDetails = $kycClarificationReason[DetailEntity::ADDITIONAL_DETAILS] ?? [];

        $requirements = $this->getFormattedOutputForClarificationReasons($clarificationReasons, $requirements);

        $requirements = $this->getFormattedOutputForAdditionalReasons($additionalDetails, $requirements);

        return $requirements;
    }

    protected function getLatestAdminCommentForField(array $reasons)
    {
        $lastReason  = [];
        $requirement = [];
        foreach ($reasons as $reason)
        {
            $lastReason = $reason;
        }

        if ((empty($lastReason) === false) and
            (in_array($lastReason[MerchantConstant::REASON_FROM], DetailConstant::NEEDS_CLARIFICATION_SOURCES, true) === true))
        {
            if ($lastReason[MerchantConstant::REASON_TYPE] === MerchantConstant::PREDEFINED_REASON_TYPE)
            {
                $requirement = $this->getRequirementForPreDefinedReason($requirement, $lastReason);
            }
            else
            {
                $requirement[Constants::REASON_CODE]        = NeedsClarificationMetaData::OTHERS;
                $requirement[Constants::REASON_DESCRIPTION] = $lastReason[MerchantConstant::REASON_CODE];
            }
        }

        return $requirement;
    }

    /**
     * @param $clarificationReasons
     * @param $requirements
     *
     * @return mixed
     */
    private function getFormattedOutputForClarificationReasons(array $clarificationReasons, array $requirements)
    {
        foreach ($clarificationReasons as $fieldName => $reasons)
        {
            $requirement = $this->getLatestAdminCommentForField($reasons);

            if (!empty($requirement))
            {
                $requirement[Constants::DISPLAY_NAME] = ActivationFields::getFieldDisplayName($fieldName);

                $group = (DocumentType::isValid($fieldName) === true) ? Constants::DOCUMENTS : Constants::FIELDS;

                $requirements[$group][$fieldName][] = $requirement;
            }
        }

        return $requirements;
    }

    private function getFormattedOutputForAdditionalReasons(array $clarificationReasons, array $requirements)
    {
        foreach ($clarificationReasons as $fieldName => $reasons)
        {
            $requirement = [];

            foreach ($reasons as $reason)
            {
                if ($reason[MerchantConstant::REASON_TYPE] === MerchantConstant::PREDEFINED_REASON_TYPE)
                {
                    $requirement = $this->getRequirementForPreDefinedReason($requirement, $reason);
                }
                else
                {
                    $requirement[Constants::REASON_CODE]        = NeedsClarificationMetaData::OTHERS;
                    $requirement[Constants::REASON_DESCRIPTION] = $reason[MerchantConstant::REASON_CODE];
                }

                $requirement[Constants::DISPLAY_NAME] = ActivationFields::getFieldDisplayName($fieldName);

                $group = (DocumentType::isValid($fieldName) === true) ? Constants::DOCUMENTS : Constants::FIELDS;

                $requirements[$group][$fieldName][] = $requirement;
            }
        }

        return $requirements;
    }

    /**
     * @param $requirement
     * @param $reason
     *
     * @return mixed
     */
    private function getRequirementForPreDefinedReason($requirement, $reason)
    {
        $preDefinedReasonCode = $reason[MerchantConstant::REASON_CODE];
        $reasonMetaData       = NeedsClarificationReasonsList::REASON_DETAILS[$preDefinedReasonCode];

        $requirement[Constants::REASON_CODE]        = $preDefinedReasonCode;
        $requirement[Constants::REASON_DESCRIPTION] = $reasonMetaData[NeedsClarificationMetaData::DESCRIPTION];

        return $requirement;
    }

    public function getNonAcknowledgedNCFields(Merchant\Entity $merchant, DetailEntity $merchantDetails): array
    {
        $isNoDocOnboardingEnabled = $merchant->isNoDocOnboardingEnabled();

        $documentResponse = (new Document\Core())->documentResponse($merchant);

        $latestClarificationFields = $this->getLatestKycClarificationReasons($merchantDetails);

        $nonAcknowledgedNCFields = [];

        $nonAcknowledgedNCFields[Constants::DOCUMENTS] = [];

        $nonAcknowledgedNCFields[Constants::FIELDS] = [];

        $totalNonAcknowledgedFieldCount = 0;

        foreach ($latestClarificationFields as $field => $clarificationDetails)
        {
            $group = DocumentType::isValid($field) === true ? Constants::DOCUMENTS : Constants::FIELDS;

            if ($this->isNCFieldAcknowledged($clarificationDetails) === false)
            {
                $nonAcknowledgedNCFields[$group][$field] = $clarificationDetails;

                $totalNonAcknowledgedFieldCount = $totalNonAcknowledgedFieldCount + 1;
            }
            /***
             * Validate bank proof is submitted for a latest bank details NC field even it is acknowledged or not acknowledged
             *   Case 1: bank_account_number is not acknowledged and bank proof is not submitted
             *   Case 2: bank_account_number is acknowledged and bank proof is not submitted
             *   In both the above cases check for bank proof submission
             ***/
            if (($this->isBankDetailsNCField($field) === true) &&
                ($this->isNCAcknowledgedForBankDocumentProofs($documentResponse, $clarificationDetails) === false) &&
                array_key_exists(DocumentType::CANCELLED_CHEQUE, $nonAcknowledgedNCFields[Constants::DOCUMENTS]) === false &&
                ($this->merchant->isLinkedAccount() === false) && $isNoDocOnboardingEnabled === false) {
                $nonAcknowledgedNCFields[Constants::DOCUMENTS][DocumentType::CANCELLED_CHEQUE] = $clarificationDetails;

                $totalNonAcknowledgedFieldCount = $totalNonAcknowledgedFieldCount + 1;
        }
        }

        $nonAcknowledgedNCFields[Merchant\Constants::COUNT] = $totalNonAcknowledgedFieldCount;

        $this->trace->info(TraceCode::MERCHANT_NON_ACKNOWLEDGED_NC_FIELDS, $nonAcknowledgedNCFields);

        return $nonAcknowledgedNCFields;
    }

    /**
     * This function returns whether a bank proof is submitted or not for a latest bank details NC field
     * Pick the latest bank details NC field clarification reason and check over bank proofs
     * Condition : (bank proof doc upload time > latest clarification creation time)
     * @param array $documentResponse
     * @param array $clarificationDetails
     *
     * @return bool
     */
    private function isNCAcknowledgedForBankDocumentProofs(array $documentResponse, array $clarificationDetails)
    {
        $bankDocumentProofAcknowledged = false;

        foreach (SelectiveRequiredFields::BANK_PROOF_DOCUMENTS as $documentType)
        {
            if(array_key_exists($documentType, $documentResponse) === true)
            {
                $cancelledChequeDocuments = $documentResponse[$documentType];

                $latestUploaded = $cancelledChequeDocuments[count($cancelledChequeDocuments) - 1];

                if ($latestUploaded[Document\Entity::CREATED_AT] > $clarificationDetails[Document\Entity::CREATED_AT])
                {
                    $bankDocumentProofAcknowledged = true;
                }
            }
        }

        return $bankDocumentProofAcknowledged;
    }

    private function isBankDetailsNCField(string $fieldName)
    {
        $bankDetailsField = false;

        if(in_array($fieldName, DetailConstant::BANK_DETAIL_FIELDS) === true)
        {
            $bankDetailsField = true;
        }

        return $bankDetailsField;
    }

    private function isNCFieldAcknowledged(array $clarificationDetails): bool
    {
        $acknowledged = false;

        if((array_key_exists(Constants::ACKNOWLEDGED, $clarificationDetails) && $clarificationDetails[Constants::ACKNOWLEDGED] === true) ||
           $clarificationDetails[MerchantConstant::REASON_FROM] === MerchantConstant::MERCHANT)
        {
            $acknowledged = true;
        }

        return $acknowledged;
    }

    private function getLatestKycClarificationReasons(DetailEntity $merchantDetails): array
    {
        $kycClarificationReasons = $merchantDetails->getKycClarificationReasons();

        $latestReasons = [];

        if (empty($kycClarificationReasons) === true)
        {
            return $latestReasons;
        }

        $clarificationReasons = $kycClarificationReasons[DetailEntity::CLARIFICATION_REASONS] ?? [];

        foreach ($clarificationReasons as $field => $clarificationDetails)
        {
            foreach ($clarificationDetails as $clarification)
            {
                if ($clarification[Merchant\Constants::IS_CURRENT] === true)
                {
                    $latestReasons[$field] = $clarification;
                }
            }
        }

        $additionalDetails = $kycClarificationReasons[DetailEntity::ADDITIONAL_DETAILS] ?? [];

        if(empty($additionalDetails) === false)
        {
            foreach ($additionalDetails as $field => $clarificationDetails)
            {
                if($this->merchant->isLinkedAccount() === true)
                {
                    // For Linked Accounts, the needs_clarification is system driven and does not involve BizOps
                    // Hence even when NC counf is > 1 we populate the requirements.
                    // Check for active clarification details in the additional details array and populate them.
                    foreach ($clarificationDetails as $clarification)
                    {
                        if ($clarification[Merchant\Constants::IS_CURRENT] === true)
                        {
                            $latestReasons[$field] = $clarification;
                        }
                    }
                }
                else
                {
                    $latestReasons[$field] = $clarificationDetails[0];
                }
            }
        }

        return $latestReasons;
    }

    public function updateNCFieldAcknowledged(string $field, DetailEntity $merchantDetails, bool $checkNoDocReasonCode = false): bool
    {
        $kycClarificationReasons = $merchantDetails->getKycClarificationReasons();

        if(empty($kycClarificationReasons) === true)
        {
            return false;
        }

        $clarificationReasons = $kycClarificationReasons[DetailEntity::CLARIFICATION_REASONS] ?? [];
        $additionalDetails    = $kycClarificationReasons[DetailEntity::ADDITIONAL_DETAILS] ?? [];

        $fieldNCReasons = [];

        $reasons = $clarificationReasons;
        $updateKey = DetailEntity::CLARIFICATION_REASONS;

        if (array_key_exists($field, $clarificationReasons) === true)
        {
            $fieldNCReasons = $clarificationReasons[$field];
        }
        if (array_key_exists($field, $additionalDetails) === true)
        {
            $updateKey = DetailEntity::ADDITIONAL_DETAILS;
            $reasons = $additionalDetails;
            $fieldNCReasons = $additionalDetails[$field];
        }

        if(empty($fieldNCReasons) === true)
        {
            return false;
        }

        $latestReasonIndex = count($fieldNCReasons) - 1;

        if ($checkNoDocReasonCode === true)
        {
            $reasonCode = $reasons[$field][$latestReasonIndex][Constants::REASON_CODE] ?? null;

            $alreadyAcknowledged = $reasons[$field][$latestReasonIndex][Constants::ACKNOWLEDGED] ?? null;

            if ($reasonCode !== NeedsClarificationReasonsList::NO_DOC_LIMIT_BREACH or $alreadyAcknowledged === true)
            {
                return false;
            }
        }

        $reasons[$field][$latestReasonIndex][Constants::ACKNOWLEDGED] = true;

        $kycClarificationReasons[$updateKey] = $reasons;

        $merchantDetails->setKycClarificationReasons($kycClarificationReasons);

        $this->repo->merchant_detail->saveOrFail($merchantDetails);

        $tracePayload = [
            'merchant_id'       => $merchantDetails->getMerchantId(),
            'field'             => $field,
            'updatedKycReasons' => $reasons[$field][$latestReasonIndex]
        ];

        $this->trace->info(TraceCode::MERCHANT_ACKNOWLEDGED_NC_FIELD, $tracePayload);

        return true;
    }

    /**
     * This function compose NC Based on Reason code and update activation status
     * @param Merchant\Entity $merchant
     * @param DetailEntity    $merchantDetail
     * @param string          $reasonCode
     *
     * @throws Throwable
     */
    public function updateActivationStatusForNoDoc(Merchant\Entity $merchant, DetailEntity $merchantDetail, string $reasonCode)
    {
        try
        {
            $this->trace->info(TraceCode::NO_DOC_UPDATE_ACTIVATION_STATUS_AFTER_VERIFICATION_FAILS, ['merchant_id' => $merchant->getId(), 'reason_code' => $reasonCode]);

            $merchantId = $merchantDetail->getMerchantId();

            $kycClarificationReasons = $this->composeNeedsClarificationForNoDoc($merchant, $reasonCode);

            $updatedKycClarificationReasons = (new MerchantDetailCore())->getUpdatedKycClarificationReasons($kycClarificationReasons, $merchantId, DetailConstant::SYSTEM);

            if (empty($updatedKycClarificationReasons) === false)
            {
                $merchantDetail->setKycClarificationReasons($updatedKycClarificationReasons);
            }

            if ($kycClarificationReasons[DetailEntity::KYC_CLARIFICATION_REASONS][DetailEntity::CLARIFICATION_REASONS] != null)
            {
                $activationStatusData = [
                    DetailEntity::ACTIVATION_STATUS => Status::NEEDS_CLARIFICATION
                ];
            }
            else
            {
                $activationStatusData = [
                    DetailEntity::ACTIVATION_STATUS => Status::UNDER_REVIEW
                ];
            }

            (new MerchantDetailCore())->updateActivationStatus($merchant, $activationStatusData, $merchant);

            if ($merchantDetail->getActivationStatus() !== $activationStatusData[DetailEntity::ACTIVATION_STATUS])
            {
                throw new LogicException('activation status not changed to ' . $activationStatusData[DetailEntity::ACTIVATION_STATUS] . ' with reason code '.$reasonCode. ' for merchant with id ' . $merchantId);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::FAILED_TO_UPDATE_ACTIVATION_STATUS_AFTER_VERFICATION_FAILS, ['merchant_id' => $merchant->getId(), 'reason_code' => $reasonCode]);
        }
    }

    /**
     * Compose NC after limit breach for no doc
     * @param Merchant\Entity $merchant
     * @return array[]
     */
    public function composeNeedsClarificationForNoDocLimitBreach(Merchant\Entity $merchant): array
    {
        return $this->composeNeedsClarificationForNoDoc($merchant, NeedsClarificationReasonsList::NO_DOC_LIMIT_BREACH);

    }

    /**
     * Compose NC for No doc after verification and dedupe failure after retry
     * @param Merchant\Entity $merchant
     * @param string          $reasonCode
     *
     * @return array[]
     */
    public function composeNeedsClarificationForNoDoc(Merchant\Entity $merchant, string $reasonCode): array
    {
        $verificationResponse = (new MerchantDetailCore())->setVerificationDetails($merchant->merchantDetail, $merchant, [], true);

        $requiredFields          = $verificationResponse['verification']['required_fields'] ?? [];
        $kycClarificationReasons = [];
        $clarificationReasons    = [];

        $baseReasons = [
            Constants::REASON_TYPE => MerchantConstant::PREDEFINED_REASON_TYPE,
            Constants::REASON_CODE => $reasonCode,
            Constants::FIELD_VALUE => null
        ];

        foreach ($requiredFields as $requiredField)
        {
            $clarificationReasons[$requiredField] = [$baseReasons];
        }
        $kycClarificationReasons[DetailEntity::CLARIFICATION_REASONS] = $clarificationReasons;

        return [
            DetailEntity::KYC_CLARIFICATION_REASONS => $kycClarificationReasons
        ];
    }

    public function updateNCFieldsAcknowledgedIfApplicableForNoDoc(Merchant\Entity $merchant, DetailEntity $merchantDetail)
    {
        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = (new MerchantDetailCore())->getValidationFields($merchantDetail, true);

        $documentsResponse = Tracer::inSpan(['name' => 'fetch_document_response'], function() use($merchant) {
            return (new Merchant\Document\Core())->documentResponse($merchant);
        });

        foreach ($validationSelectiveRequiredFields as $requiredDocumentField => $documentGroups)
        {
            $isFieldPresent = array_reduce($documentGroups, function($isFieldPresent, $documentGroup) use ($documentsResponse)
            {
                $isDocumentGroupFilled = count(array_diff($documentGroup, array_keys($documentsResponse))) === 0;

                $isFieldPresent = ($isFieldPresent or $isDocumentGroupFilled);

                return $isFieldPresent;

            }, false);

            if ($isFieldPresent === true)
            {
                foreach ($documentGroups as $documentGroup)
                {
                    foreach ($documentGroup as $groupField)
                    {
                        $this->updateNCFieldAcknowledged($groupField, $merchantDetail, true);
                    }
                }
            }
        }
    }
}
