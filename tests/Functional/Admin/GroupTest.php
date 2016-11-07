<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use RZP\Models\Admin\Group;

class GroupTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/GroupData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');
    }

    public function testAdminGroupPolymorphicRelationship()
    {
        // Organization creation has to be done through rzp auth
        $this->ba->appAuth();

        $orgId = $this->org->getId();

        $group = $this->fixtures->create('group', ['org_id' => $orgId]);

        $subGroup = $this->fixtures->create('group', ['org_id' => $orgId]);

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

        $roles = $this->fixtures->times(2)->create('role', ['org_id' => $orgId]);

        $group->roles()->saveMany($roles);

        $group->saveOrFail();

        $roleIds = $group->roles()->getRelatedIds();

        // Check if both the roles are saved
        $this->assertEquals(count($roleIds), 2);

        $role = $roles[0];

        $groupId = $role->groups()->getRelatedIds()[0];

        $this->assertEquals($groupId, $group->getId());

        $admin->roles()->save($role);

        $this->assertEquals($admin->getId(), $role->admins()->getRelatedIds()[0]);
    }

    public function testMerchantsInGroup()
    {
        $this->ba->appAuth();

        $orgId = $this->org->getId();

        $group = $this->fixtures->create('group', ['org_id' => $orgId]);

        $roles = $this->fixtures->times(2)->create('role', ['org_id' => $orgId]);

        $admin = $this->fixtures->create('admin', ['org_id' => $orgId]);

        $merchant = $this->fixtures->create('merchant', ['id' => '123']);

        $group->roles()->saveMany($roles);

        $group->admins()->save($admin);

        $group->merchants()->save($merchant);

        $admin->saveOrFailMerchant($merchant);

        $this->assertEquals($merchant->getId(), $admin->merchants()->getRelatedIds()[0]);

    }
}
