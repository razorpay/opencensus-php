<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Base;
use RZP\Models\Merchant;
use RZP\Models\FundAccount;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestValidationFailureException;


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

    /**
     * @param Entity $validation
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateBalanceId(Entity $validation)
    {
        if (($validation->balance->getType() === Merchant\Balance\Type::BANKING)
            && ($validation->balance->getAccountType() !== Merchant\Balance\AccountType::SHARED))
        {
            throw new BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_NOT_SUPPORTED_BALANCE,
                Merchant\Balance\Entity::ACCOUNT_NUMBER,
                [
                    Merchant\Balance\Entity::ACCOUNT_NUMBER => $validation->balance->getAccountNumber(),
                ]
            );
        }
    }

    /**
     * @param $validation
     * @param $input
     *
     * @throws \RZP\Exception\AssertionException
     * @throws BadRequestValidationFailureException
     */
    public function validateFundAccount($validation, $input)
    {
        assertTrue(isset($input['fund_account']) === true);

        if (($validation->balance->getType() === Merchant\Balance\Type::BANKING)
            && (empty($input['fund_account']['id']) === true))
        {
            throw new BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_FUND_ACCOUNT_ID_MISSING,
                Merchant\Balance\Entity::ACCOUNT_NUMBER,
                [
                    Merchant\Balance\Entity::ACCOUNT_NUMBER => $validation->balance->getAccountNumber(),
                ]
            );
        }

    }
}
