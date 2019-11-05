<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Jobs\Batch as BatchJob;
use RZP\Models\Merchant\Entity;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Queue;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;

/**
 * @group dns-sensitive
 */
class InstantActivationTest extends TestCase
{
    use BatchTestTrait;
    use MocksDnsTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/InstantActivationTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->setupMockDns();
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

        // Asserting job is being pushed on creation of batch entity for instant activation type.
        Queue::assertPushed(BatchJob::class);
    }

    /**
     * verifies data Migration through batch
     */
    public function testVerifyBatchDataMigration()
    {
        $merchantId = $this->createMerchantDetailFixture();

        $input = [
            [
                DetailEntity::MERCHANT_ID => $merchantId,
            ]
        ];

        $this->createAndPutExcelFileInRequest($input, __FUNCTION__);

        $this->startTest();

        $documents = $this->getDbEntities('merchant_document', ['merchant_id'=>$merchantId], 'live');

        $this->assertCount(2,$documents);

        $documentTypes = [];

        foreach ($documents as $document)
        {
            $documentTypes[] = $document['document_type'];
        }

        $this->assertContains('business_proof_url',$documentTypes);

        $this->assertContains('address_proof_url',$documentTypes);
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
        // merchant detail internally creates merchant entity
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            DetailEntity::BUSINESS_PROOF_URL => 'DJaibu63Y8clYA',
            DetailEntity::ADDRESS_PROOF_URL  => 'DJaibu63Y8clYD',
        ]);

        return $merchantDetail->getMerchantId();
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
