<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Models\FundAccount\Validation as FundAccountValidation;

class Factory
{
    public static function get(FundAccountValidation\Entity $fundAccountValidation): Base
    {
        $type = $fundAccountValidation->fundAccount->account->getEntityName();

        $processor = __NAMESPACE__ . '\\' . studly_case($type);

        return new $processor($fundAccountValidation);
    }
}
