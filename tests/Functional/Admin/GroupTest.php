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

    public function testDuplicateGroup()
    {
        $name = 'hello';

        $group = $this->fixtures->create('group',
            ['org_id' => $this->org->getId(), 'name' => $name]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['request']['content']['name'] = $name;

        $this->startTest();
    }
}
