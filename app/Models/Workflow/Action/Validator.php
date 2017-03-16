<?php

namespace RZP\Models\Workflow\Action;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ADMIN_ID     => 'required|string|max:14',
        Entity::WORKFLOW_ID  => 'required|string|max:14',
    ];

    protected static $editRules = [
        Entity::APPROVED  => 'required|boolean',
    ];

    public function validateActionBelongsToAdminOrg($action, $admin)
    {
        if ($admin->getOrgId() !== $action->getAdmin()->getOrgId())
        {
            $data = [
                'admin' => $admin->getId(),
                'admin_org' => $admin->getOrgId(),
                'action' => $action->getId(),
                'action_org' => $action->getOrgId(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND,
                $data);
        }
    }
}

