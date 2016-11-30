<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Admin\Org;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Role;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;

class Service extends Base\Service
{
    public function createGroup(string $orgId, array $input)
    {
        Org\Entity::verifyIdAndStripSign($orgId);

        $org = $this->repo->org->findOrFailPublic($orgId);

        $group = $this->core()->create($input, $org);

        return $group->toArrayPublic();
    }

    public function getGroup(string $orgId, string $groupId)
    {
        $group = $this->repo->group->findByPublicIdAndOrgIdWithRelations($groupId, $orgId);

        return $group->toArrayPublic();
    }

    public function editGroup(string $orgId, string $groupId, array $input)
    {
        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $orgId);

        $this->core()->edit($group, $input);

        return $group->toArrayPublic();
    }

    public function deleteGroup(string $orgId, string $groupId)
    {
        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $orgId);

        $this->repo->deleteOrFail($group);

        // @todo: To maintain bc. Remove first two lines later.
        $ret = $group->toArrayDeleted();
        $ret = array_merge($ret, ['success' => true]);
        return $ret;
    }

    public function fetchMultiple(string $orgId, array $input = [])
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $groups = $this->repo->group->fetchGroupsForOrg($orgId, $input);

        return $groups->toArrayPublic();
    }

    public function addRoleToGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $orgId);

        $roleIds = $input['role_ids'];

        // @todo: Check issue of sign and return value here?
        $roles = $this->repo->role->findManyByPublicIds($roleIds);
        assert ($roles->count() === count($roleIds));

        $this->repo->group->addRolesToGroup($roles, $group);

        return $group->toArrayPublic();
    }

    public function addMerchantsToGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $orgId);

        $merchantIds = $input['merchant_ids'];

        // TODO Wrap it in a transaction or sync the m2m field
        // Check for merchantIds
        $merchants = $this->repo->merchant->findMany($merchantIds);
        assert ($merchants->count() === count($merchantIds));

        $this->repo->group->addMerchantsToGroup($merchant, $group);

        return $group->toArrayPublic();
    }

    public function addAdminsToGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        Validator::validateInputKeyExists($input, 'admins');

        $adminIds = $input['admins'];

        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $orgId);

        $admins = $this->repo->admin->findManyByPublicIds($adminIds);
        assert ($admins->count() === count($adminIds));

        $this->repo->group->addAdminsToGroup($admins, $group);

        return $group->toArrayPublic();
    }

    /**
     * @todo : Correct this function. $roleId not defined
     * in arguments.
     */
    public function revokeRoleFromGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $orgId);

        $role = $this->repo->role->findByPublicIdAndOrgId($roleId, $orgId);

        $this->repo->group->revokeRoleOrFail($group, $role);
    }

    /**
     * This function gets all org groups and then calls filter on it to
     * filter out the ones not allowed
     *
     * Logic: Imagine a graph (feel free to draw a tree for better
     * visualization though). For a selected node (group in our case)
     * remove:
     *
     * - Its **siblings**.
     * - Its **direct** parent-linked chain.
     * - Its **siblings** and its **own** child hierarchy.
     */
    public function fetchEligibleParents(string $orgId, string $groupId, array $input)
    {
        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $orgId);

        // Get all groups of the current organization
        $allGroups = $this->repo->group->fetchGroupsForOrg($orgId, $input);

        $allGroups = $allGroups->toArray();

        $filteredGroups = $this->filterEligibleParents($orgId, $group, $allGroups);

        return $filteredGroups;
    }

    /**
    * This functions does all the filtering. It gets all the groups in parent hierarchy
    * and all the siblings and removes these from the array of all org groups and returns
    * the difference.
    */
    protected function filterEligibleParents(string $orgId, Entity $group, $allGroups)
    {
        $currentGroup = [$group];
        $groupId = $group->getId();

        // Get entire parent lineage (recursively)
        $rejectParents = $this->getRejectParents($orgId, $groupId);

        // Get entire children tree/hierarchy (recursively)
        $children = [];
        $rejectChildren = $this->getRejectChildren($orgId, $groupId, $children);

        // Get sublings **and** its tree/hierarchy (recursively)
        $rejectSiblings = $this->getRejectSiblings($orgId, $groupId);

        // Merge all the groups to be rejected
        $rejects = array_merge($currentGroup, $rejectParents, $rejectChildren, $rejectSiblings);

        // Remove $rejects from $allGroups
        $filtered = [];

        foreach ($allGroups as $group)
        {
            $isReject = false;

            foreach ($rejects as $reject)
            {
                if ($group['id'] === $reject['id'])
                {
                    $isReject = true;
                }
            }

            if ($isReject === false)
            {
                $group['id'] = Entity::getSignedId($group['id']);

                $filtered[] = $group;
            }
        }

        return $filtered;
    }

    // @new
    protected function getRejectParents($orgId, $groupId)
    {
        $rejectNodes = [];

        // Get all direct parents of incoming groupId
        $parentGroups = $this->getParentGroups($orgId, $groupId)->toArray();

        // Throw all direct parents in the rejected node list
        $rejectNodes = $parentGroups;

        // Loop through the parents list, take each parent
        // and then get their direct parents by recursion and so on...
        foreach ($parentGroups as $group)
        {
            // For every parent group, check its further direct parents
            $rejects = $this->getRejectParents($orgId, $group['id']);

            $rejectNodes = array_merge($rejectNodes, $rejects);
        }

        return $rejectNodes;
    }

    // @new
    public function getRejectChildren($orgId, $groupId, &$rejectNodes)
    {
        // $rejectNodes = [];

        // Get all direct children of incoming groupId
        $childrenGroups = $this->getChildrenGroups($orgId, $groupId)->toArray();

        // Throw all direct children in the rejected node list
        $rejectNodes = array_merge($rejectNodes, $childrenGroups);

        foreach ($childrenGroups as $group)
        {
            // For every child group, check its further direct children
            $rejects = $this->getRejectChildren($orgId, $group['id'], $rejectNodes);

            $rejectNodes = array_merge($rejectNodes, $rejects);
        }

        return $rejectNodes;
    }

    // @new
    protected function getRejectSiblings($orgId, $groupId)
    {
        $rejectNodes = [];

        $parentGroups = $this->getParentGroups($orgId, $groupId);

        foreach ($parentGroups as $parent)
        {
            $siblings = $parent->subGroups->toArray();

            foreach ($siblings as $sibling)
            {
                // If sibling is the current group itself then continue
                if ($sibling['id'] === $groupId)
                {
                    continue;
                }

                // Get tree/hierarchy of sibling
                $children = [];
                $siblingChildren = $this->getRejectChildren($orgId, $sibling['id'], $children);

                // Merge previous reject nodes with sibling hierarchy/tree
                // and the current sibling in context
                $rejectNodes = array_merge($rejectNodes, [$sibling], $siblingChildren);
            }
        }

        return $rejectNodes;
    }

    protected function getParentGroups(string $orgId, string $groupId)
    {
        // Get the group from current org
        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail($orgId, $groupId);

        // Get all the direct parents to which the group has been linked
        return $group->parents;
    }

    protected function getChildrenGroups(string $orgId, string $groupId)
    {
        // Get the group from current org
        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail($orgId, $groupId);

        // Get all the direct children to which the group has been linked
        return $group->subGroups;
    }
}
