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
        'RZP\\Models\\Pricing\\Repository\\getPlan' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlanByIdAndOrgId' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlanByIdWithProductAndFeatureFilter' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingRulesByPlanIdProductAndFeatureWithoutOrgId' => true,
        'RZP\\Models\\Pricing\\Repository\\getBankingSharedAccountNonFreePayouDefaultPricingRules' => true,
        'RZP\\Models\\Pricing\\Repository\\getPlanByName' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingRuleByMultipleParams' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingRulesByPlanIdProductFeaturePaymentMethodOrgId' => true,
        'RZP\\Models\\Pricing\\Repository\\getPricingPlansSummary' => true,
    );

    private array $FunctionToCCRouteMap;

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
        $this->FunctionToCCRouteMap = array(
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
        );
    }

    public function route($fqcn, $ccRequest, $legacyCallable, $sourceInput = null, $buyPricing = false)
    {
        $planId = $ccRequest['plan_id'] ?? $ccRequest['id'];
        if($planId == null) $planId = '';
        $rampPhase = $this->shouldRouteRequestToChargeCollections($fqcn, $planId);
        $methodName = Utils::extractMethodFromFunction($fqcn);

        // Modify the legacyCallable to pass rampPhase only if the legacy method accepts it
        $legacyCallableWithPhase = $this->getLegacyCallableBasedOnParameterCount($legacyCallable, $rampPhase, $sourceInput);

        // skip decomp for buy pricing
        if ($buyPricing){
            return call_user_func($legacyCallableWithPhase);
        }

        if ($rampPhase == CCRouter::DISABLE || $rampPhase == CCRouter::SHADOW) {
            // Legacy request
            $legacyResponse = call_user_func($legacyCallableWithPhase);

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
                    $legacyResponse = call_user_func($legacyCallableWithPhase);
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

            if ($methodName == 'createPlan' || $methodName == 'updatePlanRule'){
                $this->trace->count(Metric::CC_REQUEST_ROUTED, [
                    'route' => $routeName,
                    'function' => $fqcn,
                ]);
            }

            if ($this->FunctionToCCRouteMap[$fqcn] == ChargeCollections::GetPricingPlansSummaryURL) {
                $response = $this->app->charge_collections->getPricingPlansSummary($input);
                return $this->transformSummaryResponse($response);
            }

            if ($this->FunctionToCCRouteMap[$fqcn] == ChargeCollections::GetPricingPlanURL) {
                $response = $this->app->charge_collections->getPricingPlan($input);
                return $this->transformToPlanModel($response);
            }

            if ($methodName == 'createPlan'){
                $response = $this->app->charge_collections->createPricingPlan($input, $headers);
            }else if ($methodName == 'updatePlanRule'){
                $response = $this->app->charge_collections->updatePricingPlanRule($input, $headers);
            }else if ($methodName == 'deletePlanRuleForce'){
                $response = $this->app->charge_collections->deletePricingPlanRule($input);
            }else if ($methodName == 'postAddBulkPricingRules'){
                $response = $this->app->charge_collections->addBulkPricingPlanRule($input, $headers);
            }else if($methodName == 'addPlanRule'){
                $response = $this->app->charge_collections->addPricingPlanRule($input);
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

            // skip transaction active check for workflow checker route
            if ($this->isTransactionActive() && $routeName != 'action_checker_create') {
                $this->monitorChargeCollectionsRequestNotRouted($routeName,$functionName,self::REPO_TRANSACTION_ACTIVE);

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

    private function transformToPlanModel($response)
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
}
