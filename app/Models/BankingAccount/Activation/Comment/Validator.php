<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::BANKING_ACCOUNT_ID  => 'required|string|size:14',
        Entity::ADMIN_ID            => 'required|string|size:14',
        Entity::COMMENT             => 'required|string',
        Entity::SOURCE_TEAM_TYPE    => 'required|max:255|in:internal,external',
        Entity::SOURCE_TEAM         => 'required|max:255|in:product,sales,ops,bank',
        Entity::ADDED_AT            => 'required|epoch'
    ];
}
