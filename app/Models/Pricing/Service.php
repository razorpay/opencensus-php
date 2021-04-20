<?php

namespace RZP\Models\Pricing;

use App;
use RZP\Error\Error;
use RZP\Models\Bank;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Pricing\Feature as PricingFeature;

class Service extends Base\Service
{
    const MERCHANT_PRICING_UPDATE_MUTEX         = 'merchant_pricing_update_%s';
    const MERCHANT_PRICING_UPDATE_MUTEX_TIMEOUT = 30;

    public function createPlan($input)
    {
        $ruleOrgId = $this->getRuleOrgId();

        (new Pricing\Core)->create($input, $ruleOrgId);

        $plan = $this->repo->pricing->getPlanByName($input[Entity::PLAN_NAME]);

        return $plan->toArrayPublic();
    }

    public function addPlanRule($id, $input, $orgId = null)
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_ADD_ATTEMPT,
            ['id' => $id, $input]);

        $plan = $this->repo->pricing->getPlanByIdOrFailPublic($id, $orgId);

        $ruleOrgId = $plan->getOrgId();

        $rule = (new Pricing\Core)->addPlanRule($plan, $input, $ruleOrgId);

        $this->trace->info(
            TraceCode::PRICING_PLAN_RULE_ADD_SUCCESS,
            [$rule->toArray()]);

        return $rule->toArray();
    }

    public function postAddBulkPricingRules($input, $orgId = null)
    {
        $this->trace->info(
            TraceCode::BATCH_ADD_PRICING_RULE_REQUEST,
            [
                'request body' => $input,
                'org id'        => $orgId
            ]);

        $pricingRulesCollection = new PublicCollection;

        foreach ($input as $item)
        {
            $idempotencyKey = $item['idempotency_key'];
            $shouldUpdate = isset($item['update']) ? $item['update'] : false;
            try
            {
                $mutex = App::getFacadeRoot()['api.mutex'];
                $mutexKey = sprintf(self::MERCHANT_PRICING_UPDATE_MUTEX, $item[Entity::MERCHANT_ID]);
                $pricingRulesCollection = $mutex->acquireAndRelease($mutexKey, function () use ($idempotencyKey, $shouldUpdate, $item, $pricingRulesCollection, $orgId)
                {
                $result = $this->repo->transactionOnLiveAndTest(function () use ($item, $idempotencyKey, $shouldUpdate, $orgId)
                {
                    $merchant = $this->repo->merchant->findByPublicId($item[Entity::MERCHANT_ID]);

                    unset($item[Entity::MERCHANT_ID], $item['idempotency_key'], $item['update']);

                    $item = $this->setFeeBearerIfApplicable($item, $merchant);

                    array_walk($item, function (&$value, &$key)
                    {
                        $value = $value === '' ? null : $value;
                    });

                    $planId = $merchant->getPricingPlanId();

                    $plan = $this->repo->pricing->getPlanByIdOrFailPublic($planId, $orgId);

                    $ruleOrgId = $plan->getOrgId();

                    // the route is being used by terminalsService also for paypal onboarding pricing update, we don't send subtype from there
                    $methodSubtype = isset($item[Pricing\Entity::PAYMENT_METHOD_SUBTYPE]) ? $item[Pricing\Entity::PAYMENT_METHOD_SUBTYPE] : null;
                    /** @var Pricing\Entity $existingRule */
                    $existingRule = (new Pricing\Repository)->getPricingRuleByMultipleParams(
                        $planId,
                        $item[Entity::PRODUCT],
                        $item[Pricing\Entity::FEATURE],
                        $item[Pricing\Entity::PAYMENT_METHOD],
                        $item[Pricing\Entity::PAYMENT_METHOD_TYPE],
                        $methodSubtype,
                        $item[Pricing\Entity::PAYMENT_NETWORK],
                        $item[Pricing\Entity::INTERNATIONAL],
                        0,
                        $orgId);

                    if ($existingRule === null)
                    {
                        // replicates pricing plan if more than one merchants are using it.
                        if (($this->repo->merchant->fetchMerchantsCountWithPricingPlanId($planId)) !== 1)
                        {
                            $plan = $this->replicatePlanAndAssign($merchant, $plan);

                            $planId = $plan->getId();
                        }

                        (new Pricing\Core)->addPlanRule($plan, $item, $ruleOrgId);
                    }
                    else if (filter_var($shouldUpdate, FILTER_VALIDATE_BOOLEAN) === true)
                    {
                        $editRulekeys = [
                                            Entity::PERCENT_RATE,
                                            Entity::FIXED_RATE,
                                            Entity::MIN_FEE,
                                            Entity::MAX_FEE,
                                            Entity::FEE_BEARER
                        ];

                        $rule = array_filter($item, function ($k) use ($editRulekeys)
                                            {
                                                if (in_array($k, $editRulekeys, true) === true)
                                                {
                                                    return true;
                                                }

                                                return false;
                                            },
                                            ARRAY_FILTER_USE_KEY);

                        // Updates pricing rule only if it has been changed,
                        // so that plans aren't replicated unnecessarily
                        if(empty(array_diff_assoc($rule, $existingRule->toArray())) === false)
                        {
                            if (($this->repo->merchant->fetchMerchantsCountWithPricingPlanId($planId)) !== 1)
                            {
                                $plan = $this->replicatePlanAndAssign($merchant, $plan);

                                $planId = $plan->getId();
                            }
                            $existingRule = (new Pricing\Repository)->getPricingRuleByMultipleParams(
                                $planId,
                                $item[Entity::PRODUCT],
                                $item[Pricing\Entity::FEATURE],
                                $item[Pricing\Entity::PAYMENT_METHOD],
                                $item[Pricing\Entity::PAYMENT_METHOD_TYPE],
                                $methodSubtype,
                                $item[Pricing\Entity::PAYMENT_NETWORK],
                                $item[Pricing\Entity::INTERNATIONAL],
                                0,
                                $orgId);

                            (new Pricing\Core)->editPlanRule($planId, $existingRule->getId(), $rule, $orgId);
                        }
                        else
                        {
                            throw new BadRequestException(ErrorCode::BAD_REQUEST_SAME_PRICING_RULE_ALREADY_EXISTS);
                        }
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

                return $pricingRulesCollection;
                },
                static::MERCHANT_PRICING_UPDATE_MUTEX_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_PRICING_UPDATE_IN_PROGRESS);
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

    protected function setFeeBearerIfApplicable(array $input, $merchant)
    {
        $input[Pricing\Entity::FEE_BEARER] = $merchant->getFeeBearer();

        return $input;
    }

    public function replicatePlanAndAssign($merchant, $plan)
    {
        // Get merchants existig plan ID
        $planId = $merchant->getPricingPlanId();

        // Get intended pricing plans org id
        $ruleOrgId = $plan->getOrgId();

        $this->trace->info(TraceCode::BATCH_PRICING_PLAN_REPLICATE_REQUEST,
                            [
                                Entity::PLAN_ID => $planId
                            ]);

        // make an array copy out of plan into array rules
        $rules = $plan->toArray();

        // Generate a new plan id
        $planName = UniqueIdEntity::generateUniqueId();

        // Make array consumable for create plan
        for ($i = 0; $i < count($rules); $i++)
        {
            // For each rule remove generated and conflict-ible values
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

        // Create new plan with copied rules
        $newplan = (new Pricing\Core)->create([Entity::PLAN_NAME => $planName, Entity::RULES => $rules], $ruleOrgId);

        // Assign plan to merchant
        (new Merchant\Service)->assignPricingPlan($merchant->getId(),
                                                 [Merchant\Entity::PRICING_PLAN_ID => $newplan->getId()]);

        // Return the new plan
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
        $this->trace->info(TraceCode::PRICING_PLAN_FETCH_ATTEMPT);

        $input[Fetch::COUNT] = $input[Fetch::COUNT] ?? 100000;

        $input[Fetch::SKIP] = $input[Fetch::SKIP] ?? 0;

        (new Pricing\Validator)->validateInput('merchant_pricing_plans_summary', $input);

        $pricingPlans = $this->repo->useSlave( function() use ($input)
        {
            return $this->repo->pricing->getMerchantPricingPlansSummary($input);
        });

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
