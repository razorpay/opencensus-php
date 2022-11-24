<?php

namespace RZP\Tests\Unit\Database;

use Cache;
use PDOStatement;

use RZP\Tests\TestCase;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Unit\Database\Helpers\MockPDO;
use RZP\Tests\Unit\Database\Helpers\MySqlConnection;

class MySqlConnectionTest extends TestCase
{
    public function testReadPdoSelectedForNormalSelects()
    {
        //
        // Creates mock pdo and statement objects.
        //
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                           ->getMock();

        //
        // Sets expectations on the mock objects
        //
        $writePdo->expects($this->never())->method('prepare');
        $readPdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        //
        // Creates mock connection object and executes the select query
        //
        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($readPdo);
        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));
        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testWritePdoSelectedIfInsideTransaction()
    {
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                          ->getMock();

        //
        // Since this is inside a transaction, readPdo should
        // not get selected for the SELECT query and writePdo should
        // be selected.
        $readPdo->expects($this->never())->method('prepare');
        $writePdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($readPdo);

        //
        // Set the transaction counter to 1 here, to check if write pdo is selected
        //
        $mockConnection->setTransaction(1);
        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));

        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testRecheckReplicaLagWithNoReplicaLag()
    {
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                          ->getMock();

        //
        // Sets expectations on the mock objects
        //
        $writePdo->expects($this->never())->method('prepare');
        $readPdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        //
        // Creates mock connection object and executes the select query
        //
        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($readPdo);
        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));

        //
        // Sets these attributes on the connection object so that lag check
        // is re-evaluated for the select.
        //
        $mockConnection->setForceCheckReplicaLag(true);

        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testRecheckReplicaLagWithReplicationLag()
    {
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                          ->getMock();

        $lagChecker = $this->getMockBuilder(RedisLagChecker::class)->setMethods(['useReadPdoIfApplicable'])->getMock();
        $lagChecker->expects($this->once())
                   ->method('useReadPdoIfApplicable')
                   ->with($readPdo)
                   ->will($this->returnValue(null));

        //
        // The read pdo should never be used, as the replica lag
        // check gives null, indicating that the write connection should get used.
        //
        $readPdo->expects($this->never())->method('prepare');
        $writePdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($readPdo);
        $mockConnection->setLagChecker($lagChecker);

        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));

        //
        // Sets these attributes on the connection object so that lag check
        // is re-evaluated for the select.
        //
        $mockConnection->setForceCheckReplicaLag(true);

        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testWritePdoSelectedWhenRecordsHaveBeenModified()
    {
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                          ->getMock();

        //
        // In this test records have been modified by say previous
        // DMLs in the request and hence writePdo should get selected
        // for the SELECT query.
        //
        $readPdo->expects($this->never())->method('prepare');
        $writePdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($readPdo);

        //
        // Sets recordsModified to true to indeicate a previous DML
        // has been executed and the write pdo should be selected.
        //
        $mockConnection->isRecordsModified(true);
        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));

        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testReadPdoSelectedWhenForceReadPdoSet()
    {
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                          ->getMock();

        //
        // Here even if records have been modified in the request,
        // since forceReadPdo is used, the readPdo still gets selected.
        //
        $writePdo->expects($this->never())->method('prepare');
        $readPdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($readPdo);
        $mockConnection->isRecordsModified(true);
        $mockConnection->forceReadPdo(true);
        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));
        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testReadConnectionIsInitalisedAndUsedFirstTime()
    {
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                          ->getMock();
        $lagChecker = $this->getMockBuilder(RedisLagChecker::class)->setMethods(['useReadPdoIfApplicable'])->getMock();

        //
        // Initially before a query is executed, the pdo is a callback, which
        // is called and the connection is established if all read pdo selection
        // criteria are satisfied. This is how laravel lazy loads the connection.
        //
        $writePdo->expects($this->never())->method('prepare');
        $readPdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $callback = function () use ($readPdo)
        {
            return $readPdo;
        };

        $lagChecker->expects($this->once())
                   ->method('useReadPdoIfApplicable')
                   ->with($callback)
                   ->will($this->returnValue($readPdo));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($callback);

        //
        // Sets tee mocked lagChecker on the connection object,
        // so that the code flow when setting up read connection
        // first time is executed.
        //
        $mockConnection->setLagChecker($lagChecker);
        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));
        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testWritePdoSelectedOnException()
    {
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                          ->getMock();
        $lagChecker = $this->getMockBuilder(RedisLagChecker::class)
                           ->setMethods(['useReadPdoIfApplicable'])
                           ->getMock();

        //
        // If any exception is thrown while setting up the read pdo
        // connection, we always pick the write connection.
        //
        $readPdo->expects($this->never())->method('prepare');
        $writePdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $callback = function () use ($readPdo)
        {
            return $readPdo;
        };
        $lagChecker->expects($this->once())
                   ->method('useReadPdoIfApplicable')
                   ->with($callback)
                   ->will($this->throwException(new \Exception('error')));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($callback);
        $mockConnection->setLagChecker($lagChecker);
        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));
        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testReadPdoSelectedInForceReadPdo()
    {
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                          ->getMock();

        $lagChecker = $this->getMockBuilder(RedisLagChecker::class)->setMethods(['useReadPdoIfApplicable'])->getMock();
        $lagChecker->expects($this->once())
                   ->method('useReadPdoIfApplicable')
                   ->with($readPdo)
                   ->will($this->returnValue($readPdo));

         //
        // Sets expectations on the mock objects
        //
        $writePdo->expects($this->never())->method('prepare');
        $readPdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($readPdo);
        $mockConnection->setLagChecker($lagChecker);

        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));

        //
        // Sets these attributes on the connection object so that lag check
        // is re-evaluated for the select.
        //
        $mockConnection->setForceCheckReplicaLag(true);

        $mockConnection->setForceReadPdo(true);

        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testReadPdoSelectedInForceSlaveRouteReadPdo()
    {
        $readPdo   = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo  = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
                          ->setMethods(['execute', 'fetchAll', 'bindValue', 'setFetchMode'])
                          ->getMock();

        $lagChecker = $this->getMockBuilder(RedisLagChecker::class)->setMethods(['useReadPdoIfApplicable'])->getMock();
        $lagChecker->expects($this->once())
                   ->method('useReadPdoIfApplicable')
                   ->with($readPdo)
                   ->will($this->returnValue($readPdo));

         //
        // Sets expectations on the mock objects
        //
        $writePdo->expects($this->never())->method('prepare');
        $readPdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($readPdo);
        $mockConnection->setLagChecker($lagChecker );

        $mockConnection->expects($this->once())
                       ->method('prepareBindings')
                       ->with($this->equalTo(['foo' => 'bar']))
                       ->will($this->returnValue(['foo' => 'bar']));

        //
        // Sets these attributes on the connection object so that lag check
        // is re-evaluated for the select.
        //
        $mockConnection->setForceCheckReplicaLag(true);

        $mockConnection->setSlaveRoute(true);

        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }


    protected function getMockConnection($methods = [], $pdo = null)
    {
        $pdo = $pdo ?: new MockPDO;
        $config = [
            'sticky'    => true,
            'lag_check' => [
                'driver' => 'redis',
                'flag'  => ConfigKey::MASTER_PERCENT,
            ],
            'heartbeat_check' => [
                'driver'                => 'heartbeat',
                'force_run'             => env('HEARTBEAT_FORCE_RUN'),
                'enabled'               => env('HEARTBEAT_ENABLED', false),
                'mock'                  => env('HEARTBEAT_MOCK'),
                'time_threshold'        => env('HEARTBEAT_TIME_THRESHOLD'),
                'slave_time_threshold'  => env('HEARTBEAT_SLAVE_TIME_THRESHOLD'),
                'traffic_percentage'    => env('HEARTBEAT_TRAFFIC_PERCENTAGE'),
                'log_verbose'           => env('HEARTBEAT_LOG_VERBOSE'),
            ]
        ];

        $connection = $this->getMockBuilder(MySqlConnection::class)
                           ->setMethods($methods)
                           ->setConstructorArgs([$pdo, '', '', $config])
                           ->getMock();

        return $connection;
    }
}
