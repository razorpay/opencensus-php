<?php

namespace RZP\Models\Transaction\FeeBreakupNew;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ID                      => 'required|string|size:14',
        Entity::NAME                    => 'required|string',
        Entity::PERCENTAGE              => 'sometimes|nullable|integer',
        Entity::AMOUNT                  => 'required|integer',
        Entity::TRANSACTION_ID          => 'required|string|size:14',
        Entity::PRICING_RULE_ID         => 'sometimes|nullable|string|size:14',
        Entity::CREATED_AT              => 'required|integer',
        Entity::UPDATED_AT              => 'required|integer',
    );
}
