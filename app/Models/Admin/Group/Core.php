<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Admin;

class Core extends Base\Core
{
    public function create(array $input, Org\Entity $org)
    {
        $group = (new Entity)->build($input);

        $this->repo->group->validateOrgHasNoSuchGroup($group, $org);

        $group->org()->associate($org);

        $this->repo->saveOrFail($group);

        $this->associateRelevantEntitiesToGroup($input, $group);

        return $group;
    }

    public function edit(Entity $group, array $input)
    {
        $group->edit($input);

        if (isset($input['parents']) === true)
        {
            Entity::verifyIdAndStripSignMultiple($input['parents']);

            $group->parents()->sync($input['parents']);
        }

        $this->repo->saveOrFail($group);

        return $group;
    }

    protected function associateRelevantEntitiesToGroup($input, Entity $group)
    {
        if (isset($input['admins']) === true)
        {
            Admin\Entity::verifyIdAndStripSignMultiple($input['admins']);
            $group->admins()->sync($input['admins']);
        }

        if (isset($input['sub_groups']) === true)
        {
            Entity::verifyIdAndStripSignMultiple($input['sub_groups']);
            $group->subGroups()->sync($input['sub_groups']);
        }

        if (isset($input['parents']) === true)
        {
            Entity::verifyIdAndStripSignMultiple($input['parents']);
            $group->parents()->sync($input['parents']);
        }

        if (isset($input['roles']) === true)
        {
            Role\Entity::verifyIdAndStripSignMultiple($input['roles']);
            $group->roles()->sync($input['roles']);
        }
    }
}
