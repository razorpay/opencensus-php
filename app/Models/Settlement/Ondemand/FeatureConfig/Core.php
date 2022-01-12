<?php

namespace RZP\Models\Settlement\Ondemand\FeatureConfig;

use Exception;
use RZP\Constants;
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
            Entity::PRICING_PERCENT              => $input[Entity::PRICING_PERCENT],
            Entity::ES_PRICING_PERCENT           => $input[Entity::ES_PRICING_PERCENT]
        ];

        $featureConfig = (new Entity)->build($data);

        $featureConfig->generateId();

        $this->repo->saveOrFail($featureConfig);

        return $featureConfig;
    }

    public function updateFeatureConfig($featureConfig, $input)
    {
        if (isset($input[Entity::MAX_AMOUNT_LIMIT]) === true)
        {
            $featureConfig->setMaxAmountLimit($input[Entity::MAX_AMOUNT_LIMIT]);
        }

        if (isset($input[Entity::SETTLEMENTS_COUNT_LIMIT]) === true)
        {
            $featureConfig->setSettlementsCountLimit($input[Entity::SETTLEMENTS_COUNT_LIMIT]);
        }

        if (isset($input[Entity::PERCENTAGE_OF_BALANCE_LIMIT]) === true)
        {
            $featureConfig->setPercentageOfBalanceLimit($input[Entity::PERCENTAGE_OF_BALANCE_LIMIT]);
        }

        if (isset($input[Entity::PRICING_PERCENT]) === true)
        {
            $featureConfig->setPricingPercent($input[Entity::PRICING_PERCENT]);
        }

        if (isset($input[Entity::ES_PRICING_PERCENT]) === true)
        {
            $featureConfig->setEsPricingPercent($input[Entity::ES_PRICING_PERCENT]);
        }

        $this->repo->saveOrFail($featureConfig);

        return $featureConfig;
    }

    /**
     * @throws Exception
     */
    public function getFeatureConfigByMerchantId($merchantId)
    {
        try
        {
            return (new Repository)->getConfigByMerchantId($merchantId);
        }
        catch (Exception $e)
        {
            if($this->mode === Constants\Mode::TEST)
            {
                $data = [
                    Entity::MERCHANT_ID                  => $merchantId,
                    Entity::MAX_AMOUNT_LIMIT             => 10000,
                    Entity::SETTLEMENTS_COUNT_LIMIT      => 1000000,
                    Entity::PERCENTAGE_OF_BALANCE_LIMIT  => 50,
                    Entity::PRICING_PERCENT              => 30,
                    Entity::ES_PRICING_PERCENT           => 12
                ];

                return (new Entity)->build($data);
            }
            throw $e;
        }
    }
}
