<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use DB;
use Carbon\Carbon;

class User extends Base
{
    const MERCHANT_USER_ID = 'MerchantUser01';

    public function setup()
    {
        $this->fixtures->create('user', ['id' => self::MERCHANT_USER_ID]);
    }

    public function create(array $attributes = [])
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
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ]);
    }

    public function createUserForMerchant(string $merchantId = '10000000000000', array $attributes = [])
    {
        $user = $this->createEntityInTestAndLive('user', $attributes);

        $this->createUserMerchantMapping([
                'merchant_id' => $merchantId,
                'user_id'     => $user['id'],
                'role'        => 'owner',
            ]);

        return $user;
    }

    /**
     * @param string $merchantId
     * @param string $userId
     * @param string $product
     *
     * @return \RZP\Models\Base\PublicCollection
     */
    public function getMerchantUserMapping(string $merchantId,
                                           string $userId,
                                           string $product = 'primary')
    {
        return DB::table('merchant_users')
                    ->where('merchant_id', $merchantId)
                    ->where('user_id', $userId)
                    ->where('product', $product)
                    ->get();
    }
}
