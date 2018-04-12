<?php

namespace RZP\Base\Database;

use App;
use Closure;
use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

class MysqlConnection extends BaseMySqlConnection
{
    protected $lagChecker;

    protected $forceReadPdo;

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        $lagCheckConfig = $config['lag_check'] ?? null;

        $this->forceReadPdo = false;

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
                // TODO: This needs to be implemented.
                return new HeartbeatLagChecker($app, $config);
        }
    }

    public function getReadPdo()
    {
        if ($this->transactions > 0)
        {
            return $this->getPdo();
        }

        if (($this->getConfig('sticky') === true) and
            ($this->recordsModified === true) and
            ($this->forceReadPdo === false))
        {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure)
        {
            return $this->readPdo = $this->lagChecker->useReadPdoIfApplciable($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    public function forceReadPdo(bool $value)
    {
        $this->forceReadPdo = $value;
    }

    public function recordsHaveNotBeenModified(bool $value = false)
    {
        if ($this->recordsModified === true)
        {
            $this->recordsModified = $value;
        }
    }
}
