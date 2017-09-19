<?php

namespace RZP\Models\Adjustment;

use RZP\Base;
use RZP\Models\Merchant\Invoice\Entity as InvoiceEntity;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT        => 'required|integer',
        Entity::CURRENCY      => 'required|in:INR',
        Entity::DESCRIPTION   => 'required|min:10|max:255',
        Entity::SETTLEMENT_ID => 'sometimes|size:14',
    ];

    protected static $feeAdjustmentRules = [
        Entity::AMOUNT        => 'sometimes|integer',
        InvoiceEntity::TAX    => 'sometimes|integer',
        Entity::CURRENCY      => 'required|in:INR',
        Entity::DESCRIPTION   => 'required|min:10|max:255',
    ];
}
