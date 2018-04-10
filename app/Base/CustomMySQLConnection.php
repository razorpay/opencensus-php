<?php

namespace RZP\Base;

use App;
use Closure;
use Illuminate\Database\MySqlConnection;

class CustomMySQLConnection extends MySqlConnection
{
    protected $lagChecker;

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        $lagCheckConfig = $config['lag_check'] ?? null;

        $this->lagChecker = $this->getLagChecker($lagCheckConfig);

        parent::__construct($pdo, $database, $tablePrefix, $config);
    }

    protected function getlagChecker(array $config)
    {
        $driver = $config['driver'];

        $app = App::getFacadeRoot();

        switch ($driver)
        {
            case 'redis':
                return new RedisLagChecker($app, $config);

            case 'heartbeat':
                return new HeartbeatLagChecker($app, $config);
        }
    }

    public function getReadPdo()
    {
        if ($this->transactions > 0)
        {
            return $this->getPdo();
        }

        if (($this->getConfig('sticky') === true) and ($this->recordsModified === true))
        {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure)
        {
            return $this->readPdo = $this->lagChecker->useReadPdoIfApplciable($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }
}
