<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::LOW_BALANCE_CONFIG;

    public function findByBalanceIdMerchantIdAndType(string $balanceId, string $merchantId, string $type)
    {
        $query = $this->newQuery()
                      ->where(Entity::BALANCE_ID, '=', $balanceId)
                      ->where(Entity::MERCHANT_ID, '=', $merchantId);

        if ($type === Entity::AUTOLOAD_BALANCE)
        {
            $query = $query->where(Entity::TYPE, '=', Entity::LOW_BALANCE_TYPE_SERIALIZER[Entity::AUTOLOAD_BALANCE]);
        }
        else
        {
            $query = $query->where(Entity::TYPE, '!=', Entity::LOW_BALANCE_TYPE_SERIALIZER[Entity::AUTOLOAD_BALANCE]);
        }

        return $query->get();
    }

    public function getTotalEnabledConfigsCount()
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ENABLED)
                    ->get()
                    ->count();
    }

    public function getEnabledBalanceConfigsForAlert($limit)
    {
        $lowBalanceConfigAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($lowBalanceConfigAttrs)
                    ->where(Entity::STATUS, '=', Status::ENABLED)
                    ->orderByRaw(Entity::CREATED_AT . ' desc,' . Entity::ID . ' desc')
                    ->limit($limit)
                    ->get();
    }

    public function getBalanceConfigsForAlertUsingLastFetchedConfig($limit, $lastFetchedConfig)
    {
        $lowBalanceConfigAttrs = $this->dbColumn('*');

        // https://use-the-index-luke.com/no-offset
        // using seek(or keyset pagination) instead of offset for better query performance
        return $this->newQuery()
                    ->select($lowBalanceConfigAttrs)
                    ->where(Entity::STATUS, '=', Status::ENABLED)
                    ->whereRaw('('.Entity::CREATED_AT . ',' . Entity::ID . ')' . '< (?,?)',
                       [$lastFetchedConfig->getCreatedAt(), $lastFetchedConfig->getId()])
                    ->orderByRaw(Entity::CREATED_AT . ' desc,' . Entity::ID . ' desc')
                    ->limit($limit)
                    ->get();
    }
}
