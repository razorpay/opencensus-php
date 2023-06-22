<?php

namespace RZP\Services;

use App;
use RZP\Trace\TraceCode;
use Clouding\Presto\Presto;

class DataLakePresto
{
    protected $trace;
    protected $app;
    protected $prestoClient;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $config = $app['config']->get('services.presto');

        $this->prestoClient = new Presto();

        $host = sprintf('%s://%s@%s:%s',
            $config['scheme'],
            $config['user'],
            $config['host'],
            $config['port']
        );

        $this->prestoClient->addConnection([
            'host'   => $host,
            'user'   => $config['user'],
            'catalog'=> 'hive',
            'schema' => 'default',
        ]);
    }

    public function getDataFromDataLake($query, $associate = true)
    {
        $this->trace->info(TraceCode::DATALAKE_PRESTO_QUERY, ["query"=>$query]);

        try
        {
            $result = $this->prestoClient->connection()->query($query);

            if ($associate === true)
            {
                return $result->getAssoc();
            }

            return  $result->get();
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::DATALAKE_PRESTO_REQUEST_FAILURE, [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'trace'   => $e->getTrace(),
            ]);

            throw $e;
        }
    }
}
