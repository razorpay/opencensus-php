<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity as ValidationEntity;

class Factory
{
    /**
     * Returns StatusUpdater instance for artefact
     *
     * @param ValidationEntity $validation
     *
     * @return RuleResultVerifier
     * @throws LogicException
     */
    public function getInstance(ValidationEntity $validation): RuleResultVerifier
    {
        $artefactType = $validation->getArtefactType();

        switch ($artefactType)
        {
            case Constant::CIN:
                return new CinRuleResultVerifier($validation);

            case Constant::GSTIN:
                return new GstinRuleResultVerifier($validation);

            case Constant::LLP_DEED:
                return new LlpinRuleResultVerifier($validation);

            case Constant::SHOP_ESTABLISHMENT:
                return new ShopEstablishmentRuleResultVerifier($validation);

            default :
                throw new LogicException(
                    ErrorCode::SERVER_ERROR_UNHANDLED_ARTEFACT_TYPE,
                    null,
                    [ValidationEntity::ARTEFACT_TYPE => $artefactType]);
        }
    }
}
