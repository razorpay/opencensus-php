<?php

namespace RZP\Models\Payment\TerminalAnalytics;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'terminal_analytics';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|alpha_num',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num',
    );

    public function fetchUsedTerminalsForPaymentIds($paymentIds = [])
    {
        return $this->newQuery()
            ->whereIn(Entity::PAYMENT_ID, $paymentIds)
            ->get();
    }
}
