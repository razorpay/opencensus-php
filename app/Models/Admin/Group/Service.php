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
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createGroup(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        if (isset($input['admins']) === true)
        {
            $adminIds = [];

            foreach ($input['admins'] as $adminId)
            {
                $adminIds[] = Admin\Entity::verifyIdAndStripSign($adminId);
            }

            $input['admins'] = $adminIds;
        }

        if (isset($input['sub_groups']) === true)
        {
            $groupIds = [];

            foreach ($input['sub_groups'] as $groupId)
            {
                $groupIds[] = Entity::verifyIdAndStripSign($groupId);
            }

            $input['sub_groups'] = $groupIds;
        }

        if (isset($input['roles']) === true)
        {
            $roleIds = [];

            foreach ($input['roles'] as $roleId)
            {
                $roleIds[] = Role\Entity::verifyIdAndStripSign($roleId);
            }

            $input['roles'] = $roleIds;
        }

        if (isset($input['parents']) === true)
        {
            $parentGroupIds = [];

            foreach ($input['parents'] as $parentGroupId)
            {
                $parentGroupIds[] = Entity::verifyIdAndStripSign($parentGroupId);
            }

            $input['parents'] = $parentGroupIds;
        }

        $group = $this->core->create($orgId, $input);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $group->getId());

        return $group->toArrayPublic();
    }

    public function getGroup(string $orgId, string $groupId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        return $group->toArrayPublic();
    }

    public function editGroup(string $orgId, string $groupId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        if (isset($input['parents']) === true)
        {
            $groupIds = [];

            foreach ($input['parents'] as $parentId)
            {
                $groupIds[] = Entity::verifyIdAndStripSign($parentId);
            }

            $input['parents'] = $groupIds;
        }

        $group = $this->core->edit($orgId, $groupId, $input);

        return $group->toArrayPublic();
    }

    public function deleteGroup(string $orgId, string $groupId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $data = $this->core->delete($orgId, $groupId);

        return ['success' => true];
    }

    public function fetchMultiple(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $groups = $this->repo->group->fetchGroupsForOrg($orgId, $input);

        return $groups->toArrayPublic();
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
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        // Get all groups of the current organization
        $allGroups = $this->repo->group->fetchGroupsForOrg($orgId, $input);

        $allGroups = $allGroups->toArray();

        $filteredGroups = $this->filterEligibleParents($orgId, $groupId, $allGroups);

        return $filteredGroups;
    }

    public function addRoleToGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $roleIds = $input['role_ids'];
        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        // Not using sync with Ids and roles
        foreach ($roleIds as $roleId)
        {
            $role = $this->repo->role->findOrFail($roleId);

            $this->repo->group->addRoleToGroup($group, $role);
        }
    }

    public function addMerchantsToGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $merchantIds = $input['merchant_ids'];

        // TODO Wrap it in a transaction or sync the m2m field
        // Check for merchantIds
        foreach ($merchantIds as $merchantId)
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $group = $this->repo->group->retrieveByOrgIdAndIdOrFail($orgId, $groupId);

            $this->repo->group->addMerchantToGroup($group, $merchant);
        }
    }

    public function addAdminsToGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        $adminIds = [];

        if (isset($input['admins']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Admins not given in the input');
        }

        foreach ($input['admins'] as $adminId)
        {
            $adminId = Admin\Entity::verifyIdAndStripSign($adminId);

            $admin = $this->repo->admin->findOrFail($adminId);

            $this->repo->group->addAdminToGroup($group, $admin);
        }

        return $group->toArrayPublic();
    }

    public function revokeRoleFromGroup(
        string $orgId,
        string $groupId,
        array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $groupId = Entity::verifyIdAndStripSign($groupId);
        $roleId = Merchant\Entity::verifyIdAndStripSign($roleId);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        $role = $this->repo->role->findOrFail($roleId);

        $this->repo->group->revokeRoleOrFail($group, $role);
    }

    /**
    * This functions does all the filtering. It gets all the groups in parent hierarchy
    * and all the siblings and removes these from the array of all org groups and returns
    * the difference.
    */
    protected function filterEligibleParents(string $orgId, string $groupId, $allGroups)
    {
        // Get entire parent lineage (recursively)
        $rejectParents = $this->getRejectParents($orgId, $groupId);

        // Get entire children tree/hierarchy (recursively)
        $rejectChildren = $this->getRejectChildren($orgId, $groupId);

        // Get sublings **and** its tree/hierarchy (recursively)
        $rejectSiblings = $this->getRejectSiblings($orgId, $groupId);

        // Merge all the groups to be rejected
        $rejects = array_merge($rejectParents, $rejectChildren, $rejectSiblings);

        // Remove $rejects from $allGroups
        $filtered = [];

        foreach ($allGroups as $group)
        {
            $isReject = false;

            foreach ($rejects as $reject)
            {
                if (($group['id'] === $reject['id']) or
                    ($group['id'] === $groupId))
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
    protected function getRejectChildren($orgId, $groupId)
    {
        $rejectNodes = [];

        // Get all direct children of incoming groupId
        $childrenGroups = $this->getChildrenGroups($orgId, $groupId)->toArray();

        // Throw all direct children in the rejected node list
        $rejectNodes = $childrenGroups;

        foreach ($childrenGroups as $group)
        {
            // For every child group, check its further direct children
            $rejects = $this->getRejectChildren($orgId, $group['id']);

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
                $siblingChildren = $this->getRejectChildren($orgId, $sibling['id']);

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
