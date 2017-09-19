<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Admin\Org;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Jobs\MerchantSync;
use RZP\Models\Admin\Action;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->adminOrgId = $this->app['basicauth']->getAdminOrgId();
    }

    public function createGroup(array $input)
    {
        $org = $this->repo->org->findOrFailPublic($this->adminOrgId);

        $group = $this->repo->transactionOnLiveAndTest(function() use ($org, $input)
        {
            return $this->core()->create($input, $org);
        });

        return $group->toArrayPublic();
    }

    public function getGroup(string $groupId)
    {
        $relations = [
            'admins', 'merchants', 'subGroups',
            'parents', 'roles',
        ];

        $group = $this->repo->group->findByPublicIdAndOrgIdWithRelations(
            $groupId, $this->adminOrgId, $relations);

        return $group->toArrayPublic();
    }

    public function editGroup(string $groupId, array $input)
    {
        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $this->adminOrgId);

        $group = $this->repo->transactionOnLiveAndTest(function() use ($group, $input)
        {
            return $this->core()->edit($group, $input);
        });

        return $group->toArrayPublic();
    }

    public function deleteGroup(string $groupId)
    {
        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $this->adminOrgId);

        $group->setAuditAction(Action::DELETE_GROUP);

        $this->repo->deleteOrFail($group);

        $payload = [Entity::ID => $group->getId()];

        (new Merchant\Core)->syncEventToEs(MerchantSync::GROUP_DELETE, $payload);

        return $group->toArrayDeleted();
    }

    public function fetchMultiple()
    {
        $groups = $this->repo->group->fetchByOrgId($this->adminOrgId);

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
    public function fetchEligibleParents(string $groupId, array $input)
    {
        $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $this->adminOrgId);

        // Get all groups of the current organization
        $allGroups = $this->repo->group->fetchByOrgId($this->adminOrgId);

        $allGroups = $allGroups->toArray();

        $filteredGroups = $this->filterEligibleParents($this->adminOrgId, $group, $allGroups);

        return $filteredGroups;
    }

    public function getChildrenHierarchy($orgId, $groupId)
    {
        $nodes = [];

        // Get all direct children of incoming groupId
        $childrenGroups = $this->getChildrenGroups($orgId, $groupId)->toArray();

        // Throw all direct children in the rejected node list
        $nodes = $childrenGroups;

        foreach ($childrenGroups as $group)
        {
            // For every child group, check its further direct children
            $rejects = $this->getChildrenHierarchy($orgId, $group['id']);

            $nodes = array_merge($nodes, $rejects);
        }

        return $nodes;
    }

    /**
    * This functions does all the filtering. It gets all the groups in parent hierarchy
    * and all the siblings and removes these from the array of all org groups and returns
    * the difference.
    */
    protected function filterEligibleParents(string $orgId, Entity $group, $allGroups)
    {
        $currentGroup = [ $group ];

        $groupId = $group->getId();

        // Get entire parent lineage (recursively)
        $rejectParents = $this->getRejectParents($orgId, $groupId);

        // Get entire children tree/hierarchy (recursively)
        $rejectChildren = $this->getChildrenHierarchy($orgId, $groupId);

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
                    break;
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
                $siblingChildren = $this->getChildrenHierarchy($orgId, $sibling['id']);

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
        $group = $this->repo->group->findByIdAndOrgId($groupId, $orgId);

        // Get all the direct parents to which the group has been linked
        return $group->parents;
    }

    protected function getChildrenGroups(string $orgId, string $groupId)
    {
        // Get the group from current org
        $group = $this->repo->group->findByIdAndOrgId($groupId, $orgId);

        // Get all the direct children to which the group has been linked
        return $group->subGroups;
    }
}
