<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;
use DB;

class User extends Base
{
    const MERCHANT_USER_ID    = 'MerchantUser01';
    const MERCHANT_USER_EMAIL = 'merchantuser01@razorpay.com';

    public function setUp()
    {
        $user = $this->fixtures->create('user', ['id' => self::MERCHANT_USER_ID, 'email' => self::MERCHANT_USER_EMAIL]);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->createUserMerchantMapping($mappingData, 'test');
        $this->createUserMerchantMapping($mappingData, 'live');

        $mappingData['product'] = 'banking';
        $this->createUserMerchantMapping($mappingData, 'test');
        $this->createUserMerchantMapping($mappingData, 'live');
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

    public function createUserMerchantMapping(array $attributes, $mode = 'test')
    {
        $userId = $attributes['user_id'];

        $merchantId = $attributes['merchant_id'];

        $role = $attributes['role'];

        $product = $attributes['product'] ?? 'primary';

        DB::connection($mode)->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'product'     => $product,
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ]);
    }

    public function createUserMerchantMappingForDefaultUser($merchantId, $role = 'owner', $mode = 'test', $product = 'primary')
    {
        $this->createUserMerchantMapping([
            'merchant_id' => $merchantId,
            'user_id'     => self::MERCHANT_USER_ID,
            'role'        => $role,
            'product'     => $product,
        ], $mode);
    }

    public function createUserForMerchant(string $merchantId = '10000000000000',
                                          array $attributes = [],
                                          $role = 'owner',
                                          $mode = 'test')
    {
        $user = $this->createEntityInTestAndLive('user', $attributes);

        $this->createUserMerchantMapping([
                                             'merchant_id' => $merchantId,
                                             'user_id'     => $user['id'],
                                             'role'        => $role,
                                         ], $mode);

        return $user;
    }

    public function createUserForMerchantONLiveAndTest(string $merchantId = '10000000000000',
                                          array $attributes = [],
                                                 $role = 'owner')
    {
        $user = $this->createEntityInTestAndLive('user', $attributes);

        $this->createUserMerchantMapping([
                                             'merchant_id' => $merchantId,
                                             'user_id'     => $user['id'],
                                             'role'        => $role,
                                         ], 'test');

        $this->createUserMerchantMapping([
                                             'merchant_id' => $merchantId,
                                             'user_id'     => $user['id'],
                                             'role'        => $role,
                                         ], 'live');
        return $user;
    }

    public function createBankingUserForMerchant(string $merchantId = '10000000000000',
                                          array $attributes = [],
                                          $role = 'owner',
                                          $mode = 'test')
    {
        $user = $this->createEntityInTestAndLive('user', $attributes);

        $mappingData = [
            'merchant_id' => $merchantId,
            'user_id'     => $user['id'],
            'role'        => $role,
            'product'     => 'banking'
        ];

        $this->createUserMerchantMapping($mappingData, $mode);

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
