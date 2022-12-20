<?php

namespace Unit\Models\Merchant\Asv;

use Config;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;
use \RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Acs\Service;
use RZP\Models\Merchant\Acs\AsvClient\BaseClient;
use RZP\Models\Merchant\Acs\EventProcessor\LoggingEventProcessor;

class AcsServiceTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
        Config::set('applications.acs.asv_full_data_sync_splitz_experiment_id', '100000000000');
    }

    public function testHandleAccountUpdateEventSuccessfully()
    {
        $accountService = new Service();
        $accountService->eventProcessorFactory->SetEventProcessorClassList([
            LoggingEventProcessor::class
        ]);

        $input = [
            "deliveryAttempt" => 1,
            "message" => [
                "data" => "eyJhY2NvdW50X2lkIjoiS0ppVE1XQ3hRYmRqM0QiLCJtZXRhZGF0YSI6eyJtb2RpZmllZF9lbnRpdHkiOiJhY2NvdW50X2RvY3VtZW50IiwibW9kaWZpZWRfY2hpbGRfZW50aXR5IjoiYWNjb3VudF9kb2N1bWVudCIsIm1vZGlmaWVkX3RpbWVzdGFtcCI6MTY2MzU4MTY1N319",
                "messageId" => "cck3rno41gnglj2u4kig",
                "publishTime" => "2022-09-19T10:01:03Z"

            ],
            "subscription" => "projects/prod-api/subscriptions/prod-api-asv-side-effect-events-consumer"
        ];

        $accountService->handleAccountUpdateEvent($input);
    }

    public function testHandleAccountUpdateEventWithException()
    {
        $accountService = new Service();
        $accountService->eventProcessorFactory->SetEventProcessorClassList([
            BaseClient::class
        ]);

        $input = [
            "deliveryAttempt" => 1,
            "message" => [
                "data" => "eyJhY2NvdW50X2lkIjoiS0ppVE1XQ3hRYmRqM0QiLCJtZXRhZGF0YSI6eyJtb2RpZmllZF9lbnRpdHkiOiJhY2NvdW50X2RvY3VtZW50IiwibW9kaWZpZWRfY2hpbGRfZW50aXR5IjoiYWNjb3VudF9kb2N1bWVudCIsIm1vZGlmaWVkX3RpbWVzdGFtcCI6MTY2MzU4MTY1N319",
                "messageId" => "cck3rno41gnglj2u4kig",
                "publishTime" => "2022-09-19T10:01:03Z"

            ],
            "subscription" => "projects/prod-api/subscriptions/prod-api-asv-side-effect-events-consumer"
        ];

        $this->expectException(LogicException::class);

        $accountService->handleAccountUpdateEvent($input);
    }

    public function testTriggerSync()
    {
        #T1 starts - operation doesn't exist
        $traceMock = $this->createTraceMock(['info']);
        $traceMock->expects($this->exactly(2))->method('info');

        $splitzHelperMock = $this->createMock(SplitzHelper::class);
        $splitzHelperMock->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);

        $asvServiceMock = $this->createAcsServiceMock(['pushDataSyncEventsToKafka']);
        $asvServiceMock->splitzHelper = $splitzHelperMock;
        $asvServiceMock->expects($this->never())->method('pushDataSyncEventsToKafka');
        $asvServiceMock->triggerSync(['mode' => 'live', 'account_ids' => ['10000000000000']]);
        #T1 ends

        #T2 starts - operation exists but doesn't contain valid value
        $traceMock = $this->createTraceMock(['info']);
        $traceMock->expects($this->exactly(2))->method('info');

        $splitzHelperMock = $this->createMock(SplitzHelper::class);
        $splitzHelperMock->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);

        $asvServiceMock = $this->createAcsServiceMock(['pushDataSyncEventsToKafka']);
        $asvServiceMock->splitzHelper = $splitzHelperMock;
        $asvServiceMock->expects($this->never())->method('pushDataSyncEventsToKafka');
        $asvServiceMock->triggerSync(['mode' => 'live', 'operation' => 'random', 'account_ids' => ['10000000000000']]);
        #T2 ends

        #T3 starts - operation exists containing valid value, splitz True
        $traceMock = $this->createTraceMock(['info']);
        $traceMock->expects($this->exactly(2))->method('info');

        $splitzHelperMock = $this->createMock(SplitzHelper::class);
        $splitzHelperMock->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);

        $asvServiceMock = $this->createAcsServiceMock(['pushDataSyncEventsToKafka']);
        $asvServiceMock->splitzHelper = $splitzHelperMock;
        $asvServiceMock->expects($this->exactly(1))->method('pushDataSyncEventsToKafka');
        $asvServiceMock->triggerSync(['mode' => 'live', 'operation' => 'full_sync', 'account_ids' => ['10000000000000']]);
        #T3 ends

        #T4 starts - operation exists containing valid value, splitz False
        $traceMock = $this->createTraceMock(['info']);
        $traceMock->expects($this->exactly(2))->method('info');

        $splitzHelperMock = $this->createMock(SplitzHelper::class);
        $splitzHelperMock->expects($this->exactly(1))->method('isSplitzOn')->willReturn(false);

        $asvServiceMock = $this->createAcsServiceMock(['pushDataSyncEventsToKafka']);
        $asvServiceMock->splitzHelper = $splitzHelperMock;
        $asvServiceMock->expects($this->never())->method('pushDataSyncEventsToKafka');
        $asvServiceMock->triggerSync(['mode' => 'live', 'operation' => 'full_sync', 'account_ids' => ['10000000000000']]);
        #T4 ends
    }

    public function createAcsServiceMock($methods = [])
    {
        return $this->getMockBuilder(Service::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }

    function createTraceMock($methods = [])
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }
}
