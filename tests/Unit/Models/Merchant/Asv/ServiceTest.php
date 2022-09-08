<?php

namespace Unit\Models\Merchant\Asv;

use RZP\Exception\LogicException;
use \RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Acs\Service;
use RZP\Models\Merchant\Acs\AsvClient\BaseClient;
use RZP\Models\Merchant\Acs\EventProcessor\LoggingEventProcessor;

class ServiceTest extends TestCase
{

    public function testHandleAccountUpdateEventSuccessfully()
    {
        $accountService = new Service();
        $accountService->eventProcessorFactory->SetEventProcessorClassList([
            LoggingEventProcessor::class
        ]);

        $accountService->handleAccountUpdateEvent([]);
    }

    public function testHandleAccountUpdateEventWithException()
    {
        $accountService = new Service();
        $accountService->eventProcessorFactory->SetEventProcessorClassList([
            BaseClient::class
        ]);

        $this->expectException(LogicException::class);

        $accountService->handleAccountUpdateEvent([]);
    }
}
