<?php

namespace RZP\Models\Pricing\FeeBreakup;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Transaction;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'fee_breakup';

    public function fetchFeesBreakupInvoice($merchantId, $from, $to)
    {
        $txnIds = (new Transaction\Repository)->getTransactionForReport($merchantId, $from, $to);

        $feesBreakup = $this->newQuery()
                            ->whereIn(Entity::TRANSACTION_ID, $txnIds)
                            ->selectRaw(Entity::NAME . ','.
                                'SUM(' . Entity::AMOUNT . ') AS sum')
                            ->groupBy(Entity::NAME)
                            ->get();

        return $feesBreakup;
    }

}
