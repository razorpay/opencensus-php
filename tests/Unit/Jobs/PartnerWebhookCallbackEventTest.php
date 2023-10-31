<?php

namespace Unit\Jobs;

use Cache;
use Illuminate\Support\Facades\Redis;
use Mockery;
use RZP\Constants\Mode;
use Tests\Unit\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\CustomAssertions;
use RZP\Models\Base\PublicCollection;
use RZP\Services\KafkaMessageProcessor;
use RZP\Jobs\Kafka\PartnerWebhookEventHandlerJob;

class PartnerWebhookCallbackEventTest extends TestCase
{
    use TestsWebhookEvents;
    use CustomAssertions;

    protected $storkMock;

    private $entityOriginMock;

    private $entityOriginRepoMock;

    private $merchantApplicationRepoMock;

    private $merchantApplicationEntityMock;

    private $splitzService;

    private const TRANSACTION_ISOLATION_EXP_ID = 'MN4lq2eZIV1RO2';
    private const FALLBACK_QUERY_EXP_ID        = 'MX5hmiwWqKNpgJ';
    private const RESTRICT_PII_EXP_ID          = 'MuKEiJe4pGdLVE';
    private const MERCHANT_ID                  = '10000000000000';

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

        $this->merchantApplicationRepoMock = Mockery::mock('RZP\Models\Merchant\MerchantApplications\Repository');

        $this->merchantApplicationEntityMock = Mockery::mock('RZP\Models\Merchant\MerchantApplications\Entity');

        $this->mockExperimentEnabledForTransactionIsolation();

        $this->app['rzp.mode'] = 'live';
    }

    public function testPartnerWebhookCallbackEventWithEntityOwner()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $this->repoMock->shouldReceive('driver')->andReturn($this->entityOriginRepoMock);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityIdOnReadReplica')->andReturn($this->entityOriginMock);

        $this->entityOriginMock->shouldReceive('getOriginId')->andReturn('JGXV2m2t9xhTQy');

        $key = 'entity_origin_redis_key_live_' . $event['entity_type'] . '_' . $event['entity_id'];

        Redis::shouldReceive('get')->once()->with($key)->andReturn(null);

        Redis::shouldReceive('set')->once()->with($key, 'JGXV2m2t9xhTQy', 'EX', 172800)->andReturn(true);

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

        $key = 'entity_origin_redis_key_live_' . $event['entity_type'] . '_' . $event['entity_id'];

        Redis::shouldReceive('get')->once()->with($key)->andReturn(null);

        Redis::shouldReceive('set')->once()->with($key, 'NONE', 'EX', 172800)->andReturn(true);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityIdOnReadReplica')->andReturn(null);

        $this->storkMock->shouldNotReceive('request');

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    public function testPartnerWebhookCallbackEventProcessingWithCachedEntity()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $key = 'entity_origin_redis_key_live_' . $event['entity_type'] . '_' . $event['entity_id'];

        Redis::shouldReceive('get')->once()->with($key)->andReturn('JGXV2m2t9xhTQy');

        Redis::shouldNotReceive('set');

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

        $key = 'entity_origin_redis_key_live_' . $event['entity_type'] . '_' . $event['entity_id'];

        Redis::shouldReceive('get')->once()->with($key)->andReturn('NONE');

        Redis::shouldNotReceive('set');

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

        $key = 'entity_origin_redis_key_live_' . $event['entity_type'] . '_' . $event['entity_id'];

        Redis::shouldReceive('get')->once()->with($key)->andReturn(null);

        Redis::shouldReceive('set')->once()->with($key, 'JGXV2m2t9xhTQy', 'EX', 172800)->andReturn(true);

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

    public function testPartnerWebhookCallbackEventWithFallbackQuery()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $this->repoMock->shouldReceive('driver')->with('entity_origin')->andReturn($this->entityOriginRepoMock);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityIdOnReadReplica')->andReturn(null);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityId')->andReturn($this->entityOriginMock);

        $this->entityOriginMock->shouldReceive('getOriginId')->andReturn('JGXV2m2t9xhTQy');

        $key = 'entity_origin_redis_key_live_' . $event['entity_type'] . '_' . $event['entity_id'];

        Redis::shouldReceive('get')->with($key)->andReturn(null);

        Redis::shouldReceive('set')->with($key, 'JGXV2m2t9xhTQy', 'EX', 172800)->andReturn(true);

        $this->mockSplitzExperiment(self::FALLBACK_QUERY_EXP_ID, self::MERCHANT_ID);

        $this->storkMock
            ->shouldReceive('request')
            ->once()
            ->andReturn(new \WpOrg\Requests\Response());

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    public function testPartnerWebhookCallbackEventWithFallbackQueryWithExpDisabled()
    {
        $event = $this->getDummyEventDataFromStork();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $this->repoMock->shouldReceive('driver')->with('entity_origin')->andReturn($this->entityOriginRepoMock);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityIdOnReadReplica')->andReturn(null);

        $this->entityOriginMock->shouldNotReceive('fetchByEntityTypeAndEntityId');

        $key = 'entity_origin_redis_key_live_' . $event['entity_type'] . '_' . $event['entity_id'];

        Redis::shouldReceive('get')->once()->with($key)->andReturn(null);

        Redis::shouldReceive('set')->once()->with($key, 'NONE', 'EX', 172800)->andReturn(true);

        $this->mockSplitzExperiment(self::FALLBACK_QUERY_EXP_ID, self::MERCHANT_ID, null);

        $this->storkMock->shouldNotReceive('request');

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    public function testPartnerWebhookCallbackEventWithMasking()
    {
        $event = $this->getDummyEventDataForMasking();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $this->repoMock->shouldReceive('driver')->with('entity_origin')->andReturn($this->entityOriginRepoMock);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityIdOnReadReplica')->andReturn(null);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityId')->andReturn($this->entityOriginMock);

        $this->entityOriginMock->shouldReceive('getOriginId')->andReturn('JGXV2m2t9xhTQy');

        $key = 'entity_origin_redis_key_live_' . $event['entity_type'] . '_' . $event['entity_id'];

        Redis::shouldReceive('get')->with($key)->andReturn(null);

        Redis::shouldReceive('set')->with($key, 'JGXV2m2t9xhTQy', 'EX', 172800)->andReturn(true);

        $this->mockSplitzExperiment(self::RESTRICT_PII_EXP_ID, self::MERCHANT_ID);

        $partnershipServiceMock = Mockery::mock('overload:RZP\Services\Partnerships\PartnershipsService');

        $expectedEvent = $this->getEventPayloadWithMaskedAttributes();

        $partnershipServiceMock->shouldReceive('fetchMaskedData')->once()->andReturn(['response' => ['masked_data' => $expectedEvent]]);

        $this->expectWebhookEventWithContext(
            'order.paid',
            [],
            function (array $event) use ($expectedEvent)
            {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
            }
        );

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    public function testPartnerWebhookCallbackEventWithMaskingDisabled()
    {
        $event = $this->getDummyEventDataForMasking();

        $this->repoMock->shouldReceive('resetConnectionAttributes');

        $this->repoMock->shouldReceive('driver')->with('entity_origin')->andReturn($this->entityOriginRepoMock);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityIdOnReadReplica')->andReturn(null);

        $this->entityOriginRepoMock->shouldReceive('fetchByEntityTypeAndEntityId')->andReturn($this->entityOriginMock);

        $this->entityOriginMock->shouldReceive('getOriginId')->andReturn('JGXV2m2t9xhTQy');

        $key = 'entity_origin_redis_key_live_' . $event['entity_type'] . '_' . $event['entity_id'];

        Redis::shouldReceive('get')->with($key)->andReturn(null);

        Redis::shouldReceive('set')->with($key, 'JGXV2m2t9xhTQy', 'EX', 172800)->andReturn(true);

        $partnershipServiceMock = Mockery::mock('overload:RZP\Services\Partnerships\PartnershipsService');

        $partnershipServiceMock->shouldNotReceive('fetchMaskedData');

        $this->storkMock
            ->shouldReceive('request')
            ->once()
            ->andReturn(new \WpOrg\Requests\Response());

        $response = (new KafkaMessageProcessor())->process(KafkaMessageProcessor::PARTNER_WEBHOOK_CALLBACK_EVENTS, $event, 'live');

        $this->assertTrue($response);
    }

    private function mockExperimentEnabledForTransactionIsolation()
    {
        $this->repoMock->shouldReceive('driver')->with('merchant_application')->andReturn($this->merchantApplicationRepoMock);

        $this->merchantApplicationRepoMock->shouldReceive('fetchMerchantApplication')->andReturn(PublicCollection::make([$this->merchantApplicationEntityMock]));

        $this->merchantApplicationEntityMock->shouldReceive('getMerchantId')->andReturn('10000000000000');

        $this->mockSplitzExperiment(self::TRANSACTION_ISOLATION_EXP_ID, self::MERCHANT_ID);
    }

    private function mockSplitzExperiment($experimentId, $id, $variant = 'enable')
    {
        $this->mockSplitz();

        $input = [
            'experiment_id' => $experimentId,
            'id' => $id,
        ];

        $output["response"]["variant"]["name"] = $variant;

        $this->splitzService
            ->shouldReceive('evaluateRequest')
            ->with($input)
            ->byDefault()
            ->andReturn($output);
    }

    private function mockSplitz()
    {
        if (empty($this->splitzService))
        {
            $this->splitzService = Mockery::mock('RZP\Services\SplitzService');

            $this->app->instance('splitzService', $this->splitzService);
        }
    }

    public function mockCache()
    {
        Redis::shouldReceive('connection')->andReturnSelf();
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

    private function getDummyEventDataForMasking()
    {
        return [
            'entity_id' => 'LoHEtckwBAezgi',
            'entity_type' => 'payment',
            'application_id' => 'JGXV2m2t9xhTQy',
            'event' => [
                'service' => 'api-live',
                'owner_id' => 'Kwuji3tCNAS7Zt',
                'owner_type' => 'merchant',
                'context' => [
                    'id' => 'LoHEtckwBAezgi',
                ],
                'name' => 'order.paid',
                'payload' => "{\"entity\":\"event\",\"account_id\":\"acc_BFQ7uQEaa7j2z7\",\"event\":\"order.paid\"," .
                             "\"contains\":[\"payment\",\"order\"],\"payload\":{\"payment\":{\"entity\":{\"id\":\"pay_DESlfW9H8K9uqM\",".
                             "\"entity\":\"payment\",\"amount\":100,\"currency\":\"INR\",\"status\":\"captured\",\"order_id\":\"order_DESlLckIVRkHWj\"," .
                             "\"invoice_id\":null,\"international\":true,\"method\":\"netbanking\",\"amount_refunded\":0,\"refund_status\":null,".
                             "\"captured\":true,\"description\":null,\"card_id\":null,\"bank\":\"HDFC\",\"wallet\":null,\"vpa\":\"test1@okicici\"," .
                             "\"email\":\"gaurav.kumar@example.com\",\"contact\":\"+919876543210\",\"notes\":[],\"fee\":2,\"tax\":0,\"error_code\":null,".
                             "\"error_description\":null,\"created_at\":1567674599}},\"order\":{\"entity\":{\"id\":\"order_DESlLckIVRkHWj\"," .
                             "\"entity\":\"order\",\"amount\":100,\"amount_paid\":100,\"amount_due\":0,\"currency\":\"INR\",\"receipt\":\"rcptid#1\",".
                             "\"offer_id\":null,\"status\":\"paid\",\"attempts\":1,\"notes\":[],\"created_at\":1567674581}}},\"created_at\":1567674606}"
            ]
        ];
    }

    private function getEventPayloadWithMaskedAttributes()
    {
        return [
            "entity"     => "event",
            "account_id" => "acc_BFQ7uQEaa7j2z7",
            "event"      => "order.paid",
            "contains"   => ["payment", "order"],
            "payload"    => [
                "payment" => [
                    "entity" => [
                        "id"                => "pay_DESlfW9H8K9uqM",
                        "entity"            => "payment",
                        "amount"            => 100,
                        "currency"          => "INR",
                        "status"            => "captured",
                        "order_id"          => "order_DESlLckIVRkHWj",
                        "invoice_id"        => null,
                        "international"     => false,
                        "method"            => "netbanking",
                        "amount_refunded"   => 0,
                        "refund_status"     => null,
                        "captured"          => true,
                        "description"       => null,
                        "card_id"           => null,
                        "bank"              => "HDFC",
                        "wallet"            => null,
                        "vpa"               => "xxxxx@okicici",
                        "email"             => "xxxxxxxxxxxxxxxxxxxxxxxx",
                        "contact"           => "xxxxxxxxxxxxxx",
                        "notes"             => [],
                        "fee"               => 0,
                        "tax"               => 0,
                        "error_code"        => null,
                        "error_description" => null,
                        "created_at"        => 1567674599,
                    ]
                ],
                "order"   => [
                    "entity" => [
                        "id"          => "order_DESlLckIVRkHWj",
                        "entity"      => "order",
                        "amount"      => 100,
                        "amount_paid" => 100,
                        "amount_due"  => 0,
                        "currency"    => "INR",
                        "receipt"     => "rcptid#1",
                        "offer_id"    => null,
                        "status"      => "paid",
                        "attempts"    => 1,
                        "notes"       => [],
                        "created_at"  => 1567674581
                    ]
                ]
            ],
            "created_at" => 1567674606
        ];
    }
}
