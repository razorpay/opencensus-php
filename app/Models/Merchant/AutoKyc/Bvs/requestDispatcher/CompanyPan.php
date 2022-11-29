<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\Entity;
use Rzp\Obs\Verification\V1\Detail;

class CompanyPan extends Base
{
    public function canTriggerValidation(): bool
    {
        $businessTypeValue = $this->merchantDetails->getBusinessTypeValue();

        $isNoDocOnboarding = $this->merchant->isNoDocOnboardingEnabled();

        $isRouteNoDocKycEnabled = $this->merchant->isRouteNoDocKycEnabledForParentMerchant();

        if ($isNoDocOnboarding === true and $this->isDedupeCheckForNoDocOnboardingPass(Entity::COMPANY_PAN) === false)
        {
            return false;
        }

        //We trigger company pan BVS request for all business types if No Doc Onboarding feature is enabled.
        return (($this->merchantDetails->getCompanyPanVerificationStatus() === BvsValidationConstants::PENDING) and
                (BusinessType::isCompanyPanEnableBusinessTypes($businessTypeValue) === true or
                 $isNoDocOnboarding === true or $isRouteNoDocKycEnabled === true));
    }

    public function getRequestPayload(): array
    {
        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::BUSINESS_PAN,
            Constant::CONFIG_NAME     => Constant::BUSINESS_PAN,
            Constant::VALIDATION_UNIT => BvsValidationConstants::IDENTIFIER,
            Constant::DETAILS         => [
                Constant::PAN_NUMBER => $this->merchantDetails->getPan(),
                Constant::NAME       => $this->merchantDetails->getBusinessName(),
            ],
        ];

        return $payload;
    }

    public function performPostProcessOperation(BvsValidation\Entity $validation): void
    {
        if ($this->merchantDetails->getCompanyPanVerificationStatus() === BvsValidationConstants::PENDING)
        {
            $this->merchantDetails->setCompanyPanVerificationStatus(BvsValidationConstants::INITIATED);
        }
    }
}
