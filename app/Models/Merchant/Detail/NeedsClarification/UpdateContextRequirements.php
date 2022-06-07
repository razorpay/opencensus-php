<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification;


use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Store;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Partner\Activation\Entity as PAEntity;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Partner\Activation\Constants as PAConstants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater\GstInStatusUpdater;


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

    const UPDATE_NO_DOC_MERCHANT_CONTEXT_REQUIREMENTS = [
        self::default => [
            [self::COMPANY_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
        ],
        BusinessType::PROPRIETORSHIP      => [
            [self::PERSONAL_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
        ],
        BusinessType::NOT_YET_REGISTERED  => [
            [self::PERSONAL_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
        ],
    ];

    const UPDATE_MERCHANT_CONTEXT_REQUIREMENTS = [
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

    const UPDATE_PARTNER_CONTEXT_REQUIREMENTS = [
        self::default => [
            [self::COMPANY_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
            [self::GSTIN_VERIFICATION]
        ],
        BusinessType::NOT_YET_REGISTERED => [
            [self::PERSONAL_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
        ],
        BusinessType::PROPRIETORSHIP => [
            [self::PERSONAL_PAN_VERIFICATION],
            [self::BANK_DETAILS_VERIFICATION],
            [self::GSTIN_VERIFICATION]
        ]
    ];

    /**
     * Checks merchant context can be updated or not
     *
     * @param Entity $merchantDetails
     * @return bool
     */
    public function canUpdateMerchantContext(Entity $merchantDetails): bool
    {
        $requirementList = $this->getUpdateContextRequirement($merchantDetails);

        if ($merchantDetails->isSubmitted() === false or $merchantDetails->getActivationFlow() === ActivationFlow::BLACKLIST)
        {
            return false;
        }
        else if ($merchantDetails->merchant->isNoDocOnboardingEnabled() === true)
        {
            $isNoDocGstValidationCompleted = $this->isNoDocGstValidationCompleted($merchantDetails);

            if ($isNoDocGstValidationCompleted === false)
            {
                return false;
            }
        }

        $canUpdateMerchantContext = true;

        foreach ($requirementList as $requirementGroup)
        {
            $canUpdateMerchantContext = (($canUpdateMerchantContext === true) and
                                         (self::isRequirementFullFilled($merchantDetails, $requirementGroup) === true));
        }

        return $canUpdateMerchantContext;
    }

    /**
     * Checks partner context can be updated or not
     *
     * @param PAEntity $partnerActivation
     * @return bool
     */
    public function canUpdatePartnerContext(PAEntity $partnerActivation): bool
    {
        $requirementList = $this->getUpdateContextRequirement($partnerActivation);

        $activationStatus = $partnerActivation->getActivationStatus();

        if ((empty($activationStatus)  === true) or ($activationStatus === PAConstants::ACTIVATED))
        {
            return false;
        }

        $canUpdatePartnerContext = true;

        $merchantDetail = $partnerActivation->merchantDetail;

        foreach ($requirementList as $requirementGroup)
        {
            $canUpdatePartnerContext = (($canUpdatePartnerContext === true) and
                (self::isRequirementFullFilled($merchantDetail, $requirementGroup) === true));
        }

        return $canUpdatePartnerContext;
    }

    /**
     * @param PublicEntity $entity
     * @return array
     */

    public function getClarificationKeys(PublicEntity $entity): array
    {
        $requirementList = $this->getUpdateContextRequirement($entity);

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
     * Checks if requirement is full filled or not for a given entity (merchant/partner)
     *
     * @param PublicEntity $entity
     *
     * @return bool
     */
    public function shouldTriggerNeedsClarification(PublicEntity $entity): bool
    {
        $requirementList = $this->getUpdateContextRequirement($entity);

        $shouldTriggerNeedsClarification = false;

        $merchantDetails = ($entity->getEntityName() === E::PARTNER_ACTIVATION) ? $entity->merchantDetail : $entity;

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
     * Returns needs clarification requirements for a given entity (merchant/partner)
     *
     * @param PublicEntity $entity
     *
     * @return \array[][][][]
     */
    public function getUpdateContextRequirement(PublicEntity $entity): array
    {
        switch ($entity->getEntityName())
        {
            case E::PARTNER_ACTIVATION:
                $updateContextRequirements = self::UPDATE_PARTNER_CONTEXT_REQUIREMENTS;
                $type = ($entity->merchantDetail)->getBusinessType();
                break;

            case E::MERCHANT_DETAIL:
                if ($entity->merchant->isNoDocOnboardingEnabled() === true)
                {
                    return $this->getNoDocUpdateContextRequirement($entity);
                }

                $updateContextRequirements = self::UPDATE_MERCHANT_CONTEXT_REQUIREMENTS;
                $type = $entity->getBusinessType();
                break;

            default:
                return [];
        }

        $requirementList = $updateContextRequirements[self::default];

        if (isset($updateContextRequirements[$type]) === true)
        {
            $requirementList = $updateContextRequirements[$type];
        }

        return $requirementList;
    }

    public function getNoDocUpdateContextRequirement(Entity $merchantDetails): array
    {
        $updateContextRequirements = self::UPDATE_NO_DOC_MERCHANT_CONTEXT_REQUIREMENTS;

        $businessType = $merchantDetails->getBusinessType();

        $requirementList = $updateContextRequirements[self::default];

        if (isset($updateContextRequirements[$businessType]) === true)
        {
            $requirementList = $updateContextRequirements[$businessType];
        }

        if (in_array($businessType, [BusinessType::PROPRIETORSHIP, BusinessType::NOT_YET_REGISTERED], true) === true)
        {
            $companyPanStatus = $merchantDetails->getCompanyPanVerificationStatus();

            $personalPanStatus = $merchantDetails->getPoiVerificationStatus();

            $failedStatus = [BvsValidationConstants::NOT_MATCHED, BvsValidationConstants::INCORRECT_DETAILS, BvsValidationConstants::FAILED];

            // if company is verified then put company pan in requirements
            if ($companyPanStatus === DEConstants::VERIFIED)
            {
                array_push($requirementList, [self::COMPANY_PAN_VERIFICATION]);

                // if personal pan verification failed then remove it under requirements since we want to activate
                // proprietorship and unregistered business types if one of company or personal pan is verified during
                // no-doc onboarding
                if (in_array($personalPanStatus, $failedStatus, true) === true)
                {
                    $requirementList = $updateContextRequirements[self::default];
                }
            }
        }

        $isGstValidationCompleted = $this->isNoDocGstValidationCompleted($merchantDetails);

        $isGstStatusInTerminalState = $this->isArtifactStatusInTerminalState($merchantDetails->getGstinVerificationStatus());

        // add GST under requirements if it is verified or all GSTs fetched from personal/company pan have failed
        if ($isGstValidationCompleted === true and $isGstStatusInTerminalState === true)
        {
            array_push($requirementList, [self::GSTIN_VERIFICATION]);
        }

        return $requirementList;
    }

    public function isArtifactStatusInTerminalState(?string $kycArtifactStatus): bool
    {
        if (in_array($kycArtifactStatus, self::REQUIRED_VERIFICATION_STATUSES, true) === true)
        {
            return true;
        }

        return false;
    }

    public function isNoDocGstValidationCompleted(Entity $merchantDetails): bool
    {
        $data = (new StoreCore)->fetchValuesFromStore($merchantDetails->getMerchantId(),ConfigKey::ONBOARDING_NAMESPACE,
            [ConfigKey::NO_DOC_ONBOARDING_INFO],StoreConstants::INTERNAL);

        $noDocData = $data[ConfigKey::NO_DOC_ONBOARDING_INFO];

        $isPanValidationDone = $this->isNoDocPanValidationCompleted($merchantDetails);

        $noDocGsts = $noDocData['gst'] ?? [];

        $currentGstIndex = $noDocData['current_index'] ?? 0;

        $gstVerificationStatus = $merchantDetails->getGstinVerificationStatus();

        $isGstStatusInTerminalState = (new UpdateContextRequirements())->isArtifactStatusInTerminalState($gstVerificationStatus);

        if (($isPanValidationDone === true and count($noDocGsts) === 0) or
            (($currentGstIndex + 1 === count($noDocGsts)) and ($isGstStatusInTerminalState === true)) or
            ($gstVerificationStatus === DEConstants::VERIFIED))
        {
            return true;
        }

        return false;
    }

    public function isNoDocPanValidationCompleted(Entity $merchantDetails): bool
    {
        $isPersonalPanStatusInTerminalState = $this->isArtifactStatusInTerminalState($merchantDetails->getPoiVerificationStatus());

        $isCompanyPanStatusInTerminalState = $this->isArtifactStatusInTerminalState($merchantDetails->getCompanyPanVerificationStatus());

        switch ($merchantDetails->getBusinessType())
        {
            case BusinessType::PROPRIETORSHIP:
            case BusinessType::NOT_YET_REGISTERED:
                if (($merchantDetails->getPan() === null and $isPersonalPanStatusInTerminalState === true) or
                    ($merchantDetails->getPromoterPan() === null and $isCompanyPanStatusInTerminalState === true) or
                    ($isPersonalPanStatusInTerminalState === true and $isCompanyPanStatusInTerminalState === true))
                {
                    return true;
                }

                return false;

            default:
                return ($isCompanyPanStatusInTerminalState === true);
        }
    }
}
