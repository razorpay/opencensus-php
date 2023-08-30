<?php

namespace Unit\Jobs;

use Cache;
use Redis;
use Mockery;
use RZP\Constants\Mode;
use Tests\Unit\TestCase;
use RZP\Services\KafkaMessageProcessor;
use RZP\Jobs\Kafka\PartnerWebhookEventHandlerJob;

class PartnerWebhookCallbackEventTest extends TestCase
{
    private $storkMock;

    private $entityOriginMock;

    private $entityOriginRepoMock;

    private $redisMock;

    protected function setup() : void
    {
        parent::setUp();

        $this->createTestDependencyMocks();
    }

    protected function createTestDependencyMocks()
    {
        $this->storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $this->storkMock);

        $this->mockCache();

        $this->entityOriginMock = Mockery::mock('RZP\Models\EntityOrigin\Entity');

        $this->entityOriginRepoMock = Mockery::mock('RZP\Models\EntityOrigin\Repository');
    }

    public function testPartnerWebhookCallbackEventWithEntityOwner()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $this->repoMock->shouldReceive('driver')->andReturn($this->entityOriginRepoMock);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityIdOnReadReplica')->andReturn($this->entityOriginMock);

        $this->entityOriginMock->shouldReceive('getOriginId')->andReturn('JGXV2m2t9xhTQy');

        $key = 'entity_origin_redis_key_live' . $event['entity_type'] . '_' . $event['entity_id'];

        $this->redisMock->expects($this->exactly(1))->method('get')->with($key)->will($this->returnValue(null));

        $this->redisMock->expects($this->exactly(1))->method('set')->with($key, 'JGXV2m2t9xhTQy', 'EX', 172800)->will($this->returnValue(true));

        $this->storkMock
            ->shouldReceive('request')
            ->once()
            ->andReturn(new \WpOrg\Requests\Response());

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    public function testPartnerWebhookCallbackEventProcessingWithoutEntityOwner()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $this->repoMock->shouldReceive('driver')->andReturn($this->entityOriginRepoMock);

        $key = 'entity_origin_redis_key_live' . $event['entity_type'] . '_' . $event['entity_id'];

        $this->redisMock->expects($this->exactly(1))->method('get')->with($key)->will($this->returnValue(null));

        $this->redisMock->expects($this->exactly(1))->method('set')->with($key, 'NONE', 'EX', 172800)->will($this->returnValue(true));

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityIdOnReadReplica')->andReturn(null);

        $this->storkMock->shouldNotReceive('request');

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    public function testPartnerWebhookCallbackEventProcessingWithCachedEntity()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $key = 'entity_origin_redis_key_live' . $event['entity_type'] . '_' . $event['entity_id'];

        $this->redisMock->expects($this->exactly(1))->method('get')->with($key)->will($this->returnValue('JGXV2m2t9xhTQy'));

        $this->redisMock->expects($this->exactly(0))->method('set');

        $this->entityOriginRepoMock->shouldNotReceive('fetchByEntityTypeAndEntityIdOnReadReplica');

        $this->storkMock
            ->shouldReceive('request')
            ->once()
            ->andReturn(new \WpOrg\Requests\Response());

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    public function testPartnerWebhookCallbackEventProcessingWithCachedEntityForMerchant()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $this->mockCache();

        $key = 'entity_origin_redis_key_live' . $event['entity_type'] . '_' . $event['entity_id'];

        $this->redisMock->expects($this->exactly(1))->method('get')->with($key)->will($this->returnValue("NONE"));

        $this->redisMock->expects($this->exactly(0))->method('set');

        $this->entityOriginRepoMock->shouldNotReceive('fetchByEntityTypeAndEntityIdOnReadReplica');

        $this->storkMock->shouldNotReceive('request');

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    public function testPartnerWebhookEventHandlerWithEntityOwner()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $this->repoMock->shouldReceive('driver')->andReturn($this->entityOriginRepoMock);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityIdOnReadReplica')->andReturn($this->entityOriginMock);

        $this->entityOriginMock->shouldReceive('getOriginId')->andReturn('JGXV2m2t9xhTQy');

        $key = 'entity_origin_redis_key_live' . $event['entity_type'] . '_' . $event['entity_id'];

        $this->redisMock->expects($this->exactly(1))->method('get')->with($key)->will($this->returnValue(null));

        $this->redisMock->expects($this->exactly(1))->method('set')->with($key, 'JGXV2m2t9xhTQy', 'EX', 172800)->will($this->returnValue(true));

        $this->storkMock
            ->shouldReceive('request')
            ->once()
            ->andReturn(new \WpOrg\Requests\Response());

        $handler = new PartnerWebhookEventHandlerJob($event, Mode::TEST);

        $handler->handle();

        $this->assertEquals(Mode::LIVE, $handler->getMode());
    }

    public function testModeSetFromPayloadWithLiveMode()
    {
        $input = $this->getDummyEventDataFromStork();

        $handler = new PartnerWebhookEventHandlerJob($input, Mode::TEST);

        $handler->setModeFromPayload($input);

        $this->assertEquals(Mode::LIVE, $handler->getMode());
    }

    public function testModeSetFromPayloadWithTestMode()
    {
        $input = $this->getDummyEventDataFromStork();

        $input['event']['service'] = 'beta-api-test';

        $handler = new PartnerWebhookEventHandlerJob($input, Mode::LIVE);

        $handler->setModeFromPayload($input);

        $this->assertEquals(Mode::TEST, $handler->getMode());
    }

    public function testModeSetFromPayloadWithNoMode()
    {
        $input = $this->getDummyEventDataFromStork();

        $input['event']['service'] = 'beta-api';

        $handler = new PartnerWebhookEventHandlerJob($input, Mode::LIVE);

        $handler->setModeFromPayload($input);

        $this->assertEquals(Mode::LIVE, $handler->getMode());
    }

    public function mockCache()
    {
        $this->redisMock  = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get'])->getMock();
        $this->app->instance('redis', $this->redisMock);

        Redis::shouldReceive('connection')->andReturn($this->redisMock);
    }

    private function getDummyEventDataFromStork()
    {
        return [
            'entity_id' => 'LoHEtckwBAezgi',
            'entity_type' => 'qr_code',
            'application_id' => 'JGXV2m2t9xhTQy',
            'event' => [
                'service' => 'api-live',
                'owner_id' => 'Kwuji3tCNAS7Zt',
                'owner_type' => 'merchant',
                'context' => [
                    'id' => 'LoHEtckwBAezgi',
                ],
                'name' => 'qr_code.created',
                'payload' => "{\"mode\":\"test234456e56466777775\"}"
            ]
        ];
    }
}
