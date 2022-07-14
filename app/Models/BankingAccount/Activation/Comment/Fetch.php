<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        AuthType::PRIVILEGE_AUTH => [
            self::EXPAND_EACH             => 'filled|string|in:admin',
            Entity::BANKING_ACCOUNT_ID    => 'sometimes|unsigned_id',
            Entity::ADMIN_ID              => 'sometimes|unsigned_id',
            Entity::SOURCE_TEAM_TYPE      => 'sometimes|string',
            Entity::SOURCE_TEAM           => 'sometimes|string',
            Entity::TYPE                  => 'sometimes|string',
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH             => 'filled|string|in:admin',
            Entity::BANKING_ACCOUNT_ID    => 'sometimes|unsigned_id',
            Entity::ADMIN_ID              => 'sometimes|unsigned_id',
            Entity::SOURCE_TEAM_TYPE      => 'sometimes|string',
            Entity::SOURCE_TEAM           => 'sometimes|string',
            Entity::TYPE                  => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::BANKING_ACCOUNT_ID,
            Entity::ADMIN_ID,
            Entity::SOURCE_TEAM_TYPE,
            Entity::SOURCE_TEAM,
            Entity::TYPE,
            self::EXPAND_EACH,
        ],
        AuthType::PROXY_AUTH => [
            Entity::BANKING_ACCOUNT_ID,
            Entity::ADMIN_ID,
            Entity::SOURCE_TEAM_TYPE,
            Entity::SOURCE_TEAM,
            Entity::TYPE,
            self::EXPAND_EACH,
        ],
    ];
}
