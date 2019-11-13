<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundAccount;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::FUND_ACCOUNT => 'required|associative_array',
        Entity::AMOUNT       => 'sometimes|integer|min:100|max:200',
        Entity::NOTES        => 'sometimes|notes',
        Entity::CURRENCY     => 'filled|string|in:INR',
        Entity::RECEIPT      => 'sometimes|string|min:1|max:40',
        Entity::BALANCE_ID   => 'sometimes|unsigned_id',
    ];

    protected static $retryRules = [
        Entity::FUND_ACCOUNT_VALIDATION_IDS              => 'required|array|min:1',
        Entity::FUND_ACCOUNT_VALIDATION_IDS.".*"         => 'required|string',
    ];

    /**
     * @param Entity $validation
     *
     * @param array  $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateAmount(Entity $validation, array $input)
    {
        if ($validation->fundAccount->getAccountType() === FundAccount\Type::VPA)
        {
            if (isset($input['amount']))
            {
                throw new BadRequestValidationFailureException(
                    'Invalid amount field for fund account of type vpa.');
            }
        }
    }

    /**
     * @param Entity $validation
     *
     * @param array  $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateCurrency(Entity $validation, array $input)
    {
        if ($validation->fundAccount->getAccountType() === FundAccount\Type::VPA)
        {
            if (isset($input['currency']))
            {
                throw new BadRequestValidationFailureException(
                    'Invalid currency field for fund account of type vpa.');
            }
        }
    }
}
