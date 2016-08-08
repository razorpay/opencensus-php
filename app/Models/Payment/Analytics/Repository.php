<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'payment_analytics';

    public function findForTerminal($id)
    {
        $repo = $this->repo;

        return $repo->where(Entity::TERMINAL_ID, '=', $id)
                    ->get();
    }

    public function findForPayment($paymentId, $id = null)
    {
        $repo = $this->repo;

        $results =  $repo->where(Entity::PAYMENT_ID, '=', $paymentId);

        if ($id !== null)
        {
            $results = $results->where(Entity::TERMINAL_ID, '=', $id);
        }

        return $results->get();
    }
}
