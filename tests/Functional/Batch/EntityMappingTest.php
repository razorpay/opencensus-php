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

    protected function setUp(): void
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

        // Asserts association of creator for batch.
        // In this case because this is app auth neither admin nor user exists.
        $batch = $this->getDbLastEntity('batch');
        $this->assertEquals(null, $batch->getCreatorId());
        $this->assertEquals(null, $batch->getCreatorType());
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

        $merchantIds = $admin->merchants()->get()->getIds();

        $this->assertContains('10000000000000', $merchantIds);
        $this->assertContains($entries[2][Header::ENTITY_TO_ID], $merchantIds);

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
