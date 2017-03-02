<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function create(array $input, Org\Entity $org)
    {
        $group = (new Entity)->generateId();

        $group->buildWithOrg($input, $org->getId());

        $this->repo->group->validateOrgHasNoSuchGroup($group, $org);

        $group->org()->associate($org);

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

        return $group;
    }

    protected function associateRelevantEntitiesToGroup($input, Entity $group)
    {

        if (isset($input['parents']) === true)
        {
            Entity::verifyIdAndStripSignMultiple($input['parents']);

            $this->repo->sync($group, 'parents', $input['parents']);
        }
        else
        {
            // Deletion of all
            $this->repo->sync($group, 'parents', []);
        }
    }
}
