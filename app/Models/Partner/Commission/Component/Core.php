<?php

namespace RZP\Models\Partner\Commission\Component;

use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Commission;

class Core extends Base\Core
{

    public function getCommissionComponent($feeSplit, array $merchantFeeComponents, string $commissionPrefix, string $source): Entity
    {
        $commissionComponent = new Entity;

        $partnerCommissionFee = $feeSplit->filter(function($split) use ($commissionPrefix, $source) {
            return ($split->getName() === $commissionPrefix.$source);
        })->first();

        $commissionComponent->setCommissionPricingAmount($partnerCommissionFee->getAmount());

        $commissionComponent->setCommissionPricingPlanRuleId($partnerCommissionFee->getPricingRule());

        $commissionPricingRule = $this->repo->pricing->getPricingFromPricingId($partnerCommissionFee->getPricingRule());

        $pricingType = Pricing\Type::PRICING === $commissionPricingRule->getType() ? Commission\Constants::VARIABLE : Commission\Constants::FIXED;

        $commissionComponent->setCommissionPricingType($pricingType);

        $commissionComponent->setCommissionPricingPercentage($commissionPricingRule->getPercentRate());

        $commissionComponent->setCommissionPricingFixed($commissionPricingRule->getFixedRate());

        $this->fillMerchantFeeSplitComponents($commissionComponent, $merchantFeeComponents);

        $this->trace->info(TraceCode::COMMISSION_COMPONENTS_FILLED, $commissionComponent->toArrayPublic());

        return $commissionComponent;

    }

    private function fillMerchantFeeSplitComponents(Entity $component, array $merchantFeeComponents)
    {
        $component->setMerchantPricingAmount($merchantFeeComponents[Entity::MERCHANT_PRICING_AMOUNT]);

        $component->setMerchantPricingPercentage($merchantFeeComponents[Entity::MERCHANT_PRICING_PERCENTAGE]);

        $component->setMerchantPricingFixed($merchantFeeComponents[Entity::MERCHANT_PRICING_FIXED]);

        $component->setMerchantPricingPlanRuleId($merchantFeeComponents[Entity::MERCHANT_PRICING_PLAN_RULE_ID]);
    }
}