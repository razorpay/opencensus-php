<?php

namespace RZP\Modules\Acs\Wrapper;

use App;
use RZP\Base\RepositoryManager;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Base
{
    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    protected $splitzService;

    protected $asvMigrationConfig;

    protected $defaultMigrationConfig = [
        'write' => [
            'shadow' => [
                'enabled' => false,
                'full_enabled' => false,
                'splitz_experiment_id' => '',
            ],
            'reverse_shadow' => [
                'enabled' => false,
                'full_enabled' => false,
                'splitz_experiment_id' => '',
            ]
        ],
        'read' => [
            'shadow' => [
                'enabled' => false,
                'full_enabled' => false,
                'splitz_experiment_id' => '',
            ],
            'reverse_shadow' => [
                'enabled' => false,
                'full_enabled' => false,
                'splitz_experiment_id' => ''
            ]
        ]
    ];

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->repo = $this->app[Constant::REPO];

        $this->trace = $app[Constant::TRACE];

        $this->splitzService = $this->app[Constant::SPLITZ_SERVICE];

        $this->asvMigrationConfig = $app[Constant::CONFIG][Constant::ASV_MIGRATION];
    }

    /**
     * @returns string - the Route Or JobName from context based on Sync or Async flow
     */
    public function getRouteOrJobName()
    {
        $routeOrJobName = '';
        $runningInQueue = app()->runningInQueue();
        if ($runningInQueue === true) {
            $routeOrJobName = app('worker.ctx')->getJobName();
        } else {
            $routeOrJobName = app('request.ctx')->getRoute();
        }

        return $routeOrJobName;
    }

    /**
     * @param string $id
     * @param string $mode - whether shadow or reverse shadow {shadow, reverse_shadow}
     * @param string $operation - read or write
     * @return bool - returns true if  Shadow or Reverse Shadow is enabled for given route or job name for given operation
     */
    public function isShadowOrReverseShadowOnForOperation(string $id, string $mode, string $operation)
    {
        $routeOrJobName = $this->getRouteOrJobName();
        $metadata = [Constant::ROUTE_OR_JOB_NAME => $routeOrJobName, Constant::OPERATION => $operation, Constant::MODE => $mode];
        $asvMigrationConfigForAllRouteOrJobName = $this->asvMigrationConfig[Constant::ALL_ROUTE_OR_JOB] ?? $this->defaultMigrationConfig;

        // Check whether enabled flag is true for All Route Or Job Name Config, If true evaluate based on All Route Or Job Config
        $asvMigrationConfigForAllRouteOrJobName = $asvMigrationConfigForAllRouteOrJobName[$operation][$mode] ?? [];
        $metadata[Constant::MIGRATION_CONFIG] = $asvMigrationConfigForAllRouteOrJobName;

        $allRouteOrFlowEnabled = $asvMigrationConfigForAllRouteOrJobName[Constant::ENABLED] ?? false;
        if ($allRouteOrFlowEnabled === true) {
            return $this->evaluateConfig($id, $asvMigrationConfigForAllRouteOrJobName, $metadata);
        }

        // Fallback to specific Route Level evaluation of Migration of Operation as All Route Or Job Migration is not enabled yet
        $asvMigrationConfigForRouteOrJobName = $this->asvMigrationConfig[$routeOrJobName] ?? $this->defaultMigrationConfig;
        $asvMigrationConfigForRouteOrJobName = $asvMigrationConfigForRouteOrJobName[$operation][$mode] ?? [];
        $metadata[Constant::MIGRATION_CONFIG] = $asvMigrationConfigForRouteOrJobName;

        return $this->evaluateConfig($id, $asvMigrationConfigForRouteOrJobName, $metadata);
    }

    /**
     * @param string $id
     * @param array $asvOperationMigrationConfig - array with keys ['enabled' => bool, 'full_enabled' => bool, 'splitz_experiment_id' => string]
     * @param array $metadata
     * @return bool
     */
    public function evaluateConfig(string $id, array $asvOperationMigrationConfig, $metadata = [])
    {
        $enabled = $asvOperationMigrationConfig[Constant::ENABLED] ?? false;
        $fullEnabled = $asvOperationMigrationConfig[Constant::FULL_ENABLED] ?? false;

        if ($enabled === false) {
            return false;
        }

        if ($enabled === true && $fullEnabled === true) {
            return true;
        }

        $splitzExperimentId = $asvOperationMigrationConfig[Constant::SPLITZ_EXPERIMENT_ID] ?? '';
        if ($splitzExperimentId === '') {
            return false;
        }

        return $this->isSplitzOn($splitzExperimentId, $id, $metadata);
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

            $this->trace->info(TraceCode::ASV_MIGRATION_SPLITZ_REQUEST, ['request' => $request, 'metadata' => $metadata]);
            $response = $this->splitzService->evaluateRequest($request);
            $this->trace->info(TraceCode::ASV_MIGRATION_SPLITZ_RESPONSE, $response);

            if ($response['status_code'] !== 200) {
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
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_MIGRATION_SPLITZ_ERROR);

            return false;
        }
    }
}
