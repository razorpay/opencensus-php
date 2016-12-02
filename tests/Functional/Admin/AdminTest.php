<?php

namespace RZP\Tests\Functional\Admin;

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

        $this->ba->adminAuth('test');
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
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testWeakPassword()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;
        $this->testData[__FUNCTION__]['request']['content'][Admin\Entity::PASSWORD] = 'helloworld';

        $this->startTest();
    }
}
