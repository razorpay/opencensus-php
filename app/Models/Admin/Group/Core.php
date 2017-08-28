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

            $this->repo->sync($group, 'parents', $input['parents']);
        }
        else
        {
            // Deletion of all
            $this->repo->sync($group, 'parents', []);
        }
    }
}
