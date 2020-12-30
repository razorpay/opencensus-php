<?php

namespace RZP\Models\Application\ApplicationMerchantMaps;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'application_merchant_mapping';

    public function getAppMerchantMap(string $appId, string $merchantId)
    {
        $merchantIdColumn      = $this->dbColumn(Entity::MERCHANT_ID);
        $appIdColumn           = $this->dbColumn(Entity::APP_ID);

        return $this->newQuery()
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($appIdColumn, '=', $appId)
                    ->first();
    }

    public function getAllUsedApps()
    {
        return $this->newQuery()
                    ->select(Entity::APP_ID)
                    ->get();
    }

    public function getEnabledAppsForMerchant(string $merchantId)
    {
        $merchantIdColumn      = $this->dbColumn(Entity::MERCHANT_ID);
        $enabledColumn         = $this->dbColumn(Entity::ENABLED);
        $attrs                 = $this->dbColumn(Entity::APP_ID);

        return $this->newQuery()
                    ->select($attrs)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($enabledColumn, true)
                    ->get()
                    ->toArray();
    }
}
