<?php

namespace RZP\Models\User;

use DB;
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

    public function findByToken(string $token)
    {
        return $this->newQuery()
                    ->where(Entity::CONFIRM_TOKEN, '=', $token)
                    ->firstOrFailPublic();
    }

    public function getUsersForMerchant(string $merchantId)
    {
        $query = $this->newQuery()
                      ->select(Entity::ID,
                               Entity::NAME,
                               Entity::EMAIL,
                               Entity::CONTACT_MOBILE,
                               Entity::CONFIRM_TOKEN,
                               'users.created_at',
                               'merchant_users.role'
                        )
                      ->join(Table::MERCHANT_USERS, Entity::ID, '=', 'merchant_users.user_id')
                      ->where('merchant_users.merchant_id', '=', $merchantId)
                      ->orderBy(Entity::NAME, 'asc')
                      ->get();

        return $query;
    }

}
