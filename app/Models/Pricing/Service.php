<?php

namespace RZP\Models\Pricing;

use App;
use RZP\Error\Error;
use RZP\Models\Bank;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment\Method;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;

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

    public function postAddBulkPricingRules($input)
    {
        $this->trace->info(
            TraceCode::BATCH_ADD_PRICING_RULE_REQUEST,
            [
                'request body' => $input
            ]);

        $pricingRulesCollection = new PublicCollection;

        foreach ($input as $item)
        {
            $idempotencyKey = $item['idempotency_key'];
            try
            {
                $result = $this->repo->transactionOnLiveAndTest(function () use ($item, $idempotencyKey)
                {
                    $merchant = $this->repo->merchant->findByPublicId($item[Entity::MERCHANT_ID]);

                    $planId = $merchant->getPricingPlanId();

                    $plan = $this->repo->pricing->getPlanByIdOrFailPublic($planId);

                    $ruleOrgId = $plan->getOrgId();

                    unset($item[Entity::MERCHANT_ID], $item['idempotency_key']);

                    array_walk($item, function (&$value, &$key)
                    {
                        $value = $value === '' ? null : $value;
                    });

                    if (((new Pricing\Repository)->getPricingRuleByMultipleParams(
                        $planId,
                        $item[Entity::PRODUCT],
                        $item[Pricing\Entity::FEATURE],
                        $item[Pricing\Entity::PAYMENT_METHOD],
                        $item[Pricing\Entity::PAYMENT_METHOD_TYPE],
                        $item[Pricing\Entity::PAYMENT_NETWORK],
                        $item[Pricing\Entity::INTERNATIONAL],
                        0)) === null)
                    {
                        if (($this->repo->merchant->fetchMerchantsCountWithPricingPlanId($planId)) !== 1)
                        {
                            $plan = $this->replicatePLan($merchant, $plan);

                            $planId = $plan->getId();
                        }

                        (new Pricing\Core)->addPlanRule($plan, $item, $ruleOrgId);
                    }
                    else
                    {
                        $this->trace->error(TraceCode::PRICING_RULE_ALREADY_DEFINED,
                            ['pricing_rule' => $item]);

                        throw new BadRequestException(ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED);
                    }

                    return [Entity::PLAN_ID => $planId, 'success' => true, 'idempotency_key' => $idempotencyKey];
                });

                $pricingRulesCollection->push($result);
            }
            catch (\Throwable $e)
            {
                $pricingRulesCollection->push([
                    'idempotency_key'   => $idempotencyKey,
                    'success'            => false,
                    'error'             => [
                        Error::DESCRIPTION       => $e->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $e->getCode(),
                    ]
                ]);
            }
        }

        return $pricingRulesCollection->toArrayWithItems();
    }

    public function replicatePLan($merchant, $plan)
    {
        $planId = $merchant->getPricingPlanId();

        $ruleOrgId = $plan->getOrgId();

        $this->trace->info(TraceCode::BATCH_PRICING_PLAN_REPLICATE_REQUEST,
                            [
                                Entity::PLAN_ID => $planId
                            ]);

        $rules = $plan->toArray();

        $planName = UniqueIdEntity::generateUniqueId();

        for ($i = 0; $i < count($rules); $i++)
        {
            $rules[$i] = array_except(
                              $rules[$i],
                              [Entity::ID,
                              Entity::PLAN_ID,
                              Entity::ORG_ID,
                              Entity::CREATED_AT,
                              Entity::UPDATED_AT,
                              Entity::DELETED_AT,
                              Entity::EXPIRED_AT]);

            $rules[$i][Entity::INTERNATIONAL] = $rules[$i][Entity::INTERNATIONAL] === true ? '1' : '0';

            if ($rules[$i][Entity::PRODUCT] !== Product::BANKING)
            {
                unset($rules[$i][Entity::ACCOUNT_TYPE]);
            }

            if (isset($rules[$i][Entity::ACCOUNT_TYPE]) === false or
                $rules[$i][Entity::ACCOUNT_TYPE] !== Merchant\Balance\AccountType::DIRECT)
            {
                unset($rules[$i][Entity::CHANNEL]);
            }
        }

        $newplan = (new Pricing\Core)->create([Entity::PLAN_NAME => $planName, Entity::RULES => $rules], $ruleOrgId);

        (new Merchant\Service)->assignPricingPlan($merchant->getId(),
                                                 [Merchant\Entity::PRICING_PLAN_ID => $newplan->getId()]);

        return $newplan;
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

    public function getMerchantPricingPlans(array $input = []): array
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
        $this->trace->info(TraceCode::PRICING_PLAN_RULE_DELETE_ATTEMPT,
                            [
                                'plan_id'    => $planId,
                                'rule_id'    => $ruleId,
                            ]);

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
        $this->trace->info(TraceCode::PRICING_PLAN_RULE_DELETE_ATTEMPT,
                            [
                                'plan_id'    => $planId,
                                'rule_id'    => $ruleId,
                                'force'      => true,
                            ]);

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
