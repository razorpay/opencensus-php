<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'batch';

    protected $proxyFetchParamRules = [
        Entity::TYPE        => 'sometimes|in:refund',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
        Entity::TYPE        => 'sometimes|in:refund',
        Entity::STATUS      => 'sometimes|in:created,processing,processed',
    ];

    /**
     * Finds unprocessed batches by type.
     * We have choose a limit of estimated 10. For now it should work.
     * If needs we'll increase the limit later or change the logic around it.
     *
     * @param string  $type
     * @param integer $limit
     *
     * @return Base\PublicCollection
     */
    public function fetchUnprocessedByType(
        string $type,
        $limit = 10): Base\PublicCollection
    {
        $status = [Status::CREATED, Status::PROCESSING];

        return $this->newQuery()
                    ->where(Entity::TYPE, $type)
                    ->whereIn(Entity::STATUS, $status)
                    ->oldest()
                    ->limit($limit)
                    ->get();
    }
}
