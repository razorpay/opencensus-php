<?php

namespace RZP\Models\Pricing\ChargeCollections;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Pricing\Plan;
use RZP\Models\Pricing\Plan as PlanCollection;
use RZP\Models\Pricing\Entity as PricingEntity;
use RZP\Services\ChargeCollections;
use RZP\Trace\TraceCode;


/*
 * The following class aims to make the decision whether the request for pricing rules should be routed to the charge-collections service.
 */
class CCRouter
{
    protected $trace;
    protected $app;
    private string $splitzExperimentID;

    const DISABLE = 'disable';
    const SHADOW = 'shadow';
    const REVERSE_SHADOW = 'reverse_shadow';
    const ENABLE = 'enable';


    const VALID = 'valid';
    const VARIANT = 'variant';

    const VALID_CC_EXPERIMENT_VARIANTS = [self::DISABLE, self::SHADOW, self::REVERSE_SHADOW, self::ENABLE];

    const ROUTE_OR_FUNCTION_NOT_ONBOARDED = 'route_or_function_not_boarded';
    const REPO_TRANSACTION_ACTIVE = 'repo_transaction_active';
    const SPLITZ_RESPONSE_ERROR = 'splitz_response_error';
    const EXCEPTION = 'exception';
    const TRANSFORMATION_NOT_FOUND = 'transformation_not_found';


    private const ROUTE_MAP = array(
        'pricing_fetch_plan' => true,
        'pricing_create_plan' => true,
        'pricing_update_plan_rule' => true,
        'pricing_delete_plan_rule_force' => true,
        'pricing_add_plan_rule_bulk' => true,
        'pricing_add_plan_rule' => true,
        );

    private const FUNCTION_MAP = array(
        'RZP\\Models\\Pricing\\Service\\createPlan' => true,
        'RZP\\Models\\Pricing\\Service\\updatePlanRule' => true,
        'RZP\\Models\\Pricing\\Service\\deletePlanRuleForce' => true,
        'RZP\\Models\\Pricing\\Service\\postAddBulkPricingRules' => true,
        'RZP\\Models\\Pricing\\Service\\addPlanRule' => true,
        'RZP\\Models\\Pricing\\Service\\replicatePlanAndAssign' => true,
        'RZP\\Models\\Pricing\\Repository\\getPlan' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlanByIdAndOrgId' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlanByIdWithProductAndFeatureFilter' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingRulesByPlanIdProductAndFeatureWithoutOrgId' => true,
        'RZP\\Models\\Pricing\\Repository\\getBankingSharedAccountNonFreePayouDefaultPricingRules' => true,
        'RZP\\Models\\Pricing\\Repository\\getPlanByName' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingRuleByMultipleParams' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlansSummary' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingRulesByPlanIdProductFeaturePaymentMethodOrgId' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlanByIdWithoutOrgId' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingRuleIdsByMerchant' => true,
        'RZP\\Models\\Pricing\\Repository\\getZeroPricingPlanRuleForMethod' => true,
        'RZP\\Models\\Pricing\\Repository\\getBankingDirectAccountNonFreePayoutDefaultPricingRules' => true,
        'RZP\\Models\\Pricing\\Repository\\getBankingSharedAccountFreePayoutDefaultPricingRules' => true,
        'RZP\\Models\\Pricing\\Repository\\getBankingDirectAccountFreePayoutDefaultPricingRules' => true,
        'RZP\\Models\\Pricing\\Repository\\getBankingAccountChargeCollectionDefaultPricingRules' => true,
        'RZP\\Models\\Pricing\\Repository\\getBankingAccountRzpFeesDefaultPricingRules' => true,
        'RZP\\Models\\Pricing\\Repository\\getAppPayoutPricingRules' => true,
        'RZP\\Models\\Pricing\\Repository\\getPlanRule' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingFromPricingId' => true,
    );

    private const FUNCTION_TO_CC_ROUTE_MAP = array(
        'RZP\\Models\\Pricing\\Repository\\getPlan' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlanByIdAndOrgId' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlanByIdWithProductAndFeatureFilter' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingRulesByPlanIdProductAndFeatureWithoutOrgId' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getBankingSharedAccountNonFreePayouDefaultPricingRules' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPlanByName' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingRuleByMultipleParams' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingRulesByPlanIdProductFeaturePaymentMethodOrgId' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlansSummary' => ChargeCollections::GetPricingPlansSummaryURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlanByIdWithoutOrgId' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingRuleIdsByMerchant' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getZeroPricingPlanRuleForMethod' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getBankingDirectAccountNonFreePayoutDefaultPricingRules' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getBankingSharedAccountFreePayoutDefaultPricingRules' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getBankingDirectAccountFreePayoutDefaultPricingRules' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getBankingAccountChargeCollectionDefaultPricingRules' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getBankingAccountRzpFeesDefaultPricingRules' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getAppPayoutPricingRules' => ChargeCollections::GetPricingPlanURL,
        'RZP\\Models\\Pricing\\Repository\\getPlanRule' => ChargeCollections::GetPricingRuleURL,
        'RZP\\Models\\Pricing\\Repository\\getPricingFromPricingId' => ChargeCollections::GetPricingRuleURL,
    );

    public function __construct(bool $writes = false, bool $reads = false)
    {
        $app = App::getFacadeRoot();
        $this->app = $app;
        $this->trace = $app['trace'];
        $this->splitzExperimentID = "";

        if ($writes) {
            $this->splitzExperimentID = $app['config']->get('app.pricing_writes_experiment_id') ?? '';
        }
        if ($reads) {
            $this->splitzExperimentID= $app['config']->get('app.pricing_reads_experiment_id') ?? 'P3jQnkfWa0vrnm';
        }
    }

    public function route($fqcn, $ccRequest, $legacyCallable, $sourceInput = null, $buyPricing = false)
    {
        // skip decomp for buy pricing
        if ($buyPricing){
            $legacyCallableWithPhase = $this->getLegacyCallableBasedOnParameterCount($legacyCallable, self::DISABLE, $sourceInput);
            return call_user_func($legacyCallableWithPhase);
        }

        $planId = $ccRequest['plan_id'] ?? $ccRequest['id'];
        if($planId == null) $planId = '';
        $startTimeMs = round(microtime(true) * 1000);
        $rampPhase = $this->shouldRouteRequestToChargeCollections($fqcn, $planId);
        $methodName = Utils::extractMethodFromFunction($fqcn);

        // Modify the legacyCallable to pass rampPhase only if the legacy method accepts it
        $legacyCallableWithPhase = $this->getLegacyCallableBasedOnParameterCount($legacyCallable, $rampPhase, $sourceInput);
        $metricDimensions = [
            'method' => $methodName,
            'mode' => $rampPhase,
        ];

        if ($rampPhase == CCRouter::DISABLE || $rampPhase == CCRouter::SHADOW) {
            // Legacy request
            $legacyStartTimeMs = round(microtime(true) * 1000);
            $legacyResponse = call_user_func($legacyCallableWithPhase);
            $legacyEndTimeMs = round(microtime(true) * 1000);
            $this->trace->histogram(Metric::CC_ROUTER_PRICING_LEGACY_CALL_TIME, $legacyEndTimeMs- $legacyStartTimeMs, $metricDimensions);

            if ($methodName == 'postAddBulkPricingRules'){
                list($legacyResponse, $ccRequest) = $this->modifyBulkRequestBasedOnLegacyResponse($legacyResponse, $ccRequest);
            }

            if ($rampPhase == CCRouter::SHADOW) {
                $ccResponse = $this->sendChargeCollectionsRequest($fqcn, $ccRequest, $rampPhase);
                $this->trace->info(TraceCode::CC_ROUTER_SERVICE_RESPONSE, [
                    'method' => $methodName,
                    'mode' => CCRouter::SHADOW,
                    'response' => $ccResponse,
                ]);
            }
            if($rampPhase == self::SHADOW) {
                $this->compareCCAndApiReponse($ccResponse, $legacyResponse, $fqcn);
            }
            $endTimeMs = round(microtime(true) * 1000);
            $this->trace->histogram(Metric::CC_ROUTER_TOTAL_TIME, $endTimeMs- $startTimeMs, $metricDimensions);

            return $legacyResponse;

        } else if ($rampPhase == CCRouter::REVERSE_SHADOW || $rampPhase == CCRouter::ENABLE) {
            // Route to ChargeCollections
            $response = $this->sendChargeCollectionsRequest($fqcn, $ccRequest, $rampPhase);

            if ($methodName == 'postAddBulkPricingRules'){
                list($response, $sourceInput) = $this->modifyBulkRequestBasedOnCCResponse($response, $sourceInput);
                $legacyCallableWithPhase = $this->getLegacyCallableBasedOnParameterCount($legacyCallable, $rampPhase, $sourceInput);
            }

            if ($rampPhase == CCRouter::REVERSE_SHADOW) {
                try {
                    $legacyStartTimeMs = round(microtime(true) * 1000);
                    $legacyResponse = call_user_func($legacyCallableWithPhase);
                    $legacyEndTimeMs = round(microtime(true) * 1000);
                    $this->trace->histogram(Metric::CC_ROUTER_PRICING_LEGACY_CALL_TIME, $legacyEndTimeMs- $legacyStartTimeMs, $metricDimensions);
                    $this->trace->info(TraceCode::API_PRICING_LEGACY_RESPONSE, [
                        'method' => $methodName,
                        'mode' => CCRouter::REVERSE_SHADOW,
                        'response' => $legacyResponse,
                    ]);
                } catch (\Throwable $e) {
                    $this->trace->traceException($e, Trace::WARNING, TraceCode::API_PRICING_LEGACY_ERROR, [
                        'method' => $methodName,
                        'mode' => CCRouter::REVERSE_SHADOW,
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                    ]);
                }
            }
            $endTimeMs = round(microtime(true) * 1000);
            $this->trace->histogram(Metric::CC_ROUTER_TOTAL_TIME, $endTimeMs- $startTimeMs, $metricDimensions);

            return $response;
        }

        $this->trace->error(TraceCode::CC_ROUTER_ROUTE_ERROR, [
            'error' => 'unknown rampPhase found',
            'rampPhase' => $rampPhase,
        ]);

        return ApiResponse::json([
            'message' => 'Internal Server Error',
        ], 500);
    }

    public function getLegacyCallableBasedOnParameterCount($legacyCallable, $rampPhase, $sourceInput) {
        return function () use ($legacyCallable, $rampPhase, $sourceInput) {
            $reflection = new \ReflectionFunction($legacyCallable);
            if ($reflection->getNumberOfParameters() > 0) {
                return call_user_func($legacyCallable, $rampPhase, $sourceInput);  // Pass rampPhase
            } else {
                return call_user_func($legacyCallable);  // Do not pass rampPhase
            }
        };
    }

    private function sendChargeCollectionsRequest($fqcn, $input, $rampPhase = null) {

        $routeName = null;

        $headers = [ChargeCollections::X_PRICING_DECOMP_PHASE => $rampPhase ?? ''];

        try {
            $routeName = app('request.ctx')->getRoute();
            if(empty($routeName)) {
                $routeName =  app('worker.ctx')->getJobName() ?? '';
            }
            $methodName = Utils::extractMethodFromFunction($fqcn);
            $this->trace->count(Metric::CC_REQUEST_ROUTED, [
                'route' => $routeName,
                'function' => $fqcn,
                'ramp_phase' => $rampPhase,
                ]);

            if (self::FUNCTION_TO_CC_ROUTE_MAP[$fqcn] == ChargeCollections::GetPricingPlansSummaryURL) {
                $response = $this->app->charge_collections->getPricingPlansSummary($input);
                return $this->transformSummaryResponse($response);
            }

            if (self::FUNCTION_TO_CC_ROUTE_MAP[$fqcn] == ChargeCollections::GetPricingPlanURL) {
                $response = $this->app->charge_collections->getPricingPlan($input);
                return $this->transformToPlanModel($response);
            }

            if (self::FUNCTION_TO_CC_ROUTE_MAP[$fqcn] == ChargeCollections::GetPricingRuleURL) {
                $response = $this->app->charge_collections->getPricingRule($input);
                return $this->transformToPricingModel($response);
            }

            if ($methodName == 'createPlan'){
                $response = $this->app->charge_collections->createPricingPlan($input, $headers);
            }else if ($methodName == 'updatePlanRule'){
                $response = $this->app->charge_collections->updatePricingPlanRule($input, $headers);
                if (isset($response['rule'])){
                    $response = $response['rule'];
                }
            }else if ($methodName == 'deletePlanRuleForce'){
                $response = $this->app->charge_collections->deletePricingPlanRule($input);
            }else if ($methodName == 'postAddBulkPricingRules'){
                $response = $this->app->charge_collections->addBulkPricingPlanRule($input, $headers);
            }else if($methodName == 'addPlanRule'){
                $response = $this->app->charge_collections->addPricingPlanRule($input);}
            else if($methodName == 'replicatePlanAndAssign'){
                $response = $this->app->charge_collections->replicatePlanAndAssign($input, $headers);
                return $this->transformToPlanModel($response);
            }else{
                $this->trace->info(TraceCode::CC_ROUTER_EXCEPTION,
                    [
                        'Endpoint not found for method' => $methodName,
                        'function' => $fqcn,
                    ]);

                $this->monitorChargeCollectionsRequestNotRouted($routeName,$fqcn, self::TRANSFORMATION_NOT_FOUND);
                return null;
            }

            return $response;
        }catch (\Throwable $e){
            $this->trace->traceException($e, Trace::WARNING, TraceCode::CC_ROUTER_EXCEPTION);
            $this->monitorChargeCollectionsRequestNotRouted($routeName, $fqcn ,self::EXCEPTION);

            if ($rampPhase == self::REVERSE_SHADOW || $rampPhase == self::ENABLE){
                throw $e;
            } else {
                return null;
            }
        }
    }

    private function monitorChargeCollectionsRequestNotRouted($routeName, $functionName, $reason): void
    {
        $this->trace->count(Metric::CC_REQUEST_NOT_ROUTED, [
            'route' => $routeName,
            'function' => $functionName,
            'reason' => $reason,
        ]);
    }

    private function shouldRouteRequestToChargeCollections($functionName, $planID): string {

        $routeName = null;

        try {
            $routeName = app('request.ctx')->getRoute();
            if(empty($routeName)) {
                $routeName =  app('worker.ctx')->getJobName() ?? '';
            }

            if($this->isRouteApplicableForDecomp($routeName) === false &&
                $this->isFunctionApplicableForDecomp($functionName) === false) {
                $this->monitorChargeCollectionsRequestNotRouted($routeName,$functionName,self::ROUTE_OR_FUNCTION_NOT_ONBOARDED);
                return self::DISABLE;
            }

            $experimentID = $this->splitzExperimentID;
            // generate a random ID to randomly assign experiment variant
            if (empty($planID)) {
                $planID = UniqueIdEntity::generateUniqueId();
            }

            $result = $this->checkSplitzExperiment($planID, $experimentID);
            if($result[self::VALID] === false) {
                $this->monitorChargeCollectionsRequestNotRouted($routeName,$functionName,self::SPLITZ_RESPONSE_ERROR);
                return self::DISABLE;
            }

            return $result[self::VARIANT];
        }catch (\Throwable $e){
            $this->trace->traceException($e, Trace::WARNING, TraceCode::CC_ROUTER_EXCEPTION);
            $this->monitorChargeCollectionsRequestNotRouted($routeName, $functionName, self::EXCEPTION);
            return self::DISABLE;
        }
    }

    private function isTransactionActive(): bool {
        return app('repo')->isTransactionActive();
    }

    private function checkSplitzExperiment(string $id, string $experimentId): array
    {
        $startTimeMs = round(microtime(true) * 1000);
        try {
            $request = ['id' => $id, 'experiment_id' => $experimentId];

            $response = $this->app['splitzService']->evaluateRequest($request);

            $this->trace->info(TraceCode::CC_DEBUG_LOG, [
                "splitz_response" => $response,
            ]);

            if ($response['status_code'] !== 200) {
                $this->trace->info(TraceCode::CC_ROUTER_SPLITZ_ERROR, ['response' => $response]);
                return [
                    self::VALID => false,
                    self::VARIANT => '',
                ];
            }

            $variant = $response['response']['variant'] ?? [];
            $variantName = $variant['name'] ?? '';
            $endTimeMs = round(microtime(true) * 1000);
            $this->trace->histogram(Metric::CC_ROUTER_SPLITZ_RESPONSE_TIME, $endTimeMs- $startTimeMs);

            if (in_array($variantName, self::VALID_CC_EXPERIMENT_VARIANTS)) {
                return [
                    self::VALID => true,
                    self::VARIANT => $variantName,
                ];
            }else{
                $this->trace->info(TraceCode::CC_ROUTER_SPLITZ_ERROR, [
                    'invalid variant response' => $response,
                    'variant' => $variantName,
                ]);
                return [
                    self::VALID => false,
                    self::VARIANT => '',
                ];
            }
        } catch (\Throwable $e) {
            $this->trace->info(TraceCode::CC_ROUTER_SPLITZ_ERROR, [
                'splitz_exception' => $e,
                "splitz_call_response" => $response,
                "experiment_id" => $experimentId,
                "identifier" => $id,
            ]);
            $endTimeMs = round(microtime(true) * 1000);
            $this->trace->histogram(Metric::CC_ROUTER_SPLITZ_RESPONSE_TIME, $endTimeMs- $startTimeMs);

            return [
                self::VALID => false,
                self::VARIANT => '',
            ];
        }
    }

    private function isRouteApplicableForDecomp($routeName)
    {
        return self::ROUTE_MAP[$routeName] === true;
    }

    private function isFunctionApplicableForDecomp($functionName): bool
    {
        return self::FUNCTION_MAP[$functionName] === true;
    }

    private function transformSummaryResponse($response) {
        $responseForPlanMap = ['rules' => []];
        foreach ($response['plans'] as $planResponse) {
            $responseForPlanMap['rules'][] = $planResponse;
        }
        return $this->transformToPlanModel($responseForPlanMap);
    }

    public function modifyBulkRequestBasedOnLegacyResponse($legacyResponse, $ccRequest){
        $inputCount = count($legacyResponse['items']);

        for ($i = 0; $i < $inputCount; $i++) {
            if (isset($legacyResponse['items'][$i]['plan_replicated'])) {
                $ccRequest['items'][$i]['plan_replicated'] =  $legacyResponse['items'][$i]['plan_replicated'];
                unset($legacyResponse['items'][$i]['plan_replicated']);
            }
        }

        return [$legacyResponse, $ccRequest];
    }

    public function modifyBulkRequestBasedOnCCResponse($ccResponse, $legacyRequest){

        if (!isset($ccResponse['items']) || !is_array($ccResponse['items'])) {
            return [$ccResponse, $legacyRequest];
        }

        $inputCount = count($ccResponse['items']);

        for ($i = 0; $i < $inputCount; $i++) {
            if (isset($ccResponse['items'][$i]['plan_replicated']) && isset($legacyRequest[$i])) {
                $legacyRequest[$i]['plan_replicated'] = $ccResponse['items'][$i]['plan_replicated'];
                unset($ccResponse['items'][$i]['plan_replicated']);
            }
        }

        return [$ccResponse, $legacyRequest];
    }
    private function transformToPricingModel($response)
    {
        $pricingEntity = new PricingEntity;
        if(!isset($response['rule'])) {
            return $pricingEntity;
        } else {
            try {
                $entityClass = PricingEntity::class;
                $entityClass::unguard();
                $pricingEntity = new PricingEntity($response['rule']);
            } catch (\Throwable $e) {
                throw new \Exception('Could not map charge collections response to entity');
            } finally {
                $entityClass::reguard();
            }
        }
        return $pricingEntity;
    }

    public function transformToPlanModel($response)
    {
        if(!isset($response['rules']) || count($response['rules']) == 0) {
            return new PlanCollection;
        }
        try {
            $pricingEntities = array();
            $entityClass = PricingEntity::class;
            foreach($response['rules'] as $rule) {
                try {
                    $entityClass::unguard();
                    $pricingEntities[] = new PricingEntity($rule);
                } catch (\Throwable $e) {
                    throw new \Exception('Could not map charge collections response to entity');
                } finally {
                    $entityClass::reguard();
                }
            }
            return new PlanCollection($pricingEntities);
        } catch (\Throwable $e) {
            throw new \Exception('Could not map charge collections response to entity');
        }
        return new PlanCollection;
    }

    private function compareCCAndApiReponse($ccResponse, $legacyResponse, $fqcn)
    {
        try {
            // Only Comparing for read requests
            if (!isset(self::FUNCTION_TO_CC_ROUTE_MAP[$fqcn])) {
                return;
            }
            if (empty($legacyResponse)) {
                return;
            }
            if (empty($ccResponse)) {
                $this->trace->info(TraceCode::CC_ROUTER_RESPONSE_MISMATCH, [
                    'function' => $fqcn,
                    'reason' => 'empty_cc_response',
                    'cc_response' => $ccResponse,
                    'legacy_response' => $legacyResponse,
                ]);
                $this->trace->count(Metric::CC_ROUTER_RESPONSE_MISMATCH, [
                    'function' => $fqcn,
                    'reason' => 'empty_cc_response',
                ]);
                return;
            }
            if (get_class($ccResponse) !== get_class($legacyResponse)) {
                $this->trace->info(TraceCode::CC_ROUTER_RESPONSE_MISMATCH, [
                    'function' => $fqcn,
                    'reason' => 'type_mismatch',
                    'cc_response_type' => get_class($ccResponse),
                    'legacy_response_type' => get_class($legacyResponse),
                ]);
                $this->trace->count(Metric::CC_ROUTER_RESPONSE_MISMATCH, [
                    'function' => $fqcn,
                    'reason' => 'type_mismatch',
                ]);
                return;
            }

            $responseClass = get_class($legacyResponse);
            $ccRoute = self::FUNCTION_TO_CC_ROUTE_MAP[$fqcn];
            if ($responseClass == 'RZP\Models\Pricing\Plan') {
                if ($ccRoute == ChargeCollections::GetPricingPlansSummaryURL) {
                    $this->compareSummaryResponse($ccResponse, $legacyResponse, $fqcn);
                } else {
                    $this->comparePlanCollection($ccResponse, $legacyResponse, $fqcn);
                }
            } else if ($responseClass == 'RZP\Models\Pricing\Entity') {
                $this->comparePricingEntityAndPushMetrics($ccResponse, $legacyResponse, $fqcn);
            }
        } catch(\Throwable $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::CC_COMPARE_EXCEPTION, [
                'method' => $fqcn,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function comparePlanCollection($ccResponse, $legacyResponse, $fqcn)
    {
        $legacyResponseMap = array();
        $ccResponseMap = array();
        foreach ($legacyResponse as $legacyItem) {
            $legacyResponseMap[$legacyItem->getId()] = $legacyItem;
        }
        foreach ($ccResponse as $ccResponseItem) {
            $ccResponseMap[$ccResponseItem->getId()] = $ccResponseItem;
        }
        $ccResponseIds = array_keys($ccResponseMap);
        $legacyResponseIds = array_keys($legacyResponseMap);
        $keyDiff = array_diff($ccResponseIds, $legacyResponseIds);
        foreach($legacyResponseMap as $legacyItem) {
            if (!isset($ccResponseMap[$legacyItem->getId()])) {
                $this->trace->info(TraceCode::CC_ROUTER_RESPONSE_MISMATCH, [
                    'function' => $fqcn,
                    'reason' => 'id_not_present',
                    'cc_response_ids' => $ccResponseIds,
                    'legacy_response_ids' => $legacyResponseIds,
                    'id_diff' => $keyDiff,
                ]);
                $this->trace->count(Metric::CC_ROUTER_RESPONSE_MISMATCH, [
                    'function' => $fqcn,
                    'reason' => 'id_not_present',
                ]);
                return;
            } else {
                $ccResponseItem = $ccResponseMap[$legacyItem->getId()];
                if($this->comparePricingEntityAndPushMetrics($ccResponseItem, $legacyItem, $fqcn)) return;
            }
        }
    }

    private function comparePricingEntityAndPushMetrics($ccResponse, $legacyResponse, $fqcn) {
        $ccResponseArray = $ccResponse->toArray();
        $legacyResponseArray = $legacyResponse->toArray();
        $keyAbsent = false;
        $valueMismatch = false;
        foreach ($legacyResponseArray as $k => $v) {
            if (in_array($k, ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            if (!array_key_exists($k, $ccResponseArray)) {
                $keyAbsent = true;
                continue;
            }
            if ($v !== $ccResponseArray[$k]) {
                $valueMismatch = true;
                break;
            }
        }
        if ($keyAbsent || $valueMismatch) {
            $this->trace->info(TraceCode::CC_ROUTER_RESPONSE_MISMATCH, [
                'function' => $fqcn,
                'key_absent' => $keyAbsent,
                'value_mismatch' => $valueMismatch,
                'cc_response' => $ccResponseArray,
                'legacy_response' => $legacyResponseArray,
            ]);
            if ($keyAbsent) {
                $this->trace->count(Metric::CC_ROUTER_RESPONSE_MISMATCH, [
                    'function' => $fqcn,
                    'reason' => 'key_not_present',
                ]);
            }
            if ($valueMismatch) {
                $this->trace->count(Metric::CC_ROUTER_RESPONSE_MISMATCH, [
                    'function' => $fqcn,
                    'reason' => 'key_value_mismatch',
                ]);
            }
            return true;
        }
        return false;
    }

    private function compareSummaryResponse($ccResponse, $legacyResponse, $fqcn)
    {
        $ccResponseArray = $ccResponse->toArray();
        $legacyResponseArray = $legacyResponse->toArray();
        $ccResponsePlanIdToItemMap = array();
        $legacyResponsePlanIdToItemMap = array();
        foreach ($ccResponseArray as $ccSummaryItem) {
            $ccResponsePlanIdToItemMap[$ccSummaryItem['plan_id']] = $ccSummaryItem;
        }
        foreach ($legacyResponseArray as $legacySummaryItem) {
            $legacyResponsePlanIdToItemMap[$legacySummaryItem['plan_id']] = $legacySummaryItem;
        }
        foreach ($legacyResponsePlanIdToItemMap as $planId => $summaryItem) {
           if(!array_key_exists($planId, $ccResponsePlanIdToItemMap)) {
               $this->trace->info(TraceCode::CC_ROUTER_RESPONSE_MISMATCH, [
                   'function' => $fqcn,
                   'reason' => 'plan_id_not_present',
                   'cc_response' => $ccResponseArray,
                   'legacy_response' => $legacyResponseArray,
               ]);
               $this->trace->count(Metric::CC_ROUTER_RESPONSE_MISMATCH, [
                   'function' => $fqcn,
                   'reason' => 'plan_id_not_present',
               ]);
               return;
           }
           $ccSummaryItem = $ccResponsePlanIdToItemMap[$planId];
           if($summaryItem['plan_name'] != $ccSummaryItem['plan_name'] || $summaryItem['type'] != $ccSummaryItem['type']|| $summaryItem['rules_count'] != $ccSummaryItem['rules_count']) {
               $this->trace->info(TraceCode::CC_ROUTER_RESPONSE_MISMATCH, [
                   'function' => $fqcn,
                   'reason' => 'plan_id_name_mismatch',
                   'cc_response' => $ccResponseArray,
                   'legacy_response' => $legacyResponseArray,
               ]);
               $this->trace->count(Metric::CC_ROUTER_RESPONSE_MISMATCH, [
                   'function' => $fqcn,
                   'reason' => 'plan_id_name_mismatch',
               ]);
               return;
           }
        }
    }
}
