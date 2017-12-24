<?php

namespace RZP\Models\Pricing;

use RZP\Models\Bank;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment\Processor;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createPricingPlan($input)
    {
        (new Pricing\Core())->createPricing($input);

        $plan = $this->repo->pricing->getPricingPlanByName($input[Entity::PLAN_NAME]);

        return $plan->toArrayPublic();
    }

    public function addPricingPlanRule($id, $input)
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_ADD_ATTEMPT,
            ['id' => $id, $input]);

        $plan = $this->repo->pricing->getPricingPlanByIdOrFailPublic($id);

        $rule = (new Pricing\Core)->addPlanRule($plan, $input);

        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_ADD_SUCCESS,
            [$rule->toArray()]);

        return $rule->toArray();
    }

    public function getPricingPlanById($id)
    {
        $pricingPlan = $this->repo->pricing->getPricingPlanById($id);

        return $pricingPlan->toArrayPublic();
    }

    public function getPricingPlans()
    {
        $pricingPlans = $this->repo->pricing->getPricingPlansOrderedByPlanId();

        return $pricingPlans->toArrayMultiplePlansPublic();
    }

    public function getMerchantPricingPlans()
    {
        $pricingPlans = $this->repo->pricing->getMerchantPricingPlansSummary();

        $pricingPlans->map(function ($plan)
        {
            $plan->rules_count = (int) $plan->rules_count;

            return $plan;
        });

        return $pricingPlans->toArray();
    }

    public function getGatewayPricingPlans()
    {
        $pricingPlans = $this->repo->pricing->getGatewayPricingPlans();

        return $pricingPlans->toArrayMultiplePlansPublic();
    }

    public function deletePricingPlanRule($planId, $ruleId)
    {
        $flag = $this->repo->pricing->deletePlanRule($planId, $ruleId);

        if ($flag === true)
        {
            return ['message' => 'Pricing successfully deleted'];
        }
    }

    public function deletePricingPlanRuleForce($planId, $ruleId)
    {
        $flag = $this->repo->pricing->deletePlanRuleForce($planId, $ruleId);

        if ($flag === true)
        {
            return ['message' => 'Pricing successfully deleted'];
        }
    }

    public function deletePricingPlan($input)
    {
        ;
    }

    public function getSupportedNetworks()
    {
        $bankCodes = Processor\Netbanking::getSupportedBanks();

        $bankNamesMap = Bank\Name::getNames($bankCodes);

        $cards = Card\Network::getSupportedNetworksNamesMap();

        $wallets = Processor\Wallet::getWalletNetworkNamesMap();

        $networks = array(
            'bank' => $bankNamesMap,
            'card' => $cards,
            'wallet' => $wallets);

        return $networks;
    }
}
