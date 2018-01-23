<?php

namespace RZP\Models\Transaction;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class BulkUpdate extends Base\Core
{
    public function updateMultipleTransactions(string $merchantId, string $channel)
    {
        $transactionIds = $this->repo
                               ->transaction
                               ->fetchUnsettledTransactionsForMerchantUpdate($merchantId)
                               ->pluck(Entity::ID)
                               ->toArray();

        $count = $this->repo
             ->transaction
             ->bulkChannelUpdateForMerchantTransactions(
                 $merchantId,
                 $transactionIds,
                 $channel);

        return $count;
    }
}
