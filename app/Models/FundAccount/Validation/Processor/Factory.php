<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Models\FundAccount\Validation as FundAccountValidation;

class Factory
{
    /**
     * @param FundAccountValidation\Entity $fundAccountValidation
     *
     * @return Base
     * @throws \RZP\Exception\BadRequestException
     */
    public static function get(FundAccountValidation\Entity $fundAccountValidation): Base
    {
        $type = $fundAccountValidation->fundAccount->account->getEntityName();

        Type::validate($type);

        $processor = __NAMESPACE__ . '\\' . studly_case($type);

        return new $processor($fundAccountValidation);
    }
}
