<?php

namespace RZP\Models\ChargeCollections;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createInternalTransactionRules = [
        Constants::MERCHANT_ID   => 'required|string|size:14',
        Constants::ENTITY_ID     => 'required|string|size:14',
        Constants::AMOUNT        => 'required|integer',
        Constants::JOURNAL_ID    => 'required|string|size:14',
        Constants::CURRENCY      => 'required|string',
        Constants::IS_REVERSAL   => 'sometimes|boolean',
    ];
}
