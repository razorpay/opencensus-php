<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $permission = (new Entity)->build($input);

        $permission->generateId();

        $permission->setAuditAction(Action::CREATE_PERMISSION);

        $this->repo->saveOrFail($permission);

        return $permission;
    }

    public function edit(Entity $permission, array $input)
    {
        $permission->edit($input);

        $permission->setAuditAction(Action::EDIT_PERMISSION);

        $this->repo->saveOrFail($permission);

        return $permission;
    }

    public function delete(Entity $permission)
    {
        $permission->setAuditAction(Action::DELETE_PERMISSION);

        $this->repo->deleteOrFail($permission);

        return $permission;
    }
}
