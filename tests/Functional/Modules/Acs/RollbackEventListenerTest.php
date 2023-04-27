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
        $rollbackEventListener = $this->getMockedRollbackEventHandler();
        $traceMock->expects($this->once())->method('info');
        $traceMock->expects($this->never())->method('traceException');
        $rollbackEventListener->handle($rollbackEvent);
    }

    function getMockedRollbackEventHandler($methods = [])
    {
        return $this->getMockBuilder(RollbackEventListener::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }

    protected function createTraceMock()
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }
}
