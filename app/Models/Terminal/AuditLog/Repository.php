<?php

namespace RZP\Models\Terminal\AuditLog;

use RZP\Models\Base;
use RZP\Models\Terminal\AuditLog\Entity;
use RZP\Exception;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'terminal_auditlog';


    public function findForTerminal($id)
    {
        $repo = $this->repo->terminal_auditlog;

        return $repo::withTrashed()
            ->where(Entity::TERMINAL_ID, '=', $id)
            ->get();
    }

    public function findForPayment($payment_id, $id=null)
    {
        $repo = $this->repo->terminal_auditlog;

        $results =  $repo::withTrashed()
                         ->where(Entity::PAYMENT_ID, '=', $payment_id);

        if($id !== null)
        {
            $results = $results->where(Entity::TERMINAL_ID, '=', $id);
        }

        return $results->get();
    }

    public function findBetweenTimestampsForTerminal($from, $to, $terminal_id, $payment_id=null)
    {
        $repo = $this->repo->terminal_auditlog;

        $results = $repo::withTrashed()
                        ->where(Entity::TERMINAL_ID, '=', $terminal_id)
                        ->where(Entity::TIMESTAMP, '>=', $from)
                        ->where(Entity::TIMESTAMP, '<=', $to);

        if($payment_id !== null)
        {
            $results = $results->where(Entity::PAYMENT_ID, '=', $payment_id);
        }

        return $results->get();
    }
}
