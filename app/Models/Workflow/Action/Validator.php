<?php

namespace RZP\Models\Workflow\Action;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicEntity;
use RZP\Error\PublicErrorDescription;
use  RZP\Models\Workflow\Action\Checker\Entity as CheckerEntity;

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
        Entity::STATE_CHANGER_TYPE    => 'required_with:state_changer_id|nullable|string|max:255',
        Entity::OWNER_ID              => 'sometimes|nullable|string|max:14',
    ];

    protected static $needClarificationRules = [
        Constants::MESSAGE_BODY       => 'required|string|max:800',
        Constants::MESSAGE_SUBJECT    => 'required|string|max:100',
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

            $description = null;

            if (App::getFacadeRoot()['basicauth']->isAdminAuth() === true)
            {
                $description = PublicErrorDescription::BAD_REQUEST_WORKFLOW_ANOTHER_ACTION_IN_PROGRESS .
                    ' Id: ' . implode(',', $actionIds);
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ANOTHER_ACTION_IN_PROGRESS,
                null, $actionIds, $description);
        }
    }

    public function validateCloseAction($maker, bool $autoclose)
    {
        $this->validateIfCloseSupportedForPermission();

        if ($autoclose === false)
        {
            $this->canClose($maker);
        }
    }

    private function canClose($maker)
    {
        $action = $this->entity;

        $makerType = $action->getMakerType();

        $canCloseAction = false;

        // If maker of action is an admin
        if ($makerType === MakerType::ADMIN)
        {
            // If admin is the creator of action
            // or admin is SuperAdmin then close should be allowed
            if (($action->getMakerId() === $maker->getId()) or
                ($maker->isSuperAdmin() === true))
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
            if ($maker->isSuperAdmin() === true)
            {
                $canCloseAction = true;
            }
        }

        if ($canCloseAction === false)
        {
            $data = [
                'action_admin_id' => $action->getMakerId(),
                'auth_admin_id'   => $maker->getId(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_CLOSE_UNAUTHORIZED,
                null,
                $data);
        }
    }

    public function validateMakerIsNotCheckerOrOwner(Entity $action, $checkerEntity, $checkerType = 'admin')
    {
        $makerId = $action->getMakerId();

        $makerType = $action->getMakerType();

        $permission = $action->permission->getName();

        if ((($checkerType === CheckerEntity::ADMIN) and
            ($checkerEntity->isSuperAdmin() === true)) or
            (in_array($permission, Constants::WORKFLOWS_EXCLUDED_FOR_MAKER_IS_SAME_AS_CHECKER_OR_OWNER_VALIDATION) === true))
        {
            return false;
        }

        if (($makerType === CheckerEntity::ADMIN) and
            ($checkerType === CheckerEntity::ADMIN) and
            ($makerId === $checkerEntity->getId()))
        {
            return true;
            /*
            throw new Exception\BadRequestValidationFailureException(
                'You cannot work on your own workflows');
            */
        }
        return false;
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

    public function validateWorkflowActionForNeedMerchantClarification(Entity $action)
    {
        $permissionName = $action->permission->getName();

        if (in_array($permissionName,Constants::WORKFLOWS_FOR_NEED_MERCHANT_CLARIFICATION, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_WORKFLOW_FOR_NEED_MERCHANT_CLARIFICATION);
        }
    }

    private function validateIfCloseSupportedForPermission()
    {
        $action = $this->entity;

        $permissionName = $action->permission->getName();

        if(in_array($permissionName, Constants::CLOSE_OPERATION_UNSUPPORTED_PERMISSIONS) === false)
        {
            return;
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_WORKFLOW_CLOSE_NOT_SUPPORTED,
            null,
            ["workflow_name" => $action->workflow->getName()]);
    }
}
