<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleExecutionResultVerifier;

use App;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity as ValidationEntity;
use RZP\Models\Merchant\BvsValidation\Constants as ValidationConstants;
use RZP\Trace\TraceCode;

class Factory
{
    /**
     * Returns StatusUpdater instance for artefact
     *
     * @param ValidationEntity $validation
     *
     * @return RuleExecutionResultVerifier
     * @throws LogicException
     */
    public function getInstance(ValidationEntity $validation)
    {
        $artefactType   = $validation->getArtefactType();
        $validationUnit = $validation->getValidationUnit();

        switch ($artefactType.$validationUnit)
        {
            case Constant::CIN.ValidationConstants::IDENTIFIER:
            case Constant::LLP_DEED.ValidationConstants::IDENTIFIER:
                return new CinRuleResultVerifier($validation);

            case Constant::GSTIN.ValidationConstants::IDENTIFIER:
                return new GSTINRuleResultVerifier($validation);

            case Constant::PARTNERSHIP_DEED.ValidationConstants::PROOF:
                return new PartnershipDeedRuleResultVerifier($validation);

            case Constant::SHOP_ESTABLISHMENT.ValidationConstants::IDENTIFIER:
                return new ShopEstablishmentAuthRuleResultVerifier($validation);

            case Constant::MSME.ValidationConstants::PROOF:
                return new MSMERulesResultVerifier($validation);

            default :
                return new DefaultRuleResultVerifier($validation);
        }
    }
}
