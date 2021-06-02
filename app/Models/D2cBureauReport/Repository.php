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
                    ->where(function ($query)
                    {
                        $query->whereNotNull(Entity::SCORE)
                              ->orWhereNotNull(Entity::NTC_SCORE);
                    })
                    ->get()
                    ->last();
    }

    public function getReportsForCsvCreation($provider)
    {
        return $this->newQuery()
                    ->where(Entity::PROVIDER, $provider)
                    ->whereNull(Entity::CSV_REPORT_UFH_FILE_ID)
                    ->whereNotNull(Entity::SCORE)
                    ->get();
    }

    public function findByParams(array $input)
    {
        $query = $this->newQuery()
                      ->where(function ($query)
                        {
                            $query->whereNotNull(Entity::SCORE)
                                  ->orWhereNotNull(Entity::NTC_SCORE);
                        });

        if (empty($input['merchant_id']) === false)
        {
            $query->where(Entity::MERCHANT_ID, $input[Entity::MERCHANT_ID]);
        }

        if (empty($input['d2c_bureau_report_id']) === false)
        {
            $query->where(Entity::ID, $input['d2c_bureau_report_id']);
        }

        if (empty($input[Entity::USER_ID]) === false)
        {
            $query->where(Entity::USER_ID, $input[Entity::USER_ID]);
        }

        return $query->get()->last();
    }
}
