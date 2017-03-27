<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Base;
use RZP\Models\Workflow\Action\Differ;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $action = $this->core()->create($input);

        $diff = (new Differ\Core)->fetchRequest($action);

        $diff = $diff[Differ\Entity::DIFF];

        $response = [
            'action'  => $action->toArrayPublic(),
            'success' => true,
            'diff'    => $diff,
        ];

        return $response;
    }

    public function get(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $action = $this->core()->get($id);

        return $action->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $orgId = $admin->getOrgId();

        $input[Entity::ORG_ID] = $orgId;

        $actions = $this->repo->workflow_action->fetch($input);

        return $actions->toArrayPublic();
    }

    public function getActionDetails(string $actionId)
    {
        $admin = $this->repo['basicauth']->getAdmin();

        $orgId = $admin->getOrgId();

        Action\Entity::verifyIdAndStripSign($actionId);

        $action = $this->repo
                       ->workflow_action
                       ->findByIdAndOrgId($actionId, $orgId);

        $checkers = $this->repo
                         ->action_checker
                         ->fetchByActionId($actionId);

        $data = [
            'action'   => $action->toArrayPublic(),
            'checkers' => $checkers->toArrayPublic(),
        ];

        return $data;
    }

    public function getStatesOfAction(string $actionId)
    {
        Entity::verifyIdAndStripSign($actionId);

        $states = $this->repo
                       ->action_state
                       ->fetchStateTransitionsByActionId($actionId);

        return $states->toArrayPublic();
    }
}
