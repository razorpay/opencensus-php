<?php

namespace RZP\Models\Merchant\Acs\SplitzHelper;

use App;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\SplitzConstant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Modules\Acs\Wrapper\Constant;

class SplitzHelper
{
    protected $app;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    protected $resultMap;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app[Constant::TRACE];

        $this->splitzService = $this->app[Constant::SPLITZ_SERVICE];

        $this->resultMap = array();
    }

    function isSplitzOnForWriteByExperimentName(
        string $experimentName,
        string $identifier,
        string $routeOrWorker,
        array $metadata = []): bool {
        try {
            $experimentIdForEntity = $this->app->config->get(ASVV2Constant::ASV_CONFIG)[$experimentName];
            $experimentIdForRoute = $this->app->config->get(ASVV2Constant::ASV_CONFIG)[SplitzConstant::SPLITZ_SEND_WRITE_ROUTE_OR_WORKER_TO_ASV];


            return $this->isSplitzOnBulk(
                [
                    [
                    "experiment_id" => $experimentIdForEntity, "id" => $identifier,
                    ],
                    [
                    "experiment_id" => $experimentIdForRoute, "id" => $routeOrWorker,
                    ]
                ],
                $metadata
            );
        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::ACCOUNT_SERVICE_SPLITZ_EXCEPTION, [
                "splitz_call_exception" => $e->getMessage(),
                "experiment_name" => $experimentName,
                "identifier" => $identifier,
                "route" => $routeOrWorker,
            ]);
            return false;
        }
    }

    function isSplitzOnByExperimentName(string $experimentName, string $identifier): bool {
        try {
            $experimentId = $this->app->config->get(ASVV2Constant::ASV_CONFIG)[$experimentName];
            $result =  $this->isSplitzOn($experimentId, $identifier);
            return $result;
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::ACCOUNT_SERVICE_SPLITZ_EXCEPTION, [
                "splitz_call_exception" => $e->getMessage(),
                "experiment_name" => $experimentName,
                "identifier" => $identifier,
            ]);
            return false;
        }
    }
    /**
     * @param string $experimentId
     * @param string $id
     * @param array $metadata
     * @return bool
     */
    public function isSplitzOn(string $experimentId, string $id, array $metadata = []): bool
    {
        try {
            $request = ['id' => $id, 'experiment_id' => $experimentId];

            $response = $this->splitzService->evaluateRequest($request);

            if ($response['status_code'] !== 200) {
                $this->trace->info(TraceCode::ASV_SPLITZ_RESPONSE_ERROR, ['metadata'=> $metadata, 'response' => $response]);
                return false;
            }

            $variant = $response['response']['variant'] ?? [];

            $variables = $variant['variables'] ?? [];

            foreach ($variables as $variable) {
                $key = $variable['key'] ?? '';
                $value = $variable['value'] ?? '';

                if ($key === 'enabled' && $value === 'true') {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_SPLITZ_ERROR);

            return false;
        }
    }

    /**
     * @param string $experimentId
     * @param string $id
     * @param array $metadata
     * @return bool
     */
    public function isSplitzOnBulk(array $request , array $metadata = []): bool
    {
        try {

            $totalExperiments = count($request);
            if ($totalExperiments === 0) {
                return false;
            }

            $totalExperimentsEnabledTrue = 0;

            $response = $this->splitzService->bulkCallsToSplitz($request);

            for ($i = 0; $i < count($response); $i++) {
                $variant = $response[$i]['variant'] ?? [];

                $variables = $variant['variables'] ?? [];

                foreach ($variables as $variable) {
                    $key = $variable['key'] ?? '';
                    $value = $variable['value'] ?? '';

                    if ($key === 'enabled') {
                        if ($value === 'true') {
                            $totalExperimentsEnabledTrue++;
                            continue;
                        }
                        return false;
                    }
                }
            }


            return $totalExperimentsEnabledTrue === $totalExperiments;
        } catch (\Throwable $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_SPLITZ_ERROR);

            return false;
        }
    }

    function isSplitzOnForFindForImplicitJoinByExperimentName(
        string $experimentName,
        string $identifier,
        string $callingEntity,
        array $metadata = []): bool {
        try {
            $experimentIdForEntity = $this->app->config->get(ASVV2Constant::ASV_CONFIG)[$experimentName];
            $experimentIdForCallingEntity = $this->app->config->get(ASVV2Constant::ASV_CONFIG)[SplitzConstant::SPLITZ_IMPLICIT_JOIN_ENTITY];

            return $this->isSplitzOnBulk(
                [
                    [
                        "experiment_id" => $experimentIdForEntity, "id" => $identifier,
                    ],
                    [
                        "experiment_id" => $experimentIdForCallingEntity, "id" => $callingEntity,
                    ]
                ],
                $metadata
            );
        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::ACCOUNT_SERVICE_SPLITZ_EXCEPTION, [
                "splitz_call_exception" => $e->getMessage(),
                "experiment_name" => $experimentName,
                "identifier" => $identifier,
                "calling_entity" => $callingEntity,
            ]);
            return false;
        }
    }

    function checkSplitzVariantForAsvTiDBMigration(string $functionName) : bool {
        try
        {
            $request = ['id' => $functionName,
                        'experiment_id' => $this->app->config->get(ASVV2Constant::ASV_CONFIG)[SplitzConstant::ASV_SPLITZ_EXPERIMENT_TIDB_MIGRATION]];

            $response = $this->splitzService->evaluateRequest($request);

            $variant = $response['response']['variant']['name'] ?? null;

            if ($variant === 'enable')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_SPLITZ_ERROR);

            return false;
        }

        return false;
    }

    function checkSplitzValueForAsvTiDBComparison() : bool {
        try
        {
            $request = ['id' => 'constant',
                        'experiment_id' => $this->app->config->get(ASVV2Constant::ASV_CONFIG)[SplitzConstant::ASV_SPLITZ_EXPERIMENT_TIDB_COMPARISON]];

            $response = $this->splitzService->evaluateRequest($request);

            $variant = $response['response']['variant']['name'] ?? null;

            if ($variant === 'enable')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_SPLITZ_ERROR);

            return false;
        }

        return false;
    }
}

