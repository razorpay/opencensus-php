<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\Detail\Core;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

use RZP\Models\Merchant\BvsValidation;
class CinAuth extends Base
{
    public function canTriggerValidation(): bool
    {
        $businessType = $this->merchantDetails->getBusinessType();

        return (($this->merchantDetails->getCinVerificationStatus() === BvsValidationConstants::PENDING) and
                (new Core())->isLLPBusinessType($businessType) === false);
    }

    public function getRequestPayload(): array
    {
        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::CIN,
            Constant::CONFIG_NAME     => Constant::CIN,
            Constant::VALIDATION_UNIT => BvsValidationConstants::IDENTIFIER,
            Constant::DETAILS         => [
                Constant::SIGNATORY_DETAILS =>
                    [[
                         Constant::FULL_NAME => $this->merchantDetails->getPromoterPanName() ?? '',
                     ]],
                Constant::COMPANY_NAME      => $this->merchantDetails->getBusinessName() ?? '',
                Constant::CIN               => $this->merchantDetails->getCompanyCin(),
            ],
        ];

        return $payload;
    }

    public function performPostProcessOperation(BvsValidation\Entity $validation): void
    {
        if ($this->merchantDetails->getCinVerificationStatus() === BvsValidationConstants::PENDING)
        {
            $this->merchantDetails->setCinVerificationStatus(BvsValidationConstants::INITIATED);

            $input = [
                MVD\Entity::MERCHANT_ID         => $this->merchant->getId(),
                MVD\Entity::ARTEFACT_TYPE       => Constant::CIN,
                MVD\Entity::ARTEFACT_IDENTIFIER => MVD\Constants::NUMBER,
                MVD\Entity::STATUS              => BvsValidationConstants::INITIATED,
                MVD\Entity::METADATA            => [
                                                    'signatory_validation_status'=> BvsValidationConstants::INITIATED,
                                                    'bvs_validation_id' => $validation->getValidationId()]
            ];

            (new MVD\Core)->createOrEditVerificationDetail($this->merchantDetails, $input);
        }
    }
}
