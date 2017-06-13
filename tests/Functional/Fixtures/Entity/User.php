<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use DB;

class User extends Base
{
    public function create(array $attributes = array())
    {
        $merchant = $this->fixtures->create('merchant');

        $user = $this->createEntityInTestAndLive('user', $attributes);

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'owner');

        return $user;
    }

    protected function createUserMerchantMapping(string $userId, string $merchantId, string $role)
    {
        DB::table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);
    }
}
