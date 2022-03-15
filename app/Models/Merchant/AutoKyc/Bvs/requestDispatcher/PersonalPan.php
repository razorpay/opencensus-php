<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;


class PersonalPan extends Base
{
    public function canTriggerValidation(): bool
    {
        if ($this->merchant->isNoDocOnboardingEnabled()  === true)
        {
            switch ($this->merchantDetails->getBusinessType())
            {
                case BusinessType::NOT_YET_REGISTERED:
                case BusinessType::PROPRIETORSHIP:
                    return $this->merchantDetails->getPoiVerificationStatus() === BvsValidationConstants::PENDING;

                default:
                    return false;
            }
        }
        return $this->merchantDetails->getPoiVerificationStatus() === BvsValidationConstants::PENDING;
    }

    public function getRequestPayload(): array
    {
        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::PERSONAL_PAN,
            Constant::CONFIG_NAME     => Constant::PERSONAL_PAN,
            Constant::VALIDATION_UNIT => BvsValidationConstants::IDENTIFIER,
            Constant::DETAILS         => [
                Constant::PAN_NUMBER => $this->merchantDetails->getPromoterPan(),
                Constant::NAME       => $this->merchantDetails->getPromoterPanName(),
            ],
        ];

        return $payload;
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        if ($this->merchantDetails->getPoiVerificationStatus() === BvsValidationConstants::PENDING)
        {
            $this->merchantDetails->setPoiVerificationStatus(BvsValidationConstants::INITIATED);
        }
    }
}
