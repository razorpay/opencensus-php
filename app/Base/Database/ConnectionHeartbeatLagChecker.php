<?php

namespace RZP\Base\Database;

use App;
use PDO;
use Illuminate\Support\Arr;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class ConnectionHeartbeatLagChecker
{
    protected $app;

    protected $trace;

    protected $config;

    public function __construct($name)
    {
        $this->app = App::getFacadeRoot();

        $this->trace  = $this->app['trace'];

        $this->config = $this->getConfig($name);
    }

    private function getConfig(string $name)
    {
        $connections = $this->app['config']['database.connections'];

        if (is_null($config = Arr::get($connections, $name)))
        {
            throw new \InvalidArgumentException("Database [$name] not configured.");
        }

        return $config;
    }

    private function getPDOConnection()
    {
        try
        {
            $host = $this->config['read']['host'] ?? $this->config['host'];

            $port = $this->config['read']['port'] ??  $this->config['port'];

            $dbname = $this->config['read']['database'] ?? $this->config['database'];

            $username = $this->config['read']['username'] ?? $this->config['username'];

            $password = $this->config['read']['password'] ?? $this->config['password'];

            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password, array(
                PDO::ATTR_TIMEOUT => 1, // in seconds
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            return $pdo;
        }
        catch (\PDOException $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::DB_PDO_CONN_SETUP_ERROR);
        }
    }

    protected function isMySQLConnectionLagging(): bool
    {
        $pdo = $this->getPDOConnection();

        $lagThreshold = $this->config['lag_threshold'];

        $query = 'SELECT ROUND(( ROUND(UNIX_TIMESTAMP(Now(6)) * 1000000) - (UNIX_TIMESTAMP(SUBSTR(ts, 1, 19)) * 1000000 +
                  SUBSTR(ts, 21, 6))) / 1000) AS replica_lag_milli, ts, CONNECTION_ID() as connection_id
                  FROM heartbeat ORDER BY ts DESC LIMIT 1';

        try
        {
            // If PDO is null then we weren't able to establish successful connection
            if (is_null($pdo) === true)
            {
                return true;
            }

            $result = $pdo->query($query)->fetch();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::HEARTBEAT_PDO_FETCH_ERROR);

            return true;
        }

        $this->lag = $result['replica_lag_milli'];

        $this->connectionId = $result['connection_id'];

        $pdo = null;

        $this->trace->histogram(Metric::DATAWAREHOUSE_REPLICATION_LAG, $this->lag);

        $this->trace->info(TraceCode::DATA_WAREHOUSE_REPLICATION_LAG, [
            'lag'       => $this->lag,
            'threshold' => $lagThreshold,
            'is_lag'    => ($this->lag > $lagThreshold),
        ]);

        return ($this->lag > $lagThreshold);
    }

    public function isConnectionLagging(): bool
    {
        return $this->isMySQLConnectionLagging();
    }
}
