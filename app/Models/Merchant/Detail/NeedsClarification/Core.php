<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification;

use RZP\Models\Base;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Constants as MerchantConstant;
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
     * @param DetailEntity $merchantDetails
     *
     * @return bool
     */
    public function shouldTriggerNeedsClarification(DetailEntity $merchantDetails): bool
    {
        $statusChangeLogs = (new MerchantCore)->getActivationStatusChangeLog($merchantDetails->merchant);

        $needsClarificationCount = (new MerchantDetailCore())->getStatusChangeCount($statusChangeLogs, Status::NEEDS_CLARIFICATION);

        //
        // If we have already raised needs clarification flow once then don't raise it again
        //
        if ($needsClarificationCount >= 1)
        {
            return false;
        }

        //
        // If all statuses are verified then don't trigger needs clarification request
        //
        return (new UpdateContextRequirements())->shouldTriggerNeedsClarification($merchantDetails);
    }

    /**
     * @param DetailEntity $merchantDetail
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     */
    public function composeNeedsClarificationReason(DetailEntity $merchantDetail): array
    {
        $clarificationKeys = (new UpdateContextRequirements())->getClarificationKeys($merchantDetail);

        $kycClarificationReasons = [];

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

            $reason = $factory->getClarificationReasonComposer($clarificationMetadata)
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
}
