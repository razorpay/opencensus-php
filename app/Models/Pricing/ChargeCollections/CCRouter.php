<?php

namespace RZP\Models\Pricing\ChargeCollections;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Models\Pricing\Plan;
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
        );

    private const FUNCTION_MAP = array(
        'RZP\\Models\\Pricing\\Service\\createPlan' => true,
    );

    public function __construct(bool $writes = false)
    {
        $app = App::getFacadeRoot();
        $this->app = $app;
        $this->trace = $app['trace'];
        $this->splitzExperimentID = "";


        if ($writes) {
            $this->splitzExperimentID = $app['config']->get('app.pricing_writes_experiment_id');
        }
    }

    public function route($fqcn, $ccRequest, $legacyCallable)
    {
        $rampPhase = $this->shouldRouteRequestToChargeCollections($fqcn, '');
        $methodName = Utils::extractMethodFromFunction($fqcn);

        if ($rampPhase == CCRouter::DISABLE || $rampPhase == CCRouter::SHADOW) {
            // Legacy request
            $legacyResponse = call_user_func($legacyCallable);

            if ($rampPhase == CCRouter::SHADOW) {
                $ccResponse = $this->sendChargeCollectionsRequest($fqcn, $ccRequest);
                $this->trace->info(TraceCode::CC_ROUTER_SERVICE_RESPONSE, [
                    'method' => $methodName,
                    'mode' => CCRouter::SHADOW,
                    'response' => $ccResponse,
                ]);
            }

            return $legacyResponse;

        } else if ($rampPhase == CCRouter::REVERSE_SHADOW || $rampPhase == CCRouter::ENABLE) {
            // Route to ChargeCollections
            $response = $this->sendChargeCollectionsRequest($fqcn, $ccRequest);

            if ($rampPhase == CCRouter::REVERSE_SHADOW) {
                try {
                    $legacyResponse = call_user_func($legacyCallable);
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

    private function sendChargeCollectionsRequest($fqcn, $input) {

        $routeName = null;

        try {
            $routeName = app('request.ctx')->getRoute() ?? null;
            $methodName = Utils::extractMethodFromFunction($fqcn);

            if ($methodName == 'createPlan'){
                $response = $this->app->charge_collections->createPricingPlan($input);
            }else{
                $this->trace->info(TraceCode::CC_ROUTER_EXCEPTION,
                    [
                        'Endpoint not found for method' => $methodName,
                        'function' => $fqcn,
                    ]);

                $this->monitorChargeCollectionsRequestNotRouted($routeName,$fqcn, self::TRANSFORMATION_NOT_FOUND);
                return null;
            }

            $this->trace->count(Metric::CC_REQUEST_ROUTED, [
                'route' => $routeName,
                'function' => $fqcn,
            ]);

            return $response;
        }catch (\Exception $e){
            $this->trace->traceException($e, Trace::WARNING, TraceCode::CC_ROUTER_EXCEPTION);
            $this->monitorChargeCollectionsRequestNotRouted($routeName, $fqcn ,self::EXCEPTION);
            return null;
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
            $routeName = app('request.ctx')->getRoute() ?? null;

            if($this->isRouteApplicableForDecomp($routeName) === false &&
                $this->isFunctionApplicableForDecomp($functionName) === false) {
                $this->monitorChargeCollectionsRequestNotRouted($routeName,$functionName,self::ROUTE_OR_FUNCTION_NOT_ONBOARDED);
                return self::DISABLE;
            }

            if ($this->isTransactionActive()) {
                $this->monitorChargeCollectionsRequestNotRouted($routeName,$functionName,self::REPO_TRANSACTION_ACTIVE);

                return self::DISABLE;
            }

            $experimentID = $this->splitzExperimentID;

            $result = $this->checkSplitzExperiment($planID, $experimentID);
            if($result[self::VALID] === false) {
                $this->monitorChargeCollectionsRequestNotRouted($routeName,$functionName,self::SPLITZ_RESPONSE_ERROR);
                return self::DISABLE;
            }

            return $result[self::VARIANT];
        }catch (\Exception $e){
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
        } catch (\Exception $e) {
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
}
