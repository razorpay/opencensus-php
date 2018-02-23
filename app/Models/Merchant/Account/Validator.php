<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;

class Validator extends Merchant\Validator
{
     protected static $createRules = [
         Entity::NAME                                          => 'required|alpha_space_num|max:200',
         Entity::EMAIL                                         => 'required|email',
         Entity::TNC_ACCEPTED                                  => 'required|boolean|in:1',
         Entity::NOTES                                         => 'sometimes|array|max:15',
         Entity::ACCOUNT_DETAILS                               => 'required|array',
         Entity::ACCOUNT_DETAILS . '.' . Entity::BUSINESS_NAME => 'required|string|max:255',
         Entity::ACCOUNT_DETAILS . '.' . Entity::BUSINESS_TYPE => 'required|string|max:100',

         // For following only key presence is validated here.
         // Sub keys are validated in respective validators.
         Entity::BANK_ACCOUNT                                  => 'required|array',
     ];

    protected static $fetchRules = [
        Entity::EMAIL => 'sometimes|email',
    ];
}
