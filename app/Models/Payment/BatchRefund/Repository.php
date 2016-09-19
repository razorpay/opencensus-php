<?php

namespace RZP\Models\Payment\BatchRefund;

use RZP\Models\Base;
use RZP\Models\Payment\BatchRefund\Entity as BatchRefund;
use RZP\Models\Payment\BatchRefund\BatchRefundStatus;
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
        $status = array(BatchRefundStatus::CREATED, BatchRefundStatus::FAILURE, BatchRefundStatus::IN_PROGRESS);
        return $this->newQuery()
                    ->whereIn(BatchRefund::STATUS, $status)
                    ->where(BatchRefund::ATTEMPTS, '<=', 3)
                    ->oldest()
                    ->limit($limit)
                    ->get();
    }

    public function getBatchRefunds($merchantId, $skip, $take = 10)
    {
        return $this->newQuery()
                    ->latest()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->skip($skip)
                    ->take($take)
                    ->get();
    }

}
