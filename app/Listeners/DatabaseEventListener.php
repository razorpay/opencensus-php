<?php

namespace RZP\Listeners;

use App;

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
     * @param  object  $event
     * @return void
     */
    public function handle(QueryExecuted $event)
    {
        $tableName = $this->extractTableNameFromSQL($event->sql);

        $this->trace->count(Metric::DATABASE_QUERY_BINDING, [
            'route'      => $this->app['request.ctx']->getRoute() ?? $this->app['worker.ctx']->getJobName(),
            'connection' => $event->connectionName,
            'tableName' => $tableName,
        ]);

        try
        {
            $query = strtolower($event->sql);

            $isUpdateQuery = str_contains($query, "update");

            $isTokenTable = str_contains($query, "tokens");

            $idPresent = str_contains($query, "id");

            if($isUpdateQuery === true && $isTokenTable === true && $idPresent === false)
            {
                $this->trace->info(
                    TraceCode::TOKEN_UPDATE_DEBUG,
                    [
                        'application' => 'api',
                        'route'      => $this->app['request.ctx']->getRoute() ?? $this->app['worker.ctx']->getJobName(),
                        'connection' => $event->connectionName,
                        'query'      => $event->sql,
                        'bindings'   => $event->bindings,
                        'time'       => $event->time
                    ]);
            }
        }
        catch (\Throwable $e)
        {
            // silent ignore for now
        }

        $rand = rand(1,500000);

        if ($rand > $this->sampleRate)
        {
            return;
        }

        try
        {
            $this->trace->info(
                TraceCode::DB_QUERY_EXECUTION_LOG,
                [
                    'application' => 'api',
                    'route'      => $this->app['request.ctx']->getRoute() ?? $this->app['worker.ctx']->getJobName(),
                    'connection' => $event->connectionName,
                    'query'      => $event->sql,
                    'bindings'   => $event->bindings,
                    'time'       => $event->time,
                    'table_name'  => $tableName,
                ]);
        }
        catch (\Throwable $e)
        {
            // silent ignore for now
        }
    }

    // Function to extract table name from SQL query
    protected function extractTableNameFromSQL($sql) {

        $table = "";

        preg_match('/from\s+([^\s]+)/i', $sql, $matches);

        if (count($matches) > 0)
        {
            $table = trim($matches[1] ?? '', '`');

            // Remove the 'api.' prefix if it exists
            $table = preg_replace('/^.+\./', '', $table);
        }

        return $table;
    }
}
