<?php

namespace RZP\Models\Merchant\Acs\AsvRouter;

use App;
use phpDocumentor\Reflection\Types\Self_;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Constants\Metric;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\MerchantExclusionFlows;


/*
 *
 * The following class aims to make the decision whether the request should be routed to the account service.
 * The decision is to be taken on various factors like static mapping data and Spltiz experiments.
 */

class AsvRouter
{

    const None = "none";

    protected $app;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    protected SplitzHelper $spitzHelper;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app[Constant::TRACE];

        $this->spitzHelper = new SplitzHelper();
    }

    public function isExclusionFlowOrFailure(): bool
    {
        try {
            $routeOrWorkerName = $this->getRouteOrJobName();

            if ($routeOrWorkerName === self::None) {
                // if we get a none route, we should let the request go to the database
                // Since, it is possible there was some exception, or we are not able to extract out the
                // route name correctly.
                return true;
            }


            $isExclusionFlow = MerchantExclusionFlows::isExclusionFLow($routeOrWorkerName);

            // temporarily added this log if the check is working correctly.
            // we need to remove this log before we ramp up for high RPS entites.
            $this->trace->info(TraceCode::ACCOUNT_SERVICE_CHECK_EXCLUSION_FLOW_RESULT, [
                'routeOrWorkerName' => $routeOrWorkerName,
                'isExclusionFlow' => $isExclusionFlow
            ]);

            return $isExclusionFlow;
        } catch (\Exception $e) {

            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_CHECK_EXCLUSION_FLOW_EXCEPTION);

            // if we are getting and exception here, we should
            // block the request and let it go to the database
            return true;
        }
    }

    public function getRouteOrJobName()
    {
        try {
            $runningInQueue = app()->runningInQueue();
            if ($runningInQueue === true) {
                $flow = app('worker.ctx')->getJobName();
            } else {
                $flow = app('request.ctx')->getRoute();
            }

            if ($flow === null or $flow === "") {
                $this->trace->info(TraceCode::ACCOUNT_SERVICE_FLOW_IS_NONE);
                return self::None;
            }

            return $flow;

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_GET_ROUTE_OR_WORKER_NAME_EXCEPTION);
            return self::None;
        }
    }

    //TODO: remove shouldCallAccountService and use  shouldRouteFindToAccountService wherever shouldCallAccountService is used

    /**
     *
     * Check if the call should go to account service
     * Future scope to add specific mapping if calling function supports specific feature.
     * For example: Possible that Merchant Website find supports various connection type, etc.
     * Or, we support the functionality only on specific routes or jobs and not on others.
     *
     * @param string $experimentName
     * @param $id
     * @param $columns
     * @param $connectionType
     * @param $functionIdentifier
     * @return bool
     */
    function shouldCallAccountService($id, $columns, $connectionType, $repoClass, $functionName): bool
    {

        try {
            if ($connectionType != null || $columns != array("*") || !is_string($id)) {
                $this->trace->info(TraceCode::ACCOUNT_SERVICE_DO_NOT_ROUTE_REQUEST, [
                    "connection_type" => $connectionType,
                    "function_identifier" => $repoClass . "::" . $functionName,
                    "columns" => $columns,
                    "id" => $id
                ]);

                $this->trace->count(Metric::ASV_REQUEST_NOT_ROUTED_TO_ASV, [
                    "function_identifier" => $repoClass . "::" . $functionName,
                ]);
                return false;
            }

            $experimentName = AsvMaps\RepoAndFunctionToSplitzMap::getExperimentName($repoClass, $functionName);

            return $this->spitzHelper->isSplitzOnByExperimentName($experimentName, $id);
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION);
            return false;
        }
    }

    /**
     *
     * Check if the call should go to account service
     * Future scope to add specific mapping if calling function supports specific feature.
     * For example: Possible that Merchant Website find supports various connection type, etc.
     * Or, we support the functionality only on specific routes or jobs and not on others.
     *
     * @param string $experimentName
     * @param $id
     * @param $columns
     * @param $connectionType
     * @param $functionIdentifier
     * @return bool
     */
    function shouldRouteFindToAccountService($id, $columns, $connectionType, $repoClass, $functionName): bool
    {

        try {
            if ($connectionType != null || $columns != array("*") || !is_string($id)) {
                $this->trace->info(TraceCode::ACCOUNT_SERVICE_DO_NOT_ROUTE_REQUEST, [
                    "connection_type" => $connectionType,
                    "function_identifier" => $repoClass . "::" . $functionName,
                    "columns" => $columns,
                    "id" => $id
                ]);

                $this->trace->count(Metric::ASV_REQUEST_NOT_ROUTED_TO_ASV, [
                    "function_identifier" => $repoClass . "::" . $functionName,
                ]);
                return false;
            }

            $isExclusionFlow = $this->isExclusionFlowOrFailure();

            if ($isExclusionFlow === true) {
                return false;
            }

            $experimentName = AsvMaps\RepoAndFunctionToSplitzMap::getExperimentName($repoClass, $functionName);

            return $this->spitzHelper->isSplitzOnByExperimentName($experimentName, $id);
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION);
            return false;
        }
    }

    function shouldRouteToAccountService($id, $repoClass, $functionName): bool
    {
        try {
            $isExclusionFlow = $this->isExclusionFlowOrFailure();

            if ($isExclusionFlow === true) {
                return false;
            }

            $experimentName = AsvMaps\RepoAndFunctionToSplitzMap::getExperimentName($repoClass, $functionName);
            return $this->spitzHelper->isSplitzOnByExperimentName($experimentName, $id);
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION);
            return false;
        }
    }
}
