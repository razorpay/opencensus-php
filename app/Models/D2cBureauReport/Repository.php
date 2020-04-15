<?php

namespace RZP\Models\D2cBureauReport;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'd2c_bureau_report';

    public function findByProviderDetailIdAndMerchantIdCreatedAfter(string $provider, string $detailId, string $merchantId, int $after)
    {
        return $this->newQuery()
                    ->where(Entity::D2C_BUREAU_DETAIL_ID, $detailId)
                    ->where(Entity::PROVIDER, $provider)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::CREATED_AT, '>=', $after)
                    ->get()
                    ->last();
    }

    public function getReportsForCsvCreation($provider)
    {
        return $this->newQuery()
                    ->where(Entity::PROVIDER, $provider)
                    ->whereNull(Entity::CSV_REPORT_UFH_FILE_ID)
                    ->get();
    }
}
