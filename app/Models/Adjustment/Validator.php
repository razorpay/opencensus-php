<?php

namespace RZP\Models\Adjustment;

use RZP\Base;
use RZP\Models\Dispute\Entity as DisputeEntity;
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

    // Payment id is actually a comma separated list of payment_ids
    // that add up to the adjustment amount
    protected static $splitAdjustmentRules = [
        Entity::ID                => 'required|alpha_num|size:14',
        DisputeEntity::PAYMENT_ID => 'required|string',
    ];
}
