<?php

namespace RZP\Models\Merchant\Balance\BalanceConfig;

use RZP\Models\Base;
use RZP\Exception\AssertionException;

class Repository extends Base\Repository
{
    protected $entity = 'balance_config';

    protected $appFetchParamRules = [
        Entity::BALANCE_ID      => 'sometimes|alpha_num',
        Entity::TYPE            => 'sometimes|string',
    ];

    /**
     * @param $balanceConfig
     * @throws AssertionException
     */
    public function createBalanceConfig($balanceConfig)
    {
        assertTrue($balanceConfig->exists === false);

        $balanceConfig->saveOrFail();
    }

    public function getBalanceConfigsForBalanceIds(array $balanceIds): Base\PublicCollection
    {
        return $this->newQuery()
            ->whereIn(Entity::BALANCE_ID, $balanceIds)
            ->get();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }
}
