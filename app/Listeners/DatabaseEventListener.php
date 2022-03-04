<?php

namespace RZP\Listeners;

use App;

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
        $rand = rand(1,100000);

        if ($rand > $this->sampleRate)
        {
            return;
        }

        try {
            $this->trace->info(
                TraceCode::DB_QUERY_EXECUTION_LOG,
                [
                    'application' => 'api',
                    'route'      => $this->app['request.ctx']->getRoute() ?? $this->app['worker.ctx']->getJobName(),
                    'connection' => $event->connectionName,
                    'query'      => $event->sql,
                    'bindings'   => $event->bindings,
                    'time'       => $event->time
                ]);
        }
        catch (\Throwable $e)
        {
            // silent ignore for now
        }
    }
}
