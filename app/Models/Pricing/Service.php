<?php

namespace RZP\Models\Pricing;

use App;
use RZP\Error\Error;
use RZP\Models\Bank;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Payment\Method;
use RZP\Models\Admin\Org;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Models\Admin\Permission\Name as PermissionName;

class Service extends Base\Service
{
    const MERCHANT_PRICING_UPDATE_MUTEX         = 'merchant_pricing_update_%s';
    const TERMINAL_BUY_PRICING_MUTEX            = 'terminal_buy_pricing_%s';
    const MERCHANT_PRICING_UPDATE_MUTEX_TIMEOUT = 30;
    const TERMINAL_BUY_PRICING_MUTEX_TIMEOUT = 30;

    public function createPlan($input, $type = null)
    {
        // if rules are sent in json encoded form, decode it
        if (isset($input['rules']) === true and is_string($input['rules']) === true)
        {
            $input['rules'] = json_decode($input['rules'], true);
            // stringify each key value pair; to mimic how data arrives at php backend
            for ($counter = 0; $counter < count($input['rules']); $counter++)
            {
                foreach ($input['rules'][$counter] as $key => $value)
                {
                    $input['rules'][$counter][$key] = strval($value);
                }
            }
            $this->trace->info(
                TraceCode::PRICING_PLAN_CREATE_ATTEMPT, ['rules_count' => count($input['rules'])]
            );
        }
        $ruleOrgId = $this->getRuleOrgId();

        $this->repo->pricing->withBuyPricing();

        if ($type === Type::BUY_PRICING)
        {
            (new Validator())->validateInput('createBulkPricing', $input);

            $inputRules = $input[Entity::RULES];

            (new Validator)->validateBuyPricingRules($inputRules);

            $input[Entity::RULES] = (new Entity())->formattedBuyPricingRules($inputRules);
        }

        (new Pricing\Core)->create($input, $ruleOrgId);

        $plan = $this->repo->pricing->getPlanByName($input[Entity::PLAN_NAME]);

        return $plan->toArrayPublic();
    }

    public function processBuyPricingCostCalculation($input)
    {
        $startAt = millitime();

        (new Validator())->validateInput("buyPricingCost", $input);

        $this->trace->info(
            TraceCode::BUY_PRICING_PROCESS_COST_CALCULATION,
            [
                'payment_id' => $input[BuyPricing::PAYMENT]['id'],
                'terminals'  => $input[BuyPricing::TERMINALS]
            ]);

        $result = [];

        $payment = BuyPricing::getPaymentFromBuyPricingCostInput($input[BuyPricing::PAYMENT]);

        $terminals = $input[BuyPricing::TERMINALS];

        $planIds = [];

        foreach ($terminals as $terminal)
        {
            array_push($planIds, $terminal[Entity::PLAN_ID]);
        }

        $dbStartAt = millitime();

        $buyPricingPlans = $this->repo->pricing->getBuyPricingPlansByIds(array_unique($planIds))->groupBy(Entity::PLAN_ID);

        $dbEndAt = millitime();

        foreach ($terminals as $terminal)
        {
            // For buy pricing cost, gateway of terminal is the payment issuer.
            $payment->setGateway($terminal['gateway']);

            try
            {
                $cost = (new Pricing\Fee)->calculateTerminalFees($payment, $buyPricingPlans[$terminal[Entity::PLAN_ID]]);

                $result[] = array_merge($terminal, [
                    'cost'    => $cost[0],
                    'success' => true
                ]);
            }
            catch (\Throwable $e)
            {
                $result[] = array_merge($terminal, [
                    'cost'    => 0,
                    'success' => false,
                    'error'             => [
                        Error::DESCRIPTION       => $e->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $e->getCode(),
                    ]
                ]);
            }

        }

        $this->trace->info(
            TraceCode::BUY_PRICING_PROCESS_COST_CALCULATION_EXECUTION_TIME,
            [
                'execution_time'    => millitime() - $startAt,
                'db_execution_time' => $dbEndAt - $dbStartAt,
            ]);

        return ['terminals' => $result];
    }

    public function addPlanRule($id, $input, $orgId = null, $isBuyPricingRule = false)
    {
        if ($isBuyPricingRule === true)
        {
            $this->repo->pricing->onlyBuyPricing();
        }

        $plan = $this->repo->pricing->getPlanByIdOrFailPublic($id, $orgId);

        $ruleOrgId = $plan->getOrgId();

        if ($isBuyPricingRule === true)
        {
            $inputRules = $input[Entity::RULES];

            (new Validator)->validateBuyPricingRules($inputRules);

            $inputRules = (new Entity())->formattedBuyPricingRules($inputRules);

            $rules = $this->repo->transactionOnLiveAndTest(function () use ($inputRules, $plan, $ruleOrgId)
            {
                $rules = [];

                foreach ($inputRules as $inputRule)
                {
                    $rules[] = (new Pricing\Core)->addPlanRule($plan, $inputRule, $ruleOrgId);
                }

                return $rules;
            });

            return $rules;
        }

        $rule = (new Pricing\Core)->addPlanRule($plan, $input, $ruleOrgId);

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

                    if(empty($item[Pricing\Entity::APP_NAME]) === true)
                    {
                        $appName = null;
                    }
                    else
                    {
                        $appName = $item[Pricing\Entity::APP_NAME];
                    }

                    $receiverType = empty($item[Pricing\Entity::RECEIVER_TYPE]) ? null : $item[Pricing\Entity::RECEIVER_TYPE];
                    $amountRangeActive = 0; //empty($item[Pricing\Entity::AMOUNT_RANGE_ACTIVE]) ? 0 : $item[Pricing\Entity::AMOUNT_RANGE_ACTIVE];
                    $procurer = empty($item[Pricing\Entity::PROCURER]) ? null : $item[Pricing\Entity::PROCURER];

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
                        $amountRangeActive,
                        $orgId,
                        $appName,
                        $receiverType,
                        $procurer
                    );

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
                                $amountRangeActive,
                                $orgId,
                                $appName,
                                $receiverType,
                                $procurer
                            );

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

    public function postAddBulkBuyPricingRules($input)
    {
        $this->trace->info(
            TraceCode::BATCH_ADD_BUY_PRICING_RULE_REQUEST,
            [
                'request body' => $this->redactBulkInput($input),
            ]);

        $buyPricingRules = new PublicCollection();

        foreach ($input as $item)
        {
            $rowOutput = $this->processAddBulkBuyPricingRules($item);

            $buyPricingRules = $buyPricingRules->push($rowOutput);
        }

        return $buyPricingRules->toArrayWithItems();
    }

    private function redactBulkInput($input)
    {
        return [];
    }

    protected function processAddBulkBuyPricingRules($item)
    {
        $idempotencyKey = $item[Constants::IDEMPOTENCY_KEY];

        $item = $this->modifyInput($item);

        $input[Entity::PLAN_NAME] = $item[Entity::PLAN_NAME];
        $input[Entity::RULES] = array($item);

        try
        {
            $this->processEntry($input);

            return [Entity::PLAN_NAME => $input[Entity::PLAN_NAME], Constants::BATCH_SUCCESS => true, Constants::IDEMPOTENCY_KEY => $idempotencyKey];
        }
        catch (\Throwable $e)
        {
            return [
                Constants::IDEMPOTENCY_KEY   => $idempotencyKey,
                Constants::BATCH_SUCCESS     => false,
                Constants::BATCH_ERROR       => [
                    Constants::BATCH_ERROR_DESCRIPTION  => $e->getMessage(),
                    Constants::BATCH_ERROR_CODE         => $e->getCode(),
                ]
            ];
        }
    }

    private function modifyInput($rule)
    {
        unset($rule[Constants::IDEMPOTENCY_KEY]);

        array_walk($rule, function (&$value, &$key) use ($rule)
        {
            $value = $value === '' ? null : $value;

            // Networks, Issuers can be passed as array for multiple rules creation in one go.
            if (in_array($key, [Entity::PAYMENT_ISSUER, Entity::PAYMENT_NETWORK]))
            {
                $value = isset($value) ? explode(",",$value) : null;
            }

            if ($rule[Entity::PAYMENT_METHOD] === Method::CARD and $key === Entity::PAYMENT_NETWORK)
            {
                $result = [];

                foreach ($value as $v)
                {
                    $result[] = Card\Network::getCode($v);
                }

                $value = $result;
            }

            if ($key === Entity::AMOUNT_RANGE_MAX and $value === '0')
            {
                $value = null;
            }

            if(in_array($key, [Entity::MIN_FEE, Entity::MAX_FEE,
                    Entity::AMOUNT_RANGE_MAX, Entity::AMOUNT_RANGE_MIN]) && $value != null)
            {
                $value = (int)($value*100);
            }

            if (in_array($key, [Entity::FIXED_RATE, Entity::PERCENT_RATE]))
            {
                $value = (int)($value*100);
            }

            if ($key === Entity::INTERNATIONAL and is_null($value))
            {
                $value = '0';
            }

        });

        return $rule;
    }

    protected function processEntry($item)
    {
        $planName = $item[Entity::PLAN_NAME];

        $mutex = App::getFacadeRoot()['api.mutex'];
        $mutexKey = sprintf(self::TERMINAL_BUY_PRICING_MUTEX, $planName);

        return $mutex->acquireAndRelease($mutexKey, function () use ($item, $planName)
        {
            return $this->repo->transactionOnLiveAndTest(function () use ($item, $planName)
            {
                // Keeping this as rzp org. Field is not currently passed with batch.
                $ruleOrgId = Org\Entity::RAZORPAY_ORG_ID;
                $item[Entity::RULES] = (new Entity())->formattedBuyPricingRules($item[Entity::RULES]);
                $existingPlan = (new Pricing\Repository)->onlyBuyPricing()->getPlanByName($planName);

                // Create a new Plan if plan with name doesn't exist.
                if (count($existingPlan) === 0)
                {
                    (new Pricing\Core)->create($item, $ruleOrgId);
                }
                else
                {
                    $inputRules = $item[Entity::RULES];

                    $this->repo->transactionOnLiveAndTest(function () use ($inputRules, $existingPlan, $ruleOrgId)
                    {
                        $rules = [];
                        foreach ($inputRules as $inputRule)
                        {
                            $rules[] = (new Pricing\Core)->addPlanRule($existingPlan, $inputRule, $ruleOrgId);
                        }
                        return $rules;
                    });
                }
            });
        },

        static::TERMINAL_BUY_PRICING_MUTEX_TIMEOUT,
        ErrorCode::BAD_REQUEST_ANOTHER_PRICING_UPDATE_IN_PROGRESS);
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

    public function getBuyPricingPlanById($id)
    {
        $this->repo->pricing->onlyBuyPricing();

        $plan = $this->repo->pricing->getPlan($id);

        // Validation is required to verify plans before assigning to terminal.
        (new Validator)->validBuyPricingRules($plan->toArray());

        return $plan->toArrayPublic();
    }

    public function getPlans(array $input) : array
    {
        $validator = new Validator;

        $validator->validateInput('fetch', $input);

        $plans = $this->repo->pricing->getPlansOrderedByPlanId($input);

        return $plans->toArrayMultiplePlansPublic();
    }

    public function getPricingPlansSummary(array $input = []): array
    {
        $this->trace->info(TraceCode::PRICING_PLAN_FETCH_ATTEMPT,$input);

        // updated limit to fetch all records by default.
        $input[Fetch::COUNT] = $input[Fetch::COUNT] ?? 150000;

        $input[Fetch::SKIP] = $input[Fetch::SKIP] ?? 0;

        $validator = new Pricing\Validator;

        $validator->validateInput('pricing_plans_summary', $input);

        $pricingPlans = $this->repo->useSlave( function() use ($input)
        {
            return $this->repo->pricing->getPricingPlansSummary($input);
        });

        $pricingPlans->map(function ($plan)
        {
            $plan->rules_count = (int) $plan->rules_count;

            return $plan;
        });

        return $pricingPlans->toArray();
    }

    public function getBuyPricingPlansSummary(array $input = []): array
    {
        $this->repo->pricing->onlyBuyPricing();

        $pricingPlans = collect($this->getPricingPlansSummary($input));

        $ids = $pricingPlans->pluck(Entity::PLAN_ID)->toArray();

        $terminalPlans = $this->repo->terminal->getTerminalIdsByPlanIds($ids);

        $terminalPlansMap = [];

        array_walk($terminalPlans, function ($value) use (&$terminalPlansMap)
        {
            $terminalPlansMap[$value[Entity::PLAN_ID]] = $value['count'];;
        });

        $pricingPlans = $pricingPlans->map(function ($plan) use ($terminalPlansMap)
        {
            $plan['terminals_count'] = $terminalPlansMap[$plan[Entity::PLAN_ID]] ?? 0;

            return $plan;
        });

        return $pricingPlans->toArray();
    }

    public function getGatewayPricingPlans()
    {
        $pricingPlans = $this->repo->pricing->getGatewayPricingPlans();

        return $pricingPlans->toArrayMultiplePlansPublic();
    }

    public function updatePlanRule($planId, $ruleId, $input, $isBuyPricingRule = false)
    {
        if ($isBuyPricingRule === true)
        {
            $this->repo->pricing->onlyBuyPricing();
        }

        $rule = (new Pricing\Core)->editPlanRule($planId, $ruleId, $input);

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

    public function deleteBuyPlanGroupedRuleForce($planId, $ruleId)
    {
        $this->trace->info(TraceCode::BUY_PRICING_PLAN_RULE_DELETE_ATTEMPT,
            [
                'plan_id'    => $planId,
                'rule_id'    => $ruleId,
                'force'      => true,
            ]);

        $this->repo->pricing->onlyBuyPricing();

        $rule = $this->repo->pricing->getPlanRule($planId, $ruleId);

        $this->app['workflow']
            ->setEntityAndId($rule->getEntity(), $rule->getPlanId())
            ->handle($rule, (new \stdClass));

        $input = [];

        foreach (Entity::$buyPricingMethods as $attribute)
        {
            $input[$attribute] = $rule->getAttribute($attribute);
        }

        $flag = $this->repo->pricing->deleteBuyPlanGroupedRuleForce($planId, $input);

        if ($flag > 0)
        {
            return ['message' => 'Buy Pricing rule group successfully deleted'];
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
            'upi'       => array_flip(BuyPricing::$upiNetworksNames),
            'emi'       => array_flip(BuyPricing::$defaultEmiNetWorksNames),
            'nach'      => array_flip(BuyPricing::$nachNetworksNames),
            'paylater'  => array_flip(BuyPricing::$paylaterNetworksNames),
            'cardless_emi' => array_flip(BuyPricing::$cardlessEmiNetworksNames),
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
