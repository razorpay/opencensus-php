<?php

namespace RZP\Models\Pricing;

use App;
use ApiResponse;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Bank;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Admin\Admin;
use RZP\Http\Request\Requests;
use RZP\Models\Pricing\ChargeCollections\CCRouter;
use RZP\Models\Pricing\ChargeCollections\Utils;
use RZP\Models\Workflow\Action;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\Partner\Commission\Calculator;
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
use RZP\Services\ChargeCollections;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Pricing\Constants as PricingConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Workflow\Action\Core as WorkFlowActionCore;
use function PHPUnit\Framework\isEmpty;

class Service extends Base\Service
{
    const MERCHANT_PRICING_UPDATE_MUTEX         = 'merchant_pricing_update_%s';
    const TERMINAL_BUY_PRICING_MUTEX            = 'terminal_buy_pricing_%s';
    const MERCHANT_PRICING_UPDATE_MUTEX_TIMEOUT = 30;
    const TERMINAL_BUY_PRICING_MUTEX_TIMEOUT = 30;

    protected CCRouter $ccRouter;

    public function __construct()
    {
        parent::__construct();

        $this->ccRouter = new CCRouter(true);
    }

    public function createPlan($input, $type = null, $orgID = '', $internalCall = false)
    {
        $sourceInput = $input;
        $fqcn = get_class($this) . '\\' . __FUNCTION__;
        $ruleCount = $this->getInputRuleCount($input);
        $planAndRuleIds = Pricing\ChargeCollections\Utils::generatePlanAndRuleIds($ruleCount);
        $ccRequest = $this->transformCreatePlanRequest($input,$planAndRuleIds);

        $legacyCallable = function () use ($sourceInput, $type, $planAndRuleIds, $orgID, $internalCall) {
            return $this->createPlanLegacy($sourceInput, $type, $planAndRuleIds, $orgID, $internalCall);
        };

        return $this->ccRouter->route($fqcn, $ccRequest, $legacyCallable, null, $type == Type::BUY_PRICING);
    }

    public function transformCreatePlanRequest($input, $planAndRuleIds) {
        if (isset($input['rules']) === true and is_string($input['rules']) === true)
        {
            $input['rules'] = json_decode($input['rules'], true);
        }

        if (isset($input['rules']) === true && $planAndRuleIds != null){
            $input['rules'] = Utils::addPlanDetailsToRules($input['rules'], $planAndRuleIds);
        }

        foreach ($input['rules'] as &$item) {
            // Convert 'amount_range_active' to boolean if it exists and is not already a boolean
            if (isset($item['amount_range_active']) && !is_bool($item['amount_range_active'])) {
                $item['amount_range_active'] = (bool) $item['amount_range_active'];
            }
        }

        $input[Entity::ORG_ID] = $this->getRuleOrgId();

        $this->trace->info(TraceCode::CC_ROUTING_TRANSFORMED_REQUEST,
            [
                'method' => 'createPlan',
                'request' => $input,
            ]);

        return $input;
    }

    /**
     * Creates a pricing plan based on input data, type, and optional IDs for plans and rules.
     * Additional configuration options allow specifying an organization ID and a internalCall redirect identifier flag.
     *
     * @param array $input The data used to create the plan, containing pricing rules.
     * @param string|null $type Optional. The type of plan to create, such as "pricing" or "buy_pricing" or null.
     * @param array|null $planAndRuleIds Optional. An array of pre-generated plan and rule IDs to associate with the plan.
     * @param string $orgID Optional. The organization ID for the plan, used for internal plan creation calls
     *                       (e.g. merchant module). Defaults to an empty string, which triggers finding the
     *                       organization from authentication details.
     * @param bool $internalCall Optional. Determines if the call is from an internal module instead of the pricing
     *                           controller, impacting the returned plan entity type.
     * @return array The created plan in an array format with rules in an array. If $internalCall is true, returns
     *               the `Plan` entity itself (without array conversion).
     * @throws \Throwable
     */
    public function createPlanLegacy($input, $type = null, $planAndRuleIds = null, $orgID = '', $internalCall = false)
    {
        // if rules are sent in json encoded form, decode it
        if (isset($input['rules']) === true and is_string($input['rules']) === true)
        {
            $input['rules'] = json_decode($input['rules'], true);

            // Assign planId and ruleIds if $planAndRuleIds is provided
            $planId = $planAndRuleIds['planId'] ?? null;
            $ruleIds = $planAndRuleIds['ruleIds'] ?? [];


            // stringify each key value pair; to mimic how data arrives at php backend
            for ($counter = 0; $counter < count($input['rules']); $counter++)
            {
                if (isset($ruleIds[$counter])) {
                    $input['rules'][$counter][Entity::ID] = $ruleIds[$counter];
                    $input['rules'][$counter][Entity::PLAN_ID] = $planId;
                }

                foreach ($input['rules'][$counter] as $key => $value)
                {
                    $input['rules'][$counter][$key] = strval($value);
                }
            }
            $this->trace->info(
                TraceCode::PRICING_PLAN_CREATE_ATTEMPT, ['rules_count' => count($input['rules'])]
            );
        }

        if ( !empty($orgID)){
            $ruleOrgId = $orgID;
        }else{
            $ruleOrgId = $this->getRuleOrgId();
        }

        $this->repo->pricing->withBuyPricing();

        if ($type === Type::BUY_PRICING)
        {
            (new Validator())->validateInput('createBulkPricing', $input);

            $inputRules = $input[Entity::RULES];

            (new Validator)->validateBuyPricingRules($inputRules);

            $input[Entity::RULES] = (new Entity())->formattedBuyPricingRules($inputRules);
        }

        $plan = (new Pricing\Core)->create($input, $ruleOrgId);
        if ($internalCall){
            return $plan;
        }

        $plan = $this->repo->pricing->getPlanByNameLegacy($input[Entity::PLAN_NAME]);

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
        $sourceInput = $input;
        $fqcn = get_class($this) . '\\' . __FUNCTION__;
        $ruleId = UniqueIdEntity::generateUniqueId();

        $ccRequest = $this->transformAddPlanRule($input, $ruleId, $id);
        $sourceInput['id'] = $ruleId;

        $legacyCallable = function ($rampPhase, $_) use ($id, $sourceInput, $orgId, $isBuyPricingRule) {
            return $this->addPlanRuleLegacy($id, $sourceInput, $orgId, $isBuyPricingRule, $rampPhase);
        };

        return $this->ccRouter->route($fqcn, $ccRequest, $legacyCallable, null, $isBuyPricingRule);
    }

    public function transformAddPlanRule($input, $ruleId, $id)
    {
        if ( is_string($input) === true) {
            $input = json_decode($input, true);
        }

        $input['id'] = $ruleId;
        $input['plan_id']= $id;

        // Convert 'amount_range_active' to boolean if it exists and is not already a boolean
        if (isset($input['amount_range_active']) && !is_bool($input['amount_range_active'])) {
            $input['amount_range_active'] = (bool) $input['amount_range_active'];
        }

        $this->trace->info(TraceCode::CC_ROUTING_TRANSFORMED_REQUEST,
            [
                'method' => 'addPlanRule',
                'request' => $input,
            ]);

        return $input;
    }

    public function addPlanRuleLegacy($id, $input, $orgId = null, $isBuyPricingRule = false, $rampPhase = '')
    {
        if ($isBuyPricingRule === true)
        {
            $this->repo->pricing->onlyBuyPricing();
        }

        $plan = $this->repo->pricing->getPlanByIdOrFailPublicLegacy($id, $orgId);

        $ruleOrgId = $plan->getOrgId();

        if ($isBuyPricingRule === true)
        {
            $inputRules = $input[Entity::RULES];

            (new Validator)->validateBuyPricingRules($inputRules);

            $inputRules = (new Entity())->formattedBuyPricingRules($inputRules);

            $rules = $this->repo->transactionOnLiveAndTestAndAsv(function () use ($inputRules, $plan, $ruleOrgId)
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

        $rule = (new Pricing\Core)->addPlanRule($plan, $input, $ruleOrgId, $rampPhase);

        return $rule->toArray();
    }

    public function generatePlanIdsAndRuleIdsForBulkUpdateRequest($input, $orgId) {
        $route =  app('request.ctx')->getRoute() ?? null;

        foreach ($input as &$item) {
            try {
                $merchant = $this->repo->merchant->findByPublicId($item[Entity::MERCHANT_ID]);

                if ($orgId == null) {
                    $orgId = $merchant->getOrgId();
                    $orgId = Org\Entity::getSignedId($orgId);
                }

                $planId = $merchant->getPricingPlanId();

                $plan = $this->repo->pricing->getPlanByIdOrFailPublicLegacy($planId, $orgId);

                $ruleCount = $plan->count();

                $newPlanId = UniqueIdEntity::generateUniqueId();
                $newPlanName = UniqueIdEntity::generateUniqueId();

                // Generate rule IDs based on the rule count
                $ruleIds = [];
                for ($i = 0; $i < $ruleCount; $i++) {
                    $ruleIds[] = UniqueIdEntity::generateUniqueId();
                }

                $item['initial_pricing_plan_id'] = $planId;
                $item['generated_ids'] = [
                    'plan_id' => $newPlanId,
                    'plan_name' => $newPlanName,
                    'new_rule_id' => UniqueIdEntity::generateUniqueId(),
                    'ids' => $ruleIds,
                ];
            } catch (\Throwable $e) {
                $item['initial_pricing_plan_id'] = '';
                $item['generated_ids'] = null;

                $this->trace->info(TraceCode::CC_ROUTING_TRANSFORMATION_EXCEPTION,
                    [
                        'method' => 'generatePlanIdsAndRuleIdsForBulkUpdateRequest',
                        'route' => $route,
                        'exception|throwable' => $e,
                        'merchantID' => $item[Entity::MERCHANT_ID],
                        'pricing_plan_id' => $planId ?? null,
                    ]);
                continue;
            }
        }
        return $input;
    }


    public function postAddBulkPricingRules($input, $orgId = null)
    {
        $fqcn = get_class($this) . '\\' . __FUNCTION__;
        $input = $this->generatePlanIdsAndRuleIdsForBulkUpdateRequest($input, $orgId);
        $sourceInput = $input;

        $ccRequest = $this->transformAddBulkPricingRulesRequest($input);

        $legacyCallable = function ($rampPhase, $input) use ($orgId) {
            return $this->postAddBulkPricingRulesLegacy($input, $orgId, $rampPhase);
        };

        return $this->ccRouter->route($fqcn, $ccRequest, $legacyCallable, $sourceInput);
    }

    public function transformAddBulkPricingRulesRequest($input) {
        // if input is directly passed as array without 'items' key, modify the request according to charge-collections request
        if (array_keys($input) === range(0, count($input) - 1)) {
            $input = ['items' => $input];
        }

        // Ensure 'update' field in each item is a string if it exists
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as &$item) {
                if (isset($item['update']) && is_bool($item['update'])) {
                    $item['update'] = $item['update'] ? "true" : "false";
                }

                if (isset($item['percent_rate']) && !is_string($item['percent_rate'])) {
                    $item['percent_rate'] = (string) $item['percent_rate'];
                }

                if (isset($item['fixed_rate']) && !is_string($item['fixed_rate'])) {
                    $item['fixed_rate'] = (string) $item['fixed_rate'];
                }
            }
        }

        $this->trace->info(TraceCode::CC_ROUTING_TRANSFORMED_REQUEST,
            [
                'method' => 'createPlan',
                'request' => $input,
            ]);

        return $input;
    }

    // Checks if plan replication is needed based on rampPhase and CC call response
    // calls the replicatePlanAndAssign method for replication
    public function replicatePlanIfRequiredForBulkUpdate($plan, $merchant, $generatedPlanAndRuleId, $ccPlanReplicated, $rampPhase): array
    {
        // replicates the pricing plan if more than one merchants are using it.
        // make this decision based on CC-Response if Reverse-Shadow phase
        if ($rampPhase == CCRouter::REVERSE_SHADOW){
            $shouldReplicatePlan = $ccPlanReplicated ?? true;
        }else{
            $planId = $plan->getId();
            $shouldReplicatePlan = $this->repo->merchant->checkMerchantsCountWithPricingPlanIdNotEqualOne($planId);
        }

        if ($shouldReplicatePlan)
        {
            $plan = $this->replicatePlanAndAssignLegacy($merchant, $plan, $generatedPlanAndRuleId, $rampPhase);
            return [true, $plan];
        }else{
            return [false, $plan];
        }
    }

    public function postAddBulkPricingRulesLegacy($input, $orgId = null, $rampPhase = '')
    {
        $this->trace->info(
            TraceCode::BATCH_ADD_PRICING_RULE_REQUEST,
            [
                'request body' => $input,
                'org id'        => $orgId,
                'rampPhase' => $rampPhase,
            ]);

        $pricingRulesCollection = new PublicCollection;
        $processedMerchants = [];

        $inputCount = count($input);
        for ($i = 0; $i < $inputCount; $i++) {
            $item = $input[$i];

            $generatedPlanAndRuleId = $item['generated_ids'];
            unset($item['generated_ids']);
            $apiPlanReplicated = false;

            $idempotencyKey = $item['idempotency_key'];
            $shouldUpdate = isset($item['update']) ? $item['update'] : false;
            try
            {
                $mutex = App::getFacadeRoot()['api.mutex'];
                $mutexKey = sprintf(self::MERCHANT_PRICING_UPDATE_MUTEX, $item[Entity::MERCHANT_ID]);
                $pricingRulesCollection = $mutex->acquireAndRelease($mutexKey, function () use ($idempotencyKey, $shouldUpdate, $item, $pricingRulesCollection, $orgId,
                    $generatedPlanAndRuleId, $rampPhase, &$apiPlanReplicated, &$processedMerchants)
                {
                $result = $this->repo->transactionOnLiveAndTestAndAsv(function () use ($item, $idempotencyKey, $shouldUpdate, $orgId,
                    $generatedPlanAndRuleId, $rampPhase, &$apiPlanReplicated, &$processedMerchants)
                {
                    $merchant = $this->repo->merchant->findByPublicId($item[Entity::MERCHANT_ID]);
                    $merchantID = $item[Entity::MERCHANT_ID];

                    // If orgId is not passed, get it from merchant entity.
                    // Get signed orgId for verification checks
                    if($orgId == null){
                        $orgId = $merchant->getOrgId();
                        $orgId = Org\Entity::getSignedId($orgId);
                    }

                    unset($item[Entity::MERCHANT_ID], $item['idempotency_key'], $item['update']);

                    $initialPricingPlanId = $item['initial_pricing_plan_id'] ?? '';
                    unset($item['initial_pricing_plan_id']);

                    $ccPlanReplicated = $item['plan_replicated'] ?? '';
                    unset($item['plan_replicated']);

                    $item = $this->setFeeBearerIfApplicable($item, $merchant);

                    array_walk($item, function (&$value, &$key)
                    {
                        $value = $value === '' ? null : $value;
                    });

                    // fetch the planID on which to run updates
                    // In reverse-shadow phase, primary call to CC will already override the merchant's pricing plan
                    // just redo the update in API DB similar to CC DB based on initial plan_id of the merchant IF this is the first time merchant is coming.
                    // If the plan was already replicated, don't fetch initial_plan_id. Go instead with the live merchant's plan_id
                    if ($rampPhase == CCRouter::REVERSE_SHADOW && !in_array($merchantID, $processedMerchants) && !empty($initialPricingPlanId)){
                        $planId = $initialPricingPlanId;
                    }else{
                        $planId = $merchant->getPricingPlanId();
                    }
                    $plan = $this->repo->pricing->getPlanByIdOrFailPublicLegacy($planId, $orgId);

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
                    $feeBearer = empty($item[Pricing\Entity::FEE_BEARER]) ? null : $item[Pricing\Entity::FEE_BEARER];

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
                        $procurer,
                        $feeBearer
                    );

                    if ($existingRule === null)
                    {
                        [$apiPlanReplicated, $plan] = $this->replicatePlanIfRequiredForBulkUpdate($plan, $merchant, $generatedPlanAndRuleId, $ccPlanReplicated, $rampPhase);
                        if ($apiPlanReplicated){
                            $processedMerchants[] = $merchantID;
                        }
                        $planId = $plan->getId();

                        // set new rule id
                        if (!empty($generatedPlanAndRuleId['new_rule_id'])){
                            $item[Entity::ID] = $generatedPlanAndRuleId['new_rule_id'];
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

                        if(isset($item[Entity::PERCENT_RATE_SCALE_FACTOR]) && is_numeric($item[Entity::PERCENT_RATE_SCALE_FACTOR])) {
                            $editRulekeys[] = Entity::PERCENT_RATE_SCALE_FACTOR;
                        }

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
                            [$apiPlanReplicated, $plan] = $this->replicatePlanIfRequiredForBulkUpdate($plan, $merchant, $generatedPlanAndRuleId, $ccPlanReplicated, $rampPhase);
                            if ($apiPlanReplicated){
                                $processedMerchants[] = $merchantID;
                            }
                            $planId = $plan->getId();

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
                                $procurer,
                                $feeBearer
                            );

                            // set new rule id
                            if (!empty($generatedPlanAndRuleId['new_rule_id'])){
                                $rule[Entity::ID] = $generatedPlanAndRuleId['new_rule_id'];
                            }

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

                    return [Entity::PLAN_ID => $planId, 'success' => true, 'idempotency_key' => $idempotencyKey, 'plan_replicated' => $apiPlanReplicated];
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
                    ],
                    'plan_replicated' => $apiPlanReplicated,
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

    /**
     * @throws BadRequestException
     */
    public function calculateVASPrice($input)
    {
        $this->trace->info(
            TraceCode::VAS_PRICING_FETCH_REQUEST,
            [
                'request body' => $input,
            ]);

        (new Validator())->validateInput("vasPricingCost", $input);

        $merchant_id = $input[Base\PublicEntity::MERCHANT_ID];
        $feature = $input[Entity::FEATURE];

        $input[Pricing\Calculator\PayAsYouGo::UNITS] = $input[Pricing\Calculator\PayAsYouGo::UNITS] ?? 0;
        $input[Pricing\Calculator\PayAsYouGo::METHOD] = $input[Pricing\Calculator\PayAsYouGo::METHOD] ?? null;
        $input[Pricing\Calculator\PayAsYouGo::AMOUNT] = $input[Pricing\Calculator\PayAsYouGo::AMOUNT] ?? 0;
        $input[Pricing\Calculator\PayAsYouGo::FREQUENCY] = $input[Pricing\Calculator\PayAsYouGo::FREQUENCY] ?? null;


        return (new Pricing\Fee())->calculateVASFees($input, $merchant_id, $feature);

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
            return $this->repo->transactionOnLiveAndTestAndAsv(function () use ($item, $planName)
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

                    $this->repo->transactionOnLiveAndTestAndAsv(function () use ($inputRules, $existingPlan, $ruleOrgId)
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
        if (isset($input[Pricing\Entity::FEE_BEARER])) {
            return $input;
        }

        $input[Pricing\Entity::FEE_BEARER] = $merchant->getFeeBearer();

        return $input;
    }

    public function generatePlanAndRuleIdsForReplicatePlan($ruleCount){
        $newPlanId = UniqueIdEntity::generateUniqueId();
        $newPlanName = UniqueIdEntity::generateUniqueId();

        // Generate rule IDs based on the rule count
        $ruleIds = [];
        for ($i = 0; $i < $ruleCount; $i++) {
            $ruleIds[] = UniqueIdEntity::generateUniqueId();
        }

        return [
            'plan_name' => $newPlanName,
            'plan_id' => $newPlanId,
            'ids' => $ruleIds,
        ];
    }

    public function replicatePlanAndAssign($merchant, $plan)
    {
        $fqcn = get_class($this) . '\\' . __FUNCTION__;

        $generatedPlanAndRuleId = $this->generatePlanAndRuleIdsForReplicatePlan(count($plan));

        $input['generated_ids'] = $generatedPlanAndRuleId;
        $input['merchant_id'] = $merchant->getId();
        $input['plan_id'] = $plan->getId();

        $ccRequest = $input;

        $legacyCallable = function ($rampPhase, $_) use ($merchant, $plan, $generatedPlanAndRuleId, $input) {
            return $this->replicatePlanAndAssignLegacy($merchant, $plan, $generatedPlanAndRuleId, $rampPhase, $input);
        };

        return $this->ccRouter->route($fqcn, $ccRequest, $legacyCallable);
    }

    public function replicatePlanAndAssignLegacy($merchant, $plan, $generatedPlanAndRuleId, $rampPhase = '', $input = null)
    {
        // Get merchants existing plan ID, consider initial id sent in case of reverse_shadow
        $planId = $merchant->getPricingPlanId();
        if ($rampPhase == CCRouter::REVERSE_SHADOW && $input != null){
            $planId = $input['plan_id'];
        }

        // Get intended pricing plans org id
        $ruleOrgId = $plan->getOrgId();

        $this->trace->info(TraceCode::BATCH_PRICING_PLAN_REPLICATE_REQUEST,
                            [
                                Entity::PLAN_ID => $planId
                            ]);

        // make an array copy out of plan into array rules
        $rules = $plan->toArray();

        // Generate a new plan id
        if ($generatedPlanAndRuleId == null || empty($generatedPlanAndRuleId['plan_name']) ){
            $planName = UniqueIdEntity::generateUniqueId();
        }else{
            $planName = $generatedPlanAndRuleId['plan_name'];
        }

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


            // fill plan_id and rule_id if passed in input
            if ($generatedPlanAndRuleId != null){
                if ( !empty($generatedPlanAndRuleId['plan_id']) ) {
                    $rules[$i][Entity::PLAN_ID] = $generatedPlanAndRuleId['plan_id'];
                }

                if ( !empty($generatedPlanAndRuleId['ids']) && $i < count($generatedPlanAndRuleId['ids']) && !empty($generatedPlanAndRuleId['ids'][$i])) {
                    $rules[$i][Entity::ID] = $generatedPlanAndRuleId['ids'][$i];
                }
            }

            $rules[$i][Entity::INTERNATIONAL] = $rules[$i][Entity::INTERNATIONAL] === true ? '1' : '0';

            if ($rules[$i][Entity::PRODUCT] !== Product::BANKING)
            {
                unset($rules[$i][Entity::ACCOUNT_TYPE]);
            }

            if ($rules[$i][Entity::PRODUCT] === Product::BANKING &&
                (isset($rules[$i][Entity::ACCOUNT_TYPE]) === false or
                $rules[$i][Entity::ACCOUNT_TYPE] !== Merchant\Balance\AccountType::DIRECT))
            {
                unset($rules[$i][Entity::CHANNEL]);
            }

            // Adding this as the earlier logic was unsetting channel input for all requests except account_type:direct
            if (empty($rules[$i][Entity::CHANNEL]))
            {
                unset($rules[$i][Entity::CHANNEL]);
            }
        }

        // Create new plan with copied rules
        $newplan = (new Pricing\Core)->create([Entity::PLAN_NAME => $planName, Entity::RULES => $rules], $ruleOrgId);

        // Assign plan to merchant, only when pricing decomp ramp phase is not enable or reverse_shadow
        // as same action will be taken by Charge-collections service
        if ($rampPhase != CCRouter::ENABLE && $rampPhase != CCRouter::REVERSE_SHADOW ){
            (new Merchant\Service)->assignPricingPlan($merchant->getId(),
                [Merchant\Entity::PRICING_PLAN_ID => $newplan->getId()]);
        }

        // Return the new plan
        return $newplan;
    }

    public function fetchPlanForSDK($id, array $input = [])
    {
        if (empty($id) === true) {
            throw new Exception\BadRequestValidationFailureException("Plan Id is a required field");
        }
        $orgId = null;
        $type = null;
        if (empty($input[Pricing\Entity::TYPE]) === false) {
            $type = $input[Pricing\Entity::TYPE];
        }
        if (empty($input[Pricing\Entity::ORG_ID]) === false) {
            $orgId = $input[Pricing\Entity::ORG_ID];
        }

        $assignedPlan = $this->repo->pricing->getPlan($id, $type, orgId:$orgId);

        $additionalFlags = $this->getAdditionalFlags($input, $assignedPlan);

        return array_merge($assignedPlan->toArrayPublic(), $additionalFlags);
    }

    private function getAdditionalFlags(array $input, Plan $assignedPlan)
    {
        $paymentId = $input['payment_id'] ?? null;
        $response = [
            'fixed_fee_rule_present' => false,
            'custom_oauth_plan_present' => false,
            'explicit_commission_plan_present' => false,
        ];
        if(empty($paymentId) === true) {
            return $response;
        }
        try {
            $payment = $this->repo->payment->findByPublicId($paymentId);
            if ($assignedPlan->hasFixedFeeRuleForPaymentMethod($payment->getMethod())) {
                $response['fixed_fee_rule_present'] = true;
            }
            $commissionCalculator = (new Calculator($payment));
            if ($commissionCalculator->shouldChargePartnerFees() === true and $commissionCalculator->getExplicitPricingPlan() !== null) {
                $response['explicit_commission_plan_present'] = true;
            }
            $feeCalculator = new Fee();
            if ($feeCalculator->getCustomPricingPlan($payment) !== null) {
                $response['custom_oauth_plan_present'] = true;
            }
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::PRICING_ADDITIONAL_FLAGS_PLAN_FETCH_ERROR,
                array_merge($input, ['assigned_plan'=> $assignedPlan->getId(), 'error' => $e->getMessage()])
            );
        }
        return $response;
    }

    public function getPlanById($id, array $input = [])
    {
        $type = null;
        if (empty($input[Pricing\Entity::TYPE]) === false) {
            $type = $input[Pricing\Entity::TYPE];
        }
        $skipOrgIdCheck = filter_var($input['skip_org_id_check'], FILTER_VALIDATE_BOOLEAN);

        $plan = $this->repo->pricing->getPlan($id, $type, skipOrgCheck:$skipOrgIdCheck);

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

    public function updatePlanRule($planId, $ruleId, $input, $isBuyPricingRule = false, $orgId = null)
    {
        $sourceInput = $input;
        $fqcn = get_class($this) . '\\' . __FUNCTION__;
        $planAndRuleIds = Pricing\ChargeCollections\Utils::generatePlanAndRuleIds(1);
        $ccRequest = $this->transformUpdatePlanRequest($input, $planId, $ruleId, $planAndRuleIds);

        $legacyCallable = function ($rampPhase, $_) use ($planId, $ruleId, $sourceInput, $isBuyPricingRule, $planAndRuleIds, $orgId) {
            return $this->updatePlanRuleLegacy($planId, $ruleId, $sourceInput, $isBuyPricingRule, $planAndRuleIds, $orgId, $rampPhase);
        };

        return $this->ccRouter->route($fqcn, $ccRequest, $legacyCallable, null, $isBuyPricingRule);
    }

    public function transformUpdatePlanRequest($input, $planId, $ruleId, $planAndRuleIds) {
        $input['new_rule_id'] = $planAndRuleIds['ruleIds'][0] ?? '';
        $input[Entity::PLAN_ID] = $planId;
        $input['rule_id'] = $ruleId;

        $this->trace->info(TraceCode::CC_ROUTING_TRANSFORMED_REQUEST,
            [
                'method' => 'updatePlanRule',
                'request' => $input,
            ]);
        return $input;
    }

    public function updatePlanRuleLegacy($planId, $ruleId, $input, $isBuyPricingRule = false, $planAndRuleIds = null, $orgId = null, $rampPhase = '')
    {
        $newRuleIds = $planAndRuleIds['ruleIds'] ?? [];
        if (!empty($newRuleIds[0])) {
            $input[Entity::ID] = $newRuleIds[0];
        }

        if ($isBuyPricingRule === true)
        {
            $this->repo->pricing->onlyBuyPricing();
        }

        $rule = (new Pricing\Core)->editPlanRule($planId, $ruleId, $input, $orgId, $rampPhase);

        return $rule->toArray();
    }

    public function deletePlanRuleForce($planId, $ruleId)
    {
        $fqcn = get_class($this) . '\\' . __FUNCTION__;
        $ccRequest = [
            'plan_id' => $planId,
            'rule_id' => $ruleId,
        ];

        $legacyCallable = function () use ($planId, $ruleId) {
            return $this->deletePlanRuleForceLegacy($planId, $ruleId);
        };

        return $this->ccRouter->route($fqcn, $ccRequest, $legacyCallable);
    }

    public function deletePlanRuleForceLegacy($planId, $ruleId)
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

    public function createOrgPricing(array $input, string $adminId)
    {
        $this->modifyOrgIdInInput($input);

        $input['admin_id'] = $adminId;

        // Check if the admin has CREATE_MAKER access or ALL access
        $adminHasCreateAccess = $this->hasCreateMakerOrAllAccess($adminId, $input['org_id']);

        if ($adminHasCreateAccess === false)
        {
            throw new BadRequestValidationFailureException('Admin doesn\'t has access to create org pricing workflow');
        }

        $ORG_PRICING_APPROVE_CONTROLLER = 'RZP\Http\Controllers\PricingController@approveOrgPricingWorkflow';

        try
        {
            $this->app['workflow']
                ->setPermission('create_org_pricing_maker')
                ->setEntityAndId('org_pricing', $input['org_id'])
                ->setInput(['org_id' => $input['org_id']])
                ->setController($ORG_PRICING_APPROVE_CONTROLLER)
                ->handle([], ['org_id' => $input['org_id']], false, false, true);

            return [];
        }

        catch(Exception\EarlyWorkflowResponse $e)
        {

            $workflowActionData = json_decode($e->getMessage(), true);

            $workflowActionId = Action\Entity::silentlyStripSign($workflowActionData['id']);

            $input['workflow_id'] = $workflowActionId;

            $endPoint = ChargeCollections::OrgPricingURL;
            $headers = [
                ChargeCollections::X_DASHBOARD_USER_ID => $input['admin_id']
            ];
            try
            {
                $this->app->charge_collections->sendRequest($endPoint, Requests::POST, $input, $headers );
            }
            catch(\Exception $e)
            {
                (new Action\Service())->closeAction("w_action_".$input['workflow_id']);
            }
            throw $e;
        }
    }

    public function hasCreateMakerOrAllAccess($adminId, $orgId) {

        $adminAccessArray = $this->fetchOrgPricingAccessControl(['admin_id' => $adminId]);

        foreach ($adminAccessArray as $adminAccess)
        {
            if ((($adminAccess['access_type'] === PricingConstants::CREATE_MAKER) and
                 ($adminAccess['org_id'] === $orgId)) or ($adminAccess['access_type'] === PricingConstants::ALL))
            {
                return true;
            }
        }
        return false;
    }

    public function fetchOrgPricing(array $input, string $adminId)
    {
        $this->modifyOrgIdInInput($input);

        $endPoint = ChargeCollections::FetchOrgPricingURL .'?'. http_build_query($input);
        $headers = [
            ChargeCollections::X_DASHBOARD_USER_ID => $adminId
        ];

        $chargeCollectionsResponse = $this->app->charge_collections->sendRequest($endPoint, Requests::GET, [], $headers );

        if (isset($chargeCollectionsResponse['org_pricing']) === true)
        {
            return [
                'count' => count($chargeCollectionsResponse['org_pricing']),
                'items' => $chargeCollectionsResponse['org_pricing']
            ];
        }

        return [
            'count' => 0,
            'items' => []
        ];
    }

    public function updateOrgPricing(array $input, string $id, string $adminId)
    {
        $this->modifyOrgIdInInput($input);

        $endPoint = ChargeCollections::OrgPricingURL .'/'.$id;
        $headers = [
            ChargeCollections::X_DASHBOARD_USER_ID => $adminId
        ];

        return $this->app->charge_collections->sendRequest($endPoint, Requests::PATCH, $input, $headers );
    }

    public function fetchOrgPricingAccessControl(array $input)
    {
        $this->modifyOrgIdInInput($input);

        $endPoint = ChargeCollections::FetchOrgPricingAccessControl.'?'. http_build_query($input);

        $response =  $this->app->charge_collections->sendRequest($endPoint, Requests::GET, [], [] );

        foreach ($response['org_pricing_access_control'] as &$accessControl)
        {
            $adminId = $accessControl['admin_id'];

            $adminEmail = $this->repo->admin->getAdminFromId($adminId)->getEmail();

            $accessControl['admin_email'] = $adminEmail;
        }

        return $response['org_pricing_access_control'];
    }

    public function modifyOrgIdInInput(array &$input)
    {
        if (array_key_exists('organization_id', $input) === true)
        {
            $input['org_id'] = Org\Entity::silentlyStripSign($input['organization_id']);

            unset($input['organization_id']);
        }
    }

    public function createOrgPricingAccessControl(array $input, string $adminOrgId)
    {
        $validator = new Pricing\Validator;

        $validator->validateInput('create_org_pricing_access_control', $input);

        $this->modifyOrgIdInInput($input);

        $adminEmail = $input['admin_email'];

        $admin = (new Admin\Service())->getAdminFromEmail($adminOrgId, $adminEmail);

        unset($input['admin_email']);

        $input['admin_id'] = $admin['id'];

        $endPoint = ChargeCollections::CreateOrgPricingAccessControl;

        $response = $this->app->charge_collections->sendRequest($endPoint, Requests::POST, $input, [] );

        $response['OrgPricingAccessControl']['admin_email'] = $adminEmail;

        return $response;
    }

    public function revokeOrgPricingAccessControl(string $permissionId)
    {
        $endPoint = ChargeCollections::RevokeOrgPricingAccessControl . '/' . $permissionId;

        return$this->app->charge_collections->sendRequest($endPoint, Requests::DELETE, [], [] );
    }

    public function revokeAllOrgPricingAccessControl(string $adminId)
    {
        $endPoint = ChargeCollections::RevokeAllOrgPricingAccessControl . '/' . $adminId;

        return $this->app->charge_collections->sendRequest($endPoint, Requests::DELETE, [], [] );
    }

    public function approveOrgPricingWorkflow(array $input, string $adminId)
    {
        $workflowActions = ((new WorkFlowActionCore()))->fetchLastUpdatedWorkflowActionInPermissionList(
            $input['org_id'], 'org_pricing' , ['create_org_pricing_maker']);


        $workflowActionId = $workflowActions['id'];

        $endPoint = ChargeCollections::ApproveOrgPricing . '/'. $workflowActionId ;

        $headers = [
            ChargeCollections::X_DASHBOARD_USER_ID => $adminId
        ];

        return $this->app->charge_collections->sendRequest($endPoint, Requests::POST, [], $headers );
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

    private function getInputRuleCount($input){
        if (isset($input['rules']) === true and is_string($input['rules']) === true)
        {
            $input['rules'] = json_decode($input['rules'], true);
        }

        if (isset($input['rules']) === true){
            return count($input['rules']);
        }else{
            return 0;
        }
    }
}
