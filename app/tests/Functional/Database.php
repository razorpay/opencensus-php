<?php

namespace Tests\Functional;

use Artisan;

class Database
{
    protected $db;

    protected $config;

    protected $dbTransactionInProgress = false;

    public function __construct($app)
    {
        $this->db = $app['db'];

        $this->config = $app['config'];

        $this->artisan = $app['artisan'];
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

            $this->dbTransactionInProgress = false;
        }

        $this->db->disconnect('live');
        $this->db->disconnect('test');
    }

    public function setUp()
    {
        //
        // Start DB transaction so as
        // to rollback once test is finished
        // leaving a clean slate
        //
        $this->db->connection('test')->beginTransaction();
        $this->db->connection('live')->beginTransaction();

        $this->dbTransactionInProgress = true;
    }

    /**
     * Migrates database
     */
    public function migrate()
    {
        $this->artisan->call('migrate', array('--database' => 'live'));
        $this->artisan->call('migrate', array('--database' => 'test'));
    }

    public function truncate()
    {
        $this->config->set('database.default', 'test');

        $this->truncateAllTables();

        $this->config->set('database.default', 'live');

        $this->truncateAllTables();
    }

    protected function truncateAllTables()
    {
        $connection = $this->config->get('database.default');

        $config = $this->config->get('database.connections.'.$connection);

        $database = $config['database'];

        $driver = $config['driver'];

        $tables = $this->getTables($driver, $database);

        if ($driver === 'mysql')
        {
            $this->db->statement('SET FOREIGN_KEY_CHECKS=0');
        }

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

        array_pop($tables);

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
    }
}

