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

    public function fetTerminalAnalyticsForPayments($payments = array())
    {
        $paymentIds = array();

        foreach($payments as $payment)
        {
            $paymentIds[] = $payment->getId();
        }

        return $this->newQuery()
            ->whereIn(Entity::PAYMENT_ID, $paymentIds)
            ->get();
    }
}