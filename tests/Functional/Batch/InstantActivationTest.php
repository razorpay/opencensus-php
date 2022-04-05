<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Jobs\Batch as BatchJob;
use RZP\Models\Merchant\Entity;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Queue;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class InstantActivationTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/InstantActivationTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    /**
     * verifies queue entries after instant activation batch upload
     */
    public function testCreateBatchOfInstantActivation()
    {
        Queue::fake();

        $entries = $this->getInvalidFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    /**
     * verifies data Migration through batch
     */
    public function testVerifyBatchDataMigration()
    {
        $merchantId = $this->createMerchantDetailFixture();

        $businessPanUrlInput = [
            'merchant_id'   => $merchantId,
            'document_type' => 'business_pan_url',
            'file_store_id' => 'DA6dXJfU4WzeAF',
            'entity_type'   => 'merchant',
            'source'        => Source::UFH,
        ];

        $businessPanDocument = $this->fixtures->on('live')->create(
            'merchant_document',
            $businessPanUrlInput
        );

        $input = [
            [
                DetailEntity::MERCHANT_ID => $merchantId,
            ]
        ];

        $this->createAndPutExcelFileInRequest($input, __FUNCTION__);

        $this->startTest();

        $merchantDocument = $this->getLastEntity('merchant_document', 'live');

        $expectedFileAttributes = [
            'document_type' => 'personal_pan',
            'merchant_id'   => $merchantId,
            'file_store_id' => 'DA6dXJfU4WzeAF',
            'entity_type'   => 'merchant',
            'source'        => Source::UFH,
        ];

        foreach ($expectedFileAttributes as $attributeName => $attributeValue)
        {
            $this->assertEquals($attributeValue, $merchantDocument[$attributeName]);
        }

        $oldDocument = $this->getDbEntity('merchant_document', ['id' => $businessPanDocument->getId()], 'live');

        $this->assertNotNull($oldDocument);
    }

    /**
     * After job execution verifies success and failure count in batch entity
     */
    public function testVerifyBatchForSuccessAndFailureCount()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entity = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $entity['success_count']);
        $this->assertEquals(3, $entity['failure_count']);

        // Processing should have happened immediately in tests as queue are sync basically.
        $this->assertInputFileExistsForBatch($response[Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Entity::ID]);
    }

    /**
     * creates fixtures required for data Migration
     *
     * @return string
     */
    protected function createMerchantDetailFixture(): string
    {
        $mid = '10000000000000';

        // merchant detail internally creates merchant entity
        $this->fixtures->edit('merchant', $mid, [Entity::WEBSITE => 'example.com']);

        $this->fixtures->create('merchant_detail', [
            DetailEntity::MERCHANT_ID   => $mid,
            DetailEntity::BUSINESS_TYPE => '1',
        ]);

        return $mid;
    }

    /**
     * returns Data Migration file entries having (invalid + valid) merchants ids
     *
     * @return array
     */
    protected function getDefaultFileEntries(): array
    {
        $successEntries = [
            DetailEntity::MERCHANT_ID => $this->createMerchantDetailFixture(),
        ];

        $defaultEntries = $this->getInvalidFileEntries();

        array_push($defaultEntries, $successEntries);

        return $defaultEntries;
    }

    /**
     * returns data migration file  entries having invalid merchants ids
     *
     * @param int $noOfEntries
     *
     * @return array
     */
    protected function getInvalidFileEntries(int $noOfEntries = 3): array
    {
        $entries = [];

        for ($index = 1; $index <= $noOfEntries; $index++)
        {
            $entries[] = [DetailEntity::MERCHANT_ID => (string) $index];
        }

        return $entries;
    }
}
