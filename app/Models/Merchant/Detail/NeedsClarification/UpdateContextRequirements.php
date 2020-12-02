<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification;

use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class UpdateContextRequirements
{
    const default                     = 'default';
    const CLARIFICATION_REFERENCE_KEY = 'clarification_reference_key';
    const STATUS                      = 'status';
    const STATUS_KEY                  = 'status_key';

    const REQUIRED_VERIFICATION_STATUSES = [
        BvsValidationConstants::VERIFIED, BvsValidationConstants::INCORRECT_DETAILS, BvsValidationConstants::NOT_MATCHED, BvsValidationConstants::FAILED
    ];

    const BANK_DETAILS_VERIFICATION = [
        self::CLARIFICATION_REFERENCE_KEY => Constants::BANK_ACCOUNT_NUMBER,
        self::STATUS                      => self::REQUIRED_VERIFICATION_STATUSES,
        self::STATUS_KEY                  => Entity::BANK_DETAILS_VERIFICATION_STATUS,
    ];

    const PERSONAL_PAN_VERIFICATION = [
        self::CLARIFICATION_REFERENCE_KEY => Constants::PERSONAL_PAN_IDENTIFIER,
        self::STATUS                      => self::REQUIRED_VERIFICATION_STATUSES,
        self::STATUS_KEY                  => Entity::POI_VERIFICATION_STATUS,
    ];

    const POA_VERIFICATION = [
        self::CLARIFICATION_REFERENCE_KEY => Constants::POA_DOC,
        self::STATUS                      => self::REQUIRED_VERIFICATION_STATUSES,
        self::STATUS_KEY                  => Entity::POA_VERIFICATION_STATUS,
    ];

    const COMPANY_PAN_VERIFICATION = [
        self::CLARIFICATION_REFERENCE_KEY => Constants::COMPANY_PAN_IDENTIFIER,
        self::STATUS                      => self::REQUIRED_VERIFICATION_STATUSES,
        self::STATUS_KEY                  => Entity::COMPANY_PAN_VERIFICATION_STATUS,
    ];

    const CIN_VERIFICATION = [
        self::CLARIFICATION_REFERENCE_KEY => Constants::CIN_IDENTIFER,
        self::STATUS                      => self::REQUIRED_VERIFICATION_STATUSES,
        self::STATUS_KEY                  => Entity::CIN_VERIFICATION_STATUS,
    ];

    const LLPIN_VERIFICATION = [
        self::CLARIFICATION_REFERENCE_KEY => Constants::LLPIN_IDENTIFIER,
        self::STATUS                      => self::REQUIRED_VERIFICATION_STATUSES,
        self::STATUS_KEY                  => Entity::CIN_VERIFICATION_STATUS,
    ];

    const GSTIN_VERIFICATION = [
        self::CLARIFICATION_REFERENCE_KEY => Constants::GSTIN_IDENTIFER,
        self::STATUS                      => self::REQUIRED_VERIFICATION_STATUSES,
        self::STATUS_KEY                  => Entity::GSTIN_VERIFICATION_STATUS,
    ];

    const SHOP_ESTABLISHMENT_VERIFICATION = [
        self::CLARIFICATION_REFERENCE_KEY => Constants::SHOP_ESTABLISHMENT_IDENTIFIER,
        self::STATUS                      => self::REQUIRED_VERIFICATION_STATUSES,
        self::STATUS_KEY                  => Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS,
    ];

    const UPDATE_CONTEXT_REQUIREMENTS = [
        self::default                 => [
            [self::POA_VERIFICATION],
            [self::PERSONAL_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION]
        ],
        BusinessType::PROPRIETORSHIP  => [
            [self::POA_VERIFICATION],
            [self::PERSONAL_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
            [self::GSTIN_VERIFICATION],
            [self::SHOP_ESTABLISHMENT_VERIFICATION]
        ],
        BusinessType::PRIVATE_LIMITED => [
            [self::POA_VERIFICATION],
            [self::PERSONAL_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
            [self::COMPANY_PAN_VERIFICATION],
            [self::CIN_VERIFICATION],
        ],
        BusinessType::PUBLIC_LIMITED  => [
            [self::POA_VERIFICATION],
            [self::PERSONAL_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
            [self::COMPANY_PAN_VERIFICATION],
            [self::CIN_VERIFICATION],
        ],
        BusinessType::LLP             => [
            [self::POA_VERIFICATION],
            [self::PERSONAL_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
            [self::COMPANY_PAN_VERIFICATION],
            [self::LLPIN_VERIFICATION],
        ],
    ];

    /**
     * Checks merchant context can be updated or not
     *
     * @param Entity $merchantDetails
     *
     * @return bool
     */
    public function canUpdateMerchantContext(Entity $merchantDetails): bool
    {
        $businessType = $merchantDetails->getBusinessType();

        $requirementList = $this->getUpdateContextRequirement($businessType);

        $canUpdateMerchantContext = true;

        foreach ($requirementList as $requirementGroup)
        {
            $canUpdateMerchantContext = (($canUpdateMerchantContext === true) and
                                         (self::isRequirementFullFilled($merchantDetails, $requirementGroup) === true));
        }

        return $canUpdateMerchantContext;
    }

    /**
     * @param Entity $merchantDetails
     *
     * @return array
     */

    public function getClarificationKeys(Entity $merchantDetails): array
    {
        $businessType = $merchantDetails->getBusinessType();

        $requirementList = $this->getUpdateContextRequirement($businessType);

        $fields = [];

        foreach ($requirementList as $requirementGroup)
        {
            foreach ($requirementGroup as $requirements)
            {
                array_push($fields, $requirements[self::CLARIFICATION_REFERENCE_KEY]);
            }
        }

        return array_unique($fields);
    }

    /**
     * Checks if requirement is full filled or not for a merchant
     *
     * @param Entity $merchantDetails
     *
     * @return bool
     */
    public function shouldTriggerNeedsClarification(Entity $merchantDetails): bool
    {
        $businessType = $merchantDetails->getBusinessType();

        $requirementList = $this->getUpdateContextRequirement($businessType);

        $shouldTriggerNeedsClarification = false;

        foreach ($requirementList as $requirementGroup)
        {
            $isRequirementGroupSatisfied = false;

            foreach ($requirementGroup as $requirements)
            {
                $statusKey = $requirements[self::STATUS_KEY];

                $verificationStatus = $merchantDetails->getAttribute($statusKey);

                $isRequirementGroupSatisfied = (($isRequirementGroupSatisfied == true) or
                                                ($verificationStatus === BvsValidationConstants::VERIFIED));
            }
            $shouldTriggerNeedsClarification = (($shouldTriggerNeedsClarification === true) or
                                                ($isRequirementGroupSatisfied === false));
        }

        return $shouldTriggerNeedsClarification;
    }

    /**
     * @param Entity $merchantDetails
     * @param array  $requirementGroup
     *
     * @return bool
     */
    protected function isRequirementFullFilled(Entity $merchantDetails, array $requirementGroup): bool
    {
        foreach ($requirementGroup as $requirements)
        {
            $statusKey = $requirements[self::STATUS_KEY];
            $status    = $requirements[self::STATUS];

            $attributeValue = $merchantDetails->getAttribute($statusKey);

            if (($attributeValue === null) or
                (array_search($attributeValue, $status, true) !== false))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns needs clarification requirements
     *
     * @param string|null $businessType
     *
     * @return \array[][][][]
     */
    protected function getUpdateContextRequirement(string $businessType = null): array
    {
        $requirementList = self::UPDATE_CONTEXT_REQUIREMENTS[self::default];

        if (isset(self::UPDATE_CONTEXT_REQUIREMENTS[$businessType]) === true)
        {
            $requirementList = self::UPDATE_CONTEXT_REQUIREMENTS[$businessType];
        }

        return $requirementList;
    }
}