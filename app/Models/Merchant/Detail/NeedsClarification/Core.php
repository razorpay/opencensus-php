<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification;

use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Document;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Constants as MerchantConstant;
use RZP\Models\Merchant\Detail\SelectiveRequiredFields;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Type as DocumentType;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;
use RZP\Models\Merchant\Detail\NeedsClarificationMetaData;
use RZP\Models\Merchant\Detail\Constants as DetailConstant;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList;
use RZP\Models\Merchant\Detail\ActivationFields as ActivationFields;
use RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer\Factory;

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
        $statusChangeLogs = ($entity->getEntityName() === E::PARTNER_ACTIVATION) ? $entity->getActivationStatusChangeLog() :
                                                    (new MerchantCore)->getActivationStatusChangeLog($entity->merchant);

        $needsClarificationCount = (new MerchantDetailCore())->getStatusChangeCount($statusChangeLogs, Status::NEEDS_CLARIFICATION);

        /*
           If we have already raised needs clarification flow once then don't raise it again
        */
        if ($needsClarificationCount >= 1)
        {
            return false;
        }

        /*
           If all statuses are verified then don't trigger needs clarification request
        */
        return (new UpdateContextRequirements())->shouldTriggerNeedsClarification($entity);
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

        foreach ($clarificationKeys as $clarificationKey)
        {
            $clarificationMetadata = NeedsClarificationMetaData::SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA[$clarificationKey] ?? [];

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
                array_key_exists(DocumentType::CANCELLED_CHEQUE, $nonAcknowledgedNCFields[Constants::DOCUMENTS]) === false) {
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
                $latestReasons[$field] = $clarificationDetails[0];
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

    public function composeNeedsClarificationForNoDocLimitBreach(Merchant\Entity $merchant): array
    {
        $verificationResponse = (new MerchantDetailCore())->setVerificationDetails($merchant->merchantDetail, $merchant, [], true);

        $requiredFields = $verificationResponse['verification']['required_fields'] ?? [];

        $kycClarificationReasons = [];

        $clarificationReasons = [];

        $baseReasons = [
            Constants::REASON_TYPE => MerchantConstant::PREDEFINED_REASON_TYPE,
            Constants::REASON_CODE => NeedsClarificationReasonsList::NO_DOC_LIMIT_BREACH,
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
