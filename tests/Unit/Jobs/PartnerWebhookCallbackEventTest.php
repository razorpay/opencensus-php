<?php

namespace Unit\Jobs;

use Mockery;
use Tests\Unit\TestCase;
use RZP\Services\KafkaMessageProcessor;

class PartnerWebhookCallbackEventTest extends TestCase
{
    private $storkMock;

    private $entityOriginMock;

    private $entityOriginRepoMock;

    protected function setup() : void
    {
        parent::setUp();

        $this->createTestDependencyMocks();
    }

    protected function createTestDependencyMocks()
    {
        $this->storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $this->storkMock);

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

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityId')->andReturn(null);

        $this->storkMock->shouldNotReceive('request');

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
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
