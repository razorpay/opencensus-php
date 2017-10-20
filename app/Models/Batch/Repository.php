<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'batch';

    protected $proxyFetchParamRules = [
        Entity::TYPE        => 'sometimes|string|custom',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
        Entity::STATUS      => 'sometimes|in:created,processing,processed',
    ];

    protected function validateType($attribute, $value)
    {
        Type::validateType($value);
    }

    /**
     * Finds unprocessed batches to be processed via CRON.
     *
     * We have choose a limit of estimated 10. For now it should work.
     * If needs we'll increase the limit later or change the logic around it.
     *
     * @param integer $limit
     *
     * @return Base\PublicCollection
     */
    public function fetchUnprocessedForCron($limit = 10): Base\PublicCollection
    {
        return $this->newQuery()
                    ->whereIn(Entity::TYPE, Type::$cronGroup)
                    ->where(Entity::STATUS, Status::CREATED)
                    ->oldest()
                    ->limit($limit)
                    ->get();
    }
}
