<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{

    // For filter that corresponds to addQueryParamForSourceTeam
    const FOR_SOURCE_TEAM_TYPE = 'for_source_team_type';

    const RULES = [
        AuthType::PRIVILEGE_AUTH => [
            self::EXPAND_EACH             => 'filled|string|in:admin,user',
            Entity::BANKING_ACCOUNT_ID    => 'sometimes|unsigned_id',
            Entity::ADMIN_ID              => 'sometimes|unsigned_id',
            Entity::SOURCE_TEAM_TYPE      => 'sometimes|string',
            Entity::SOURCE_TEAM           => 'sometimes|string',
            Entity::TYPE                  => 'sometimes|string',
            self::FOR_SOURCE_TEAM_TYPE    => 'sometimes|string|in:external,internal',
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH             => 'filled|string|in:admin,user',
            Entity::BANKING_ACCOUNT_ID    => 'sometimes|unsigned_id',
            Entity::ADMIN_ID              => 'sometimes|unsigned_id',
            Entity::SOURCE_TEAM_TYPE      => 'sometimes|string',
            Entity::SOURCE_TEAM           => 'sometimes|string',
            Entity::TYPE                  => 'sometimes|string',
            self::FOR_SOURCE_TEAM_TYPE    => 'sometimes|string|in:external,internal',
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
            self::FOR_SOURCE_TEAM_TYPE,
        ],
        AuthType::PROXY_AUTH => [
            Entity::BANKING_ACCOUNT_ID,
            Entity::ADMIN_ID,
            Entity::SOURCE_TEAM_TYPE,
            Entity::SOURCE_TEAM,
            Entity::TYPE,
            self::EXPAND_EACH,
            self::FOR_SOURCE_TEAM_TYPE,
        ],
    ];
}
