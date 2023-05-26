<?php

namespace RZP\Models\Merchant\Acs\AsvRouter;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Trace\TraceCode;

/*
 *
 * The following class aims to make the decision whether the request should be routed to the account service.
 * The decision is to be taken on various factors like static mapping data and Spltiz experiments.
 */
class AsvRouter {

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
    function shouldCallAccountService($id, $columns, $connectionType, $repoClass, $functionName): bool {

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


}
