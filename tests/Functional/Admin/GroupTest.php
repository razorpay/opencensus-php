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
    }

    public function testAdminGroupPolymorphicRelationship()
    {
        // Organization creation has to be done through rzp auth
        $this->ba->appAuth();

        $org = $this->fixtures->create('org');

        $group = $this->fixtures->create('group', ['org_id' => $org->getId()]);

        $subGroup = $this->fixtures->create('group', ['org_id' => $org->getId()]);

        $admin = $this->fixtures->create('admin', ['org_id' => $org->getId()]);
    }
}
