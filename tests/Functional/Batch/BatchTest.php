<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Tests\Functional\TestCase;

/**
 * Class: BatchTest
 * Include only basic tests cases per type. If requires per type specific test
 * cases consider adding specific test class.
 */
class BatchTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/BatchTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateBatchOfContactType()
    {
        // Creates a in-active contact to assert that it is not used in the flow.
        $this->fixtures->create(
            'contact',
            [
                'id'           => '100TestContact',
                'active'       => false,
                'type'         => 'vendor',
                'name'         => 'Another Example',
                'email'        => 'another@example.com',
                'contact'      => '9988998899',
                'reference_id' => null,
            ]);

        $entries = $this->getFileEntries(__FUNCTION__);

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $contacts = $this->getDbEntities('contact');

        $this->assertCount(3 + 1, $contacts);
        $this->assertCount(2, $contacts->where('name', 'Another Example'));
    }

    protected function getFileEntries(string $callee): array
    {
        return $this->testData["{$callee}RequestFileEntries"];
    }
}
