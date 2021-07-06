<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Detail\ShopEstablishmentAreaCodeMapping;

class ShopEstablishmentAuth extends Base
{
    protected $areaCode;

    public function canTriggerValidation(): bool
    {
        $this->areaCode = (new ShopEstablishmentAreaCodeMapping())->getAreaCode(
            $this->merchantDetails->getBusinessRegisteredCity() ?? '',
            $this->merchantDetails->getBusinessRegisteredState() ?? ''
        );

        return (
            ($this->merchantDetails->getShopEstbVerificationStatus() === BvsValidationConstants::PENDING) and
            (empty($this->merchantDetails->getShopEstbNumber()) === false) and
            (empty($this->areaCode) === false));
    }

    public function getRequestPayload(): array
    {
        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::SHOP_ESTABLISHMENT,
            Constant::CONFIG_NAME     => Constant::SHOP_ESTABLISHMENT_AUTH,
            Constant::VALIDATION_UNIT => BvsValidationConstants::IDENTIFIER,
            Constant::DETAILS         => [
                Constant::SHOP_REGISTRATION_NUMBER => $this->merchantDetails->getShopEstbNumber(),
                Constant::SHOP_AREA_CODE           => $this->areaCode,
                Constant::SHOP_ENTITY_NAME         => $this->merchantDetails->getBusinessName(),
                Constant::SHOP_OWNER_NAME          => $this->merchantDetails->getPromoterPanName(),
            ],
        ];

        return $payload;
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        $this->merchantDetails->setShopEstbVerificationStatus(BvsValidationConstants::INITIATED);
    }
}
