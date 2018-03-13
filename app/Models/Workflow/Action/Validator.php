<?php

namespace RZP\Models\Workflow\Action;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicEntity;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_ID     => 'sometimes|nullable|string|max:14',
        Entity::ENTITY_NAME   => 'sometimes|string|max:255',
        Entity::MAKER_ID      => 'required|string|max:14',
        Entity::MAKER_TYPE    => 'required|string|max:11|custom',
        Entity::WORKFLOW_ID   => 'required|string|max:14',
        Entity::PERMISSION_ID => 'required|string|max:14',
        Entity::ORG_ID        => 'required|string|max:14'
    ];

    protected static $editRules = [
        Entity::TITLE                 => 'sometimes|string',
        Entity::DESCRIPTION           => 'sometimes|string',
        Entity::APPROVED              => 'sometimes|boolean',
        Entity::STATE                 => 'sometimes|string|max:25',
        Entity::STATE_CHANGER_ROLE_ID => 'sometimes|nullable|string|max:14',
        Entity::STATE_CHANGER_ID      => 'sometimes|nullable|string|max:14',
    ];

    public function validateLiveActionsOnEntity(string $entityId, string $entity, string $permissionName)
    {
        $entityId = PublicEntity::stripDefaultSign($entityId);

        $actions = (new Core)->fetchOpenActionOnEntityOperation(
            $entityId, $entity, $permissionName);

        $actions = $actions->toArray();

        // If there are any action in progress
        if (empty($actions) === false)
        {
            $actionIds = [];

            foreach ($actions as $action)
            {
                $actionIds[] = $action[Entity::ID];
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ANOTHER_ACTION_IN_PROGRESS,
                null, $actionIds);
        }
    }

    public function validateCloseAction($admin)
    {
        $action = $this->entity;

        $makerType = $action->getMakerType();

        $canCloseAction = false;

        // If maker of action is an admin
        if ($makerType === MakerType::ADMIN)
        {
            // If admin is the creator of action
            // or admin is SuperAdmin then close should be allowed
            if (($action->getMakerId() === $admin->getId()) or
                ($admin->isSuperAdmin() === true))
            {
                $canCloseAction = true;
            }
        }
        // If maker of action is a merchant
        else if ($makerType === MakerType::MERCHANT)
        {
            // Only SuperAdmin can close for now.
            // Other admins should just reject, we'll see
            // later if they want any admin to be able to close
            // or not.
            if ($admin->isSuperAdmin() === true)
            {
                $canCloseAction = true;
            }
        }

        if ($canCloseAction === false)
        {
            $data = [
                'action_admin_id' => $action->getMakerId(),
                'auth_admin_id'   => $admin->getId(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_CLOSE_UNAUTHORIZED,
                null,
                $data);
        }
    }

    public function validateActionIsOpen(Entity $action = null)
    {
        if ($action === null)
        {
            $action = $this->entity;
        }

        if ($action->isClosed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_CLOSED);
        }
    }

    public function validateMakerType($attribute, $makerType)
    {
        if (MakerType::exists($makerType) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACTION_INVALID_TYPE);
        }
    }
}
