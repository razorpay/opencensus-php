<?php

namespace RZP\Models\Merchant\Product;

use RZP\Models\Base\Repository as BaseRepository;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends BaseRepository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_product';

    protected $proxyFetchParamRules = [
        Entity::MERCHANT_ID  => 'sometimes|string|size:14',
        Entity::PRODUCT_NAME => 'sometimes|string',
    ];

    public function fetchMerchantProductConfigByProductName(string $merchantId, string $productName)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::PRODUCT_NAME, '=', $productName)
                    ->first();
    }

    public function fetchMerchantProductConfigByProductNames(string $merchantId, array $productNames)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->whereIn(Entity::PRODUCT_NAME, $productNames)
                    ->get();
    }

    public function fetchMerchantProductConfigByProductId(string $merchantId, string $merchantProductId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::ID, '=', $merchantProductId)
                    ->first();
    }
}
