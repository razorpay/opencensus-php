<?php

namespace RZP\Models\Pricing;

use App;
use RZP\Models\Bank;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createPlan($input)
    {
        $ruleOrgId = $this->getRuleOrgId();

        (new Pricing\Core)->create($input, $ruleOrgId);

        $plan = $this->repo->pricing->getPlanByName($input[Entity::PLAN_NAME]);

        return $plan->toArrayPublic();
    }

    public function addPlanRule($id, $input)
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_ADD_ATTEMPT,
            ['id' => $id, $input]);

        $plan = $this->repo->pricing->getPlanByIdOrFailPublic($id);

        $ruleOrgId = $plan->getOrgId();

        $rule = (new Pricing\Core)->addPlanRule($plan, $input, $ruleOrgId);

        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_ADD_SUCCESS,
            [$rule->toArray()]);

        return $rule->toArray();
    }

    public function getPlanById($id)
    {
        $plan = $this->repo->pricing->getPlan($id);

        return $plan->toArrayPublic();
    }

    public function getPlans(array $input) : array
    {
        $validator = new Validator;

        $validator->validateInput('fetch', $input);

        $plans = $this->repo->pricing->getPlansOrderedByPlanId($input);

        return $plans->toArrayMultiplePlansPublic();
    }

    public function getMerchantPricingPlans(array $input): array
    {
        $pricingPlans = $this->repo->pricing->getMerchantPricingPlansSummary($input);

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

    public function deletePlanRule($planId, $ruleId)
    {
        $flag = $this->repo->pricing->deletePlanRule($planId, $ruleId);

        if ($flag === true)
        {
            return ['message' => 'Pricing successfully deleted'];
        }
    }

    public function updatePlanRule($planId, $ruleId, $input)
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_UPDATE_ATTEMPT,
            ['id' => $ruleId, $input]);

        $rule = (new Pricing\Core)->editPlanRule($planId, $ruleId, $input);

        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_UPDATE_SUCCESS,
            [$rule->toArray()]);

        return $rule->toArray();
    }

    public function deletePlanRuleForce($planId, $ruleId)
    {
        $rule = $this->repo->pricing->getPlanRule($planId, $ruleId);

        $this->app['workflow']
             ->setEntityAndId($rule->getEntity(), $rule->getPlanId())
             ->handle($rule, (new \stdClass));

        $flag = $this->repo->pricing->deletePlanRuleForce($planId, $ruleId);

        if ($flag === true)
        {
            return ['message' => 'Pricing successfully deleted'];
        }
    }

    public function getSupportedNetworks()
    {
        $netbankingBanks = Processor\Netbanking::getSupportedBanks();

        $netbankingBankNamesMap = Processor\Netbanking::getNames($netbankingBanks);

        $cards = Card\Network::getSupportedNetworksNamesMap();

        $wallets = Processor\Wallet::getWalletNetworkNamesMap();

        $emandateBanks = Gateway::getAvailableEmandateBanks();

        $emandateBankNamesMap = Bank\Name::getNames($emandateBanks);

        $networks = [
            'bank'      => $netbankingBankNamesMap,
            'card'      => $cards,
            'wallet'    => $wallets,
            'emandate'  => $emandateBankNamesMap,
        ];

        return $networks;
    }

    /**
     * If crossOrgId is present, rule org Id is same as crossOrgId else it is same as admin org Id.
     *
     * @return mixed
     */
    private function getRuleOrgId()
    {
        $app = App::getFacadeRoot();

        $orgId = $app['basicauth']->getOrgId();

        $crossOrgId = $app['basicauth']->getCrossOrgId();

        $ruleOrgId = $crossOrgId ?: $orgId;

        return $ruleOrgId;
    }
}
