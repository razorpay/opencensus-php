<?php

namespace Tests\Functional;

use Artisan;

class Database
{
    protected $db;

    protected $dbTransactionInProgress = false;

    public function __construct($db)
    {
        $this->db = $db;
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
        // Run DB migration
        $this->migrate();

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
    protected function migrate()
    {
        Artisan::call('migrate', array('--database' => 'live'));
        Artisan::call('migrate', array('--database' => 'test'));
    }

}

