<?php

namespace RZP\Models\Payment\BatchRefund;

use RZP\Models\Base;
use RZP\Models\Payment\BatchRefund\Entity as BatchRefund;
use RZP\Exception;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'batch_refund';

    protected $proxyFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_num'
    ];

    public function findUnprocessedRefunds($limit = 10)
    {
        $status = array(BatchRefund::CREATED, BatchRefund::FAILURE);
        return $this->newQuery()
                    ->whereIn(BatchRefund::STATUS, $status)
                    ->where(BatchRefund::RETRY_ATTEMPT, '<', 3)
                    ->orderBy(BatchRefund::CREATED_AT, 'asc')
                    ->limit($limit)
                    ->get();
    }

}
