<?php

namespace RZP\Tests\Functional\Modules\Acs;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\Events as QueueEvents;
use Illuminate\Support\Facades\Event;
use RZP\Constants\Mode;
use RZP\Jobs\SyncStakeholder;
use RZP\Modules\Acs\SyncEventManager;
use RZP\Modules\Acs\TriggerSyncEvent;
use RZP\Events\Kafka as KafkaEvents;
use RZP\Jobs\Kafka as KafkaJobs;
use RZP\Modules\Acs\TriggerSyncListener;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class TriggerSyncEventTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DispatchesJobs;

    public function testKafkaProcessorTriggersJobProcessed()
    {
        $dummyPayload = ['dummy' => false];
        $jobStub = $this->createStub(KafkaJobs\BvsValidationJob::class);

        $processorMock = $this->getMockBuilder(KafkaMessageProcessor::class)
            ->onlyMethods(['getJob'])
            ->getMock();
        $processorMock->expects($this->once())
            ->method('getJob')
            ->willReturn($jobStub);

        Event::Fake([KafkaEvents\JobProcessed::class]);
        $processorMock->process(
            KafkaMessageProcessor::API_BVS_EVENTS, $dummyPayload, Mode::LIVE
        );
        Event::assertDispatched(KafkaEvents\JobProcessed::class);
    }

    public function testRequestTerminationTriggersSyncEvent()
    {
        Event::Fake([TriggerSyncEvent::class]);

        $this->ba->publicLiveAuth();
        $this->fixtures->merchant->activate('10000000000000');
        $request = array(
            'url' => '/checkout',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ],
        );
        $this->sendRequest($request);

        Event::assertDispatched(TriggerSyncEvent::class);
    }

    public function testQueueJobProcessedTriggersPublishJobs()
    {
        $jobStub = $this->createStub(SyncStakeholder::class);
        $this->createSyncManagerMockAndExpectPublish();

        // ideally should trigger the event and assert expectations testing listener wiring as well
        // but facing weird issues with that
        // event(new QueueEvents\JobProcessed('dummyConnection', $jobStub));
        $listener = new TriggerSyncListener();
        $listener->handle(new QueueEvents\JobProcessed('dummyConnection', $jobStub));
    }

    public function testKafkaJobProcessedTriggersPublishJobs()
    {
        $jobStub = $this->createStub(KafkaJobs\BvsValidationJob::class);
        $this->createSyncManagerMockAndExpectPublish();
        event(new KafkaEvents\JobProcessed($jobStub));
    }

    public function testSyncEventTriggersPublishJobs()
    {
        $this->createSyncManagerMockAndExpectPublish();
        event(new TriggerSyncEvent());
    }

    protected function createSyncManagerMockAndExpectPublish($times = 1, ...$args)
    {
        $mock = $this->getMockBuilder(SyncEventManager::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['publishOutboxJobs'])
            ->getMock();
        $this->app->instance(SyncEventManager::SINGLETON_NAME, $mock);

        if (empty($args) == false)
        {
            $mock->expects($this->exactly($times))
                ->method('publishOutboxJobs')
                ->with(...$args);
        }
        else
        {
            $mock->expects($this->exactly($times))
                ->method('publishOutboxJobs');
        }

        return $mock;
    }
}
