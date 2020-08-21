<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Jobs\MerchantSync;
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

        return $this->checkAdminAccessToMerchantIds($nodes, $merchant->id);
    }

    private function getAllNodes($admin)
    {
        // 1. Get all the groups of the admin

        $groups = $admin->groups->toArray();

        $parentGroupIds = [];

        $allSubAdminAssociativeIds = [];

        $allSubGroupAssociativeIds = [];

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

        foreach ($allSubAdminIds as $subAdminId)
        {
            $allSubAdminAssociativeIds[$subAdminId] = $subAdminId;
        }

        foreach ($allSubGroupIds as $subGroupId)
        {
            $allSubGroupAssociativeIds[$subGroupId] = $subGroupId;
        }

        // We have all the subgroups (recursively) in $allSubGroupIds now
        return [
            'groups' => $allSubGroupAssociativeIds,
            'admins' => $allSubAdminAssociativeIds,
        ];
    }

    private function checkAdminAccessToMerchantIds($nodes, $merchantId): bool
    {
        $groups = $nodes['groups'];

        $admins = $nodes['admins'];

        $associativeEntities = array_merge($groups, $admins);

        //
        // This is un setting as this is default for all claimed merchants will give return record
        // In case any admin access to SF_CLAIMED_MERCHANTS_GROUP_ID will give access to all merchants
        //
        unset($associativeEntities[Constant::SF_CLAIMED_MERCHANTS_GROUP_ID]);

        $entities = array_values($associativeEntities);

        //
        // Query Has been Merged for separate 2
        // queries for groups and admins
        // because ENTITY_IDs are unique across Entity
        //
        $record = \DB::table('merchant_map')
                     ->whereIn('entity_id', $entities)
                     ->where('merchant_id', $merchantId)
                     ->count();

        if ($record > 0)
        {
            return true;
        }

        return false;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $groupIds
     *
     * @return array
     */
    public function removeMerchantFromGroups(Merchant\Entity $merchant, array $groupIds)
    {
        $this->trace->info(TraceCode::MERCHANT_GROUP_DETACH_REQUEST,
                           [
                               'action'   => 'detach_in_merchant_map',
                               'merchantId' => $merchant->getId(),
                               'groupIds' => $groupIds,
                           ]
        );

        $this->repo->detach($merchant, 'groups', $groupIds);

        return $merchant->toArrayPublic();
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $groupIds
     *
     * @return array
     */
    public function addMerchantToGroups(Merchant\Entity $merchant, array $groupIds)
    {
        $this->trace->info(TraceCode::MERCHANT_GROUP_ATTACH_REQUEST,
                           [
                               'action'   => 'attach_in_merchant_map',
                               'merchantId' => $merchant->getId(),
                               'groupIds' => $groupIds,
                           ]
        );

        $this->repo->sync($merchant, 'groups', $groupIds,false);

        return $merchant->toArrayPublic();
    }
}
