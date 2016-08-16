<?php

namespace RZP\Models\Pricing;

use RZP\Models\Base;
use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Pricing;
use RZP\Models\Payment\Processor;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createPricingPlan($input)
    {
        $pricing = (new Pricing\Entity)->build($input);

        $this->trace->info(
            TraceCode::PRICING_PLAN_CREATE_ATTEMPT,
            $input);

        $plan = $this->repo->pricing->getPricingPlanByName($input[Entity::PLAN_NAME]);

        Pricing\Validator::validatePlanCountZero($plan);

        $pricing->generateId();

        $this->repo->saveOrFail($pricing);

        $plan = new Plan(array($pricing));

        $this->trace->info(
            TraceCode::PRICING_PLAN_CREATE_SUCCESS,
            $plan->toArrayPublic());

        return $plan->toArrayPublic();
    }

    public function uploadPricingPlan($input)
    {
        $pricing = (new Pricing\Entity)->build($input[0]);

        $this->trace->info(
            TraceCode::PRICING_PLAN_CREATE_ATTEMPT,
            $input[0]);

        $plan = $this->repo->pricing->getPricingPlanByName($input[0][Entity::PLAN_NAME]);

        Pricing\Validator::validatePlanCountZero($plan);

        $pricing->generateId();

        $this->repo->transactionOnLiveAndTest(function() use ($pricing, $input){

            $this->repo->saveOrFail($pricing);

            $planId = $pricing->plan_id;
            $plan = $this->repo->pricing->getPricingPlanByIdOrFailPublic($planId);

            foreach ($input as $key => $value)
            {
                if ($key === 0)
                {
                    continue;
                }
                $rule = (new Pricing\Entity)->addPlanRule($value, $plan);

                $rule->getValidator()->matchPaymentRules($plan);
                $rule->generateId();

                $this->repo->saveOrFail($rule);
            }
        });

        $plan = $this->repo->pricing->getPricingPlanByName($input[0][Entity::PLAN_NAME]);

        return $plan->toArrayPublic();
    }

    public function addPricingPlanRule($id, $input)
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_ADD_ATTEMPT,
            ['id' => $id, $input]);

        $plan = $this->repo->pricing->getPricingPlanByIdOrFailPublic($id);

        $rule = (new Pricing\Entity)->addPlanRule($input, $plan);

        $rule->getValidator()->matchPaymentRules($plan);
        $rule->generateId();

        (new Pricing\Repository)->saveOrFail($rule);

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
        $pricingPlans = $this->repo->pricing->getMerchantPricingPlans();

        return $pricingPlans->toArrayMultiplePlansPublic();
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

    public function replacePricingPlanRule($input)
    {
        $this->core->checkPlanId($id);

        $this->replacePlanRule($ruleId);
    }

    public function deletePricingPlan($input)
    {
        ;
    }

    public function getSupportedNetworks()
    {
        $bankCodes = Processor\Netbanking::getSupportedBanks('live');

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
