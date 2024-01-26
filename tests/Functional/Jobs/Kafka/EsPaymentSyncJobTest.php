<?php


namespace Functional\Jobs\Kafka;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\Kafka\EsPaymentEntitySync;

class EsPaymentSyncJobTest extends TestCase
{
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
