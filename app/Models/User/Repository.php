<?php

namespace RZP\Models\User;

use RZP\Models\Base;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'user';

    protected $appFetchParamRules = [
        Entity::EMAIL       => 'sometimes|email|max:255',
    ];

    public function findByEmail(string $email)
    {
        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', $email)
                    ->firstOrFailPublic();
    }

    public function getOwners(string $merchantId)
    {
        return $this->newQuery()
                    ->join(Table::MERCHANT_USERS, Entity::ID, '=', 'merchant_users.user_id')
                    ->where('merchant_users.merchant_id', '=', $merchantId)
                    ->where('merchant_users.role', '=', Entity::OWNER)
                    ->get();
    }
}
