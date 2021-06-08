<?php

namespace RZP\Models\Settlement\Ondemand\FeatureConfig;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function createFeatureConfig($input)
    {
        $data = [
            Entity::MERCHANT_ID                  => $input[Entity::MERCHANT_ID],
            Entity::MAX_AMOUNT_LIMIT             => $input[Entity::MAX_AMOUNT_LIMIT],
            Entity::SETTLEMENTS_COUNT_LIMIT      => $input[Entity::SETTLEMENTS_COUNT_LIMIT],
            Entity::PERCENTAGE_OF_BALANCE_LIMIT  => $input[Entity::PERCENTAGE_OF_BALANCE_LIMIT],
            Entity::PRICING_PERCENT              => $input[Entity::PRICING_PERCENT]
        ];

        $featureConfig = (new Entity)->build($data);

        $featureConfig->generateId();

        $this->repo->saveOrFail($featureConfig);

        return $featureConfig;
    }

    public function updateFeatureConfig($featureConfig, $input)
    {
        $featureConfig->setMaxAmountLimit($input[Entity::MAX_AMOUNT_LIMIT]);

        $featureConfig->setSettlementsCountLimit($input[Entity::SETTLEMENTS_COUNT_LIMIT]);

        $featureConfig->setPercentageOfBalanceLimit($input[Entity::PERCENTAGE_OF_BALANCE_LIMIT]);

        $featureConfig->setPricingPercent($input[Entity::PRICING_PERCENT]);

        $this->repo->saveOrFail($featureConfig);

        return $featureConfig;
    }

    public function getFeatureConfigByMerchantId($merchantId)
    {
        return (new Repository)->getConfigByMerchantId($merchantId);
    }
}
