<?php

namespace RZP\Models\Merchant\Acs\AsvRouter;

use App;
use phpDocumentor\Reflection\Types\Self_;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Models\Merchant\Detail\Repository as MerchantDetailRepository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Constants\Metric;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\AsvFlows;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as AsvSdkIntegrationConstant;


/*
 *
 * The following class aims to make the decision whether the request should be routed to the account service.
 * The decision is to be taken on various factors like static mapping data and Spltiz experiments.
 */

class AsvRouter
{

    const None = "none";

    const PARTNER_NOT_FOUND = "partner_not_found";

    const CREATE_TRANSACTION_WITH_ASV_ALSO = "create_transaction_with_asv_also";

    const REPOSITORY_MANAGER_ID  = "repository_manager";

    protected $app;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    protected SplitzHelper $splitzHelper;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app[Constant::TRACE];

        $this->splitzHelper = new SplitzHelper();
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


            $isExclusionFlow = AsvFlows::isExclusionFLow($routeOrWorkerName);

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

    public function isWriteFlowOrFailure(): bool
    {
        try {
            $routeOrWorkerName = $this->getRouteOrJobName();
            if ($routeOrWorkerName === self::None) {
                // if we get a none route, we should let the request go to the database
                // Since, it is possible there was some exception, or we are not able to extract out the
                // route name correctly.
                return false;
            }


            $isWriteFLow = AsvFlows::isWriteFLow($routeOrWorkerName);

            // temporarily added this log if the check is working correctly.
            $this->trace->count(Metric::ACCOUNT_SERVICE_CHECK_WRITE_FLOW_RESULT, [
                'routeOrWorkerName' => $routeOrWorkerName,
                'isWriteFlow' => $isWriteFLow
            ]);

            return $isWriteFLow;
        } catch (\Exception $e) {

            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_CHECK_WRITE_FLOW_EXCEPTION);

            // if we are getting and exception here, we should
            // block the request and let it go to the api
            return false;
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

    function logAndTrackRequestNotRoutedToAsv($id, $columns, $connectionType, $repoClass, $functionName, $flow): void
    {
        $this->trace->info(TraceCode::ACCOUNT_SERVICE_DO_NOT_ROUTE_REQUEST, [
            "flow" => $flow,
            "route_or_job_name" => $this->getRouteOrJobName(),
            "connection_type" => $connectionType,
            "function_identifier" => $repoClass . "::" . $functionName,
            "columns" => $columns,
            "id" => $id
        ]);

        $this->trace->count(Metric::ASV_REQUEST_NOT_ROUTED_TO_ASV, [
            "flow" => $flow,
            "route_or_job_name" => $this->getRouteOrJobName(),
            "connection_type" => $connectionType!==null ? $connectionType: "none",
            "function_identifier" => $repoClass . "::" . $functionName,
            "id_str" => is_string($id) ? "true" : "false",
            "partial_columns" => $columns != array("*") ? "true": "false",
        ]);
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
                $this->logAndTrackRequestNotRoutedToAsv($id, $columns, $connectionType, $repoClass, $functionName, "normal");
                return false;
            }

            $experimentName = AsvMaps\RepoAndFunctionToSplitzMap::getExperimentName($repoClass, $functionName);
            $isExperimentRemoved = AsvMaps\RepoAndFunctionToSplitzMap::isExperimentRemoved($experimentName);
            if ($isExperimentRemoved === true){
                return true;
            }

            return $this->splitzHelper->isSplitzOnByExperimentName($experimentName, $id);
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
                $this->logAndTrackRequestNotRoutedToAsv($id, $columns, $connectionType, $repoClass, $functionName, "normal");
                return false;
            }

            return $this->shouldRouteToAccountService($id, $repoClass, $functionName);
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION);
            return false;
        }
    }

    function shouldRouteToAccountService($id, $repoClass, $functionName): bool
    {
        try {
            if ($this->isWriteFlowOrFailure() === true) {
                if ($this->isTransactionActive($repoClass) === true) {
                    return false;
                }

                return $this->shouldRouteWriteRequestToAccountService($repoClass, $functionName, $id);
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

            return $this->splitzHelper->isSplitzOnByExperimentName($experimentName, $id);
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION);
            return false;
        }
    }

    public function shouldRouteWriteRequestToAccountService($repoClass, $functionName, $id): bool
    {
        try {
            if ($this->isWriteFlowOrFailure() === false) {
                return false;
            }

            $experimentName       = AsvMaps\RepoAndFunctionToSplitzMap::getExperimentNameForWriteMigration();
            $routeOrWorkerName    = $this->getRouteOrJobName();
            $isRequestRoutedToAsv = $this->splitzHelper->isSplitzOnForWriteByExperimentName(
                $experimentName,
                $id,
                $routeOrWorkerName,
            );


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

            if ($this->isWriteFlowOrFailure() === true) {

                if ($this->isTransactionActive($repoClass) === true) {
                    return false;
                }

                return $this->shouldRouteWriteRequestToAccountService($repoClass, $functionName, $id);
            }

            $isExclusionFlow = $this->isExclusionFlowOrFailure();

            if ($isExclusionFlow === true) {
                return false;
            }

            $experimentName = AsvMaps\RepoAndFunctionToSplitzMap::getExperimentName($repoClass, $functionName);

            $resp =  $this->splitzHelper->isSplitzOnForFindForImplicitJoinByExperimentName(
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
                $this->logAndTrackRequestNotRoutedToAsv($id, $columns, $connectionType, $repoClass, $functionName, "implicit");
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

    public function shouldRouteFilterToAsv(string $callingIdentifier): bool
    {
        try {

            $isExclusionFlow = $this->isExclusionFlowOrFailure();

            if ($isExclusionFlow === true) {
                return false;
            }

            $experimentName = AsvMaps\RepoAndFunctionToSplitzMap::getExperimentNameForFilterMigration();

            return $this->splitzHelper->isSplitzOnByExperimentName($experimentName, $callingIdentifier);
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ACCOUNT_SERVICE_ROUTER_EXCEPTION);
            return false;
        }
    }

    public function shouldCreateTransactionWithAsvAlso(): bool
    {
        return $this->shouldRouteWriteRequestToAccountService(
            RepositoryManager::class,
            self::CREATE_TRANSACTION_WITH_ASV_ALSO,
            self::REPOSITORY_MANAGER_ID
        );
    }

    public function isTransactionActive($repoClass): bool {

        $repoClass = (new $repoClass());
        if (property_exists($repoClass, 'repo') === false) {
            $this->trace->info(TraceCode::ASV_ROUTER_REPO_NOT_FOUND);
            return true;
        }

        if ($repoClass->repo->isTransactionActive() === true) {
            return true;
        }

        return false;
    }

    public function shouldRouteBeMigratedToTiDB(string $functionName) : bool {
        $result = $this->splitzHelper->checkSplitzVariantForAsvTiDBMigration($functionName);

        $this->trace->info(TraceCode::ASV_TIDB_MIGRATION_DEBUG, [
            'shouldRouteBeMigratedToTiDB' => $result,
        ]);

        return $result;
    }

    public function shouldShadowCompareTiDBResults() : bool {
        return $this->splitzHelper->checkSplitzValueForAsvTiDBComparison();
    }

}
