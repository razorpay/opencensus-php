<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\Entity;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\VerificationDetail as MVD;
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

        if (($this->merchant->isLinkedAccount() === true) and
            ($this->merchant->parent->isRouteNoDocKycEnabled() === true))
        {
            switch($this->merchantDetails->getBusinessType())
            {
                case BusinessType::NOT_YET_REGISTERED:
                case BusinessType::INDIVIDUAL:
                case BusinessType::PROPRIETORSHIP:
                {
                    return Constant::GSTIN;
                }

                default:
                {
                    return Constant::GSTIN_WITH_BUSINESS_PAN_FOR_NO_DOC;
                }
            }
        }

        return Constant::GSTIN;
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        if ($this->merchantDetails->getGstinVerificationStatus() === BvsValidationConstants::PENDING)
        {
            $this->merchantDetails->setGstinVerificationStatus(BvsValidationConstants::INITIATED);

            $input = [
                MVD\Entity::MERCHANT_ID         => $this->merchant->getId(),
                MVD\Entity::ARTEFACT_TYPE       => Constant::GSTIN,
                MVD\Entity::ARTEFACT_IDENTIFIER => MVD\Constants::NUMBER,
                MVD\Entity::STATUS              => BvsValidationConstants::INITIATED,
                MVD\Entity::METADATA            => [
                                                    'signatory_validation_status'=> BvsValidationConstants::INITIATED,
                                                    'bvs_validation_id' => $entity->getValidationId()]
            ];

            (new MVD\Core)->createOrEditVerificationDetail($this->merchantDetails, $input);

        }
    }
}
