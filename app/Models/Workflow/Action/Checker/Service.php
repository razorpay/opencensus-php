<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;

class Service extends Base\Service
{
    public function create(string $actionId, array $input)
    {
        $actionId = Action\Entity::verifyIdAndStripSign($actionId);

        $input[Entity::ACTION_ID] = $actionId;

        $checker = $this->core()->create($input);

        return $checker->toArrayPublic();
    }

    public function fetchMultiple(string $actionId, array $input)
    {
        $actionId = Action\Entity::verifyIdAndStripSign($actionId);

        $action = $this->repo->workflow_action->findOrFailPublic($actionId);

        $admin = $this->app['basicauth']->getAdmin();

        // The one who is querying for the action and action's org must be same
        // TODO put cross org access when required
        (new Action\Validator)->validateActionBelongsToAdminOrg(
            $action, $admin);

        $input[Entity::ACTION_ID] = $actionId;

        $checkers = $this->repo->action_checker->fetch($input);

        return $checkers->toArrayPublic();
    }

    public function get(string $actionId, string $checkerId)
    {
        $checkerId = Entity::verifyIdAndStripSign($checkerId);

        $checker = $this->repo->action_checker->findByIdAndActionId(
            $checkerId, $actionId);

        return $checker->toArrayPublic();
    }
}
