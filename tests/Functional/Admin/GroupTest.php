<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class GroupTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/GroupData.php';

        parent::setUp();

        // create org, and its hostnames
        $this->org = $this->fixtures->create('org');

        $hostnames = $this->fixtures->times(2)->create(
            'org_hostname',
            ['org_id' => $this->org->getId()]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());
    }

    public function testCreateGroup()
    {
        return $this->startTest();
    }

    public function testDeleteGroup()
    {
        $group = $this->fixtures->create('group', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $group->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditGroup()
    {
        $group = $this->fixtures->create('group', ['org_id' => $this->org->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $group->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGetMultipleGroups()
    {
        $this->fixtures->times(2)->create('group', ['org_id' => $this->org->getId()]);

        $this->startTest();
    }

    public function testGetGroup()
    {
        $group = $this->fixtures->create('group',
            ['org_id' => $this->org->getId(), 'name' => 'Testing wala group']);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $group->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['response']['content']['id'] = $group->getPublicId();

        $this->startTest();
    }

    public function testDuplicateGroup()
    {
        $name = 'hello';

        $group = $this->fixtures->create('group',
            ['org_id' => $this->org->getId(), 'name' => $name]);

        $this->testData[__FUNCTION__]['request']['content']['name'] = $name;

        $this->startTest();
    }

    public function testParentGroupDelete()
    {
        $l0Group = $this->fixtures->create('group', ['org_id' => $this->org->getId()]);

        // create parent groups
        $l1Groups = $this->fixtures->times(3)->create('group', ['org_id' => $this->org->getId()]);

        $func = function($g) {
            return $g->getId();
        };

        $l1GroupIds = array_map($func, $l1Groups);

        $l0Group->parents()->sync($l1GroupIds);

        // modify request
        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $l0Group->getPublicId());

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->startTest();

        $this->assertEquals(0, count($l0Group->parents->all()));
    }

    public function testParentGroupAssignment()
    {
        $l0Group = $this->fixtures->create('group', ['org_id' => $this->org->getId()]);

        // create parent groups
        $l1Groups = $this->fixtures->times(3)->create('group', ['org_id' => $this->org->getId()]);

        $func = function($g) {
            return $g->getPublicId();
        };

        $l1GroupIds = array_map($func, $l1Groups);

        // modify request
        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $l0Group->getPublicId());

        $request['content']['parents'] = $l1GroupIds;

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->startTest();

        // check parents
        $createdParents = $l0Group->parents->all();

        $func = function($g) {
            return $g->getPublicId();
        };

        $createdParentIds = array_map($func, $createdParents);

        $this->assertEquals(count(array_intersect($l1GroupIds, $createdParentIds)),
                            count(array_intersect($createdParentIds, $l1GroupIds)));

        // check sub groups
        foreach ($createdParents as $createdParent) {
            $subGroups = $createdParent->subgroups->all();

            $subGroupIds = array_map($func, $subGroups);

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

        $func = function($g) {
            return $g->getId();
        };

        $loneGroupIds = array_map($func, $loneGroups);

        // modify request
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $groupWithoutChild->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        // read response content
        $content = $this->response->getContent();
        $content = json_decode($content, true);

        $filteredParentIds = array_column($content, 'id');

        $expectedParentIds = array_map($func, $loneGroups);

        $this->assertEquals(count(array_intersect($filteredParentIds, $expectedParentIds)),
                            count(array_intersect($expectedParentIds, $filteredParentIds)));
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

        $url = sprintf($url, $selectedGroup->getPublicId());

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

        $func = function($g) {
            return $g->getPublicId();
        };

        $allGroupIds = array_map($func, $allGroups);

        // get allowed parent-groups' ids
        $expectedParentIds = array_diff($allGroupIds,
                                       $selectedGroupsDescendantsIds,
                                       [$selectedGroup->getPublicId()]);

        $this->assertEquals(count(array_intersect($filteredParentIds, $expectedParentIds)),
                            count(array_intersect($expectedParentIds, $filteredParentIds)));
    }

    public function testSiblingsNotAllowedAsParents()
    {
        list($l0Group, $allGroups) = $this->buildGroupHierarchy(3);

        // select a group without a parent
        $selectedGroup = $allGroups[1];

        // modify request
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $selectedGroup->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        // read response content
        $content = $this->response->getContent();
        $content = json_decode($content, true);

        $filteredParentIds = array_column($content, 'id');

        $func = function($g) {
            return $g->getId();
        };

        // Fetching children of siblings of $selectedGroup
        $expectedParents = $allGroups[2]->subGroups->all();
        $expectedParents = array_merge($expectedParents, $allGroups[3]->subGroups->all());
        $expectedParentIds = array_map($func, $expectedParents);

        $this->assertEquals(count(array_intersect($filteredParentIds, $expectedParentIds)),
                            count(array_intersect($expectedParentIds, $filteredParentIds)));
    }

    public function testUnconnectedGroupsAsEligibleParentsForEachOther()
    {
        $groups = $this->fixtures->times(3)->create('group',
                    ['org_id' => $this->org->getId()]);

        $selectedGroup = $groups[0];

        // modify request
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $selectedGroup->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        // read response content
        $content = $this->response->getContent();
        $content = json_decode($content, true);

        $filteredParentIds = array_column($content, 'id');

        $expectedParentIds = [$groups[1], $groups[2]];

        $this->assertEquals(count(array_intersect($filteredParentIds, $expectedParentIds)),
                            count(array_intersect($expectedParentIds, $filteredParentIds)));
    }

    /*
     * Builds an n-ary tree group hierarchy structure
     * For example, for n=1 total_groups = 2
     *                  n=2 total_groups = 7
     *                  n=3 total_groups = 40
     *
     * @param integer $level => n
     * @return Group\Entity Starting group node of the n-ary tree, which doesn't have any children
     * @return Collection A collection of all group nodes in the tree
     */
    protected function buildGroupHierarchy($level)
    {
        // create child group
        $l0Group = $this->fixtures->create('group', ['org_id' => $this->org->getId()]);

        $childGroups = $allGroups = [$l0Group];

        $func = function($g) {
            return $g->getId();
        };

        // create parent groups
        for ($i=0; $i < $level; $i++)
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

                $parentGroupIds = array_map($func, $parentsGroups);

                $childGroup->parents()->sync($parentGroupIds);

                // use below to check data creation
                // s($childGroup['id'],
                //   array_map($func, $childGroup->parents->all()));

                $newChildGroups = array_merge($newChildGroups, $parentsGroups);
            }

            $childGroups = $newChildGroups;

            $allGroups = array_merge($allGroups, $childGroups);
        }

        return [$l0Group, $allGroups];
    }
}
