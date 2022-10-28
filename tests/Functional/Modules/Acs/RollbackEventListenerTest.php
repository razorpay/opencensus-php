<?php

namespace RZP\Tests\Functional\Modules\Acs;


use RZP\Models\Merchant\Entity;
use RZP\Modules\Acs\RollbackEvent;
use RZP\Tests\Functional\TestCase;
use Razorpay\Outbox\Job\Repository;
use Razorpay\Trace\Logger as Trace;
use Psr\Log\LoggerInterface as Logger;
use Razorpay\Outbox\Encoder\JsonEncoder;
use RZP\Modules\Acs\RollbackEventListener;
use Razorpay\Outbox\Job\Core as OutboxCore;
use Razorpay\Outbox\Encrypt\AES256GCMEncrypt;

class RollbackEventListenerTest extends TestCase
{

    function testRollbackEventHandle()
    {
        $merchant = new Entity();
        $merchant['id'] = '10000000000000';
        $rollbackEvent = new RollbackEvent($merchant, 'afterRollback.updated');

        $traceMock = $this->createTraceMock();
        $rollbackEventListener = $this->getMockedRollbackEventHandler(['publishOutboxJobForRollback']);
        $traceMock->expects($this->once())->method('info');
        $traceMock->expects($this->never())->method('traceException');
        $rollbackEventListener->expects($this->never())->method('publishOutboxJobForRollback');
        $rollbackEventListener->handle($rollbackEvent);
    }

    function testPublishOutboxJobForRollback()
    {

        $jobName = RollbackEventListener::ASV_OUTBOX_JOB_NAME;
        $metadata = [
            'async_job_name' => 'none',
            'route' => 'test_route',
            'rzp_internal_app_name' => 'test_app_name'
        ];
        $payloadMetadata = array_merge(['request_id' => $this->app['request']->getId(), 'task_id' => $this->app['request']->getTaskId()], $metadata);

        $jobPayload = [
            'account_id' => '10000000000000',
            'mode' => 'live',
            'mock' => false,
            'metadata' => $payloadMetadata,
        ];

        // T1 starts - Outbox push successful
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->once())->method('info');
        $traceMock->expects($this->once())->method('count');
        $outboxMock = $this->createOutboxMock();
        $outboxMock->expects($this->once())->method('send');

        $rollbackEventListener = new RollbackEventListener();
        $rollbackEventListener->publishOutboxJobForRollback($jobName, $jobPayload, 'live', $metadata);
        // T1 ends

        // T2 starts - Outbox push failed
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('info');
        $traceMock->expects($this->once())->method('traceException');
        $traceMock->expects($this->once())->method('count');
        $outboxMock = $this->createOutboxMock();
        $outboxMock->expects($this->once())->method('send')->will($this->throwException(new \Exception('db error encountered')));

        $rollbackEventListener = new RollbackEventListener();
        $rollbackEventListener->publishOutboxJobForRollback($jobName, $jobPayload, 'live', $metadata);
        // T2 ends
    }

    function getMockedRollbackEventHandler($methods = [])
    {
        $rollbackEventHandler = $this->getMockBuilder(RollbackEventListener::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();

        return $rollbackEventHandler;
    }

    protected function createTraceMock()
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }

    protected function createOutboxMock(array $methods = ['send'])
    {
        $encrypter = new AES256GCMEncrypt('OUTBOX_ENCRYPTION_KEY');
        $encoder = new JsonEncoder();
        $repo = new Repository(\Database\Connection::LIVE);
        $trace = $this->getMockBuilder(Logger::class)->getMock();
        $mock = $this->getMockBuilder(OutboxCore::class)
            ->setConstructorArgs([$encrypter, $encoder, $repo, $trace])
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('outbox', $mock);
        return $mock;
    }
}
