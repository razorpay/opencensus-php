<?php

namespace RZP\Models\Terminal\Action;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Terminal\Action;

class Repository extends Base\Repository
{
    protected $entity = 'terminal_action';

    public function findForTerminal($id)
    {
        return $this->newQuery()
                    ->where(Entity::TERMINAL_ID, '=', $id)
                    ->get();
    }

    public function findBetweenTimesampsForTerminal($from, $to, $id)
    {
        return $this->newQuery()
                    ->where(Entity::TERMINAL_ID, '=', $id)
                    ->where(Entity::CREATED_AT, '>=', $from)
                    ->where(Entity::CREATED_AT, '<=', $to)
                    ->get();
    }
}
