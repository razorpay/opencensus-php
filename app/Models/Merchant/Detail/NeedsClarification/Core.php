<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification;

use RZP\Models\Base;
use RZP\Models\Merchant\Constants as MerchantConstant;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Type as DocumentType;
use RZP\Models\Merchant\Detail\NeedsClarificationMetaData;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList;
use RZP\Models\Merchant\Detail\ActivationFields as ActivationFields;

/**
 * This class contains logic specific to kyc clarification
 *
 * @package RZP\Models\Merchant\Detail\NeedClarification
 */
class Core extends Base\Core
{

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
                array_merge_recursive($currentClarificationReason,
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

        $requirements = $this->getFormattedOutput($clarificationReasons, $requirements);

        $requirements = $this->getFormattedOutput($additionalDetails, $requirements);

        return $requirements;
    }

    /**
     * @param $clarificationReasons
     * @param $requirements
     *
     * @return mixed
     */
    private function getFormattedOutput(array $clarificationReasons, array $requirements)
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
                    $requirement[Constants::REASON_DESCRIPTION] = $reason[MerchantConstant::REASON];
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
