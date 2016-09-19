<?php

namespace RZP\Models\Terminal\Action;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Terminal\Action;
use RZP\Models\Terminal;

class Service extends Base\Service
{
    public function createAction($gateway, $input)
    {
        $action = (new Action\Core)->create($input, $gateway);

        return $action->toArrayPublic();
    }

    public function getActionsForTerminal($id)
    {
        $actions = $this->repo->terminal_action->findForTerminal($id);

        return $actions->toArrayPublic();
    }

    public function deleteTerminalActions($id)
    {
        $repo = $this->repo->terminal_action;

        return $repo->where(Action\Entity::TERMINAL_ID, '=', $id)
                    ->delete();
    }

    public function getActionsForTerminalBetweenTimestamps($id, $from, $to)
    {
        $actions = $this->repo->terminal_action->findBetweenTimesampsForTerminal($from, $to, $id);

        return $actions->toArrayPublic();
    }
}
