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
            $group->admins()->sync($input['admins']);
        }

        if (isset($input['sub_groups']) === true)
        {
            $group->subGroups()->sync($input['sub_groups']);
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

        $group->edit($input);

        if (isset($input['admins']) === true)
        {
            $group->admins()->sync($input['admins']);
        }

        if (isset($input['sub_groups']) === true)
        {
            $group->subGroups()->sync($input['sub_groups']);
        }

        $this->repo->saveOrFail($group);

        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        return $group;
    }
}

