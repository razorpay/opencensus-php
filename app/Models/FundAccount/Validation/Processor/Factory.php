<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Models\FundAccount\Entity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundAccount\Validation as FundAccountValidation;

class Factory
{
    /**
     * @param FundAccountValidation\Entity $fundAccountValidation
     *
     * @return Base
     * @throws BadRequestValidationFailureException
     */
    public static function get(FundAccountValidation\Entity $fundAccountValidation): Base
    {
        $type = $fundAccountValidation->fundAccount->account->getEntityName();

        if (in_array($type, [Entity::BANK_ACCOUNT, Entity::VPA], true) === false)
        {
            throw new BadRequestValidationFailureException(
                "Invalid fund account type: " . $type,
                FundAccountValidation\Entity::FUND_ACCOUNT,
                [
                    FundAccountValidation\Entity::FUND_ACCOUNT_ID => $fundAccountValidation->fundAccount->getId(),
                ]
            );
        }

        $processor = __NAMESPACE__ . '\\' . studly_case($type);

        return new $processor($fundAccountValidation);
    }
}
