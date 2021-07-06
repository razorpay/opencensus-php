<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class GstinAuth extends Base
{
    public function canTriggerValidation(): bool
    {
        return ($this->merchantDetails->getGstinVerificationStatus() === BvsValidationConstants::PENDING);
    }

    public function getRequestPayload(): array
    {
        return [
            Constant::ARTEFACT_TYPE   => Constant::GSTIN,
            Constant::CONFIG_NAME     => Constant::GSTIN,
            Constant::VALIDATION_UNIT => BvsValidationConstants::IDENTIFIER,
            Constant::DETAILS         => [
                Constant::GSTIN      => $this->merchantDetails->getGstin(),
                Constant::LEGAL_NAME => $this->merchantDetails->getPromoterPanName() ?? '',
                Constant::TRADE_NAME => $this->merchantDetails->getBusinessName() ?? ''
            ],
        ];
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        $this->merchantDetails->setGstinVerificationStatus(BvsValidationConstants::INITIATED);
    }
}
