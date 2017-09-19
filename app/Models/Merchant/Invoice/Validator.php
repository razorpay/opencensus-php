<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Exception;
use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MONTH       => 'required|integer|between:1,12',
        Entity::YEAR        => 'required|digits:4',
        Entity::TYPE        => 'required|string',
        Entity::DESCRIPTION => 'sometimes|string|nullable',
        Entity::AMOUNT      => 'required|integer',
        Entity::TAX         => 'required|integer',
        Entity::AMOUNT_DUE  => 'sometimes|integer|min:0',
        Entity::GSTIN       => 'sometimes|string|size:15|nullable',
    ];

    protected static $editGstinRules = [
        Entity::INVOICE_NUMBER  => 'required|string',
    ];

    protected static $createQueueRules = [
        Entity::MONTH       => 'sometimes|integer|between:1,12',
        Entity::YEAR        => 'sometimes|digits:4',
        'merchant_ids'      => 'sometimes|array',
        'merchant_ids.*'    => 'sometimes|string|size:14',
    ];

    protected static $bulkCreateRules = [
        'invoice_entities'      => 'required|array',
        'invoice_entities.*'    => 'required|array',
    ];

    protected static $createValidators = [
        Entity::TYPE,
    ];

    protected function validateType($input)
    {
        if (Type::isValid($input[Entity::TYPE]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid commission type: ', $input[Entity::TYPE]);
        }
    }
}