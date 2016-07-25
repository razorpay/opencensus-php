<?php

namespace RZP\Models\Terminal\Absence;

use RZP\Models\Base;
use RZP\Models\Terminal\Absence\Entity;
use RZP\Exception;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'terminal_absence';



    public function findForTerminal($terminal)
    {
        $repo = $this->repo->terminal_absence;

        return $repo::withTrashed()
            ->where(Entity::TERMINAL_ID, '=', $terminal->getId())
            ->get();
    }

    public function findForGateway($gateway)
    {
        $repo = $this->repo->terminal_absence;

        $results =  $repo::withTrashed()
                         ->where(Entity::GATEWAY, '=', $gateway->getId());

        return $results->get();
    }

    public function findBetweenTimestampsForGateway($from, $to, $gateway=null)
    {
        $repo = $this->repo->terminal_absence;

        $results = $repo::withTrashed()
                        ->where(Entity::DOWNTIME_FROM, '>=', $from)
                        ->where(Entity::DOWNTIME_TO, '<=', $to);

        if($gateway !== null)
        {
            $results = $results->where(Entity::GATEWAY, '=', $gateway->getId());
        }

        return $results->get();
    }
}
