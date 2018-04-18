<?php

namespace RZP\Tests\Unit\Database;

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
        // Creates mock pdo and statemeent objects.
        //
        $readPdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
            ->setMethods(['execute', 'fetchAll', 'bindValue'])
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
        $readPdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
            ->setMethods(['execute', 'fetchAll', 'bindValue'])
            ->getMock();

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
        $mockConnection->transactions = 1;
        $mockConnection->expects($this->once())
            ->method('prepareBindings')
            ->with($this->equalTo(['foo' => 'bar']))
            ->will($this->returnValue(['foo' => 'bar']));

        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testWritePdoSelectedWhenRecordsHaveBeenModified()
    {
        $readPdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
            ->setMethods(['execute', 'fetchAll', 'bindValue'])
            ->getMock();

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
        $mockConnection->recordsModified = true;
        $mockConnection->expects($this->once())
            ->method('prepareBindings')
            ->with($this->equalTo(['foo' => 'bar']))
            ->will($this->returnValue(['foo' => 'bar']));

        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testReadPdoSelectedWhenForceReadPdoSet()
    {
        $readPdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
            ->setMethods(['execute', 'fetchAll', 'bindValue'])
            ->getMock();

        $writePdo->expects($this->never())->method('prepare');
        $readPdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));

        $mockConnection = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mockConnection->setReadPdo($readPdo);
        $mockConnection->recordsModified = true;
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
        $readPdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
            ->setMethods(['execute', 'fetchAll', 'bindValue'])
            ->getMock();
        $lagChecker = $this->getMockBuilder(RedisLagChecker::class)->setMethods(['useReadPdoIfApplicable'])->getMock();

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
        $mockConnection->lagChecker = $lagChecker;
        $mockConnection->expects($this->once())
            ->method('prepareBindings')
            ->with($this->equalTo(['foo' => 'bar']))
            ->will($this->returnValue(['foo' => 'bar']));
        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    public function testWritePdoSelectedOnException()
    {
        $readPdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo = $this->getMockBuilder(MockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder(PDOStatement::class)
            ->setMethods(['execute', 'fetchAll', 'bindValue'])
            ->getMock();
        $lagChecker = $this->getMockBuilder(RedisLagChecker::class)
            ->setMethods(['useReadPdoIfApplicable'])
            ->getMock();

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
        $mockConnection->lagChecker = $lagChecker;
        $mockConnection->expects($this->once())
            ->method('prepareBindings')
            ->with($this->equalTo(['foo' => 'bar']))
            ->will($this->returnValue(['foo' => 'bar']));
        $results = $mockConnection->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
    }

    protected function getMockConnection($methods = [], $pdo = null)
    {
        $pdo = $pdo ?: new MockPDO;
        $config = [
            'sticky' => true,
            'lag_check' => [
                'driver' => 'redis',
                'flag' => ConfigKey::SKIP_SLAVE
            ],
        ];

        $connection = $this->getMockBuilder(MySqlConnection::class)
            ->setMethods($methods)
            ->setConstructorArgs([$pdo, '', '', $config])
            ->getMock();

        return $connection;
    }
}
