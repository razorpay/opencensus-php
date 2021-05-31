<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class CompanyPan extends Base
{
    public function canTriggerValidation(): bool
    {
        $businessTypeValue = $this->merchantDetails->getBusinessTypeValue();

        return (($this->merchantDetails->getCompanyPanVerificationStatus() === BvsValidationConstants::PENDING) and
                BusinessType::isCompanyPanEnableBusinessTypes($businessTypeValue) === true);
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

    public function performPostProcessOperation(): void
    {
        $this->merchantDetails->setCompanyPanVerificationStatus(BvsValidationConstants::INITIATED);
    }
}