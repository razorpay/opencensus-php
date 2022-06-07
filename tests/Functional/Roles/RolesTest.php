<?php

namespace RZP\Tests\Functional\Roles;

use DB;
use Requests_Response;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class RolesTest extends TestCase
{
    const DEFAULT_X_MERCHANT_ID = '100000merchant';
    const EXISTING_MERCHANT_FOR_INVITED_USER_ID = '10000000000001';

    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->unitTestCase = new \Tests\Unit\TestCase();

        $this->testDataFilePath = __DIR__ . '/helpers/RolesTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testFetchRoles()
    {
        $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);
        $user2 = $this->fixtures->user->createEntityInTestAndLive('user', []);
        $user3 = $this->fixtures->user->createEntityInTestAndLive('user', []);
        $user4 = $this->fixtures->user->createEntityInTestAndLive('user', []);
        $user5 = $this->fixtures->user->createEntityInTestAndLive('user', []);
        $user6 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC 1', 'id' => '100customRole1',]);

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, $customRole1['id']);
        $this->createMerchantUserMappingInLiveAndTest($user2['id'], self::DEFAULT_X_MERCHANT_ID, $customRole1['id']);
        $this->createMerchantUserMappingInLiveAndTest($user3['id'], self::DEFAULT_X_MERCHANT_ID, $customRole1['id']);

        $customRole2 = $this->fixtures->create('roles', ['name' => 'CAC 2', 'id' => '100customRole2',]);

        $this->createMerchantUserMappingInLiveAndTest($user4['id'], self::DEFAULT_X_MERCHANT_ID, $customRole2['id']);
        $this->createMerchantUserMappingInLiveAndTest($user5['id'], self::DEFAULT_X_MERCHANT_ID, $customRole2['id']);

        $customRole3 = $this->fixtures->create('roles', ['name' => 'CAC 3', 'id' => '100customRole3',]);

        $this->createMerchantUserMappingInLiveAndTest($user6['id'], self::DEFAULT_X_MERCHANT_ID, $customRole3['id']);

        $response = $this->startTest();
    }

    protected function createMerchantUserMappingInLiveAndTest(string $userId, string $merchantId, string $role)
    {
        DB::connection('live')->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'product'     => 'banking',
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);

        DB::connection('test')->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'product'     => 'banking',
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);
    }
}
