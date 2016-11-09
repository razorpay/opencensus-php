<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(string $orgId, array $input)
    {
        $org = $this->repo->org->findOrFailPublic($orgId);

        $admin = (new Entity)->build($input);

        $group->org()->associate($org);

        $this->repo->saveOrFail($group);

        return $group;
    }

    public function delete(string $orgId, string $groupId)
    {
        $group = $this->repo->group->retrieveByOrgIdAndIdOrFail(
            $orgId, $groupId);

        $this->repo->deleteOrFail($group);

        return $group;
    }
}

