<?php

namespace Models\Pricing;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Bank;
use Models\Card;
use Models\Pricing;
use Models\Payment\Processor;
use Trace\TraceCode;

class Service extends Base\Service
{
    protected $repo = null;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Pricing\Repository();
    }

    public function createPricingPlan($input)
    {
        $pricing = (new Pricing\Entity)->build($input);

        $this->trace->info(
            TraceCode::PRICING_PLAN_CREATE_ATTEMPT,
            $input);

        $plan = $this->repo->getPricingPlanByName($input[Entity::PLAN_NAME]);

        Pricing\Validator::validatePlanCountZero($plan);

        $pricing->generateId();

        $this->repo->saveOrFail($pricing);

        $plan = new Plan(array($pricing));

        $this->trace->info(
            TraceCode::PRICING_PLAN_CREATE_SUCCESS,
            $plan->toArrayPublic());

        return $plan->toArrayPublic();
    }

    public function addPricingPlanRule($id, $input)
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_ADD_ATTEMPT,
            ['id' => $id, $input]);

        $plan = $this->repo->getPricingPlanByIdOrFailPublic($id);

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
        $pricingPlan = $this->repo->getPricingPlanById($id);

        return $pricingPlan->toArrayPublic();
    }

    public function getPricingPlans()
    {
        $pricingPlans = $this->repo->getPricingPlansOrderedByPlanId();

        return $pricingPlans->toArrayMultiplePlansPublic();
    }

    public function getMerchantPricingPlans()
    {
        $pricingPlans = $this->repo->getMerchantPricingPlans();

        return $pricingPlans->toArrayMultiplePlansPublic();
    }

    public function getGatewayPricingPlans()
    {
        $pricingPlans = $this->repo->getGatewayPricingPlans();

        return $pricingPlans->toArrayMultiplePlansPublic();
    }

    public function deletePricingPlanRule($planId, $ruleId)
    {
        $flag = $this->repo->deletePlanRule($planId, $ruleId);

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