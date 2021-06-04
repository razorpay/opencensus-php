<?php

namespace RZP\Models\WalletAccount;

use Lib\PhoneBook;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const BEFORE_CREATE_FUND_ACCOUNT_WALLET_ACCOUNT = 'before_create_fund_account_wallet_account';

    protected static $createRules = [
        Entity::PHONE      => 'required|string|max:13|custom',
        Entity::EMAIL      => 'sometimes|email',
        Entity::NAME       => 'sometimes|string|max:100',
        Entity::PROVIDER   => 'required|string|in:amazonpay',
    ];

    protected static $beforeCreateFundAccountWalletAccountRules = [
        Entity::PROVIDER => 'required|string|in:amazonpay',
        Entity::PHONE    => 'required|filled',
        Entity::EMAIL    => 'sometimes|filled',
        Entity::NAME     => 'sometimes|filled',
    ];

    public function validatePhone($attribute, $value)
    {
        $phonebook = new PhoneBook($value, true);

        if ($phonebook->isValidNumber() === false)
        {
            throw new BadRequestValidationFailureException('Invalid contact number');
        }
    }
}
