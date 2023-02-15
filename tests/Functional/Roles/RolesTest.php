<?php

namespace RZP\Tests\Functional\Roles;

use DB;
use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Permission;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\Merchant\RazorpayX\RolePermissionChange;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class RolesTest extends TestCase
{
    use DbEntityFetchTrait;
    use HeimdallTrait;

    const DEFAULT_X_MERCHANT_ID = '100000merchant';
    const EXISTING_MERCHANT_FOR_INVITED_USER_ID = '10000000000001';

    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->unitTestCase = new \Tests\Unit\TestCase();

        $this->testDataFilePath = __DIR__ . '/helpers/RolesTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
        ]);

        $this->addAssignablePermissionsToOrg($this->org);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->proxyAuth();
    }

    public function testFetchRolesWithNoExistingFinanceUser()
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
        $user7 = $this->fixtures->user->createEntityInTestAndLive('user', []);
        $user8 = $this->fixtures->user->createEntityInTestAndLive('user', []);
        $user9 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC 1', 'id' => '100customRole1','org_id' => "100000razorpay"]);

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, $customRole1['id']);
        $this->createMerchantUserMappingInLiveAndTest($user2['id'], self::DEFAULT_X_MERCHANT_ID, $customRole1['id']);
        $this->createMerchantUserMappingInLiveAndTest($user3['id'], self::DEFAULT_X_MERCHANT_ID, $customRole1['id']);

        $customRole2 = $this->fixtures->create('roles', ['name' => 'CAC 2', 'id' => '100customRole2','org_id' => "100000razorpay"]);

        $this->createMerchantUserMappingInLiveAndTest($user4['id'], self::DEFAULT_X_MERCHANT_ID, $customRole2['id']);
        $this->createMerchantUserMappingInLiveAndTest($user5['id'], self::DEFAULT_X_MERCHANT_ID, $customRole2['id']);

        $customRole3 = $this->fixtures->create('roles', ['name' => 'CAC 3', 'id' => '100customRole3', 'org_id' => "100000razorpay"]);

        $this->createMerchantUserMappingInLiveAndTest($user6['id'], self::DEFAULT_X_MERCHANT_ID, $customRole3['id']);

        $this->createMerchantUserMappingInLiveAndTest($user7['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');
        $this->createMerchantUserMappingInLiveAndTest($user8['id'], self::DEFAULT_X_MERCHANT_ID, 'vendor');

        $response = $this->startTest();
    }

    public function testFetchRolesWithExistingFinanceUser()
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
        $user7 = $this->fixtures->user->createEntityInTestAndLive('user', []);
        $user8 = $this->fixtures->user->createEntityInTestAndLive('user', []);
        $user9 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC C', 'id' => '100customRole1','org_id' => "100000razorpay"]);

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, $customRole1['id']);
        $this->createMerchantUserMappingInLiveAndTest($user2['id'], self::DEFAULT_X_MERCHANT_ID, $customRole1['id']);
        $this->createMerchantUserMappingInLiveAndTest($user3['id'], self::DEFAULT_X_MERCHANT_ID, $customRole1['id']);

        $customRole2 = $this->fixtures->create('roles', ['name' => 'CAC B', 'id' => '100customRole2','org_id' => "100000razorpay"]);

        $this->createMerchantUserMappingInLiveAndTest($user4['id'], self::DEFAULT_X_MERCHANT_ID, $customRole2['id']);
        $this->createMerchantUserMappingInLiveAndTest($user5['id'], self::DEFAULT_X_MERCHANT_ID, $customRole2['id']);

        $customRole3 = $this->fixtures->create('roles', ['name' => 'CAC A', 'id' => '100customRole3', 'org_id' => "100000razorpay"]);

        $this->createMerchantUserMappingInLiveAndTest($user6['id'], self::DEFAULT_X_MERCHANT_ID, $customRole3['id']);

        $this->createMerchantUserMappingInLiveAndTest($user7['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');
        $this->createMerchantUserMappingInLiveAndTest($user8['id'], self::DEFAULT_X_MERCHANT_ID, 'finance_l1');

        $response = $this->startTest();
    }

    public function testFetchRolesWithStandardRolesOnly()
    {
        $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $response = $this->startTest();
    }

    public function testFetchRoleByIdCustomRole()
    {
        $this->createPrivileges();

        $merchant = $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC 2', 'id' => '100customRole2', 'org_id' => "100000razorpay"]);

        $this->testData[__FUNCTION__]['request']['url'] = '/cac/role/role_'.$customRole1['id'];

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');

        $this->fixtures->create('role_access_policy_map',
            [
                'role_id' => '100customRole2',
                'authz_roles'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'access_policy_ids' => ['accessPolicy10', 'accessPolicy11', 'accessPolicy13'],
            ]);

        $this->startTest();
    }

    public function testFetchSelfRole()
    {
        $this->createPrivileges();

        $merchant = $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC 2', 'id' => '100customRole2', 'org_id' => "100000razorpay"]);

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');

        $this->fixtures->create('role_access_policy_map',
            [
                'role_id' => '100customRole2',
                'authz_roles'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'access_policy_ids' => ['accessPolicy10', 'accessPolicy11', 'accessPolicy13'],
            ]);

        $this->startTest();
    }

    // currently this API is scoped out for phase 1, we will be needing this in future
    /*public function testDeleteRole()
    {
        $lastRoleEntity = $this->getDbLastEntity('roles');
        $lastRoleMapEntity = $this->getDbLastEntity('role_access_policy_map');

        $this->createPrivileges();

        $merchant = $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

//        $this->createStandardRole();

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC 2', 'id' => '100customRole2', 'org_id' => "100000razorpay"]);

        $this->fixtures->create('role_access_policy_map',
            [
                'role_id' => '100customRole2',
                'authz_roles'   => ['authz_roles_4', 'authz_roles_5', 'authz_roles_6'],
                'access_policy_ids' => ['accessPolicy14', 'accessPolicy15', 'accessPolicy16'],
            ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/cac/role/100customRole2';

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $this->startTest();

        $afterTestRoleLastEntity = $this->getDbLastEntity('roles');
        $this->assertEquals($lastRoleEntity, $afterTestRoleLastEntity);

        $afterTestRoleMapLastEntity = $this->getDbLastEntity('role_access_policy_map');
        $this->assertEquals($lastRoleMapEntity, $afterTestRoleMapLastEntity);
    }*/

    public function testCreateRole()
    {
        $this->createPrivileges();

        $merchant = $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $this->testData[__FUNCTION__]['request']['url'] = '/cac/role/';

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');

        $this->startTest();

        $lastRoleMapEntity = $this->getDbLastEntity('role_access_policy_map')->toArrayPublic();

        $accessPolicyIds = $lastRoleMapEntity['access_policy_ids'];

        $this->assertEquals($this->testData[__FUNCTION__]['request']['content']['access_policy_ids'], $accessPolicyIds);
    }

    public function testEditRole()
    {
        $this->createPrivileges();

        $merchant = $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC 1', 'id' => '100customRole1', 'org_id' => "100000razorpay"]);

        $this->testData[__FUNCTION__]['request']['url'] = '/cac/role/role_'.$customRole1['id'];

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');

        $this->fixtures->create('role_access_policy_map',
            [
                'role_id' => '100customRole1',
                'authz_roles'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'access_policy_ids' => ['XaccessPolicy1', 'XaccessPolicy2', 'XaccessPolicy3'],
            ]);

        $this->startTest();

        $lastRoleMapEntity = $this->getDbLastEntity('role_access_policy_map')->toArrayPublic();

        $accessPolicyIds = $lastRoleMapEntity['access_policy_ids'];

        $this->assertEquals($this->testData[__FUNCTION__]['request']['content']['access_policy_ids'], $accessPolicyIds);
    }


    public function testEditRoleSendEmail()
    {
        Mail::fake();
        $this->createPrivileges();

        $merchant = $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC 1', 'id' => '100customRole1', 'org_id' => "100000razorpay"]);

        $this->testData[__FUNCTION__]['request']['url'] = '/cac/role/role_'.$customRole1['id'];

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, '100customRole1');

        $this->fixtures->create('role_access_policy_map',
            [
                'role_id' => '100customRole1',
                'authz_roles'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'access_policy_ids' => ['XaccessPolicy1', 'XaccessPolicy2', 'XaccessPolicy3'],
            ]);

        $this->startTest();

        $lastRoleMapEntity = $this->getDbLastEntity('role_access_policy_map')->toArrayPublic();

        $accessPolicyIds = $lastRoleMapEntity['access_policy_ids'];

        $this->assertEquals($this->testData[__FUNCTION__]['request']['content']['access_policy_ids'], $accessPolicyIds);

        Mail::assertQueued(RolePermissionChange::class, function($mail) {
            $this->assertEquals($mail->view,"emails.merchant.role_permission_change");
            return true;
        });
    }

    public function createStandardRole(string $role = 'owner_test', string $mid = self::DEFAULT_X_MERCHANT_ID)
    {

        DB::connection('live')->table('access_control_roles')
            ->insert([
                'id'          => $role,
                'name'        => $role,
                'description' => 'Standard role - '. $role,
                'merchant_id' => self::DEFAULT_X_MERCHANT_ID,
                'type'        => 'standard',
                'created_by'  => 'test@rzp.com',
                'updated_by'  => 'test@rzp.com',
                'created_at'  => Carbon::now(Timezone::IST)->timestamp,
                'updated_at'  => Carbon::now(Timezone::IST)->timestamp,
                'org_id'      => '100000razorpay'
            ]);

        DB::connection('test')->table('access_control_roles')
            ->insert([
                'id'          => $role,
                'name'        => $role,
                'description' => 'Standard role - '.$role,
                'merchant_id' => self::DEFAULT_X_MERCHANT_ID,
                'type'        => 'standard',
                'created_by'  => 'test@rzp.com',
                'updated_by'  => 'test@rzp.com',
                'created_at'  => Carbon::now(Timezone::IST)->timestamp,
                'updated_at'  => Carbon::now(Timezone::IST)->timestamp,
                'org_id'      => '100000razorpay'
            ]);
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
            'id'            => 'XaccessPolicy1',
            'privilege_id'  => $privilege2->getId(),
            'action'        => 'view',
            'authz_roles'   => ['authz_roles_1'],
        ]);

        $accessPolicy2 = $this->fixtures->create('access_policy_authz_roles_map', [
            'id'            => 'XaccessPolicy2',
            'privilege_id'  => $privilege2->getId(),
            'action'        => 'create',
            'authz_roles'   => ['authz_roles_2'],
        ]);

        $accessPolicy3 = $this->fixtures->create('access_policy_authz_roles_map', [
            'id'            => 'XaccessPolicy3',
            'privilege_id'  => $privilege3->getId(),
            'action'        => 'view',
            'authz_roles'   => ['authz_roles_3'],
        ]);

        $accessPolicy4 = $this->fixtures->create('access_policy_authz_roles_map', [
            'id'            => 'XaccessPolicy4',
            'privilege_id'  => $privilege3->getId(),
            'action'        => 'create',
            'authz_roles'   => ['authz_roles_4'],
        ]);
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

    public function testFetchRoleMap()
    {
        $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC 1', 'id' => '100customRole1','org_id' => "100000razorpay"]);

        $customRole2 = $this->fixtures->create('roles', ['name' => 'CAC 2', 'id' => '100customRole2','org_id' => "100000razorpay"]);

        $customRole3 = $this->fixtures->create('roles', ['name' => 'CAC 3', 'id' => '100customRole3', 'org_id' => "100000razorpay"]);

        $response = $this->startTest();
    }

    public function testFetchRoleMapWithOnlyStandardRoles()
    {
        $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $response = $this->startTest();
    }

    public function testGetRolesForPermissionName()
    {
        $role = $this->fixtures->create(
            'role',
            ['org_id' => $this->org->getId(), 'name' => 'Finance']);

        $merchant = $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'rx_custom_access_control_disabled')
                    {
                        return 'on';
                    }

                    return 'control';
                }));

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = '100000merchant';

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $result = $this->startTest();

        $this->assertCount(1, $result);
    }

    public function testFetchAuthZRolesByRoleIdSuccess()
    {
        $this->createPrivileges();

        $merchant = $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $customRole1 = $this->fixtures->create('roles', ['name' => 'CAC 2', 'id' => '100customRole2', 'org_id' => "100000razorpay"]);

        $this->testData[__FUNCTION__]['request']['url'] = '/cac/role/'.$customRole1['id'].'/authz_roles';
        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = $merchant['id'];

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');

        $this->fixtures->create('role_access_policy_map',
            [
                'role_id' => '100customRole2',
                'authz_roles'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'access_policy_ids' => ['accessPolicy10', 'accessPolicy11', 'accessPolicy13'],
            ]);

        $this->ba->capitalCardsAuth();

        $this->startTest();

        $lastRoleMapEntity = $this->getDbLastEntity('role_access_policy_map')->toArrayPublic();

        $authZRoles = $lastRoleMapEntity['authz_roles'];

        $this->assertEquals($this->testData[__FUNCTION__]['response']['content']['authz_roles'], $authZRoles);
    }

    public function testFetchAuthZRolesByRoleIdFailure()
    {
        $this->ba->capitalCardsAuth();

        $this->startTest();
    }
}
