<?php

namespace RZP\Tests\Functional;

use Artisan;

class Database
{
    protected $db;

    protected $config;

    protected $dbTransactionInProgress = false;

    protected static $fixturesDone = false;

    public function __construct($app)
    {
        $this->db = $app['db'];

        $this->config = $app['config'];
    }

    public function tearDown()
    {
        //
        // Undo DB Changes after test
        //
        if ($this->dbTransactionInProgress === true)
        {
            $this->db->connection('live')->rollBack();
            $this->db->connection('test')->rollBack();
            $this->db->connection('auth')->rollBack();

            $this->dbTransactionInProgress = false;
        }

        $this->db->disconnect('live');
        $this->db->disconnect('test');
        $this->db->disconnect('auth');
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
        if (self::$fixturesDone)
        {
            // Alread run, just begin transaction
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
        $this->db->connection('test')->beginTransaction();
        $this->db->connection('live')->beginTransaction();
        $this->db->connection('auth')->beginTransaction();

        $this->dbTransactionInProgress = true;

        $this->config->set('database.default', 'test');
    }

    /**
     * Migrates database
     */
    public function migrate()
    {
        \Artisan::call('migrate', ['--database' => 'live']);
        \Artisan::call('migrate', ['--database' => 'test']);

        //
        // Creating the auth database here, mainly for wercker.
        // There isn't a straightforward way of creating multiple
        // database on the wercker MySQL service
        //
        $authDb = env('DB_AUTH_DATABASE', 'auth_test');

        $this->db->connection('auth')->statement('CREATE DATABASE IF NOT EXISTS ' . $authDb);

        \Artisan::call('migrate', ['--database' => 'auth', '--path' => '/vendor/razorpay/oauth/database/migrations']);
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
        $this->config->set('database.default', 'live');

        $this->truncateAllTables();

        $this->config->set('database.default', 'test');

        $this->truncateAllTables();

        $this->config->set('database.default', 'auth');

        $this->truncateAllTables();

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
                  FROM INFORMATION_SCHEMA.TABLES where table_schema in ('$database');";

            $results = $this->db->select($query);
        }
        else if ($driver === 'sqlite')
        {
            $results = $this->db->select("SELECT GROUP_CONCAT(name, ';') as query FROM sqlite_master WHERE type='table';");
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
}

