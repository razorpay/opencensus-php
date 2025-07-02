<?php

namespace RZP\Tests\Functional\Roles;

use DB;
use Mail;
use Cache;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Permission;
use RZP\Models\Roles\Entity;
use RZP\Models\Roles\Constants;
use RZP\Services\RazorXClient;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Roles\Service as RolesService;
use AuthzAdmin\Client\Model as AuthzAdminModel;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\Merchant\RazorpayX\RolePermissionChange;
use RZP\Tests\Functional\Helpers\PrivateMethodTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class RolesTest extends TestCase
{
    use DbEntityFetchTrait;
    use HeimdallTrait;
    use PrivateMethodTrait;

    const DEFAULT_MERCHANT_ID = '10000000000000';
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

        $this->mockCACMigrationExperiment('inactive');
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
            ->onlyMethods(['getTreatment'])
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

    public function testMigrateAuthz()
    {
        // 1. create entities
        $this->fixtures->create('roles', [
            'id'            => '100customRole2',
            'name'          => 'custom role',
            'type'          => 'custom',
            'merchant_id'   => self::DEFAULT_X_MERCHANT_ID,
            'created_by'    => '1000000000user',
            'updated_by'    => '1000000000user',
            'description'   => 'this is a custom role',
        ]);


        $this->fixtures->create('role_access_policy_map', [
            'role_id'           => '100customRole2',
            'authz_roles'       => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            'access_policy_ids' => ['accessPolicy10', 'accessPolicy11', 'accessPolicy13'],
        ]);

        // 2. set mocks
        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldReceive('adminAPIMigrateRole')
                ->withArgs(function($req){
                    $expectedReq = new AuthzAdminModel\V1MigrateRoleRequest([
                        'roles'     => [
                            new AuthzAdminModel\V1MigrateRole([
                                'id'            => '100customRole2',
                                'name'          => 'custom role',
                                'type'          => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                                'owner_type'    => 'merchant',
                                'owner_id'      => self::DEFAULT_X_MERCHANT_ID,
                                'child_names'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                                'created_by'    => '1000000000user',
                                'updated_by'    => '1000000000user',
                                'description'   => 'this is a custom role',
                            ])
                        ],
                        'org_id'    => 'razorpayx'
                    ]);

                    $this->assertEquals($expectedReq, $req);

                    return true;
                })
                ->once()
                ->andReturn(new AuthzAdminModel\V1MigrateRoleResponse([
            'success'   => true,
        ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        // 3. execute test
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMigrateApi()
    {
        /**
         * Migrate 2 roles in this test.
         * 1. Role#1 does not exist in DB so it should get created
         * 2. Role#2 exists in DB so it should get updated
         */

        // 1. create dependent entities
        $this->fixtures->create('access_policy_authz_roles_map', [
            'id'            => 'accessPolicy10',
            'privilege_id'  => '1000privilege1',
            'action'        => 'view',
            'authz_roles'   => ['authz_roles_1'],
        ]);

        $this->fixtures->create('access_policy_authz_roles_map', [
            'id'            => 'accessPolicy20',
            'privilege_id'  => '1000privilege1',
            'action'        => 'create',
            'authz_roles'   => ['authz_roles_2'],
        ]);

        // 2. create entities for test
        $this->fixtures->create('roles', [
            'id'                => '100customRole3',
            'merchant_id'       => '100000merchant',
            'name'              => 'CAC 3',
            'type'              => 'standard', // should be updated in the test
            'description'       => 'Test custom role (original)', // should be updated in the test
            'created_by'        => '100000merchant',
            'updated_by'        => '100000merchant',
            'org_id'            => '100000razorpay',
            'product'           => 'banking',
        ]);

        $this->fixtures->create('role_access_policy_map', [ // should be updated in the test
            'role_id'           => '100customRole3',
            'authz_roles'       => ['authz_roles_1', 'authz_roles_2'],
            'access_policy_ids' => ['accessPolicy10', 'accessPolicy20'],
        ]);

        // 3. execute test & assert response
        $this->ba->adminAuth();

        $this->startTest();

        $roleRepo = new \RZP\Models\Roles\Repository();

        $roleMapRepo = new \RZP\Models\RoleAccessPolicyMap\Repository();

        $role1 = $roleRepo->fetchRole('100customRole2');

        $role2 = $roleRepo->fetchRole('100customRole3');

        $roleMap1 = $roleMapRepo->findByRoleId('100customRole2');

        $roleMap2 = $roleMapRepo->findByRoleId('100customRole3');

        // assertions for role1 created
        $this->assertArraySelectiveEquals([
            'id'                => '100customRole2',
            'merchant_id'       => '100000merchant',
            'name'              => 'CAC 2',
            'type'              => 'custom',
            'description'       => 'Test custom role',
            'created_by'        => '100000merchant',
            'updated_by'        => '100000merchant',
        ], $role1->toArray());
        $this->assertEquals(['authz_roles_1'], $roleMap1->getAuthzRoles());
        $this->assertEquals(['accessPolicy10'], $roleMap1->getAccessPolicyIds());

        // assertions for role2 updated
        $this->assertArraySelectiveEquals([
            'id'                => '100customRole3',
            'merchant_id'       => '100000merchant',
            'name'              => 'CAC 3',
            'type'              => 'custom',
            'description'       => 'Test custom role (updated)',
            'created_by'        => '100000merchant',
            'updated_by'        => '100000merchant',
        ], $role2->toArray());
        $this->assertEquals(['authz_roles_1'], $roleMap2->getAuthzRoles());
        $this->assertEquals(['accessPolicy10'], $roleMap2->getAccessPolicyIds());
    }

    public function testCreateRoleOnAuthz()
    {
        // 1. prepare test data
        $this->ba->proxyAuth();

        $request = [
            'method'  => 'post',
            'url'     => '/cac/role',
            'content' => [
                'name'          => 'custom role',
                'description'   => 'this is a custom role',
                'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ];

        // 2. set mocks
        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        // mock is required to pass the basic auth check
        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $authzAdminClientMock->shouldReceive('adminAPICreateRole')
        ->withArgs(function($req, $passport){
            $expectedReq = new AuthzAdminModel\V1Role([
                'id'            => null,
                'name'          => 'custom role',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'this is a custom role',
            ]);

            $this->assertEquals($expectedReq, $req);
            $this->assertNotNull($passport);

            return true;
        })
        ->once()
        ->andReturn(new AuthzAdminModel\V1Role([
            'id'            => '100customRole2',
            'name'          => 'custom role',
            'org_id'        => 'razorpayx',
            'type'          => AuthzAdminModel\V1RolePolicyType::CUSTOM,
            'owner_type'    => 'merchant',
            'owner_id'      => self::DEFAULT_MERCHANT_ID,
            'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            'created_by'    => 'MerchantUser01',
            'children'      => null,
            'description'   => 'this is a custom role',
        ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals([
            'id'            => '100customRole2',
            'name'          => 'custom role',
            'type'          => 'custom',
            'merchant_id'   => self::DEFAULT_MERCHANT_ID,
            'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            'created_by'    => 'MerchantUser01',
            'description'   => 'this is a custom role',
        ], $response);
    }

    public function testUpdateStandardRoleOnAuthz()
    {
        // 1. prepare test data
        $this->ba->proxyAuth();

        $request = [
            'method'  => 'patch',
            'url'     => '/cac/role/owner',
            'content' => [
                'name'          => 'custom role',
                'description'   => 'this is a custom role',
                'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ];

        // 2. set mocks
        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldNotReceive('adminAPIUpdateRole');

        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $this->expectException(\RZP\Exception\BadRequestValidationFailureException::class);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['error']['description'], "Can't Edit Standard Roles");
    }

    public function testUpdateCustomRoleOnAuthz()
    {
        // 1. prepare test data
        $this->ba->proxyAuth();

        $request = [
            'method'  => 'patch',
            'url'     => '/cac/role/100customRole2',
            'content' => [
                'name'          => 'custom role',
                'description'   => 'this is a custom role',
                'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ];

        // 2. set mocks
        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $authzAdminClientMock->shouldReceive('adminAPIUpdateRole')
        ->withArgs(function($req, $passport){
            $expectedReq = new AuthzAdminModel\V1Role([
                'id'            => '100customRole2',
                'name'          => 'custom role',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'this is a custom role',
            ]);

            $this->assertEquals($expectedReq, $req);
            $this->assertNotNull($passport);

            return true;
        })
        ->once()
        ->andReturn(new AuthzAdminModel\V1Role([
            'id'            => '100customRole2',
            'name'          => 'custom role',
            'org_id'        => 'razorpayx',
            'type'          => AuthzAdminModel\V1RolePolicyType::CUSTOM,
            'owner_type'    => 'merchant',
            'owner_id'      => self::DEFAULT_MERCHANT_ID,
            'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            'created_by'    => 'MerchantUser01',
            'children'      => null,
            'description'   => 'this is a custom role',
        ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals([
            'id'            => '100customRole2',
            'name'          => 'custom role',
            'type'          => 'custom',
            'merchant_id'   => self::DEFAULT_MERCHANT_ID,
            'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            'created_by'    => 'MerchantUser01',
            'description'   => 'this is a custom role',
        ], $response);
    }

    private function mockCACMigrationExperiment($result, $merchantId = null)
    {
        $splitzMock = \Mockery::mock(SplitzService::class, [$this->app])->makePartial();

        $input = [
            'id'            => $merchantId ?? self::DEFAULT_X_MERCHANT_ID,
            'experiment_id' => env('CAC_MIGRATION_SPLITZ_EXPERIMENT_ID'),
        ];

        $response = [
            'response' => [
                'variant' => [
                    'name' => $result,
                ]
            ]
        ];

        $splitzMock->shouldReceive('evaluateRequest')
            ->zeroOrMoreTimes()
            ->with($input)
            ->andReturn($response);

        $this->app->instance('splitzService', $splitzMock);
    }

    public function testFetchRolesWithStandardRolesCACMigration()
    {
        $this->ba->proxyAuth();

        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        // mock is required to pass the basic auth check
        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $authzAdminClientMock->shouldReceive('adminAPIListRole')
            ->once()
            ->withArgs(function($paginationToken, $roleNamePrefix, $roleNames, $roleIds, $orgId, $keyId, $keyOwnerType, $keyOwnerId, $ownerIds, $type, $types)
            {
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals([Entity::ORG_ID_FOR_ROLES], $ownerIds);
                $this->assertEquals(null, $roleNames);
                $this->assertEquals(null, $roleIds);
                $this->assertEquals(['ROLE_POLICY_TYPE_STANDARD'], $types);

                return true;
            })
            ->andReturn(new AuthzAdminModel\V1ListRoleResponse([
                'items' => [
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole1',
                        'name' => 'Admin',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Perform all tasks except for team management',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole2',
                        'name' => 'Finance',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_4', 'authz_roles_5', 'authz_roles_6'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Create and issue payouts and contacts',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole3',
                        'name' => 'Operations',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_7', 'authz_roles_8', 'authz_roles_9'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Create and manage Payout Links',
                    ]),
                ]
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $response = $this->startTest();
    }

    public function testFetchRoleMapCACMigration()
    {
        $this->ba->proxyAuth();

        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        // mock is required to pass the basic auth check
        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $authzAdminClientMock->shouldReceive('adminAPIListRole')
            ->once()
            ->withArgs(function($paginationToken, $roleNamePrefix, $roleNames, $roleIds, $orgId, $keyId, $keyOwnerType, $keyOwnerId, $ownerIds, $type, $types)
            {
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals([self::DEFAULT_MERCHANT_ID, Entity::ORG_ID_FOR_ROLES], $ownerIds);
                $this->assertEquals(null, $roleNames);
                $this->assertEquals(null, $roleIds);
                $this->assertEquals(['ROLE_POLICY_TYPE_CUSTOM', 'ROLE_POLICY_TYPE_STANDARD'], $types);

                return true;
            })
            ->andReturn(new AuthzAdminModel\V1ListRoleResponse([
                'items' => [
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole1',
                        'name' => 'Admin',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Perform all tasks except for team management',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole2',
                        'name' => 'Finance',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_4', 'authz_roles_5', 'authz_roles_6'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Create and issue payouts and contacts',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole3',
                        'name' => 'Operations',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_7', 'authz_roles_8', 'authz_roles_9'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Create and manage Payout Links',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100customRole1',
                        'name' => 'CAC 1',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                        'owner_type' => 'merchant',
                        'owner_id' => self::DEFAULT_MERCHANT_ID,
                        'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Test custom role',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100customRole2',
                        'name' => 'CAC 2',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                        'owner_type' => 'merchant',
                        'owner_id' => self::DEFAULT_MERCHANT_ID,
                        'child_ids' => ['authz_roles_4', 'authz_roles_5', 'authz_roles_6'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Test custom role',
                    ]),
                ]
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $response = $this->startTest();
    }

    public function testFetchRoleMapAdminCACMigration()
    {
        $merchant = $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID]);

        $this->mockCACMigrationExperiment('active', self::DEFAULT_X_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldReceive('adminAPIListRole')
            ->once()
            ->withArgs(function($paginationToken, $roleNamePrefix, $roleNames, $roleIds, $orgId, $keyId, $keyOwnerType, $keyOwnerId, $ownerIds, $type, $types)
            {
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals([self::DEFAULT_X_MERCHANT_ID, Entity::ORG_ID_FOR_ROLES], $ownerIds);
                $this->assertEquals(null, $roleNames);
                $this->assertEquals(null, $roleIds);
                $this->assertEquals(['ROLE_POLICY_TYPE_CUSTOM', 'ROLE_POLICY_TYPE_STANDARD'], $types);

                return true;
            })
            ->andReturn(new AuthzAdminModel\V1ListRoleResponse([
                'items' => [
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole1',
                        'name' => 'Admin',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Perform all tasks except for team management',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole2',
                        'name' => 'Finance',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_4', 'authz_roles_5', 'authz_roles_6'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Create and issue payouts and contacts',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole3',
                        'name' => 'Operations',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_7', 'authz_roles_8', 'authz_roles_9'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Create and manage Payout Links',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100customRole1',
                        'name' => 'CAC 1',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                        'owner_type' => 'merchant',
                        'owner_id' => self::DEFAULT_MERCHANT_ID,
                        'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Test custom role',
                    ]),
                    new AuthzAdminModel\V1Role([
                        'id' => '100customRole2',
                        'name' => 'CAC 2',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                        'owner_type' => 'merchant',
                        'owner_id' => self::DEFAULT_MERCHANT_ID,
                        'child_ids' => ['authz_roles_4', 'authz_roles_5', 'authz_roles_6'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => 'Test custom role',
                    ]),
                ]
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = '100000merchant';

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $response = $this->startTest();
    }

    public function testUpdateRoleAccessPolicyMap()
    {
        // 1. create dependencies
        $this->createPrivileges();

        $this->fixtures->create('role_access_policy_map',
        [
            'role_id' => '100customRole1',
            'authz_roles'   => ['authz_roles_1', 'authz_roles_2'],
            'access_policy_ids' => ['XaccessPolicy1', 'XaccessPolicy2'],
        ]);

        // acts as control-group: update operations shouldn't impact this role
        $this->fixtures->create('role_access_policy_map',
        [
            'role_id' => '100customRole2',
            'authz_roles'   => ['authz_roles_1'],
            'access_policy_ids' => ['XaccessPolicy1'],
        ]);

        $this->ba->adminAuth();

        $dataToReplace = [
            'request'  => [
                'content'   => [
                    'role_ids'          => ['100customRole1'],
                    'access_policy_ids' => ['XaccessPolicy3', 'XaccessPolicy4', 'XaccessPolicy1', 'XaccessPolicy2', 'XaccessPolicy4'], // adding duplicates to test deduplication
                    'operation'         => 'append',
                ],
            ],
        ];

        // 2. test appending access policy
        $this->startTest($dataToReplace);

        // 3. assert append access policy
        $roleAccessPolicyMapRepo = new \RZP\Models\RoleAccessPolicyMap\Repository;

        $updatedCustomRole1 = $roleAccessPolicyMapRepo->findByRoleId('100customRole1');
        $updatedCustomRole2 = $roleAccessPolicyMapRepo->findByRoleId('100customRole2');

        $this->assertEquals(['XaccessPolicy1', 'XaccessPolicy2', 'XaccessPolicy3', 'XaccessPolicy4'], $updatedCustomRole1->getAccessPolicyIds());
        $this->assertEquals(['authz_roles_1', 'authz_roles_2', 'authz_roles_3', 'authz_roles_4'], $updatedCustomRole1->getAuthzRoles());

        $this->assertEquals(['XaccessPolicy1'], $updatedCustomRole2->getAccessPolicyIds());
        $this->assertEquals(['authz_roles_1'], $updatedCustomRole2->getAuthzRoles());

        // 4. test removing access policy
        $dataToReplace = [
            'request'  => [
                'content'   => [
                    'role_ids'          => ['100customRole1'],
                    'access_policy_ids' => ['XaccessPolicy2','XaccessPolicy4'],
                    'operation'         => 'remove',
                ],
            ],
        ];

        $this->startTest($dataToReplace);

        // 5. assert remove access policy
        $updatedCustomRole1 = $roleAccessPolicyMapRepo->findByRoleId('100customRole1');
        $updatedCustomRole2 = $roleAccessPolicyMapRepo->findByRoleId('100customRole2');

        $this->assertEquals(['XaccessPolicy1','XaccessPolicy3'], $updatedCustomRole1->getAccessPolicyIds());
        $this->assertEquals(['authz_roles_1', 'authz_roles_3'], $updatedCustomRole1->getAuthzRoles());

        $this->assertEquals(['XaccessPolicy1'], $updatedCustomRole2->getAccessPolicyIds());
        $this->assertEquals(['authz_roles_1'], $updatedCustomRole2->getAuthzRoles());
    }

    public function testLocateRoleUsingExperiment_MxInBA_ExpOn()
    {
        // 1. create dependencies
        $merchant = $this->fixtures->create('merchant', [
            'id' => self::DEFAULT_X_MERCHANT_ID
        ]);

        $role = $this->fixtures->create('roles',[
            'name'          => 'CAC 2',
            'id'            => '100customRole2',
            'org_id'        => '100000razorpay',
            'merchant_id'   => $merchant['id'],
        ]);

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(true, true, true);

        // 3. execute test & assert response
        $method = $this->getPrivateMethod(RolesService::class, 'locateRoleUsingExperiment');
        $res = $method->invokeArgs(new RolesService(), [$role['id']]);

        $this->assertEquals([
            'location'      => 'authz',
            'merchant_id'   => $merchant['id']
        ], $res);
    }

    public function testLocateRoleUsingExperiment_MxInBA_ExpOff()
    {
        // 1. create dependencies
        $merchant = $this->fixtures->create('merchant', [
            'id' => self::DEFAULT_X_MERCHANT_ID
        ]);

        $role = $this->fixtures->create('roles',[
            'name'          => 'CAC 2',
            'id'            => '100customRole2',
            'org_id'        => '100000razorpay',
            'merchant_id'   => $merchant['id'],
        ]);

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(true, true, false);

        // 3. execute test & assert response
        $method = $this->getPrivateMethod(RolesService::class, 'locateRoleUsingExperiment');
        $res = $method->invokeArgs(new RolesService(), [$role['id']]);

        $this->assertEquals([
            'location'      => 'api',
            'merchant_id'   => $merchant['id'],
            'role'          => null,
        ], $res);
    }

    public function testLocateRoleUsingExperiment_MxNotInBA_RoleNotInAPI()
    {
        // 1. create dependencies
        // nothing to create

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, false, false);

        // 3. execute test & assert response
        $method = $this->getPrivateMethod(RolesService::class, 'locateRoleUsingExperiment');
        $res = $method->invokeArgs(new RolesService(), ['100customRole9']);

        $this->assertEquals([
            'location'      => 'authz',
            'merchant_id'   => null
        ], $res);
    }

    public function testLocateRoleUsingExperiment_MxNotInBA_RoleInAPI_ExpOn()
    {
        // 1. create dependencies
        $merchant = $this->fixtures->create('merchant', [
            'id' => self::DEFAULT_X_MERCHANT_ID
        ]);

        $role = $this->fixtures->create('roles',[
            'name'          => 'CAC 2',
            'id'            => '100customRole2',
            'org_id'        => '100000razorpay',
            'merchant_id'   => $merchant['id'],
        ]);

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, true, true);

        // 3. execute test & assert response
        $method = $this->getPrivateMethod(RolesService::class, 'locateRoleUsingExperiment');
        $res = $method->invokeArgs(new RolesService(), [$role['id']]);

        $this->assertEquals([
            'location'      => 'authz',
            'merchant_id'   => $merchant['id']
        ], $res);
    }

    public function testLocateRoleUsingExperiment_MxNotInBA_RoleInAPI_ExpOff()
    {
        // 1. create dependencies
        $merchant = $this->fixtures->create('merchant', [
            'id' => self::DEFAULT_X_MERCHANT_ID
        ]);

        $role = $this->fixtures->create('roles',[
            'name'          => 'CAC 2',
            'id'            => '100customRole2',
            'org_id'        => '100000razorpay',
            'merchant_id'   => $merchant['id'],
        ]);

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, true, false);

        // 3. execute test & assert response
        $method = $this->getPrivateMethod(RolesService::class, 'locateRoleUsingExperiment');
        $res = $method->invokeArgs(new RolesService(), [$role['id']]);

        $this->assertArraySelectiveEquals([
            'location'      => 'api',
            'merchant_id'   => $merchant['id']
        ], $res);

        $this->assertEquals($role['id'], $res['role']->getId());
    }

    private function setMocksForLocateRoleUsingExperiment($mockBasicAuth, $shouldCallSplitz, $migrationExperimentEnabled)
    {
        // 1. set basicAuth mock
        if ($mockBasicAuth)
        {
            $merchant = (new \RZP\Models\Merchant\Repository())->find(self::DEFAULT_X_MERCHANT_ID);

            $basicAuthMock = \Mockery::mock(\RZP\Http\BasicAuth\BasicAuth::class, [$this->app])->makePartial();

            $basicAuthMock->shouldReceive('getMerchant')
                ->zeroOrMoreTimes()
                ->andReturn($merchant);

            $this->app->instance('basicauth', $basicAuthMock);
        }

        // 2. set splitz mock
        if ($shouldCallSplitz)
        {
            $splitzMock = \Mockery::mock(\RZP\Services\SplitzService::class, [$this->app])->makePartial();

            $splitzMock->shouldReceive('evaluateRequest')
                ->once()
                ->with([
                    'id'            => self::DEFAULT_X_MERCHANT_ID,
                    'experiment_id' => env('CAC_MIGRATION_SPLITZ_EXPERIMENT_ID'),
                ])
                ->andReturn([
                    'response' => [
                        'variant' => [
                            'name' => $migrationExperimentEnabled ? 'active' : 'control',
                        ]
                    ]
                ]);

            $this->app->instance('splitzService', $splitzMock);
        }
    }

    public function testGetRoleUsingExperiment_API_Read()
    {
        // 1. create dependencies
        $merchant = $this->fixtures->create('merchant', [
            'id' => self::DEFAULT_X_MERCHANT_ID
        ]);

        $role = $this->fixtures->create('roles',[
            'id'            => '100customRole2',
            'name'          => 'CAC 2',
            'org_id'        => '100000razorpay',
            'type'          => 'custom',
            'merchant_id'   => $merchant['id'],
        ]);

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, true, false);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldNotReceive('adminAPIGetRole');

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        // 3. execute test & assert response
        $res = (new RolesService())->getRoleUsingExperiment($role['id']);

        $this->assertArraySelectiveEquals([
            'id'            => '100customRole2',
            'name'          => 'CAC 2',
            'merchant_id'   => '100000merchant',
            'type'          => 'custom',
        ], $res->toArray());
    }

    public function testGetRoleUsingExperiment_Authz_Read_RoleExistsOnAPI()
    {
        // 1. create dependencies
        $merchant = $this->fixtures->create('merchant', [
            'id' => self::DEFAULT_X_MERCHANT_ID
        ]);

        $role = $this->fixtures->create('roles',[
            'id'            => '100customRole2',
            'name'          => 'CAC 2',
            'org_id'        => '100000razorpay',
            'type'          => 'custom',
            'merchant_id'   => $merchant['id'],
        ]);

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, true, true);

        $this->mockAuthzAdminForGetRoleUsingExperiment();

        // 3. execute test & assert response
        $res = (new RolesService())->getRoleUsingExperiment('100customRole2');

        $this->assertArraySelectiveEquals([
            'id'            => '100customRole2',
            'name'          => 'custom role',
            'type'          => 'custom',
            'merchant_id'   => self::DEFAULT_X_MERCHANT_ID,
            'description'   => 'this is a custom role',
        ], $res->toArray());
    }

    public function testGetRoleUsingExperiment_Authz_Read_RoleDoesNotExistOnAPI()
    {
        // 1. create dependencies
        // nothing to create

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, false, true);

        $this->mockAuthzAdminForGetRoleUsingExperiment(false, null);

        // 3. execute test & assert response
        $res = (new RolesService())->getRoleUsingExperiment('100customRole2');

        $this->assertArraySelectiveEquals([
            'id'            => '100customRole2',
            'name'          => 'custom role',
            'type'          => 'custom',
            'merchant_id'   => self::DEFAULT_X_MERCHANT_ID,
            'description'   => 'this is a custom role',
        ], $res->toArray());
    }

    public function testGetRoleUsingExperiment_Authz_Read_RoleDoesNotBelongToCAC()
    {
        $this->setMocksForLocateRoleUsingExperiment(false, false, true);

        $res = (new RolesService())->getRoleUsingExperiment('sellerapp');

        $this->assertEquals(null, $res);
    }

    public function testGetRoleNameUsingExperiment()
    {
        // 1. create dependencies
        // nothing to create

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, false, true);

        $this->mockAuthzAdminForGetRoleUsingExperiment(false, null);

        $cacheKey = Constants::CACHE_KEY_ROLE_NAME . '100customRole2';

        Cache::store('redis');

        Cache::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once()
            ->with($cacheKey, 'custom role', Constants::ROLE_NAME_TTL)
            ->andReturn(null);

        // 3. execute test & assert response
        $res = (new RolesService())->getRoleNameUsingExperiment('100customRole2');

        $this->assertEquals('custom role', $res);
    }

    public function testGetRoleNameUsingExperimentNullRole()
    {
        // 1. create dependencies
        $merchant = $this->fixtures->create('merchant', [
            'id' => self::DEFAULT_X_MERCHANT_ID
        ]);

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(true, true, false);

        // 3. execute test & assert response
        $res = (new RolesService())->getRoleNameUsingExperiment('100customRole2');

        $this->assertNull($res);
    }

    public function testGetRoleNameUsingExperimentCacheHit()
    {
        // 1. create dependencies
        // nothing to create

        // 2. set mocks
        $cacheKey = Constants::CACHE_KEY_ROLE_NAME . '100customRole2';

        Cache::store('redis');

        Cache::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn('custom role');

        // 3. execute test & assert response
        $res = (new RolesService())->getRoleNameUsingExperiment('100customRole2');

        $this->assertEquals('custom role', $res);
    }

    public function testGetAuthzRolesUsingExperiment_API_Read()
    {
        // 1. create dependencies
        $merchant = $this->fixtures->create('merchant', [
            'id' => self::DEFAULT_X_MERCHANT_ID
        ]);

        $role = $this->fixtures->create('roles',[
            'id'            => '100customRole2',
            'name'          => 'CAC 2',
            'org_id'        => '100000razorpay',
            'type'          => 'custom',
            'merchant_id'   => $merchant['id'],
        ]);

        $this->fixtures->create('role_access_policy_map',
        [
            'role_id'           => '100customRole2',
            'authz_roles'       => ['authz_roles_1'],
            'access_policy_ids' => ['accessPolicy10'],
        ]);

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, true, false);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldNotReceive('adminAPIGetRole');

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        // 3. execute test & assert response
        $res = (new RolesService())->getAuthzRolesUsingExperiment($role['id']);

        $this->assertEquals(['authz_roles_1'], $res);
    }

    public function testGetAuthzRolesUsingExperiment_Authz_Read_RoleExistOnAPI()
    {
        // 1. create dependencies
        $merchant = $this->fixtures->create('merchant', [
            'id' => self::DEFAULT_X_MERCHANT_ID
        ]);

        $role = $this->fixtures->create('roles',[
            'id'            => '100customRole2',
            'name'          => 'CAC 2',
            'org_id'        => '100000razorpay',
            'type'          => 'custom',
            'merchant_id'   => $merchant['id'],
        ]);

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, true, true);

        $this->mockAuthzAdminForGetRoleUsingExperiment(true);

        // 3. execute test & assert response
        $res = (new RolesService())->getAuthzRolesUsingExperiment($role['id']);

        $this->assertEquals(['authz_role_name_1'], $res);
    }

    public function testGetAuthzRolesUsingExperiment_Authz_Read_RoleDoesNotExistOnAPI()
    {
        // 1. create dependencies
        // nothing to create

        // 2. set mocks
        $this->setMocksForLocateRoleUsingExperiment(false, false, true);

        $this->mockAuthzAdminForGetRoleUsingExperiment(true, null);

        // 3. execute test & assert response
        $res = (new RolesService())->getAuthzRolesUsingExperiment('100customRole2');

        $this->assertEquals(['authz_role_name_1'], $res);
    }

    public function testGetAuthzRolesUsingExperiment_Authz_Read_RoleDoesNotBelongToCAC()
    {
        $this->setMocksForLocateRoleUsingExperiment(false, false, true);

        $res = (new RolesService())->getAuthzRolesUsingExperiment('sellerapp');

        $this->assertEquals([], $res);
    }

    public function testAdminAPIGetRole_RoleNotFound()
    {
        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->andThrow(new \AuthzAdmin\Client\ApiException('sql: no rows in result set', 404));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $res = (new \RZP\Models\AuthzAdmin\Service())->adminAPIGetRole('100customRole2', self::DEFAULT_X_MERCHANT_ID, false);

        $this->assertEquals([], $res);
    }

    private function mockAuthzAdminForGetRoleUsingExperiment($shouldExpandChildren = false, $merchantId = self::DEFAULT_X_MERCHANT_ID)
    {
        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        if ($shouldExpandChildren)
        {
            $authzResponse = new AuthzAdminModel\V1Role([
                'children'  => [
                    [
                        'id'    => 'authz_role_id_1',
                        'name'  => 'authz_role_name_1',
                    ]
                ]
            ]);
        }
        else
        {
            $authzResponse = new AuthzAdminModel\V1Role([
                'id'            => '100customRole2',
                'name'          => 'custom role',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_X_MERCHANT_ID,
                'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'this is a custom role',
            ]);
        }

        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
        ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren) use ($shouldExpandChildren, $merchantId) {

            $this->assertEquals('100customRole2', $roleId);
            $this->assertEquals('razorpayx', $orgId);
            $this->assertEquals($merchantId, $ownerId);
            $this->assertEquals($shouldExpandChildren, $expandChildren);

            return true;
        })
        ->once()
        ->andReturn($authzResponse);

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);
    }

    public function testFetchRoleByIdCustomRoleCACMigration()
    {
        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/cac/role/role_100customRole1';

        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        // mock is required to pass the basic auth check
        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('100customRole1', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertFalse($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100customRole1',
                'name'          => 'custom role',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'this is a custom role',
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $this->startTest();
    }

    public function testFetchRoleByIdRoleFinanceL1CACMigration()
    {
        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/cac/role/role_finance_l1';

        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        // mock is required to pass the basic auth check
        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Finance L1', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertFalse($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole9',
                'name'          => 'Finance L1',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => Entity::ORG_ID_FOR_ROLES,
                'created_by'    => '10000000system',
                'children'      => null,
                'description'   => 'this is a standard role',
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $this->startTest();
    }

    public function testAdminAPIGetRoleForStandardRoles()
    {
        $this->ba->proxyAuth();

        $roles = [
            'role_owner' => [
                'id' => 'owner',
                'name' => 'Owner',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_vendor' => [
                'id' => 'vendor',
                'name' => 'Vendor',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_finance_l2' => [
                'id' => 'finance_l2',
                'name' => 'Finance L2',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_view_only' => [
                'id' => 'view_only',
                'name' => 'View Only',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_operations' => [
                'id' => 'operations',
                'name' => 'Operations',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_finance_l3' => [
                'id' => 'finance_l3',
                'name' => 'Finance L3',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_admin' => [
                'id' => 'admin',
                'name' => 'Admin',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_chartered_accountant' => [
                'id' => 'chartered_accountant',
                'name' => 'Chartered Accountant',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_finance_l1' => [
                'id' => 'finance_l1',
                'name' => 'Finance L1',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_finance' => [
                'id' => 'finance',
                'name' => 'Finance',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_petty_cash_employee' => [
                'id' => 'petty_cash_employee',
                'name' => 'Petty Cash Employee',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
            'role_banking_readonly' => [
                'id' => 'banking_readonly',
                'name' => 'Owner - Read Only',
                'description' => 'this is a standard role',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => '10000000system',
                'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            ],
        ];

        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        foreach ($roles as $roleKey => $expectedResponse) {
            $authzAdminClientMock->shouldReceive('adminAPIGetRole')
                ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren) use ($expectedResponse) {
                    $this->assertEquals($expectedResponse['name'], $roleId);
                    $this->assertEquals('razorpayx', $orgId);
                    $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                    $this->assertFalse($expandChildren);

                    return true;
                })
                ->once()
                ->andReturn(new AuthzAdminModel\V1Role([
                    'id'            => '100standardRole1',
                    'name'          => $expectedResponse['name'],
                    'org_id'        => 'razorpayx',
                    'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                    'owner_type'    => 'merchant',
                    'owner_id'      => Entity::ORG_ID_FOR_ROLES,
                    'created_by'    => '10000000system',
                    'child_ids'     => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                    'children'      => null,
                    'description'   => 'this is a standard role',
                ]));

            $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

            $response = (new \RZP\Models\AuthzAdmin\Service())->adminAPIGetRole($expectedResponse['id'], self::DEFAULT_MERCHANT_ID, false);

            $this->assertEquals($expectedResponse, $response);
        }
    }

    public function testAdminAPIGetRoleSellerApp()
    {
        $res = (new \RZP\Models\AuthzAdmin\Service())->adminAPIGetRole('sellerapp', self::DEFAULT_X_MERCHANT_ID, false);

        $this->assertEquals([], $res);
    }

    public function testFetchSelfRoleCACMigration()
    {
        $this->ba->proxyAuth();

        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => '100000razorpay',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertFalse($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => '100000razorpay',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $this->startTest();
    }

    public function testGetRolesUsingExperiment()
    {
        // 1. create dependencies
        $apiRole = $this->fixtures->create('roles', [
            'id'        => '100customRole2',
            'name'      => 'CAC 2',
            'org_id'    => '100000razorpay'
        ]);

        // 2. set mocks
        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        // 2.1. mock for standard role
        $authzAdminClientMock->shouldReceive('adminAPIListRole')
            ->once()
            ->withArgs(function($paginationToken, $roleNamePrefix, $roleNames, $roleIds, $orgId, $keyId, $keyOwnerType, $keyOwnerId, $ownerIds, $type)
            {
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(['owner'], $roleNames);
                $this->assertEquals(null, $roleIds);
                $this->assertEquals(AuthzAdminModel\V1RolePolicyType::STANDARD, $type);

                return true;
            })
            ->andReturn(new AuthzAdminModel\V1ListRoleResponse([
                'items' => [
                    new AuthzAdminModel\V1Role([
                        'id' => '100standardRole1',
                        'name' => 'Owner',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::STANDARD,
                        'owner_type' => 'merchant',
                        'owner_id' => Entity::ORG_ID_FOR_ROLES,
                        'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => '',
                    ]),
                ]
            ]));

        // 2.2. mock for custom role
        $authzAdminClientMock->shouldReceive('adminAPIListRole')
            ->once()
            ->withArgs(function($paginationToken, $roleNamePrefix, $roleNames, $roleIds, $orgId, $keyId, $keyOwnerType, $keyOwnerId, $ownerIds, $type)
            {
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(null, $roleNames);
                $this->assertEquals(['100customRole1', '100customRole2'], $roleIds);
                $this->assertEquals(AuthzAdminModel\V1RolePolicyType::CUSTOM, $type);
                return true;
            })
            ->andReturn(new AuthzAdminModel\V1ListRoleResponse([
                'items' => [
                    new AuthzAdminModel\V1Role([
                        'id' => '100customRole1',
                        'name' => 'CAC 1',
                        'org_id' => '100000razorpay',
                        'type' => AuthzAdminModel\V1RolePolicyType::CUSTOM,
                        'owner_type' => 'merchant',
                        'owner_id' => self::DEFAULT_X_MERCHANT_ID,
                        'child_ids' => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                        'created_by' => 'MerchantUser01',
                        'children' => null,
                        'description' => '',
                    ]),
                ]
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        // 3. execute test & assert response
        $res = (new RolesService())->getRolesUsingExperiment([
            'owner',
            '100customRole1',
            '100customRole2'
        ]);

        $this->assertEquals([
            [
                'id' => 'owner',
                'name' => 'Owner',
                'type' => 'standard',
                'merchant_id' => '100000razorpay',
                'created_by' => 'MerchantUser01',
                'description' => '',
            ],
            [
                'id' => '100customRole1',
                'name' => 'CAC 1',
                'type' => 'custom',
                'merchant_id' => '100000merchant',
                'created_by' => 'MerchantUser01',
                'description' => '',
            ],
            [
                'id' => '100customRole2',
                'merchant_id' => '100000merchant',
                'name' => 'CAC 2',
                'description' => 'Test custom role',
                'type' => 'custom',
                'created_by' => 'test@razorpay.com',
            ],
        ], $res);
    }
}
