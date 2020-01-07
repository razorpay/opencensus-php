<?php

namespace RZP\Models\Settlement\Transfer;

use RZP\Base;
use RZP\Models\Settlement\Details;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::SOURCE_MERCHANT_ID          => 'required|string|size:14',
        Entity::MERCHANT_ID                 => 'required|string|size:14',
        Entity::SETTLEMENT_ID               => 'required|string|size:14',
        Entity::SETTLEMENT_TRANSACTION_ID   => 'required|string|size:14',
        Entity::BALANCE_ID                  => 'required|string|size:14',
        Entity::CURRENCY                    => 'required|string|size:3',
        Entity::AMOUNT                      => 'required|integer',
        Entity::FEE                         => 'required|integer',
        Entity::TAX                         => 'required|integer',
    ];
}
