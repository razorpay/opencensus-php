<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;
use RZP\Models\Batch\Header;
use RZP\Tests\Functional\Fixtures\Entity\Org as Org;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;

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

    public function getDefaultFileEntries()
    {
        return [
            [
                Header::ENTITY_FROM_ID => Org::ADMIN_ROLE,
                Header::ENTITY_TO_ID   => '10000000000000',
            ],
            [
                Header::ENTITY_FROM_ID => Org::SUPER_ADMIN,
                Header::ENTITY_TO_ID   => $this->fixtures->create('merchant'),
            ],
            [
                Header::ENTITY_FROM_ID => Org::ADMIN_ROLE,
                Header::ENTITY_TO_ID   => $this->fixtures->create('merchant'),
            ],
        ];
    }
}

