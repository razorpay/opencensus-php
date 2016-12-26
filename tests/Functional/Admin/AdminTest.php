<?php

namespace RZP\Tests\Functional\Admin;

use Carbon\Carbon;
use Hash;

use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Role;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class AdminTest extends TestCase
{
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AdminData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $this->orgId = $this->org->getId();

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken);

        $this->repo = (new Admin\Repository);
    }

    public function testCreateAdmin()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $superAdminRole = Role\Entity::getSignedId(Org::ADMIN_ROLE);

        $this->testData[__FUNCTION__]['request']['content']['roles'] = (array) $superAdminRole;

        $group = Group\Entity::getSignedId(Org::DEFAULT_GRP);

        $this->testData[__FUNCTION__]['request']['content']['groups'] = (array) $group;

        $result = $this->startTest();

        $this->assertEquals($result['roles'][0]['id'], $superAdminRole);

        $this->assertEquals($result['groups'][0]['id'], $group);
    }

    public function testCreateAdminWithWrongEmailDomain()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGetAdmin()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID  => $this->orgId,
            Admin\Entity::EMAIL   => 'testadmin@rzp.com',
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('groups', $result);
    }

    public function testEditAdmin()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
        ]);

        $dummyGrp = $this->fixtures->create(
            'group', ['org_id' => $this->orgId]);

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        $admin->groups()->sync([$dummyGrp->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $managerRole = Role\Entity::getSignedId(Org::MANAGER_ROLE);

        $this->testData[__FUNCTION__]['request']['content']['roles'] = (array) $managerRole;

        $group = Group\Entity::getSignedId(Org::DEFAULT_GRP);

        $this->testData[__FUNCTION__]['request']['content']['groups'] = (array) $group;

        $result = $this->startTest();

        $this->assertEquals($result['roles'][0]['id'], $managerRole);

        $this->assertEquals($result['groups'][0]['id'], $group);
    }

    public function testDeleteAllRolesAdmin()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
        ]);

        $dummyGrp = $this->fixtures->create(
            'group', ['org_id' => $this->orgId]);

        $admin->groups()->sync([$dummyGrp->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $admin = $this->getAdmin(
            $this->org->getPublicId(),
            $admin->getPublicId(),
            $this->authToken);

        $this->assertEquals(0, count($admin['roles']));
    }

    public function testDeleteAllGroupsAdmin()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
        ]);

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $admin = $this->getAdmin(
            $this->org->getPublicId(),
            $admin->getPublicId(),
            $this->authToken);

        $this->assertEquals(0, count($admin['groups']));
    }

    public function testDeleteAdmin()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicOrgId(), $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testDeleteAdminFailed()
    {
        $admin = $this->fixtures->create('admin', ['org_id' => $this->orgId]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicOrgId(), $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGetMultipleAdmin()
    {
        $admin = $this->fixtures->times(3)->create(
            'admin', ['org_id' => $this->orgId]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();
    }

    public function testGetCurrentAdmin()
    {
        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId]);

        $admin->groups()->sync([Org::DEFAULT_GRP]);

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        $adminToken = $this->fixtures->create(
            'admin_token',
            [
                'token' => 'secondToken',
                'admin_id' => $admin->getId(),
            ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $result = $this->startTest();

        $this->assertEquals($admin->getPublicId(), $result['id']);
    }

    public function testLockUnusedAccounts()
    {
        $now = Carbon::now();
        $now_minus_120 = $now->subDays(120);
        $now_minus_40 = $now->subDays(40);

        $admins = $this->fixtures->times(2)->create(
            'admin',
            [
                'org_id' => $this->orgId,
                'last_login_at' => $now_minus_120->timestamp,
                'created_at' => $now_minus_120->timestamp,
                'updated_at' => $now_minus_120->timestamp,
            ]);

        // Unactivated Accounts
        $this->fixtures->times(2)->create(
            'admin',
            [
                'org_id' => $this->orgId,
                'last_login_at' => null,
                'created_at' => $now_minus_40->timestamp,
                'updated_at' => $now_minus_40->timestamp,
            ]);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGetMerchantIds()
    {
        $grp = $this->fixtures->create(
            'group', ['org_id' => $this->orgId]);
        $subGrp = $this->fixtures->create(
            'group', ['org_id' => $this->orgId]);

        $subGrp->parents()->attach($grp);

        $merchantsGrp = $this->fixtures->create(
            'merchant', ['org_id' => $this->orgId]);
        $merchantsSubGrp = $this->fixtures->create(
            'merchant', ['org_id' => $this->orgId]);

        $grp->merchants()->attach($merchantsGrp);
        $subGrp->merchants()->attach($merchantsSubGrp);

        $adminGrp = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId]);
        $adminSubGrp = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId]);

        $adminGrp->merchants()->attach($merchantsGrp);
        $adminSubGrp->merchants()->attach($merchantsSubGrp);

        $grp->admins()->attach($adminGrp);
        $subGrp->admins()->attach($adminSubGrp);

        $merchantsAdminGrp = $this->fixtures->create(
            'merchant', ['org_id' => $this->orgId]);
        $merchantsAdminSubGrp = $this->fixtures->create(
            'merchant', ['org_id' => $this->orgId]);

        $adminGrp->merchants()->attach($merchantsAdminGrp);
        $adminSubGrp->merchants()->attach($merchantsAdminSubGrp);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $adminGrp->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertEquals(count($result), 4);
    }

    public function testLoginUserDoesNotExist()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testLoginOAuth()
    {
        $admin = $this->fixtures->create(
            'admin',
            [
                'org_id' => $this->orgId,
                'email' => 'test@email.com',
                'oauth_access_token' => 'test oauth token',
                'oauth_provider_id'  => 'test oauth provider id',
            ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testFailedLoginOAuth()
    {
        $admin = $this->fixtures->create(
            'admin',
            [
                'org_id' => $this->orgId,
                'email' => 'test@email.com',
                'oauth_access_token' => 'test oauth token 2',
                'oauth_provider_id'  => 'test oauth provider id',
            ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $this->startTest();

        $admin = $this->getEntityById('admin', $admin->getId(), true);

        $this->assertEquals($admin['failed_attempts'], 1);
    }

    public function testSelfEditAdminFailed()
    {
        $admin = $this->ba->getAdmin();

        $org = $admin->org;

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $org->getPublicId(), $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testSelfDeleteAdminFailed()
    {
        $admin = $this->ba->getAdmin();

        $data = $this->testData['testSelfEditAdminFailed'];

        $this->runRequestResponseFlow($data, function() use ($admin)
        {
            $this->deleteAdmin(
                $admin->getPublicOrgId(),
                $admin->getPublicId(),
                $this->authToken);
        });
    }

    public function testSuperAdmin()
    {
        $admin = $this->ba->getAdmin();

        $this->assertEquals($admin->isSuperAdmin(), true);
    }

    public function testPasswordResetSuccess()
    {
        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $admin->setPassword('M!2#uWd');

        $this->repo->saveOrFail($admin);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $newPassword = $this->testData[__FUNCTION__]['request']['content']['password'];

        $this->ba->appAuth();

        $result = $this->startTest();

        if ((isset($result['success']) === true) and
            ($result['success'] === true))
        {
            $admin = $this->repo->findOrFailPublic($admin->getId());

            $this->assertTrue(Hash::check($newPassword, $admin['password']));
        }
    }

    public function testPasswordResetMismatch()
    {
        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $oldPwd = 'M!2#uWd';

        $admin->setPassword($oldPwd);

        $this->repo->saveOrFail($admin);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check($oldPwd, $admin['password']));
    }

    public function testPasswordResetInvalid()
    {
        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $oldPwd = 'M!2#uWd';

        $admin->setPassword($oldPwd);

        $this->repo->saveOrFail($admin);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testPasswordResetInvalidAuthType()
    {
        $org = $this->fixtures->create('org', ['auth_type' => 'google_auth']);

        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $oldPwd = 'M!2#uWd';

        $admin->setPassword($oldPwd);

        $this->repo->saveOrFail($admin);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testAdminLogout()
    {
        $now = Carbon::now();

        $admin = $this->fixtures->create(
            'admin',
            [
                'name'     => 'test admin',
                'org_id'   => Org::RZP_ORG,
                'username' => 'ram@razorpay.com',
                'password' => 'Heimdall!432',
            ]);

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        // Create some admin tokens
        $adminTokens = $this->fixtures->times(3)->create(
            'admin_token',
            [
                'admin_id'   => $admin->getId(),
                'created_at' => $now->timestamp,
                'expires_at' => $now->addYear(1)->timestamp,
            ]);

        $adminToken = $adminTokens[0];

        $token = $adminToken->getValidToken();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'org_' . Org::RZP_ORG);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        // Replace auth with this route
        $this->ba->adminAuth('test', $token);

        $admin = $this->ba->getAdmin()->toArray();

        $this->assertEquals($admin['name'], 'test admin');

        $this->startTest();
     }

     public function testGetAdminByEmailOnAppAuth()
     {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID  => $this->orgId,
            Admin\Entity::EMAIL   => 'testadmin@rzp.com',
            Admin\Entity::NAME    => 'test admin app auth',
        ]);

        $this->ba->appAuth();

        $result = $this->startTest();
     }
}
