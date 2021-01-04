<?php

namespace RZP\Models\Settlement\OndemandPayout;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID             => 'required|alpha_num|size:14',
        Entity::USER_ID                 => 'sometimes|nullable|alpha_num|size:14',
        Entity::AMOUNT                  => 'required|integer',
        Entity::FEES                    => 'sometimes|integer',
        Entity::TAX                     => 'sometimes|integer',
        Entity::MODE                    => 'sometimes|in:NEFT,IMPS|nullable',
        Entity::SETTLEMENT_ONDEMAND_ID  => 'sometimes|alpha_num|size:14',
        Entity::STATUS                  => 'sometimes|string',
        Entity::INITIATED_AT            => 'sometimes|epoch|nullable',
        Entity::PROCESSED_AT            => 'sometimes|epoch|nullable',
        Entity::REVERSED_AT             => 'sometimes|epoch|nullable',
        Entity::UTR                     => 'sometimes|string|nullable',
    ];
}
