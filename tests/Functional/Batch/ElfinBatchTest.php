<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Tests\Functional\TestCase;

class ElfinBatchTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/ElfinBatchTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateBatchOfElfinType()
    {
        $rows = $this->testData[__FUNCTION__ . 'FileRows'];
        $this->createAndPutExcelFileInRequest($rows, __FUNCTION__);

        $response = $this->startTest();

        $entity = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $entity['success_count']);
        $this->assertEquals(0, $entity['failure_count']);

        $this->assertInputFileExistsForBatch($response[Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Entity::ID]);
    }
}
