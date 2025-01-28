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

        if(isset($input[Entity::MAX_LIMIT_PER_WORKING_DAY]) && $input[Entity::MAX_LIMIT_PER_WORKING_DAY] !== "")
        {
            $data[Entity::MAX_LIMIT_PER_WORKING_DAY] = $input[Entity::MAX_LIMIT_PER_WORKING_DAY];
        }

        $featureConfig = (new Entity)->build($data);

        $featureConfig->generateId();

        $this->createFeatureConfigInCapitalEs($featureConfig);

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

        if (isset($input[Entity::MAX_LIMIT_PER_WORKING_DAY]) === true && $input[Entity::MAX_LIMIT_PER_WORKING_DAY] !== "")
        {
            $featureConfig->setMaxLimitPerWorkingDay($input[Entity::MAX_LIMIT_PER_WORKING_DAY]);
        }

        $this->updateFeatureConfigInCapitalEs($featureConfig);

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
            return $this->getFeatureConfigFromCapitalEs($merchantId);
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

    private function createFeatureConfigInCapitalEs($featureConfig)
    {
        if ($this->mode === Constants\Mode::TEST)
        {
            return;
        }

        $this->app['capital_early_settlements']->createMerchantFeatureConfig($featureConfig->toArray());
    }

    private function updateFeatureConfigInCapitalEs($featureConfig)
    {
        if ($this->mode === Constants\Mode::TEST)
        {
            return;
        }

        $this->app['capital_early_settlements']->updateMerchantFeatureConfig($featureConfig->toArray());
    }

    private function getFeatureConfigFromCapitalEs($merchantId)
    {
        if ($this->mode === Constants\Mode::TEST)
        {
            return (new Repository)->getConfigByMerchantId($merchantId);
        }

        $response = $this->app['capital_early_settlements']->getFeatureConfig('merchant', $merchantId);
        $featureConfig = $response['merchant_feature_config'];

        $data = [
            Entity::MERCHANT_ID                 => $featureConfig[Entity::MERCHANT_ID],
            Entity::MAX_AMOUNT_LIMIT            => (int) $featureConfig[Entity::MAX_AMOUNT_LIMIT],
            Entity::PERCENTAGE_OF_BALANCE_LIMIT => (int) $featureConfig[Entity::PERCENTAGE_OF_BALANCE_LIMIT],
            Entity::SETTLEMENTS_COUNT_LIMIT     => (int) $featureConfig[Entity::SETTLEMENTS_COUNT_LIMIT],
            Entity::ES_PRICING_PERCENT          => (int) $featureConfig[Entity::ES_PRICING_PERCENT],
        ];

        if (isset($featureConfig[Entity::MAX_LIMIT_PER_WORKING_DAY]) === true)
        {
            $data[Entity::MAX_LIMIT_PER_WORKING_DAY] = (int) $featureConfig[Entity::MAX_LIMIT_PER_WORKING_DAY];
        }
        if (isset($featureConfig[Entity::PRICING_PERCENT]) === true)
        {
            $data[Entity::PRICING_PERCENT] = (int) $featureConfig[Entity::PRICING_PERCENT];
        }

        return (new Entity)->build($data);
    }
}
