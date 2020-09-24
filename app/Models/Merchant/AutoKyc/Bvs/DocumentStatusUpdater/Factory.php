<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity as ValidationEntity;

class Factory
{
    /**
     * Returns StatusUpdater instance for artefact
     *
     * @param Entity $merchantDetails
     * @param string $artefactType
     *
     * @param string $validationId
     *
     * @return StatusUpdater
     * @throws LogicException
     */
    public function getInstance(Entity $merchantDetails, string $artefactType, ?string $validationId): StatusUpdater
    {
        switch ($artefactType)
        {
            case Constant::CIN:
            case Constant::LLPIN:

                return new DefaultStatusUpdater(
                    $merchantDetails,
                    Entity::CIN_VERIFICATION_STATUS,
                    $artefactType);

            case Constant::GSTIN:

                return new DefaultStatusUpdater(
                    $merchantDetails,
                    Entity::GSTIN_VERIFICATION_STATUS,
                    $artefactType);

            case Constant::PERSONAL_PAN :

                return new POI($merchantDetails, $artefactType, $validationId);

            case Constant::AADHAAR :
            case Constant::VOTER_ID:
            case Constant::PASSPORT:

                return new POA($merchantDetails, $artefactType);

            default :

                throw new LogicException(
                    ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_TYPE,
                    null,
                    [ValidationEntity::ARTEFACT_TYPE => $artefactType]);

        }
    }
}
