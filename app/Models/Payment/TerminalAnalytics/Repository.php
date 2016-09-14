<?php

namespace RZP\Models\Payment\TerminalAnalytics;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'terminal_analytics';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|alpha_num',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num',
    );

    public function findForTerminal($id)
    {
        $repo = $this->repo;

        return $repo->where(Entity::TERMINAL_ID, '=', $id)
                    ->get();
    }

    /**
     * @param $paymentId
     * @param $id
     * @return mixed
     */
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