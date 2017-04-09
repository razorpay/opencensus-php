<?php

namespace RZP\Models\Workflow\Action;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ADMIN_ID    => 'required|string|max:14',
        Entity::WORKFLOW_ID => 'required|string|max:14',
        Entity::ORG_ID      => 'required|string|max:14',
        Entity::DIFFER      => 'required|array',
    ];

    protected static $editRules = [
        Entity::TITLE       => 'sometimes|string',
        Entity::DESCRIPTION => 'sometimes|string',
        Entity::APPROVED    => 'sometimes|boolean',
        Entity::STATE       => 'sometimes|string|max:25',
    ];

    public function validateLiveActionsOnEntity(
        string $entity,
        string $entityId)
    {
        $app = App::getFacadeRoot();

        $orgId = $app['basicauth']->getAdminOrgId();

        $actions = (new Differ\Core)->fetchByEntityAndEntityId(
            $entity, $entityId);

        // If there are any action in progress
        if (empty($actions) === false)
        {
            $actionIds = [];

            foreach ($actions as $action)
            {
                $actionIds[] = $action['_source'][Differ\Entity::ACTION_ID];
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ANOTHER_ACTION_IN_PROGRESS,
                $actionIds);
        }
    }

    public function validateActionBelongsToAdminOrg($action, $admin)
    {
        if ($admin->getOrgId() !== $action->admin->getOrgId())
        {
            $data = [
                'admin'      => $admin->getId(),
                'admin_org'  => $admin->getOrgId(),
                'action'     => $action->getId(),
                'action_org' => $action->getOrgId(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND,
                $data);
        }
    }
}
