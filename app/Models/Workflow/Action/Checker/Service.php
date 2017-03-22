<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Exception;
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

        $action = $this->repo->action_checker->findOrFailPublic($actionId);

        $admin = $this->app['basicauth']->getAdmin();

        (new Action\Validator)->validateActionBelongsTAdminOrg($action, $admin);

        $input[Entity::ACTION_ID] = $actionId;

        $checkers = $this->repo->action_checker->fetch($input);

        return $checkers->toArrayPublic();
    }

    public function get(string $actionId, string $checkerId)
    {
        $checkerId = Entity::verifyIdAndStripSign($checkerId);

        return $this->repo->action_checker->findByIdAndActionId($checkerId, $actionId);
    }

    public function fetchActionsForChecker(array $input)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $roleId  = $admin->role->getId();

        // - Action on maker need to trigger a workflow.
        // - Once triggered on every check action corresponding
        //   values should be set in cache and cleared from cache
        //   as necessary.
        // - Here just pull from the corresponding cache.
    }
}
