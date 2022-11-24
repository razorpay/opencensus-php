<?php

namespace RZP\Tests\Functional\AccessControlHistoryLogs;

use DB;
use \WpOrg\Requests\Response;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\AccessControlHistoryLogs as HistoryLogs;

class AccessControlHistoryLogs extends TestCase
{
    const DEFAULT_X_MERCHANT_ID = '100000merchant';
    const EXISTING_MERCHANT_FOR_INVITED_USER_ID = '10000000000001';
    private $str;

    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->unitTestCase = new \Tests\Unit\TestCase();

        $this->testDataFilePath = __DIR__ . '/helpers/AccessControlHistoryLogs.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateHistoryLogs()
    {
        $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');

        $privilege1 = $this->fixtures->create('access_control_privileges',
            [
                'id'          => '1000privilege1',
                'name'        => 'Account Setting',
                'description' => 'A/c setting test description',
                'parent_id'   => null,
                'visibility'  => 1,
            ]);

        $accessPolicy1 = $this->fixtures->create('access_policy_authz_roles_map', [
            'id'            => '10accessPolicy',
            'privilege_id'  => $privilege1->getId(),
            'action'        => 'read',
            'authz_roles'   => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
        ]);

        $input = [
            HistoryLogs\Entity::ENTITY_TYPE     => 'access_policy_authz_roles_map',
            HistoryLogs\Entity::ENTITY_ID       => $accessPolicy1->getId(),
            HistoryLogs\Entity::MESSAGE         => 'Test log',
            HistoryLogs\Entity::PREVIOUS_VALUE  => [],
            HistoryLogs\Entity::NEW_VALUE       => $accessPolicy1->toArray(),
            HistoryLogs\Entity::OWNER_TYPE      => 'merchant',
            HistoryLogs\Entity::OWNER_ID        => self::DEFAULT_X_MERCHANT_ID,
        ];

        $this->app['basicauth']->setUser($user1);

        $response = (new HistoryLogs\Service())->create($input);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__]['data'], $response);
    }

    protected function createMerchantUserMappingInLiveAndTest(string $userId, string $merchantId, string $role, string $roleId = null)
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
