<?php

namespace RZP\Models\Terminal\Action;

use RZP\Models\Base;
use RZP\Models\Terminal\Action\Entity;
use RZP\Exception;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'TerminalActionLog';


    public function findForTerminal($terminal)
    {
        $repo = $this->repo;

        return $repo::withTrashed()
            ->where(Entity::TERMINAL_ID, '=', $terminal->getId())
            ->get();
    }

    public function findBetweenTimesampsForTerminal($from, $to, $terminal)
    {
        $repo = $this->repo;

        return $repo::withTrashed()
                ->where(Entity::TERMINAL_ID, '=', $terminal->getId())
                ->where(Entity::TIMESTAMP, '>=', $from)
                ->where(Entity::TIMESTAMP, '<=', $to)
                ->get();
    }
}
