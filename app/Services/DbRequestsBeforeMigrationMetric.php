<?php

namespace RZP\Services;

use RZP\Constants\Metric as MetricConstants;
use RZP\Trace\TraceCode;


class DbRequestsBeforeMigrationMetric
{
    protected $app;

    protected $metrics;

    protected $trace;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $this->app['trace'];

        $this->metrics = [];

    }

    public function setMetric($route, $table, $action)
    {
        if (isset($this->metrics[$route][$table][$action]) === false)
        {
            $this->metrics = array_merge_recursive($this->metrics, [
                $route => [
                    $table => [
                        $action => 1
                    ],
                ]
            ]);
        }
        else
        {
            $this->metrics[$route][$table][$action]++;
        }
    }

    public function __destruct()
    {
        foreach($this->metrics as $route => $routeVal)
        {
            foreach($routeVal as $table => $tableVal)
            {
                foreach($tableVal as $action => $count)
                {
                    $dimensions = [
                        MetricConstants::LABEL_ROUTE         => $route,
                        MetricConstants::LABEL_TABLE_NAME    => $table,
                        MetricConstants::LABEL_ACTION        => $action,
                    ];

                    $this->trace->count(MetricConstants::DB_REQUESTS_BEFORE_MIGRATION, $dimensions, $count);

                    $this->trace->info(
                        TraceCode::TRACE_DB_MIGRATION_METRIC,
                        [
                            'route' => $route,
                            'table' => $table,
                            'action'=> $action,
                            'count' => $count
                        ]);
                }
            }
        }
    }
}
