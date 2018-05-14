<?php

namespace RZP\Base\Database;

use App;
use Closure;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Trace\Facades\Trace as TraceFacade;
use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Base\Database\LagChecker;

class MySqlConnection extends BaseMySqlConnection
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

    /**
     * @var Trace
     */
    protected $trace;

    /**
     * Indicates if the read PDO connection needs to be reevaluated for replica lag.
     * @var boolean
     */
    protected $forceCheckReplicaLag;

    /**
     * Holds the previously established read pdo connection if any, for usage later once replication lag is resolved.
     * @var mixed
     */
    protected $previousReadPdo;

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);

        $lagCheckConfig = $config['lag_check'];

        $this->forceReadPdo = false;

        $this->lagChecker = $this->getLagChecker($lagCheckConfig);

        $this->trace = TraceFacade::getFacadeRoot();

        $this->forceCheckReplicaLag = false;

        $this->previousReadPdo = null;
    }

    protected function getLagChecker(array $config)
    {
        $driver = $config['driver'];

        switch ($driver)
        {
            case 'redis':
                return new LagChecker\RedisLagChecker($config);

            case 'heartbeat':
                // TODO: This needs to be implemented.
                return new LagChecker\HeartbeatLagChecker($config);

            default:
                throw new LogicException('LagChecker driver not implemented: ' . $driver);
        }
    }

    public function getReadPdo()
    {
        try
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
            // first time, or an established pdo connection needs to be rechecked
            // for replica lag (long running queue workers),use the lagChecker to
            // determine whether to establish the connection or not.
            //
            if (($this->readPdo instanceof Closure) or
                ($this->forceCheckReplicaLag === true))
            {
                //
                // If we had used a readPdo connection previously, use that to
                // check the replica lag, as the current readPdo connection will either
                // be the write connection or null, depending on previous lag checks
                //
                if ($this->previousReadPdo !== null)
                {
                    $this->readPdo = $this->previousReadPdo;
                }

                $result = $this->lagChecker->useReadPdoIfApplicable($this->readPdo);

                //
                // If the lag checker returns null, i.e read connection is not to be used,
                // store the current connection in $previousReadPdo so that it
                // can be used to check lag in the future. (Useful for queue workers)
                //
                if ($result === null)
                {
                    $this->previousReadPdo = $this->readPdo;
                }

                //
                // Reset this to false here, so that the lag check is not
                // evaluated again on subsequent selects.
                //
                $this->forceCheckReplicaLag = false;

                $this->readPdo = $result;
            }

            return $this->readPdo ?: $this->getPdo();
        }
        catch (\Throwable $ex)
        {
            //
            // If there is any exception in setting up the replica connection,
            // we trace it and fallback to the master connection.
            //
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::DB_READ_CONN_SETUP_ERROR);

            //
            // The previousReadPdo is set to readPdo here, so that on subsequent,
            // selects the lag check can be evaluated when forceCheckReplicaLag
            // is set to true again.
            //
            $this->previousReadPdo = $this->readPdo;

            //
            // Setting the readPdo to the master connection here, so that on further
            // selects in the same request, the lag check is not evaluated again.
            //
            $this->readPdo = $this->getPdo();

            return $this->readPdo;
        }
    }

    public function forceReadPdo(bool $value)
    {
        $this->forceReadPdo = $value;
    }

    /**
     * Resets some attributes used to maintain the connection state,
     * without explicitly recreating the connection object. This is
     * mainly useful for queue processes, where we want to reuse the
     * same connection object.
     */
    public function resetConnectionAttributes()
    {
        // Reset record of previous DML operations made using this connection.
        $this->recordsHaveNotBeenModified();

        // Reet the forceReadPdo flag to false if previously set to true.
        $this->forceReadPdo(false);

        //
        // Sets this flag to true, so that the read pdo connection is evaluated
        // again for replica lag.
        //
        $this->forceCheckReplicaLag = true;
    }

    /**
     * This function is complementary to the `recordsHaveBeenModified` method
     * in the parent class. It only sets the `recordsModified` flag to false if
     * it was previously set to true.
     *
     * @param  bool|boolean $value
     */
    protected function recordsHaveNotBeenModified(bool $value = false)
    {
        if ($this->recordsModified === true)
        {
            $this->recordsModified = $value;
        }
    }
}
