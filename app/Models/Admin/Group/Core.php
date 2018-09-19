<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Jobs\MerchantSync;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function create(array $input, Org\Entity $org)
    {
        $group = (new Entity)->generateId();

        $group->org()->associate($org);

        $group->build($input);

        $this->repo->group->validateOrgHasNoSuchGroup($group, $org);

        $group->setAuditAction(Action::CREATE_GROUP);

        $this->repo->saveOrFail($group);

        $this->associateRelevantEntitiesToGroup($input, $group);

        return $group;
    }

    public function edit(Entity $group, array $input)
    {
        $group->setAuditAction(Action::EDIT_GROUP);

        $group->edit($input);

        $this->repo->saveOrFail($group);

        $this->associateRelevantEntitiesToGroup($input, $group);

        $payload = [Entity::ID => $group->getId()];

        // If the parent (groups) hierarchy changed
        (new Merchant\Core)->syncEventToEs(MerchantSync::GROUP_EDIT, $payload);

        return $group;
    }

    protected function associateRelevantEntitiesToGroup($input, Entity $group)
    {

        if (isset($input['parents']) === true)
        {
            Entity::verifyIdAndStripSignMultiple($input['parents']);

            $this->repo->group->validateExists($input['parents']);

            $this->repo->sync($group, 'parents', $input['parents']);
        }
        else
        {
            // Deletion of all
            $this->repo->sync($group, 'parents', []);
        }
    }

    public function groupCheck($admin, $merchant)
    {
        // TODO: Enforce there's no cycle in the graph (while creation/assigning)

        // If the admin has access to all the merchants then just return true
        if ($admin->canSeeAllMerchants())
        {
            return true;
        }

        // 1. Get all the required groups and admins for the $admin

        // Get all groups and admins required to look into in case
        // there's a hierarchy (or actually a graph)
        $nodes = $this->getAllNodes($admin);

        $merchantIds = $this->getMerchantIdsOfNodes($nodes);

        if (in_array($merchant->id, $merchantIds))
        {
            return true;
        }

        return false;
    }

    private function getAllNodes($admin)
    {
        // 1. Get all the groups of the admin

        $groups = $admin->groups->toArray();

        $parentGroupIds = [];

        foreach ($groups as $group)
        {
            $parentGroupIds[] = $group['id'];
        }

        // We have all the parent group IDs now
        // 2. Get all the sub groups of the parent groups now

        // To get the sub/child groups, raw query would be something
        // like this:
        // SELECT entity_id FROM group_map WHERE group_id IN ($groupIds)
        // This gets all the groups that belong to (child/sub) $groupIds

        $allSubGroupIds = $parentGroupIds;

        // Adding current admin also because current admin can have direct merchants.
        $allSubAdminIds = [$admin->getId()];

        $groupIds = $parentGroupIds;

        $exit = false;

        while (!$exit)
        {
            $subGroups = \DB::table('group_map')
                ->whereIn('group_id', $groupIds)
                ->where('entity_type', 'group')
                ->get();

            $groupIds = [];

            foreach ($subGroups as $subGroup)
            {
                $groupIds[] = $subGroup->entity_id;

                $allSubGroupIds[] = $subGroup->entity_id;
            }

            if (count($groupIds) === 0)
            {
                $exit = true;
            }

            // Also get all the admins

            $subAdmins = \DB::table('group_map')
                ->whereIn('group_id', $groupIds)
                ->where('entity_type', 'admin')
                ->get();

            foreach ($subAdmins as $subAdmin)
            {
                $allSubAdminIds[] = $subAdmin->entity_id;
            }
        }

        // We have all the subgroups (recursively) in $allSubGroupIds now
        return [
            'groups' => $allSubGroupIds,
            'admins' => $allSubAdminIds
        ];
    }

    private function getMerchantIdsOfNodes($nodes)
    {
        $groups = $nodes['groups'];

        $admins = $nodes['admins'];

        $allMerchantIds = [];

        // TODO: I think we can merge these 2 queries
        // because ENTITY_IDs are unique across

        $merchants = \DB::table('merchant_map')
            ->whereIn('entity_id', $groups)
            ->where('entity_type', 'group')
            ->get();

        foreach ($merchants as $merchant)
        {
            $allMerchantIds[] = $merchant->merchant_id;
        }

        $merchants = \DB::table('merchant_map')
            ->whereIn('entity_id', $admins)
            ->where('entity_type', 'admin')
            ->get();

        foreach ($merchants as $merchant)
        {
            $allMerchantIds[] = $merchant->merchant_id;
        }

        return $allMerchantIds;
    }
}
