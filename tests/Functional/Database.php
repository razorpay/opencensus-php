<?php

namespace RZP\Tests\Functional;

use Artisan;
use Database\Connection;
use Illuminate\Database\DatabaseManager;

class Database
{
    /**
     * @var DatabaseManager
     */
    protected $db;

    protected $config;

    protected $dbTransactionInProgress = false;

    protected static $fixturesDone = false;

    /**
     * DB connections defined for the app
     *
     * @var array
     */
    protected static $dbConnections = [
        'live',
        'test',
        'auth',
    ];

    public function __construct($app)
    {
        $this->db = $app['db'];

        $this->config = $app['config'];

        if ($this->isPaymentUpiMocked() === false)
        {
            self::$dbConnections[3] = 'payments_upi_live';
            self::$dbConnections[4] = 'payments_upi_test';
        }
    }

    public function tearDown()
    {
        //
        // Undo DB Changes after test
        //
        if ($this->dbTransactionInProgress === true)
        {
            foreach (self::$dbConnections as $connection)
            {
                $this->db->connection($connection)->rollBack();
            }

            $this->dbTransactionInProgress = false;
        }

        foreach (self::$dbConnections as $connection)
        {
            $this->db->disconnect($connection);
        }
    }

    public function setUp()
    {
        $this->config->set('database.default', 'test');

        // Truncate tables
        $this->truncateTestingDatabaseIfRequired();
    }

    public function runFixtures($fixtures)
    {
        if ($this->shouldRunFixtures() === false)
        {
            return $this->beginTransaction();
        }

        if ($this->shouldRunFixturesOnce() === true)
        {
            $this->runFixturesOnce($fixtures);
        }
        else
        {
            $this->runFixturesAgain($fixtures);
        }
    }

    protected function runFixturesAgain($fixtures)
    {
        // Run migrations
        $this->migrate();

        // Begin transaction
        $this->beginTransaction();

        // Seed database
        $fixtures->setUp();
    }

    protected function runFixturesOnce($fixtures)
    {
        if (self::$fixturesDone === true)
        {
            // Already run, just begin transaction
            $this->beginTransaction();

            return;
        }

        // Run migrations
        $this->migrate();

        // Seed database
        $fixtures->setUp();

        $this->beginTransaction();

        self::$fixturesDone = true;
    }

    public function beginTransaction()
    {
        //
        // Start DB transaction so as
        // to rollback once test is finished
        // leaving a clean slate
        //
        foreach (self::$dbConnections as $connection)
        {
            $this->db->connection($connection)->beginTransaction();
        }

        $this->dbTransactionInProgress = true;

        $this->config->set('database.default', 'test');
    }

    /**
     * Migrates database
     */
    public function migrate()
    {
        $this->createDatabases();

        \Artisan::call('migrate', ['--database' => 'live_migration']);
        \Artisan::call('migrate', ['--database' => 'test_migration']);

        // Run Auth DB migrations from the oauth package
        \Artisan::call('migrate', ['--database' => 'auth', '--path' => '/vendor/razorpay/oauth/database/migrations']);

        // Run P2P DB migrations from the P2p Service
        Artisan::call('migrate', ['--database' => 'live_migration', '--path' => 'database/migrations/p2p']);
        Artisan::call('migrate', ['--database' => 'test_migration', '--path' => 'database/migrations/p2p']);

        if ($this->isPaymentUpiMocked() === false)
        {
            $path = 'database/migrations/payments_upi';

            Artisan::call('migrate', ['--database' => 'payments_upi_live', '--path' => $path]);
            Artisan::call('migrate', ['--database' => 'payments_upi_test', '--path' => $path]);
        }
    }

    protected function createDatabases()
    {
        //
        // Define a dummy connection called 'mysql_init'
        // with no database specified, used just to connect
        // to MySQL and create the actual DB's we need
        //
        $tempMysqlConf = [
            'driver'   => env('DB_LIVE_DRIVER'),
            'host'     => env('DB_LIVE_HOST'),
            'port'     => env('DB_LIVE_PORT'),
            'database' => null,
            'username' => env('DB_LIVE_USERNAME'),
            'password' => env('DB_LIVE_PASSWORD'),
        ];

        $this->config->set('database.connections.mysql_init', $tempMysqlConf);

        $apiLiveDb = env('DB_LIVE_DATABASE', 'api_live');

        $this->db->connection('mysql_init')->getPdo()->exec("CREATE DATABASE IF NOT EXISTS `{$apiLiveDb}`");
        $this->db->connection('mysql_init')->getPdo()->exec("GRANT ALL PRIVILEGES ON `{$apiLiveDb}`.* TO 'root'@'%'");

        $apiTestDb = env('DB_TEST_DATABASE', 'api_test');
        $this->db->connection('mysql_init')->getPdo()->exec("CREATE DATABASE IF NOT EXISTS `{$apiTestDb}`");
        $this->db->connection('mysql_init')->getPdo()->exec("GRANT ALL PRIVILEGES ON `{$apiTestDb}`.* TO 'root'@'%'");

        $authDb = env('DB_AUTH_DATABASE', 'auth');
        $this->db->connection('mysql_init')->getPdo()->exec("CREATE DATABASE IF NOT EXISTS `{$authDb}`");

        if ($this->isPaymentUpiMocked() === false)
        {
            $pdo = $this->db->connection('mysql_init')->getPdo();

            $puLiveDb = env('DB_UPI_PAYMENTS_LIVE_DATABASE', 'payments_upi_live');

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$puLiveDb}`");
            $pdo->exec("GRANT ALL PRIVILEGES ON `{$puLiveDb}`.* TO 'root'@'%'");

            $puTestDb = env('DB_UPI_PAYMENTS_TEST_DATABASE', 'payments_upi_test');

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$puTestDb}`");
            $pdo->exec("GRANT ALL PRIVILEGES ON `{$puTestDb}`.* TO 'root'@'%'");
        }
    }

    protected function truncateTestingDatabaseIfRequired()
    {
        if ((env('TRUNCATE_DATABASE') === true) and
            (self::$fixturesDone === false))
        {
            $this->truncate();
        }
    }

    protected function truncate()
    {
        foreach (self::$dbConnections as $connection)
        {
            $this->config->set('database.default', $connection);

            $this->truncateAllTables();
        }
    }

    protected function truncateAllTables()
    {
        $connection = $this->config->get('database.default');

        $config = $this->config->get('database.connections.'.$connection);

        $database = $config['database'];

        $driver = $config['driver'];

        if ($driver === 'mysql')
        {
            $this->db->statement('SET FOREIGN_KEY_CHECKS=0');
            $this->db->statement('SET GROUP_CONCAT_MAX_LEN=10000');
        }

        $tables = $this->getTables($driver, $database);

        foreach ($tables as $table)
        {
            if (strpos($table, 'migrations') !== false)
            {
                continue;
            }

            $query = $this->getTruncateTableQuery($table, $driver);

            $this->db->statement($query);
        }

        if ($driver === 'mysql')
        {
            $this->db->statement('SET FOREIGN_KEY_CHECKS=1');
            $this->db->statement('SET GROUP_CONCAT_MAX_LEN=1024');
        }
    }

    protected function getTables($driver, $database)
    {
        if ($driver === 'mysql')
        {
            $query = "SELECT GROUP_CONCAT(Concat(table_schema,'.',TABLE_NAME) SEPARATOR ';') as query
                  FROM INFORMATION_SCHEMA.TABLES where table_schema in ('$database') and table_type != 'VIEW';";

            $results = $this->db->select($query);
        }
        else if ($driver === 'sqlite')
        {
            $results = $this->db->select(
                "SELECT GROUP_CONCAT(name, ';') as query FROM sqlite_master WHERE type='table';");
        }

        $query = $results[0]->query;

        $tables = explode(';', $query);

        return $tables;
    }

    protected function getTruncateTableQuery($table, $driver)
    {
        if ($driver === 'mysql')
        {
            return 'TRUNCATE TABLE ' . $table;
        }
        else if ($driver === 'sqlite')
        {
            return 'DELETE FROM ' . $table;
        }

        return null;
    }

    protected function shouldRunFixturesOnce()
    {
        return (env('RUN_FIXTURES_ONCE', false));
    }

    protected function shouldRunFixtures()
    {
        return (env('RUN_FIXTURES', true));
    }

    protected function isPaymentUpiMocked()
    {
        return filter_var(env('DB_UPI_PAYMENTS_MOCKED'), FILTER_VALIDATE_BOOLEAN);
    }
}
