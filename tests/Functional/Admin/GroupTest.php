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

    public function testParentGroupAssignment()
    {
        // create child group
        $l0Group = $this->fixtures->create('group', ['org_id' => $this->org->getId()]);

        // create parent groups
        $l1Groups = $this->fixtures->times(3)->create('group', ['org_id' => $this->org->getId()]);

        $l1GroupIds = array_map(create_function('$g', 'return $g->getPublicId();'), $l1Groups);

        // modify request
        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $this->org->getPublicId(), $l0Group->getPublicId());

        $request['content']['parents'] = $l1GroupIds;

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->startTest();

        // check parents
        $createdParents = $l0Group->parents->all();
        $createdParentIds = array_map(create_function('$g', 'return $g->getPublicId();'), $createdParents);
        $this->assertEquals(count(array_intersect($l1GroupIds, $createdParentIds)),
                            count(array_intersect($createdParentIds, $l1GroupIds)));

        // check sub groups
        foreach ($createdParents as $createdParent) {
            $subGroups = $createdParent->subgroups->all();
            $subGroupIds = array_map(create_function('$g', 'return $g->getPublicId();'), $subGroups);
            $this->assertEquals(1, count($subGroupIds));
            $this->assertEquals($l0Group->getPublicId(), $subGroupIds[0]);
        }
}
}
