<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Jobs\Batch as BatchJob;
use RZP\Models\Merchant\Entity;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Queue;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

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
     * verifies whitelist merchant L1 submission through batch
     */
    public function testVerifyBatchForWhitelistActivation()
    {
        $merchantId = $this->createWhiteListMerchantFixture();

        $input = [
            [
                DetailEntity::MERCHANT_ID => $merchantId,
            ]
        ];

        $this->createAndPutExcelFileInRequest($input, __FUNCTION__);

        $this->startTest();

        $liveMerchant        = $this->getDbEntityById('merchant', $merchantId, 'live');
        $liveMerchantDetails = $liveMerchant->merchantDetail;

        $this->assertSame(true, $liveMerchant->isActivated());
        $this->assertSame(true, $liveMerchant->getHoldFunds());
        $this->assertSame('8931', $liveMerchant->getCategory());
        $this->assertSame('others', $liveMerchant->getCategory2());

        $this->assertSame('whitelist', $liveMerchantDetails->getActivationFlow());
        $this->assertSame('instantly_activated', $liveMerchantDetails->getActivationStatus());
    }

    /**
     * verifies greylist merchant L1 submission through batch
     */
    public function testVerifyBatchForGreylistActivation()
    {
        $merchantId = $this->createGreyListMerchantFixture();

        $input = [
            [
                DetailEntity::MERCHANT_ID => $merchantId,
            ]
        ];

        $this->createAndPutExcelFileInRequest($input, __FUNCTION__);

        $this->startTest();

        $liveMerchant        = $this->getDbEntityById('merchant', $merchantId, 'live');
        $liveMerchantDetails = $liveMerchant->merchantDetail;

        $this->assertSame(false, $liveMerchant->isActivated());
        $this->assertSame(false, $liveMerchant->getHoldFunds());
        $this->assertSame('5399', $liveMerchant->getCategory());
        $this->assertSame('others', $liveMerchant->getCategory2());

        $this->assertSame('greylist', $liveMerchantDetails->getActivationFlow());
        $this->assertSame(null, $liveMerchantDetails->getActivationStatus());
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
     * File row count is more than allowed limit
     */
    public function testCreateBatchWithMoreThanAllowedEntries()
    {
        $entries = $this->getInvalidFileEntries(1001);

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    /**
     * creates entities require for whitelist flow L1 submission
     *
     * @return string
     */
    protected function createWhiteListMerchantFixture()
    {
        return $this->createMerchantFixture('financial_services',
                                            'accounting');
    }

    /**
     * creates entities require  for greylist flow   L1 submission
     *
     * @return string merchantId
     */
    protected function createGreyListMerchantFixture()
    {
        return $this->createMerchantFixture('others',
                                            null);
    }

    /**
     * creates fixtures required for instant activation
     *
     * @param string      $businessCategory
     * @param string|null $businessSubCategory
     *
     * @return string
     */
    protected function createMerchantFixture(string $businessCategory = "others",
                                             string $businessSubCategory = null): string
    {
        $plan = $this->fixtures->create('pricing');

        // merchant detail internally creates merchant entity
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            DetailEntity::BUSINESS_CATEGORY    => $businessCategory,
            DetailEntity::BUSINESS_SUBCATEGORY => $businessSubCategory,
            DetailEntity::PROMOTER_PAN         => 'ABCDE1234E',
            DetailEntity::BUSINESS_NAME        => 'test',
            DetailEntity::BUSINESS_WEBSITE     => 'https://www.example.com',
            DetailEntity::BUSINESS_TYPE        => '1',
            DetailEntity::BUSINESS_DBA         => 'test',
        ]);

        $this->fixtures->edit('merchant', $merchantDetail->getMerchantId(), [
            Entity::PRICING_PLAN_ID => $plan->getPlanId()
        ]);

        $this->fixtures->edit('methods', $merchantDetail->getMerchantId(), [
            DetailEntity::MERCHANT_ID => $merchantDetail->getMerchantId(),
            'disabled_banks'          => [],
            'banks'                   => '[]',
            'netbanking'              => 0,
            'debit_card'              => 0,
            'credit_card'             => 0,
        ]);

        return $merchantDetail->getMerchantId();
    }

    /**
     * returns instant activation file entries having (invalid + valid) merchants ids
     *
     * @return array
     */
    protected function getDefaultFileEntries(): array
    {
        $successEntries = [
            DetailEntity::MERCHANT_ID => $this->createGreyListMerchantFixture(),
        ];

        $defaultEntries = $this->getInvalidFileEntries();

        array_push($defaultEntries, $successEntries);

        return $defaultEntries;
    }

    /**
     * returns instant activation file entries having invalid merchants ids
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
