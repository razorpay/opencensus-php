<?php

namespace RZP\Models\Terminal\AuditLog;

use RZP\Models\Base;
use RZP\Models\Terminal\AuditLog\Entity;
use RZP\Exception;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'TerminalAuditLog';


    public function findForTerminal($terminal)
    {
        $repo = $this->repo;

        return $repo::withTrashed()
            ->where(Entity::TERMINAL_ID, '=', $terminal->getId())
            ->get();
    }

    public function findForPayment($payment, $terminal=null)
    {
        $repo = $this->repo;

        $results =  $repo::withTrashed()
                         ->where(Entity::PAYMENT_ID, '=', $payment->getId());

        if($terminal !== null)
        {
            $results = $results->where(Entity::TERMINAL_ID, '=', $terminal->getId());
        }

        return $results->get();
    }

    public function findBetweenTimestampsForPayment($from, $to, $payment, $terminal=null)
    {
        $repo = $this->repo;

        $results = $repo::withTrashed()
                        ->where(Entity::PAYMENT_ID, '=', $payment->getId())
                        ->where(Entity::TIMESTAMP, '>=', $from)
                        ->where(Entity::TIMESTAMP, '<=', $to);

        if($terminal !== null)
        {
            $results = $results->where(Entity::TERMINAL_ID, '=', $terminal->getId());
        }

        return $results->get();
    }
}
