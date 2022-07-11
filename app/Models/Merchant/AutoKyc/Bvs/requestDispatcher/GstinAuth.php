<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\Entity;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class GstinAuth extends Base
{

    public function __construct(Entity $merchant, DetailEntity $merchantDetails)
    {
        parent::__construct($merchant, $merchantDetails);
    }

    public function canTriggerValidation(): bool
    {
        return ($this->merchantDetails->getGstinVerificationStatus() === BvsValidationConstants::PENDING);
    }

    public function getRequestPayload(): array
    {
        $requestPayload = [
            Constant::ARTEFACT_TYPE   => Constant::GSTIN,
            Constant::CONFIG_NAME     => $this->getConfigName(),
            Constant::VALIDATION_UNIT => BvsValidationConstants::IDENTIFIER,
            Constant::DETAILS         => [
                Constant::GSTIN      => $this->merchantDetails->getGstin(),
                Constant::LEGAL_NAME => $this->merchantDetails->getPromoterPanName() ?? '',
                Constant::TRADE_NAME => $this->merchantDetails->getBusinessName() ?? ''
            ],
        ];

        if ($this->merchant->isNoDocOnboardingEnabled() === true)
        {
            $requestPayload[Constant::COMPLIANCE_STATUS] = [
                Constant::IS_ANY_DELAY => false,
                Constant::IS_DEFAULTER => false,
            ];
        }

        return $requestPayload;
    }

    public function getConfigName()
    {
        if ($this->merchant->isNoDocOnboardingEnabled() === true)
        {
            switch($this->merchantDetails->getBusinessType())
            {
                case BusinessType::PROPRIETORSHIP:
                case BusinessType::UNREGISTERED:
                    return Constant::GSTIN;

                default :
                    return Constant::GSTIN_WITH_BUSINESS_PAN_FOR_NO_DOC;
            }
        }
        return Constant::GSTIN;
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        if ($this->merchantDetails->getGstinVerificationStatus() === BvsValidationConstants::PENDING)
        {
            $this->merchantDetails->setGstinVerificationStatus(BvsValidationConstants::INITIATED);
        }
    }
}
