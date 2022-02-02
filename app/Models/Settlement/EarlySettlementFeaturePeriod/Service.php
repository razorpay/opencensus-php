<?php

namespace RZP\Models\Settlement\EarlySettlementFeaturePeriod;

use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Ondemand;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\SettlementOndemand\DisableES;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Models\Settlement\Ondemand\FeatureConfig;


class Service extends Base\Service
{
    public function enableFeaturePeriod(array $inputs)
    {
        $result = new Base\PublicCollection;

        foreach ($inputs as $input)
        {
            $this->app['api.mutex']->acquireAndReleaseStrict(
                'early_settlement_feature_period_'.$input[Entity::MERCHANT_ID],
                function() use ($input, $result) {

                    $this->trace->info(TraceCode::EARLY_SETTLEMENT_FEATURE_PERIOD_CREATE, [
                        'input' => $input,
                    ]);

                    $idempotencyKey = $input[\RZP\Models\Batch\Constants::IDEMPOTENCY_KEY] ?? '';

                    unset($input[\RZP\Models\Batch\Constants::IDEMPOTENCY_KEY]);

                    try
                    {
                        (new Validator)->validateInput(Validator::EARLY_SETTLEMENT_FEATURE_PERIOD_INPUT, $input);

                        $this->merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

                        $this->app['basicauth']->setMerchant($this->merchant);

                        $this->repo->transaction(function () use ($input)
                        {
                            $featurePeriod = null;

                            try
                            {
                                $featurePeriod = $this->core()->getFeaturePeriodByMerchantId($input[Entity::MERCHANT_ID]);
                            }
                            catch (\Exception $e)
                            {
                                $this->trace->traceException(
                                    $e,
                                    null);
                            }

                            $this->createOrUpdateFeaturePeriod($input, $featurePeriod);

                            $this->createOrUpdatePricing($input[Entity::FULL_ACCESS], $input[Entity::ES_PRICING]);

                            if ($featurePeriod === null)
                            {
                                $this->enableEarlySettlement($input);
                            }

                        });

                        $result->push([
                            'idempotency_key'   => $idempotencyKey,
                            'success'           => true,
                        ]);

                    }
                    catch (\Throwable $e)
                    {
                        $result->push([
                            'idempotency_key'   => $idempotencyKey,
                            'success'           => false,
                            'error'             => [
                                Error::DESCRIPTION       => $e->getMessage(),
                                Error::PUBLIC_ERROR_CODE => $e->getCode(),
                            ]
                        ]);

                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::EARLY_SETTLEMENT_FEATURE_PERIOD_CREATION_FAILURE,
                            [
                                'merchant_id' => $input[Entity::MERCHANT_ID]
                            ]);
                    }

                });
        }

        $this->trace->info(
            TraceCode::EARLY_SETTLEMENT_FEATURE_PERIOD_CREATION_RESPONSE,
            [
                'response' => $result->toArrayWithItems(),
            ]);

        return $result->toArrayWithItems();
    }

    public function disableFeaturePeriod(): array
    {
        DisableES::dispatch($this->mode);

        return [
            'response' => 'DisableES job dispatched',
        ];
    }

    public function disableFeature($merchantId)
    {
        $this->trace->info(TraceCode::EARLY_SETTLEMENT_FEATURE_PERIOD_DELETE, [
            'merchant_id' => $merchantId,
        ]);

        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['basicauth']->setMerchant($this->merchant);

        $featurePeriod = (new Repository)->findFeaturePeriodByMerchantId($merchantId);

        $this->repo->transaction(function () use ($featurePeriod)
        {
            $this->removeFeatureFlag($featurePeriod->getFeature());

             if ($featurePeriod->getFeature() === Feature\Constants::ES_AUTOMATIC)
             {
                 $this->disableScheduledES($featurePeriod);
             }

            (new Repository)->deleteFeaturePeriod($featurePeriod);

            (new Ondemand\Core)->updateOndemandPricingPercentByFeature($this->merchant,
                $featurePeriod->getInitialOndemandPricing(),
                PricingFeature::SETTLEMENT_ONDEMAND);
        });
    }

    private function createOrUpdateFeatureConfig($input)
    {
        try
        {
            $featureConfig = (new FeatureConfig\Core)->getFeatureConfigByMerchantId($input[Entity::MERCHANT_ID]);

            $featureConfigInput = [
                FeatureConfig\Entity::MAX_AMOUNT_LIMIT    => $input[Entity::AMOUNT_LIMIT],
                FeatureConfig\Entity::ES_PRICING_PERCENT  => $input[Entity::ES_PRICING]
            ];

            (new FeatureConfig\Core)->updateFeatureConfig($featureConfig, $featureConfigInput);
        }
        catch (\Exception $e)
        {
            $featureConfigInput = [
                FeatureConfig\Entity::MERCHANT_ID                 => $input[Entity::MERCHANT_ID],
                FeatureConfig\Entity::MAX_AMOUNT_LIMIT            => $input[Entity::AMOUNT_LIMIT],
                FeatureConfig\Entity::SETTLEMENTS_COUNT_LIMIT     => FeatureConfig\Entity::DEFAULT_SETTLEMENTS_COUNT_LIMIT,
                FeatureConfig\Entity::PERCENTAGE_OF_BALANCE_LIMIT => FeatureConfig\Entity::DEFAULT_PERCENTAGE_OF_BALANCE_LIMIT,
                FeatureConfig\Entity::PRICING_PERCENT             => FeatureConfig\Entity::DEFAULT_PRICING_PERCENT,
                FeatureConfig\Entity::ES_PRICING_PERCENT          => $input[Entity::ES_PRICING]
            ];

            (new FeatureConfig\Core)->createFeatureConfig($featureConfigInput);
        }
    }

    private function createOrUpdateFeaturePeriod($input, $featurePeriod)
    {
        if ($featurePeriod ===  null)
        {
            $this->core()->createFeaturePeriod($input, $this->merchant);

        }
        else
        {
            $this->core()->updateFeaturePeriod($featurePeriod, $input);

            $this->createOrUpdateFeatureConfig($input);
        }
    }

    private function createOrUpdatePricing($fullAccess, $pricing)
    {
        if ($fullAccess === 'no')
        {
            (new Ondemand\Service)->createOrUpdatePricingRule($this->merchant, $pricing,
                                                             PricingFeature::ESAUTOMATIC_RESTRICTED);
        }
        else
        {
            $pricingPlanId = $this->merchant->getPricingPlanId();

            (new \RZP\Models\Merchant\Service)->createOrUpdateScheduledEarlySettlementPricing($pricingPlanId,
                                                                                                    $pricing);
        }
    }

    private function enableEarlySettlement($input)
    {
        if ($input[Entity::FULL_ACCESS] === 'yes')
        {
            (new \RZP\Models\Merchant\Service)->enableScheduledEs(true, false);
        }
        else if ($input[Entity::FULL_ACCESS] ===  'no')
        {
            $this->createOrUpdateFeatureConfig($input);

            (new \RZP\Models\Merchant\Service)->enablePartialScheduledEs(false);
        }
    }

    private function removeFeatureFlag($featureFlag)
    {
        if ($this->merchant->isFeatureEnabled($featureFlag) === true)
        {
            $feature = (new Feature\Repository) ->findByEntityTypeEntityIdAndNameOrFail(
                Feature\Constants::MERCHANT,
                $this->merchant->getId(),
                $featureFlag);

            (new Feature\Core)->delete($feature, true);
        }
    }

    private function disableScheduledES($featurePeriod)
    {
        $initialOndemandPricing  = $featurePeriod->getInitialOndemandPricing();

        $initialScheduleId  = $featurePeriod->getInitialScheduleId();

        (new \RZP\Models\Merchant\Service)->disableScheduledES($initialOndemandPricing, $initialScheduleId);
    }

}
