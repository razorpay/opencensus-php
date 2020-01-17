<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ADMIN_ID     => 'sometimes|string|max:14',
        Entity::ACTION_ID    => 'required|string|max:14',
        Entity::STEP_ID      => 'required|string|max:14',
        Entity::APPROVED     => 'required|boolean',
        Entity::USER_COMMENT => 'sometimes|string|max:255',
    ];

    protected static $createValidators = [
        Entity::ADMIN_ID,
    ];

    public function validateAdminId(array $input)
    {
        // This validator makes sense but now to keep it
        // simple (for MVP) we'll let the maker check his
        // own action.

        return;

        // $action = (new Action\Repository)->findOrFailPublic(
        //     $input[Entity::ACTION_ID]);

        // if ($action->getAdminId() === $input[Entity::ADMIN_ID])
        // {
        //     $data = [
        //         'action_id' => $action->getId(),
        //         'admin_id'  => $admin->getId(),
        //     ];

        //     throw new Exception\BadRequestValidationFailureException(
        //         ErrorCode::BAD_REQUEST_WORKFLOW_INVALID_CHECKER, null,
        //         $data);
        // }
    }
}
