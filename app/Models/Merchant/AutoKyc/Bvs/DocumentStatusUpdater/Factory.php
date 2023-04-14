<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Constants\Entity as E;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\BvsValidation\Entity as ValidationEntity;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Document\Core as DocumentCore;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Trace\TraceCode;

class Factory
{
    /**
     * Returns StatusUpdater instance for artefact
     *
     * @param MerchantEntity   $merchant
     * @param Entity           $merchantDetails
     * @param ValidationEntity $validation
     *
     * @return StatusUpdater
     * @throws LogicException
     */

    public function getInstance(MerchantEntity $merchant,
                                Entity $merchantDetails,
                                ValidationEntity $validation): StatusUpdater
    {
        $artefactType = $validation->getArtefactType();

        switch ($artefactType)
        {
            case Constant::CIN:
            case Constant::LLP_DEED:

                return new DefaultStatusUpdater(
                    $merchant,
                    $merchantDetails,
                    Entity::CIN_VERIFICATION_STATUS,
                    $validation);

            case Constant::GSTIN:

                return $this->getStatusUpdaterForGSTIN($merchant, $merchantDetails, $validation);

            case Constant::BUSINESS_PAN :
                $documentStatusKey = Entity::COMPANY_PAN_VERIFICATION_STATUS;

                if ($validation->getValidationUnit() === Constants::PROOF)
                {
                    $documentStatusKey = Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS;
                }

                return new PanStatusUpdater(
                    $merchant,
                    $merchantDetails,
                    $documentStatusKey,
                    $validation);

            case Constant::PERSONAL_PAN :

                return $this->getStatusUpdaterForPersonalPan($merchant, $merchantDetails, $validation);

            case Constant::BANK_ACCOUNT :

                return $this->getStatusUpdaterForBankAccount($merchant, $merchantDetails, $validation);

            case Constant::AADHAAR :

                return $this->getStatusUpdaterForAadhaar($merchant, $merchantDetails, $validation);

            case Constant::VOTERS_ID:
            case Constant::PASSPORT:

                return new POA($merchant,$merchantDetails, $validation);

            case Constant::SHOP_ESTABLISHMENT :

                return $this->getStatusUpdaterForShopEstb($merchant, $merchantDetails, $validation);

            case Constant::MSME:

                return new DefaultStatusUpdater(
                    $merchant, $merchantDetails,
                    Entity::MSME_DOC_VERIFICATION_STATUS,
                    $validation);

            case Constant::PARTNERSHIP_DEED :
                return new PartnershipDeedOcrStatusUpdater($merchant,$merchantDetails, $validation);

            case Constant::CERTIFICATE_OF_INCORPORATION:
                return new CertificateOfIncorporationStatusUpdater($merchant,$merchantDetails, $validation);

            case Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE:
                return new TrustSocietyNgoBusinessCertificateStatusUpdater($merchant,$merchantDetails, $validation);

            case Constant::COMMON:
                return new NullStatusUpdater($merchant,$merchantDetails, $validation);

            case Constant::WEBSITE_POLICY:
                return new WebsitePolicyStatusUpdater($merchant, $merchantDetails, $validation);

            case Constant::NEGATIVE_KEYWORDS:
                return new NegativeKeywordsStatusUpdater($merchant, $merchantDetails, $validation);

            case Constant::MCC_CATEGORISATION_WEBSITE:
                return new MccCategorisationStatusUpdater($merchant, $merchantDetails, $validation);

            default :

                throw new LogicException(
                    ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_TYPE,
                    null,
                    [ValidationEntity::ARTEFACT_TYPE => $artefactType]);

        }
    }

    /**
     * @param MerchantEntity   $merchant
     * @param Entity           $merchantDetails
     * @param ValidationEntity $validation
     *
     * @return StatusUpdater
     */
    public function getStatusUpdaterForGSTIN(MerchantEntity $merchant,
                                             Entity $merchantDetails,
                                             ValidationEntity $validation): StatusUpdater
    {
        if ($validation->getValidationUnit() === Constants::PROOF)
        {
            return new GstCertificateOcrStatusUpdater(
                $merchant,
                $merchantDetails,
                $validation);
        }

        return new GstInStatusUpdater(
            $merchant,
            $merchantDetails,
            Entity::GSTIN_VERIFICATION_STATUS,
            $validation);
    }

    /**
     * @param MerchantEntity   $merchant
     * @param Entity           $merchantDetails
     * @param ValidationEntity $validation
     *
     * @return StatusUpdater
     */
    public function getStatusUpdaterForAadhaar(MerchantEntity $merchant,
                                               Entity $merchantDetails,
                                               ValidationEntity $validation): StatusUpdater
    {
        $validationId = $validation->getValidationId();
        $merchant_document = (new DocumentCore())->getDocument($merchant->getMerchantId(), $validationId);
        if ($validation->getValidationUnit() === Constants::IDENTIFIER)
        {
            return new DefaultStatusUpdater(
                $merchant,
                $merchantDetails,
                StakeholderEntity::AADHAAR_VERIFICATION_WITH_PAN_STATUS,
                $validation,
                E::STAKEHOLDER);
        }
        else
        {
            // poa should be returned even for aadhaar_back for joint validation experiment
            $isExperimentEnabledForJointValidation = (new Merchant\Core)->isRazorxExperimentEnable($merchant->getMerchantId(),
                RazorxTreatment::AADHAAR_FRONT_AND_BACK_JOINT_VALIDATION);

            if (isset($merchant_document) === false or
                Type::isPoaDocument($merchant_document->getDocumentType()) === true or
                ($isExperimentEnabledForJointValidation and
                    Type::isJointValidationDocumentType($merchant_document->getDocumentType()) === true))
            {
                return new POA($merchant, $merchantDetails, $validation);
            }
            else
            {
                return new NullStatusUpdater($merchant, $merchantDetails, $validation);
            }
        }
    }

    /**
     * @param MerchantEntity   $merchant
     * @param Entity           $merchantDetails
     * @param ValidationEntity $validation
     *
     * @return StatusUpdater
     */
    public function getStatusUpdaterForPersonalPan(MerchantEntity $merchant,
                                                   Entity $merchantDetails,
                                                   ValidationEntity $validation): StatusUpdater
    {
        if ($validation->getValidationUnit() === Constants::PROOF)
        {
            return new PanStatusUpdater(
                $merchant,
                $merchantDetails,
                Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS,
                $validation);
        }

        return new PanStatusUpdater(
            $merchant,
            $merchantDetails,
            Entity::POI_VERIFICATION_STATUS,
            $validation);
    }

    public function getStatusUpdaterForBankAccount(MerchantEntity $merchant,
                                                   Entity $merchantDetails,
                                                   ValidationEntity $validation): StatusUpdater
    {
        if ($validation->getValidationUnit() === Constants::PROOF and $merchant->isNoDocOnboardingEnabled() === false)
        {
            return new DefaultStatusUpdater(
                $merchant,
                $merchantDetails,
                Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS,
                $validation);
        }

        return new BankAccount($merchant, $merchantDetails,$validation);
    }

    public function getStatusUpdaterForShopEstb(MerchantEntity $merchant,
                                                Entity $merchantDetails,
                                                ValidationEntity $validation): StatusUpdater
    {
        if ($validation->getValidationUnit() === Constants::PROOF)
        {
            return new ShopEstbStatusUpdater(
                $merchant,
                $merchantDetails,
                $validation);
        }

        return new DefaultStatusUpdater(
            $merchant,
            $merchantDetails,
            Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS,
            $validation);
    }

}
