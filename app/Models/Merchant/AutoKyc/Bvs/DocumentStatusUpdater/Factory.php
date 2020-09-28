<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\BvsValidation\Entity as ValidationEntity;

class Factory
{
    /**
     * Returns StatusUpdater instance for artefact
     *
     * @param MerchantEntity $merchant
     * @param string $artefactType
     *
     * @param string|null $validationId
     *
     * @return StatusUpdater
     * @throws LogicException
     */
    public function getInstance(MerchantEntity $merchant, string $artefactType, ?string $validationId): StatusUpdater
    {
        switch ($artefactType)
        {
            case Constant::CIN:
            case Constant::LLP_DEED:

                return new DefaultStatusUpdater(
                    $merchant,
                    Entity::CIN_VERIFICATION_STATUS,
                    $artefactType);

            case Constant::GSTIN:

                return new DefaultStatusUpdater(
                    $merchant,
                    Entity::GSTIN_VERIFICATION_STATUS,
                    $artefactType);

            case Constant::PERSONAL_PAN :

                return new POI($merchant, $artefactType, $validationId);

            case Constant::AADHAAR :
            case Constant::VOTERS_ID:
            case Constant::PASSPORT:

                return new POA($merchant, $artefactType);

            default :

                throw new LogicException(
                    ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_TYPE,
                    null,
                    [ValidationEntity::ARTEFACT_TYPE => $artefactType]);

        }
    }
}
