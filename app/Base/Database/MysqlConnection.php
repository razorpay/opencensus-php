<?php

namespace RZP\Base\Database;

use Closure;
use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

use RZP\Base\Database\LagChecker;

class MysqlConnection extends BaseMySqlConnection
{
    /**
     * LagChecker object to determine which pdo connection to use
     * @var LagChecker\LagChecker
     */
    protected $lagChecker;

    /**
     * Flag to force use the read connection overriding the sticky config.
     * @var boolean
     */
    protected $forceReadPdo;

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        $lagCheckConfig = $config['lag_check'];

        $this->forceReadPdo = false;

        $this->lagChecker = $this->getLagChecker($lagCheckConfig);

        parent::__construct($pdo, $database, $tablePrefix, $config);
    }

    protected function getlagChecker(array $config)
    {
        $driver = $config['driver'];

        switch ($driver)
        {
            case 'redis':
                return new LagChecker\RedisLagChecker($config);

            case 'heartbeat':
                // TODO: This needs to be implemented.
                return new LagChecker\HeartbeatLagChecker($config);
        }
    }

    public function getReadPdo()
    {
        //
        // If there is an active transaction, we always want
        // to use the master connection.
        //
        if ($this->transactions > 0)
        {
            return $this->getPdo();
        }

        //
        // If a DML query has been executed in the request and 'sticky' config
        // is true and we are not force using the read pdo, then always use
        // the master connection
        //
        if (($this->getConfig('sticky') === true) and
            ($this->recordsModified === true) and
            ($this->forceReadPdo === false))
        {
            return $this->getPdo();
        }

        //
        // When the pdo connection to replica is going to get established the
        // first time, use the lagChecker to determine whethr to establish
        // the connection or not.
        //
        if ($this->readPdo instanceof Closure)
        {
            $this->readPdo = $this->lagChecker->useReadPdoIfApplciable($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    public function forceReadPdo(bool $value)
    {
        $this->forceReadPdo = $value;
    }

    /**
     * This function is complemntary to the 'recordsHaveBeenModified' method
     * in the parent class. It only sets the recordsModified flag to false if
     * it was previously set to true.
     *
     * @param  bool|boolean $value
     */
    public function recordsHaveNotBeenModified(bool $value = false)
    {
        if ($this->recordsModified === true)
        {
            $this->recordsModified = $value;
        }
    }
}
