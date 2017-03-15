<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ACTION_ID => 'required|string|max:14|custom',
        Entity::STEP_ID   => 'required|string|max:14',
        Entity::APPROVED  => 'required|boolean',
    ];

    protected static $createValidators = [
        Entity::ADMIN_ID,
    ];

    public function validateAdminId(array $input)
    {
        $action = $this->repo->workflow_action->findByPublicId(
            $input[Entity::ACTION_ID]);

        if ($action->getAdminId() === $input[Entity::ADMIN_ID])
        {
            $data = [
                'action_id' => $action->getId(),
                'admin_id'  => $admin->getId(),
            ];

            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_INVALID_CHECKER,
                $data);
        }
    }

    public function validateActionId(string $attr, string $actionId)
    {
        $action = $this->repo->workflow_action->findByPublicId(
            $actionId);

        if ($action->isClosed() === true)
        {
            $data = [
                'action_id' => $action->getId(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_CLOSED,
                $data);
        }
    }
}

