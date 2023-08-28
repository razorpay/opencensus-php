<?php

namespace Unit\Jobs;

use Cache;
use Redis;
use Mockery;
use Tests\Unit\TestCase;
use RZP\Services\KafkaMessageProcessor;

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

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityId')->andReturn($this->entityOriginMock);

        $this->entityOriginMock->shouldReceive('getOriginId')->andReturn('JGXV2m2t9xhTQy');

        $key = 'entity_origin_redis_key_' . $event['entity_type'] . '_' . $event['entity_id'];

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

        $key = 'entity_origin_redis_key_' . $event['entity_type'] . '_' . $event['entity_id'];

        $this->redisMock->expects($this->exactly(1))->method('get')->with($key)->will($this->returnValue(null));

        $this->redisMock->expects($this->exactly(1))->method('set')->with($key, 'NONE', 'EX', 172800)->will($this->returnValue(true));

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityId')->andReturn(null);

        $this->storkMock->shouldNotReceive('request');

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    public function testPartnerWebhookCallbackEventProcessingWithCachedEntity()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $key = 'entity_origin_redis_key_' . $event['entity_type'] . '_' . $event['entity_id'];

        $this->redisMock->expects($this->exactly(1))->method('get')->with($key)->will($this->returnValue('JGXV2m2t9xhTQy'));

        $this->redisMock->expects($this->exactly(0))->method('set');

        $this->entityOriginRepoMock->shouldNotReceive('fetchByEntityTypeAndEntityId');

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

        $key = 'entity_origin_redis_key_' . $event['entity_type'] . '_' . $event['entity_id'];

        $this->redisMock->expects($this->exactly(1))->method('get')->with($key)->will($this->returnValue("NONE"));

        $this->redisMock->expects($this->exactly(0))->method('set');

        $this->entityOriginRepoMock->shouldNotReceive('fetchByEntityTypeAndEntityId');

        $this->storkMock->shouldNotReceive('request');

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
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
