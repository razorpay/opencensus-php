<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $editRules = [
        Entity::STATUS           => 'sometimes|string|custom',
        Entity::FAILURE_REASON   => 'sometimes|string|max:100',
        Entity::REMARKS          => 'sometimes|string|max:100',
        Entity::BANK_STATUS_CODE => 'sometimes|string|max:30',
    ];

    protected static $initiateFundTransferRules = [
        Entity::PURPOSE         => 'required|filled|string|max:30|in:refund,settlement',
        Entity::SOURCE_TYPE     => 'sometimes|filled|string|max:32|in:refund,payout'
    ];

    protected static $bulkReconcileRules = [
        'from' => 'required_with:to|epoch|date_format:U',
        'to'   => 'required_with:from|epoch|date_format:U',
    ];

    protected function validateStatus($attribute, $value)
    {
        if (Status::isValidForBulkUpdate($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid status',
                $attribute,
                $value);
        }
    }
}
