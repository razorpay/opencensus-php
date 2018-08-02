<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use DB;

class User extends Base
{
    const MERCHANT_USER_ID = 'MerchantUser01';

    public function setup()
    {
        $this->fixtures->create('user', ['id' => self::MERCHANT_USER_ID]);
    }

    public function create(array $attributes = array())
    {
        $merchant = $this->fixtures->create('merchant');

        $user = $this->createEntityInTestAndLive('user', $attributes);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        return $user;
    }

    public function createUserMerchantMapping(array $attributes)
    {
        $userId = $attributes['user_id'];

        $merchantId = $attributes['merchant_id'];

        $role = $attributes['role'];

        DB::connection('test')->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);
    }

    public function getMerchantUserMapping($merchantId, $userId)
    {
        return DB::table('merchant_users')
                    ->where('merchant_id', $merchantId)
                    ->where('user_id', $userId)
                    ->get();
    }

    public function createUserForMerchant(string $merchantId, array $attributes = array())
    {
        $user = $this->fixtures->create('user', $attributes);

        $mappingData = ['user_id' => $user['id'], 'merchant_id' => $merchantId, 'role' => 'owner'];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        return $user;
    }
}
