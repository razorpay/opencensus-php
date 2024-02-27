<?php


namespace Functional\Jobs\Kafka;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\Kafka\EsPaymentEntitySync;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class EsPaymentSyncJobTest extends TestCase
{
    use DbEntityFetchTrait;

    private $repoManager;
    private $repo;
    private $esRepo;
    private $payload;
    private $documents;
    const MAX_BATCH_SIZE = 1000;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock the dependencies
        $this->repoManager = Mockery::mock('RZP\Base\RepositoryManager');
        $this->esRepo = Mockery::mock('RZP\Models\Base\EsRepository');
        $this->app['rzp.mode'] = 'test';

        // Set up the payload
        $this->payload = [
            'action' => 'create',
            'entity' => 'payment',
            'id' => 123,
            'rearch' => false,
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testHandleSyncApiEntities()
    {
        $this->repoManager->shouldReceive('driver')->andReturn($this->repo);
        $this->esRepo->shouldReceive('bulkUpdate')->with($this->documents)->andReturn(['errors' => false]);

        $job = new EsPaymentEntitySync($this->payload);

        $job->handle();

        // Assertions
        $this->assertNotNull($job->getPayload());
    }

    public function testHandleSyncsWithRearchEntities()
    {

        $this->payload['rearch'] = true;

        $this->repoManager->shouldReceive('driver')->andReturn($this->repo);
        $this->esRepo->shouldReceive('bulkUpdate')->with($this->documents)->andReturn(['errors' => false]);

        $job = new EsPaymentEntitySync($this->payload);

        $job->handle();

        $this->assertNotNull($job->getPayload());
    }

    public function testHandleRiskNotification()
    {
        $this->fixtures->payment->createStatusCreated(
            [
                'cps_route' => 7,
                'method'    => 'upi',
                'gateway'   => 'upi_icici'
            ]
        );

        $payment = $this->getDbLastPayment();

        $this->payload = [
            "action" => "risk_notification",
            "entity" =>  "payment",
            "id" => $payment->getPublicId(),
            "rearch" => true,
            "risk_data" => [
                "merchant_id" =>  $payment->getMerchantId(),
                "risk" => [
                    "fraud_type" => "suspected",
                    "reason" => "PAYMENT_SUSPECTED_FRAUD_BY_SHEILD",
                    "risk_score" => 0,
                    "triggered_rules" => [
                        "review" => [
                            [
                            "id" => 636,
                            "rule_code" => "INTERNATIONAL_DDOS_RULE",
                            "rule_description" => "Velocity rule for UPI transactions on hourly basis",
                            "rule_id" => "rule_LDyogYPu4w6J9C",
                            ],
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals(7, $payment->getCpsRoute());

        $this->repoManager->shouldReceive('driver')->andReturn($this->repo);
        $this->esRepo->shouldReceive('bulkUpdate')->with($this->documents)->andReturn(['errors' => false]);

        $job = new EsPaymentEntitySync($this->payload);

        $job->handle();

        $this->assertNotNull($job->getPayload());
    }

    public function testInSufficientPayload()
    {

        $dummyPayload = [
            EsPaymentEntitySync::ACTION => 'create',
            EsPaymentEntitySync::ENTITY => 'payment',
        ];

        // Mock the repo and esRepo
        $this->repoManager->shouldReceive('payment')->andReturn($this->repo);
        $this->esRepo->shouldReceive('bulkUpdate')->with($this->documents)->andThrow(new \Exception('Test exception'));

        $job = new EsPaymentEntitySync($dummyPayload);

        // Act
        $job->handle();

        // Assertions
        $this->assertNotNull($job->getPayload());


    }
}
