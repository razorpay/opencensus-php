<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(string $orgId, array $input)
    {
        $group = (new Entity)->build($input);

        $group->getValidator()->validateCreateInput($orgId, $input);

        $org = $this->repo->org->findOrFail($orgId);

        $group->org()->associate($org);

        $this->repo->saveOrFail($group);

        if (isset($input['admins']) === true)
        {
            $admins = $this->repo->admin->retrieveByIds(
                $orgId, $input['admins']);

            foreach ($admins as $admin)
            {
                $group->admins()->attach($admin);
            }
        }

        if (isset($input['sub_groups']) === true)
        {
            $subGroups = $this->repo->group->retrieveByIds(
                $orgId, $input['sub_groups']);

            foreach ($subGroups as $subGroup)
            {
                $group->subGroups()->attach($subGroup);
            }
        }

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $group->getId());

        return $group;
    }

    public function delete(string $orgId, string $groupId)
    {
        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        $this->repo->deleteOrFail($group);

        return $group;
    }

    public function edit(string $orgId, string $groupId, array $input)
    {
        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        if (isset($input['admins']) === true)
        {
            $group->admins()->sync($input['admins']);

            unset($input['admins']);
        }

        if (isset($input['sub_groups']) === true)
        {
            $group->subGroups()->sync($input['sub_groups']);

            unset($input['sub_groups']);
        }

        $group->edit($input);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        return $group;
    }
}

