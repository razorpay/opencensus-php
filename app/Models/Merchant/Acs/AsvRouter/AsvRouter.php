<?php

namespace RZP\Models\Merchant\Acs\AsvRouter;

use App;
use phpDocumentor\Reflection\Types\Self_;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Models\Merchant\Detail\Repository as MerchantDetailRepository;
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

    const PARTNER_NOT_FOUND = "partner_not_found";

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
            $this->trace->count(Metric::ACCOUNT_SERVICE_CHECK_EXCLUSION_FLOW_RESULT, [
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
            $isExperimentRemoved = AsvMaps\RepoAndFunctionToSplitzMap::isExperimentRemoved($experimentName);
            if ($isExperimentRemoved === true){
                return true;
            }

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
            $isExperimentRemoved = AsvMaps\RepoAndFunctionToSplitzMap::isExperimentRemoved($experimentName);
            if ($isExperimentRemoved === true){
                return true;
            }

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
            $isExperimentRemoved = AsvMaps\RepoAndFunctionToSplitzMap::isExperimentRemoved($experimentName);
            if ($isExperimentRemoved === true){
                return true;
            }

            return $this->spitzHelper->isSplitzOnByExperimentName($experimentName, $id);
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION);
            return false;
        }
    }

    public function shouldRouteWriteRequestToAccountService($repoClass, $functionName, $id): bool
    {
        try {
            if ((new AsvMaps\WriteEnabledOnAsv)->checkIfWriteEnabled($repoClass, $functionName) === false) {
                return false;
            }

            $experimentName = AsvMaps\RepoAndFunctionToSplitzMap::getExperimentName($repoClass, $functionName);
            $routeOrWorkerName = $this->getRouteOrJobName();
            $isRequestRoutedToAsv = false;

            if((new AsvMaps\PartnershipFlows())->checkIfPartnerShipFlow($routeOrWorkerName) === true) {
                $partnerId = $this->getPartnerId();
                $isRequestRoutedToAsv = $this->spitzHelper->isSplitzOnForPartnershipWriteByExperimentName(
                    $experimentName,
                    $id,
                    $this->getRouteOrJobName(),
                    $partnerId,
                );
            }
            else {
                $isRequestRoutedToAsv = $this->spitzHelper->isSplitzOnForWriteByExperimentName(
                    $experimentName,
                    $id,
                    $this->getRouteOrJobName()
                );
            }


            $this->logAndReportMetrics($repoClass, $routeOrWorkerName, $isRequestRoutedToAsv, $functionName);

            return $isRequestRoutedToAsv;
        } catch (\Throwable $e) {
            $this->trace->count(Metric::ASV_WRITE_REQUEST_ROUTER_ERROR, [
                'error_code' => $e->getCode(),
            ]);

            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION);
            return false;
        }
    }

    public function shouldRouteImplicitJoinToAccountService($id, $entityName, $repoClass, $functionName): bool {
        try {
            $isExclusionFlow = $this->isExclusionFlowOrFailure();

            if ($isExclusionFlow === true) {
                return false;
            }

            $experimentName = AsvMaps\RepoAndFunctionToSplitzMap::getExperimentName($repoClass, $functionName);

            $resp =  $this->spitzHelper->isSplitzOnForFindForImplicitJoinByExperimentName(
                $experimentName,
                $id,
                $entityName
            );

            $this->trace->info(TraceCode::ASV_IMPLICIT_JOIN_ROUTER_RESULT, [
                'isImplicitJoinRoutedToASV' => $resp,
                'function_identifier' => $repoClass . '::' . $functionName,
            ]);

            return $resp;
        } catch (\Throwable $e) {
            $this->trace->traceException
            (
                $e,
                Trace::WARNING,
                TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION,
                [
                    'flow' => 'implicit_join'
                ]
            );
            return false;
        }
    }

    public function shouldRouteFindForImplicitJoinToAccountService($id, $entityName, $columns, $connectionType, $repoClass, $functionName): bool {
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

            return $this->shouldRouteImplicitJoinToAccountService($id, $entityName, $repoClass, $functionName);
        } catch (\Throwable $e) {
            $this->trace->traceException
            (
                $e,
                Trace::WARNING,
                TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION,
                [
                    'flow' => 'implicit_join'
                ]
            );
            return false;
        }
    }

    /**
     * @param $repoClass
     * @param string $routeOrWorkerName
     * @param bool $isRequestRoutedToAsv
     * @param $functionName
     * @return void
     */
    public function logAndReportMetrics($repoClass, string $routeOrWorkerName, bool $isRequestRoutedToAsv, $functionName): void
    {
        // Metric is temporary, will be removed/seperated to avoid highcardinality.
        // When We ramp up for all entities.
        // Why both metric/log?: It is hard to get insights from logs for over
        // 7 days, hence, also adding a metric.
        if ($repoClass != MerchantRepository::class and $repoClass != MerchantDetailRepository::class) {
            $this->trace->count(Metric::ASV_WRITE_REQUEST_ROUTER_RESULT, [
                'routeOrWorkerName' => $routeOrWorkerName,
                'isWriteRequestRouted' => $isRequestRoutedToAsv,
                'identifier' => $repoClass . '::' . $functionName,
            ]);
        } else {
            $this->trace->count(Metric::ASV_WRITE_MERCHANT_AND_MERCHANT_DETAIL_ROUTER_RESULT, [
                'routeOrWorkerName' => $routeOrWorkerName,
                'isWriteRequestRouted' => $isRequestRoutedToAsv,
                'identifier' => $repoClass . '::' . $functionName,
            ]);
        }

        $this->trace->info(TraceCode::ASV_WRITE_REQUEST_ROUTER_RESULT, [
            'routeOrWorkerName' => $routeOrWorkerName,
            'isWriteRequestRouted' => $isRequestRoutedToAsv,
            'function_identifier' => $repoClass . '::' . $functionName,
        ]);
    }

    /*
     *  Get partner id or default: PARTNER_NOT_FOUND if not present.
     */
    private function getPartnerId(): string
    {
        $partnerId = "";
        try {
           $partnerId = $this->app['basicauth']->getMerchantId();
        } catch (\Throwable $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_ERROR_FINDING_PARTNER_ID);
        }

        if ($partnerId === "" or $partnerId===null) {
            $partnerId = self::PARTNER_NOT_FOUND;
        }

        $this->trace->info(TraceCode::ASV_PARTNER_ID_FIND_RESULT, [
            'partner_id' => $partnerId,
        ]);

        return $partnerId;
    }

}
