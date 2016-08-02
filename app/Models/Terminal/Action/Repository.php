<?php

namespace RZP\Models\Terminal\Action;

use RZP\Models\Base;
use RZP\Models\Terminal\Action\Entity;
use RZP\Exception;
use RZP\Models\Terminal\Action;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'terminal_action';

    public function createAction($id, $input)
    {
        $action = (new Action\Core)->create($input, $id);

        return $action;
    }


    public function findForTerminal($id)
    {
        $repo = $this->repo->terminal_action;

        return $repo::withTrashed()
            ->where(Entity::TERMINAL_ID, '=', $id)
            ->get();
    }

    public function findBetweenTimesampsForTerminal($from, $to, $id)
    {
        $repo = $this->repo->terminal_action;

        return $repo::withTrashed()
                ->where(Entity::TERMINAL_ID, '=', $id)
                ->where(Entity::CREATED_AT, '>=', $from)
                ->where(Entity::CREATED_AT, '<=', $to)
                ->get();
    }
}
