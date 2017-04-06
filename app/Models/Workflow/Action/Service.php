<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Base;
use RZP\Models\Workflow\Action\Differ;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $action = $this->core()->create($input);

        return $action->toArrayPublic();
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

        $actions = $this->repo->workflow_action->findByOrgId($orgId);

        return $actions->toArrayPublic();
    }

    public function getActionDetails(string $actionId)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $orgId = $admin->getOrgId();

        Entity::verifyIdAndStripSign($actionId);

        $relations = ['workflow.steps', 'admin'];

        $action = $this->repo
                       ->workflow_action
                       ->findByIdAndOrgId($actionId, $orgId, $relations)
                       ->first();

        $checkers = $this->repo
                         ->action_checker
                         ->fetchByActionId($actionId);

        $data = $action->toArrayPublicWithAdminAndSteps();

        $data['checkers'] = $checkers->map(function ($checker) {
            return $checker-toPublicArray();
        })->toArray();

        return $data;
    }

    public function getStatesOfAction(string $actionId)
    {
        Entity::verifyIdAndStripSign($actionId);

        $states = $this->repo
                       ->action_state
                       ->fetchStateTransitionsByActionId($actionId)
                       ->map(function ($state) {
                        return $state->toArrayPublic();
                       })
                       ->toArray();

        return $states;
    }
}
