<?php

namespace RZP\Tests\Functional\AccessControlPrivileges;

use DB;
use \WpOrg\Requests\Response;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class AccessControlPrivileges extends TestCase
{
    const DEFAULT_X_MERCHANT_ID = '100000merchant';
    const EXISTING_MERCHANT_FOR_INVITED_USER_ID = '10000000000001';
    private $str;

    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->unitTestCase = new \Tests\Unit\TestCase();

        $this->testDataFilePath = __DIR__ . '/helpers/AccessControlPrivilegesTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testFetchAllPrivileges()
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

        $this->createPrivileges();

        $response = $this->startTest();
    }

    public function createPrivileges()
    {
        $privilege1 = $this->fixtures->create('access_control_privileges',
            [
                'id'          => '1000privilege1',
                'name'        => 'Account Setting',
                'label'       => 'account_setting',
                'description' => 'A/c setting test description',
                'parent_id'   => null,
                'visibility'  => 1,
            ]);

        $privilege2 = $this->fixtures->create('access_control_privileges',
            [
                'id'          => '1000privilege2',
                'name'        => 'Tax Setting',
                'label'       => 'tax_setting',
                'description' => 'Tax setting test description',
                'extra_data'  => [
                    'tool_tip'      => 'PRIVILEGE 2',
                ],
                'parent_id'   => $privilege1->getId(),
                'visibility'  => 1,
            ]);

        $this->str = '1000privilege3';
        $privilege3 = $this->fixtures->create('access_control_privileges',
            [
                'id'          => '' . $this->str . '',
                'name'        => 'Business Setting',
                'label'       => 'business_setting',
                'description' => 'Business setting test description',
                'extra_data'  => [
                    'tool_tip'      => 'PRIVILEGE 3',
                ],
                'parent_id'   => $privilege1->getId(),
                'visibility'  => 1,
            ]);

        $accessPolicy1 = $this->fixtures->create('access_policy_authz_roles_map', [
            'privilege_id'  => $privilege2->getId(),
            'action'        => 'view',
            'authz_roles'   => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
        ]);

        $accessPolicy2 = $this->fixtures->create('access_policy_authz_roles_map', [
            'privilege_id'  => $privilege2->getId(),
            'action'        => 'create',
            'authz_roles'   => ['authz_roles_1_4', 'authz_roles_1_5'],
        ]);

        $accessPolicy3 = $this->fixtures->create('access_policy_authz_roles_map', [
            'privilege_id'  => $privilege3->getId(),
            'action'        => 'view',
            'authz_roles'   => ['authz_roles_2_1', 'authz_roles_2_2', 'authz_roles_2_3'],
        ]);

        $accessPolicy4 = $this->fixtures->create('access_policy_authz_roles_map', [
            'privilege_id'  => $privilege3->getId(),
            'action'        => 'create',
            'authz_roles'   => ['authz_roles_2_4', 'authz_roles_2_5'],
        ]);
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
