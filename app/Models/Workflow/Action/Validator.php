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
    ];

    public function validateLiveActionsOnEntity(
        string $entity,
        string $entityId)
    {
        $app = App::getFacadeRoot();

        $orgId = $app['basicauth']->getAdminOrgId();

        $actions = (new Repository)->findOpenActionsByOrgId($orgId);

        $openActionIds = array_map(function ($action){
            return $action[Entity::ID];
        }, $actions->toArray());

        $diffs = (new Differ\Core)->fetchByEntityAndEntityId(
            $entity, $entityId, $openActionIds);

        if (empty($diffs) === false)
        {
            $actionIds = [];

            foreach ($diffs as $diff)
            {
                $actionIds[] = $diff[Differ\Entity::ACTION_ID];
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

