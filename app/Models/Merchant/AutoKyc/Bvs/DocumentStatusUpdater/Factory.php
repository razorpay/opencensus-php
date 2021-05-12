<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Models\Merchant\BvsValidation\Entity as ValidationEntity;


class Factory
{
    /**
     * Returns StatusUpdater instance for artefact
     *
     * @param MerchantEntity   $merchant
     * @param ValidationEntity $validation
     *
     * @return StatusUpdater
     * @throws LogicException
     */
    public function getInstance(MerchantEntity $merchant, ValidationEntity $validation): StatusUpdater
    {
        $artefactType = $validation->getArtefactType();
        $validationId = $validation->getValidationId();

        switch ($artefactType)
        {
            case Constant::CIN:
            case Constant::LLP_DEED:

                return new DefaultStatusUpdater(
                    $merchant,
                    Entity::CIN_VERIFICATION_STATUS,
                    $artefactType,
                    $validationId);

            case Constant::GSTIN:

                return new DefaultStatusUpdater(
                    $merchant,
                    Entity::GSTIN_VERIFICATION_STATUS,
                    $artefactType,
                    $validationId);

            case Constant::BUSINESS_PAN :

                return $this->getStatusUpdater($merchant,
                                               $validation,
                                               Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS);

            case Constant::PERSONAL_PAN :

                return $this->getStatusUpdaterForPersonalPan($merchant, $validation);

            case Constant::BANK_ACCOUNT :

                return $this->getStatusUpdaterForBankAccount($merchant, $validation);

            case Constant::AADHAAR :

                return $this->getStatusUpdaterForAadhaar($merchant, $validation);

            case Constant::VOTERS_ID:
            case Constant::PASSPORT:

                return new POA($merchant, $artefactType, $validationId);

            case Constant::SHOP_ESTABLISHMENT :

                return new DefaultStatusUpdater(
                    $merchant,
                    Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS,
                    $artefactType,
                    $validationId);

            case Constant::MSME:

                return new DefaultStatusUpdater(
                    $merchant, Entity::MSME_DOC_VERIFICATION_STATUS,
                    $artefactType, $validationId);

            default :

                throw new LogicException(
                    ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_TYPE,
                    null,
                    [ValidationEntity::ARTEFACT_TYPE => $artefactType]);

        }
    }

    /**
     * @param MerchantEntity   $merchant
     * @param ValidationEntity $validation
     *
     * @return StatusUpdater
     */
    public function getStatusUpdaterForAadhaar(MerchantEntity $merchant, ValidationEntity $validation): StatusUpdater
    {
        $artefactType = $validation->getArtefactType();
        $validationId = $validation->getValidationId();

        if ($validation->getValidationUnit() === Constants::IDENTIFIER)
        {
            return new DefaultStatusUpdater(
                $merchant,
                StakeholderEntity::AADHAAR_VERIFICATION_WITH_PAN_STATUS,
                $artefactType,
                $validationId,
                E::STAKEHOLDER);
        }

        return new POA($merchant, $artefactType, $validationId);
    }

    /**
     * @param MerchantEntity   $merchant
     * @param ValidationEntity $validation
     *
     * @return StatusUpdater
     */
    public function getStatusUpdaterForPersonalPan(MerchantEntity $merchant, ValidationEntity $validation): StatusUpdater
    {
        $artefactType = $validation->getArtefactType();
        $validationId = $validation->getValidationId();

        if ($validation->getValidationUnit() === Constants::PROOF)
        {
            return new DefaultStatusUpdater(
                $merchant,
                Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS,
                $artefactType,
                $validationId);
        }

        return new POI($merchant, $artefactType, $validationId);
    }

    /**
     * @param MerchantEntity   $merchant
     * @param ValidationEntity $validation
     *
     * @param string           $documentTypeStatusKey
     *
     * @return StatusUpdater
     * @throws LogicException
     */
    public function getStatusUpdater(MerchantEntity $merchant, ValidationEntity $validation, string $documentTypeStatusKey): StatusUpdater
    {
        $artefactType   = $validation->getArtefactType();
        $validationUnit = $validation->getValidationUnit();
        $validationId   = $validation->getValidationId();

        if ($validationUnit === Constants::PROOF)
        {

            return new DefaultStatusUpdater(
                $merchant,
                $documentTypeStatusKey,
                $artefactType,
                $validationId);
        }

        throw new LogicException(
            ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_TYPE,
            null,
            [
                ValidationEntity::ARTEFACT_TYPE   => $artefactType,
                ValidationEntity::VALIDATION_UNIT => $validationUnit
            ]);
    }

    public function getStatusUpdaterForBankAccount(MerchantEntity $merchant, ValidationEntity $validation): StatusUpdater
    {
        $artefactType = $validation->getArtefactType();
        $validationId = $validation->getValidationId();

        if ($validation->getValidationUnit() === Constants::PROOF)
        {
            return new DefaultStatusUpdater(
                $merchant,
                Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS,
                $artefactType,
                $validationId);
        }

        return new BankAccount($merchant, $artefactType, $validationId);
    }
}
