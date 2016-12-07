<?php

namespace RZP\Tests\Functional\Admin;

use Carbon\Carbon;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org as Org;

use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Group;

class AdminTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AdminData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
        ]);

        $this->orgId = $this->org->getId();

        $this->ba->adminAuth();
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

        $this->startTest();
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

        $this->assertEquals(0, count($admin->groups->all()));
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

        $this->assertEquals(0, count($admin->roles->all()));
    }

    public function testDeleteAdmin()
    {
        $adminToken = $this->fixtures->create('admin_token', ['token' => 'secondToken']);

        $admin = $adminToken['admin'];

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin['org_id'], $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testDeleteAdminFailed()
    {
        $admin = $this->fixtures->create('admin', ['org_id' => $this->orgId]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin['org_id'], $admin->getPublicId());

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

        $result = $this->startTest();
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
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $org = $admin->org;

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $org->getPublicId(), $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }
}
