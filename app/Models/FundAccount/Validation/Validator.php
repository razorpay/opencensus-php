<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\FundAccount;
use RZP\Exception\BadRequestException;


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
     * @throws BadRequestException
     */
    public function validateAmount(Entity $validation, array $input)
    {
        if ($validation->fundAccount->getAccountType() === FundAccount\Type::VPA)
        {
            if (isset($input['amount']))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INVALID_AMOUNT);
            }
        }
    }

    /**
     * @param Entity $validation
     *
     * @param array  $input
     *
     * @throws BadRequestException
     */
    public function validateCurrency(Entity $validation, array $input)
    {
        if ($validation->fundAccount->getAccountType() === FundAccount\Type::VPA)
        {
            if (isset($input['currency']))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INVALID_CURRENCY);
            }
        }
    }

    /**
     * @param Entity $validation
     *
     * @throws BadRequestException
     */
    public function validateBalanceId(Entity $validation)
    {
        if (($validation->balance->getType() === Merchant\Balance\Type::BANKING) and
            ($validation->balance->getAccountType() !== Merchant\Balance\AccountType::SHARED))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_NOT_SUPPORTED_BALANCE,
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
     * @throws BadRequestException
     */
    public function validateFundAccount($validation, $input)
    {
        if (($validation->balance->getType() === Merchant\Balance\Type::BANKING) and
            (empty($input['fund_account']['id']) === true))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_FUND_ACCOUNT_ID_MISSING,
                Entity::FUND_ACCOUNT,
                [
                    Merchant\Balance\Entity::ACCOUNT_NUMBER => $validation->balance->getAccountNumber(),
                ]
            );
        }
    }
}
