<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class PersonalPan extends Base
{
    public function canTriggerValidation(): bool
    {
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

    public function performPostProcessOperation(): void
    {
        $this->merchantDetails->setPoiVerificationStatus(BvsValidationConstants::INITIATED);
    }
}
