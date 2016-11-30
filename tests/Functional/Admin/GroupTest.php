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
        list($l0Group, $allGroups) = $this->buildGroupHierarchy(1);

        $l1Groups = array_diff($allGroups, [$l0Group]);
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

    public function testAncestorsNotAllowedAsParents()
    {
        list($l0Group, $allGroups) = $this->buildGroupHierarchy(2);

        $groupWithoutChild = $l0Group;

        // create another hierarchy of groups
        $loneGroups = $this->fixtures->times(2)->create('group', ['org_id' => $this->org->getId()]);
        $loneGroupIds = array_map(create_function('$g', 'return $g->getId();'), $loneGroups);

        // modify request
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $groupWithoutChild->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        // read response content
        $content = $this->response->getContent();
        $content = json_decode($content, true);

        $filteredParentIds = array_column($content, 'id');

        $this->assertEquals(count(array_intersect($filteredParentIds, $loneGroupIds)),
                            count(array_intersect($loneGroupIds, $filteredParentIds)));
    }

    public function testDescendantsNotAllowedAsParents()
    {
        list($l0Group, $allGroups) = $this->buildGroupHierarchy(2);

        // select a group without a parent
        $selectedGroup = end($allGroups);

        // reset internal pointer of array to first element
        reset($allGroups);

        // create another hierarchy of groups
        $loneGroups = $this->fixtures->times(2)->create('group', ['org_id' => $this->org->getId()]);

        // add lone groups to allGroups
        $allGroups = array_merge($allGroups, $loneGroups);

        // modify request
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $selectedGroup->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        // read response content
        $content = $this->response->getContent();
        $content = json_decode($content, true);

        $filteredParentIds = array_column($content, 'id');

        // get descendants of selectGroup
        $selectedGroupsDescendantsIds = [
                                            ($selectedGroup->subGroups->all()[0])->getPublicId(),
                                            $l0Group->getPublicId()
                                        ];


        $allGroupIds = array_map(create_function('$g', 'return $g->getPublicId();'), $allGroups);

        // get allowed parent-groups' ids
        $allowedParentIds = array_diff($allGroupIds,
                                       $selectedGroupsDescendantsIds,
                                       [$selectedGroup->getPublicId()]);

        $this->assertEquals(count(array_intersect($filteredParentIds, $allowedParentIds)),
                            count(array_intersect($allowedParentIds, $filteredParentIds)));
    }

    public function testSiblingsNotAllowedAsParents()
    {
        list($l0Group, $allGroups) = $this->buildGroupHierarchy(3);

        // select a group without a parent
        $selectedGroup = $allGroups[1];

        // modify request
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $selectedGroup->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        // read response content
        $content = $this->response->getContent();
        $content = json_decode($content, true);

        $filteredParentIds = array_column($content, 'id');

        // Fetching children of siblings of $selectedGroup
        $allowedParents = $allGroups[2]->subGroups->all();
        $allowedParents = array_merge($allowedParents, $allGroups[3]->subGroups->all());
        $allowedParentIds = array_map(create_function('$g', 'return $g->getId();'), $allowedParents);

        $this->assertEquals(count(array_intersect($filteredParentIds, $allowedParentIds)),
                            count(array_intersect($allowedParentIds, $filteredParentIds)));
    }

    /*
     * Builds an n-ary tree group hierarchy structure
     * @param integer $level => n
     * @return Group\Entity Starting group node of the n-ary tree, which doesn't have any children
     * @return Collection A collection of all group nodes in the tree
     */
    protected function buildGroupHierarchy($level)
    {
        // create child group
        $l0Group = $this->fixtures->create('group', ['org_id' => $this->org->getId()]);

        $childGroups = $allGroups = [$l0Group];

        // create parent groups
        for ($i=0; $i<$level; $i++)
        {
            $newChildGroups = [];

            foreach ($childGroups as $childGroup)
            {
                $parentsGroups = $this->fixtures->times($level)->create('group',
                    ['org_id' => $this->org->getId()]);

                // When $level=1, $this->fixtures->times($level) returns an object of the entity but not an array
                if ($level === 1 )
                {
                    $parentsGroups = [$parentsGroups];
                }

                $parentGroupIds = array_map(create_function('$g', 'return $g->getId();'), $parentsGroups);

                $childGroup->parents()->sync($parentGroupIds);

                // use below to check data creation
                // s($childGroup['id'],
                //   array_map(create_function('$g', 'return $g->getId();'), $childGroup->parents->all()));

                $newChildGroups = array_merge($newChildGroups, $parentsGroups);
            }

            $childGroups = $newChildGroups;

            $allGroups = array_merge($allGroups, $childGroups);
        }

        return [$l0Group, $allGroups];
    }
}
