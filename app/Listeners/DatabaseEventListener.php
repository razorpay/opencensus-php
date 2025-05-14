<?php

namespace RZP\Listeners;

use App;

use Database\Connection;
use RZP\Base\ConnectionType;
use RZP\Base\Database\Metric;
use RZP\Trace\TraceCode;
use Illuminate\Database\Events\QueryExecuted;

class DatabaseEventListener
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Trace
     */
    protected $trace;

    const ASV_TABLES = [
        "merchants",
        "merchant_details",
        "merchant_business_details",
        "merchant_website",
        "stakeholders",
        "merchant_documents",
        "merchant_emails",
    ];

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->sampleRate = $this->app['config']->get('database.db_mysql_query_sampling_rate');
    }

    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(QueryExecuted $event)
    {

        $result = $this->extractTableNamesAndOperationsFromSQL($event->sql);

        try {
            foreach ($result['table_operations'] as $operationArray) {
                foreach ($operationArray as $table => $operation) {
                    $this->trace->count(Metric::DATABASE_QUERY_BINDING, [
                        'route' => $this->app['request.ctx']->getRoute() ?? $this->app['worker.ctx']->getJobName(),
                        'connection' => $event->connectionName,
                        'tableName' => $table,
                    ]);

                    $this->trace->count(Metric::TABLE_OPERATION_QUERY_BINDING, [
                        'connection' => $event->connectionName,
                        'tableName' => $table,
                        'operation' => $operation
                    ]);

                    if (in_array($table, self::ASV_TABLES, true) === true &&
                        in_array($event->connectionName, Connection::ASV_ROUTEING_CONNECTIONS, true) === false){

                        $this->trace->count(Metric::ASV_DATABASE_QUERY_BINDING, [
                            'route' => $this->app['request.ctx']->getRoute() ?? $this->app['worker.ctx']->getJobName(),
                            'connection' => $event->connectionName,
                            'tableName' => $table,
                            'operation' => $operation
                        ]);

                        $this->trace->info(
                            TraceCode::DB_QUERY_FOR_ASV_TABLES_EXECUTION_LOG,
                            [
                                'route' => $this->app['request.ctx']->getRoute() ?? $this->app['worker.ctx']->getJobName(),
                                'connection' => $event->connectionName,
                                'table' => $table,
                                'operation' => $operation,
                                'query' => $event->sql,
                                'time' => $event->time,
                            ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // silent ignore for now
        }

        $rand = rand(1, 500000);

        if ($rand > $this->sampleRate) {
            return;
        }

        try {
            $this->trace->info(
                TraceCode::DB_QUERY_EXECUTION_LOG,
                [
                    'application' => 'api',
                    'route' => $this->app['request.ctx']->getRoute() ?? $this->app['worker.ctx']->getJobName(),
                    'connection' => $event->connectionName,
                    'query' => $event->sql,
                    'bindings' => $event->bindings,
                    'time' => $event->time,
                    'tables' => $result['table_operations'] ?? [],
                ]);
        } catch (\Throwable $e) {
            // silent ignore for now
        }
    }

    // Function to extract table name from SQL query
    protected function extractTableNameFromSQL($sql)
    {

        $table = "";

        preg_match('/from\s+([^\s]+)/i', $sql, $matches);

        if (count($matches) > 0) {
            $table = trim($matches[1] ?? '', '`');

            // Remove the 'api.' prefix if it exists
            $table = preg_replace('/^.+\./', '', $table);
        }

        return $table;
    }

    public function extractTableNamesAndOperationsFromSQL($sql)
    {
        $results = [];
        $tables  = [];

        try {
            // Define an array of regex patterns for different clauses, handling optional prefixes and backticks
            $regexPatterns = [
                // FROM with optional prefix and alias, capture only the table name
                'select' => '/from\s+(?:[a-zA-Z0-9_]+\.)?`?([^\s`\.]+)`?/i',
                // JOIN with optional prefix, capture only the table name
                'join'   => '/join\s+(?:[a-zA-Z0-9_]+\.)?`?([^\s`\.]+)`?/i',
                'update' => '/update\s+(?:[a-zA-Z0-9_]+\.)?`?([^\s`\.]+)`?/i',
                'insert' => '/insert\s+into\s+(?:[a-zA-Z0-9_]+\.)?`?([^\s`\.]+)`?/i',
                'delete' => '/delete\s+from\s+(?:[a-zA-Z0-9_]+\.)?`?([^\s`\.]+)`?/i'
            ];

            // Process each SQL operation based on priority
            foreach (['delete', 'insert', 'update', 'select'] as $operation) {
                if (preg_match($regexPatterns[$operation], $sql, $matches)) {
                    $table = trim($matches[1], '`');

                    // Ensure the table is only added once
                    if (!in_array($table, $tables)) {
                        // Add to tables array
                        $tables[] = $table;

                        // Add to results array with operation
                        $results[] = [$table => strtoupper($operation)];
                    }

                    // Since we've found a matching operation, break and stop further processing
                    break;
                }
            }

            // Process JOINs after other operations
            if (preg_match_all($regexPatterns['join'], $sql, $joinMatches)) {
                foreach ($joinMatches[1] as $joinTable) {
                    $joinTable = trim($joinTable, '`');

                    // Ensure the join table is only added once
                    if (!in_array($joinTable, $tables)) {
                        // Add to tables array
                        $tables[] = $joinTable;

                        // Add to results array with operation
                        $results[] = [$joinTable => 'JOIN'];
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore any exceptions and return empty arrays
            return [
                'tables' => [],
                'table_operations' => []
            ];
        }

        // Return both tables array and results array
        return [
            'tables' => $tables,
            'table_operations' => $results
        ];
    }


}
