<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch;
use RZP\Models\Batch\Header;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Queue;
use RZP\Tests\Functional\Fixtures\Entity\Org as Org;

class EntityMappingTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/EntityMappingTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testCreateEntityMappingBatchQueued()
    {
        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Just asserting that job is being pushed on creation of batch entity
        // for payment link type.

        Queue::assertPushed(BatchJob::class);
    }

    public function testCreateEntityMappingBatchStatus()
    {
        $entries = $this->getDefaultFileEntries();
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $batch = $this->getLastEntity('batch', true);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);

        $admin = $this->getDbEntityById('admin', Org::MAKER_ADMIN);

        $this->assertEquals(2, $admin->merchants()->count());
    }

    public function getDefaultFileEntries()
    {
        return [
            [
                Header::ENTITY_FROM_ID => Org::MAKER_ADMIN,
                Header::ENTITY_TO_ID   => '10000000000000',
            ],
            [
                Header::ENTITY_FROM_ID => Org::SUPER_ADMIN,
                Header::ENTITY_TO_ID   => $this->fixtures->create('merchant')->getId(),
            ],
            [
                Header::ENTITY_FROM_ID => Org::MAKER_ADMIN,
                Header::ENTITY_TO_ID   => $this->fixtures->create('merchant')->getId(),
            ],
        ];
    }
}

