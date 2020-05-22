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

    public function findByParams(array $input)
    {
        $query = $this->newQuery();

        if (isset($input[Entity::MERCHANT_ID]) === true)
        {
            $query->where(Entity::MERCHANT_ID, $input[Entity::MERCHANT_ID]);
        }

        if (isset($input['d2c_bureau_report_id']) === true)
        {
            $query->where(Entity::ID, $input['d2c_bureau_report_id']);
        }

        if (isset($input[Entity::USER_ID]) === true)
        {
            $query->where(Entity::USER_ID, $input[Entity::USER_ID]);
        }

        return $query->get()->last();
    }
}
