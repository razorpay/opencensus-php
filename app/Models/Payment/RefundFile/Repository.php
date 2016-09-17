<?php

namespace RZP\Models\Payment\RefundFile;

use RZP\Models\Base;
use RZP\Models\Payment\RefundFile;
use RZP\Exception;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'refund_file';

    protected $proxyFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_num'
    ];

    public function findUnprocessedRefunds($limit = 10)
    {
        $status = array(RefundFile::CREATED, RefundFile::FAILURE);
        return $this->newQuery()
                    ->whereIn(RefundFile\Entity::STATUS, $status)
                    ->where(RefundFile\Entity::RETRY_ATTEMPT, '<', 3)
                    ->orderBy(RefundFile\Entity::CREATED_AT, 'asc')
                    ->limit($limit)
                    ->get();
    }

}
