<?php

namespace RZP\Modules\Acs;

use RZP\Constants\Environment;
use Illuminate\Support\Facades\DB;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Contracts\Queue\ShouldQueue;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;

class QueryShadowModeListener implements ShouldQueue
{

    var array $COMPARE_PROPERTIES_MAP = [
        'fetchMerchantIdsByActivationStatus' => ['merchant_id'],
        'findMerchantByActivationStatusAndActivationFormMileStone' => ['merchant_id'],
        'getFailedSettlementsForRetry' => ['merchant_id','id'],
        'fetchTransactedMerchants' => ['merchant_id'],
        'findMerchantWithContactNumbersExcludingMerchant' => ['merchant_id']
    ];

    var array $COMPARE_LENGTH_MAP = [
        'fetchMerchantIdsByActivationStatus',
        'findMerchantByActivationStatusAndActivationFormMileStone',
        'getFailedSettlementsForRetry',
        'fetchTransactedMerchants',
        'findMerchantWithContactNumbersExcludingMerchant'
    ];

    public $app;
    public $trace;

    public function __construct()
    {
        $this->app    = \App::getFacadeRoot();
        $this->trace  = $this->app['trace'];
    }

    public function handle(QueryShadowModeEvent $event)
    {
        if ((new AsvRouter())->shouldShadowCompareTiDBResults())
        {
            try {
                $queryString = $event->queryString;

                $appMode = $this->app['rzp.mode'] ?? 'test';

                // Execute query in API DB
                $liveDbResults = DB::connection($appMode)->select(DB::raw($queryString), $event->queryBindings);

                // Execute query in TiDB
                $tiDbConnection = $this->getTiDBConnection($appMode);
                $tiDbResults = DB::connection($tiDbConnection)->select(DB::raw($queryString), $event->queryBindings);

                $diffFound = false;

                // Compare results

                if (in_array($event->functionName, $this->COMPARE_LENGTH_MAP)) {
                    $diffFound = $this->compareLength($liveDbResults, $tiDbResults);
                }

                if (!$diffFound and in_array($event->functionName, array_keys($this->COMPARE_PROPERTIES_MAP))) {
                    $diffFound = $this->compareKeys($liveDbResults, $tiDbResults, $event->functionName);
                }

                $this->trace->debug(TraceCode::ASV_TIDB_MIGRATION_DEBUG, [
                    'function'                          => $event->functionName,
                    'query'                             => $event->queryString,
                    'connectionUsedForDataWarehouse'    => $tiDbConnection,
                    'diffFound'                         => $diffFound
                ]);

                //Publish metrics and log error
                if ($diffFound) {
                    $this->trace->error(TraceCode::ASV_TIDB_MIGRATION_SHADOW_MODE_MISMATCH, [
                        'function'          => $event->functionName,
                        'query'             => $event->queryString,
                        'liveDbResults'     => $liveDbResults,
                        'tidbResults'       => $tiDbResults
                    ]);

                    $this->trace->count(Metric::ASV_TIDB_MIGRATION_SHADOW_MODE_DIFF_TOTAL, [
                        'function' => $event->functionName
                    ]);
                }
            } catch (\Throwable $e) {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::ASV_TIDB_MIGRATION_ERROR,
                    [
                        'function'          => $event->functionName,
                        'query'             => $event->queryString
                    ]
                );
            }
        }
    }

    private function compareKeys($liveDbResults, $tiDbResults, $functionName)
    {
        $diff = false;
        foreach ($this->COMPARE_PROPERTIES_MAP[$functionName] as $key)
        {
            $liveDbArray = [];
            $tidbArray = [];
            foreach ($liveDbResults as $row)
            {
                $liveDbArray[] = json_decode(json_encode((array)$row), true)[$key];
            }
            foreach ($tiDbResults as $row)
            {
                $tidbArray[] = json_decode(json_encode((array)$row), true)[$key];
            }
            $diff = !empty(array_diff($liveDbArray, $tidbArray));
        }
        return $diff;
    }

    private function compareLength($liveDbResults, $tiDBResults)
    {
        return count($liveDbResults) != count($tiDBResults);
    }

    private function getTiDBConnection($appMode) {
        if ((in_array($this->app['env'], [Environment::TESTING, Environment::TESTING_DOCKER], true) === true) or
            (Environment::isEnvironmentQA($this->app['env']) === true) or
            ($this->app->runningUnitTests() === true)) {
            return $appMode;
        }
        return 'data-warehouse-merchant-'.$appMode;
    }
}