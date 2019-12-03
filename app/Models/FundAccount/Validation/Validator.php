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

    protected static $balanceTypeBankingRules = [
        Entity::AMOUNT                          => 'sometimes|integer|min:100|max:200',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::CURRENCY                        => 'filled|string|in:INR',
        Entity::BALANCE_ID                      => 'required|custom',
        Entity::FUND_ACCOUNT                    => 'required|associative_array',
        Entity::FUND_ACCOUNT . '.' . Entity::ID => 'required|unsigned_id',
    ];

    protected static $fundAccountTypeVpaRules = [
        Entity::FUND_ACCOUNT                    => 'required|associative_array',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::RECEIPT                         => 'sometimes|string|min:1|max:40',
        Entity::BALANCE_ID                      => 'required|custom',
    ];

    protected static $retryRules = [
        Entity::FUND_ACCOUNT_VALIDATION_IDS      => 'required|array|min:1',
        Entity::FUND_ACCOUNT_VALIDATION_IDS.".*" => 'required|string',
    ];

    /**
     * @param $attribute
     * @param $value
     *
     * @throws BadRequestException
     */
    public function validateBalanceId($attribute, $value)
    {
        $validation = $this->entity;

        if (empty($validation->balance) OR
            (($validation->balance->getType() === Merchant\Balance\Type::BANKING) and
            ($validation->balance->getAccountType() !== Merchant\Balance\AccountType::SHARED)))
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
}
