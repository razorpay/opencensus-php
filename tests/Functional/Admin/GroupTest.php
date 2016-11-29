<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use RZP\Models\Admin\Group;

class GroupTest extends TestCase
{
    use RequestResponseFlowTrait;
    use EntityActionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/GroupData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $this->ba->adminAuth('test');
    }

    public function testCreateGroup()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        return $this->startTest();
    }

    public function testDeleteGroup()
    {
        $group = $this->fixtures->create('group', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $group->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditGroup()
    {
        $group = $this->fixtures->create('group', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $group->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGetMultipleGroups()
    {
        $groups = $this->fixtures->times(2)->create('group', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();
    }

    public function testAdminGroupPolymorphicRelationship()
    {
        // Organization creation has to be done through rzp auth
        $this->ba->appAuth();

        $orgId = $this->org->getId();

        $group = $this->fixtures->create('group', ['org_id' => $orgId]);

        $subGroup = $this->fixtures->create('group', ['org_id' => $orgId, 'name' => 'asd']);

        $admin = $this->fixtures->create('admin', ['org_id' => $orgId]);

        $group->admins()->save($admin);

        $group->subGroups()->save($subGroup);

        $group->saveOrFail();

        $subGroup->saveOrFail();

        $parentGroup = $subGroup->parents()->findOrFail($group->getId());

        $this->assertEquals($group->getId(), $parentGroup->getId());
    }

    public function testRolesForGroup()
    {
        $this->ba->appAuth();

        $orgId = $this->org->getId();

        $group = $this->fixtures->create('group', ['org_id' => $orgId]);

        $admin = $this->fixtures->create('admin', ['org_id' => $orgId]);

        $role = $this->fixtures->create('role', ['org_id' => $orgId]);

        $group->roles()->save($role);

        $group->saveOrFail();

        $roleIds = $group->roles()->getRelatedIds();

        // Check if both the roles are saved
        $this->assertEquals(count($roleIds), 1);

        $groupId = $role->groups()->getRelatedIds()[0];

        $this->assertEquals($groupId, $group->getId());

        $admin->roles()->save($role);

        $this->assertEquals($admin->getId(), $role->admins()->getRelatedIds()[0]);
    }
}
