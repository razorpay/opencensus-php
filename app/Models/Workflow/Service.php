<?php

namespace RZP\Models\Workflow;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;

class Service extends Base\Service
{
    public function create(array $input)
    {
        Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);

        Permission\Entity::verifyIdAndStripSignMultiple($input['permissions']);

        $workflow = $this->core()->create($input);

        return ['success' => true];
    }

    public function fetch(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        return $workflow->toArrayPublic();
    }

    // TODO - workflow updates only allowed for name and permissions.
    // If a new permission is added, we will have trigger new and perhaps
    // older actions to follow the workflow is available.
    public function update(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        $workflow = $this->core()->update($workflow, $input);

        return $workflow->toArrayPublic();
    }

    // TODO - Cascade and delete everything ?
    public function delete(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        return $this->core()->delete($workflow);
    }

    public function getActionsForChecker()
    {
        $data = (new Manager)->getActionsForAdmin();

        return $data;
    }

    public function getActionsByMaker()
    {
        $actions = (new Manager)->getActionsByMaker();

        return $actions->toArrayPublic();
    }
}
