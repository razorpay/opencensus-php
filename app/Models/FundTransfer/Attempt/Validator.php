<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $bulkUpdateRules = [
        'ids'                    => 'required|array',
        Entity::STATUS           => 'sometimes|string|custom',
        Entity::FAILURE_REASON   => 'sometimes|string|max:100',
        Entity::REMARKS          => 'sometimes|string|max:100',
        Entity::BANK_STATUS_CODE => 'sometimes|string|max:4',
    ];

    protected function validateStatus($attribute, $value)
    {
        if (Status::isValidForBulkUpdate($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid status',
                $attribute,
                $value);
        }
    }
}
