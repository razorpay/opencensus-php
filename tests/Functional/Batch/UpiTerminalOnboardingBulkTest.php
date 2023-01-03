<?php

namespace RZP\Tests\Functional\Batch;

use Mockery;
use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class UpiTerminalCreationBulkTest extends TestCase
{
    use BatchTestTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiTerminalOnboardingBulkTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testBulkTerminalCreationValidateFile()
    {
        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Batch\Header::UPI_TERMINAL_ONBOARDING_MERCHANT_ID          => '10NodalAccount',
                Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY              => 'upi_juspay',
                Batch\Header::UPI_TERMINAL_ONBOARDING_VPA                  => 'umesh.rzp@abfspay',
                Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY_TERMINAL_ID  => 'parentMerchantId',
                Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY_ACCESS_CODE  => 'parentChannelId',
                Batch\Header::UPI_TERMINAL_ONBOARDING_EXPECTED             => 1,
                Batch\Header::UPI_TERMINAL_ONBOARDING_VPA_HANDLE           => '',
                Batch\Header::UPI_TERMINAL_ONBOARDING_RECURRING            => '',
                Batch\Header::UPI_TERMINAL_ONBOARDING_MCC                  => '',
                Batch\Header::UPI_TERMINAL_ONBOARDING_CATEGORY2            => '',
                Batch\Header::UPI_TERMINAL_ONBOARDING_MERCHANT_TYPE        => 'online',
            ],
        ];
    }

    public function testBulkUpiTerminalOnboardingCompletelyMigratedBatchUpload()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $batch = Mockery::mock('RZP\Services\Mock\BatchMicroService')->makePartial();

        $this->app->instance('batchService', $batch);

        $expectedOutputData = $this->testData[__FUNCTION__]['expected_file_data'];

        $batch->shouldReceive('isCompletelyMigratedBatchType')
            ->andReturnUsing(function (string $type)
            {
                return true;
            });

        $batch->shouldReceive('forwardToBatchServiceRequest')
            ->andReturnUsing(function (array $input, $merchant, $ufhFile) use ($expectedOutputData)
            {
                $rows = $this->parseCsvFile($ufhFile->getFullFilePath());

                $this->assertArraySelectiveEquals($rows, $expectedOutputData);

                return [
                    'id'               => 'Be6Ob5J8kaMV6o',
                    'created_at'       => 1590521524,
                    'updated_at'       => 1590521524,
                    'entity_id'        => '100000Razorpay',
                    'name'             =>  null,
                    'batch_type_id'    => 'upi_terminal_onboarding',
                    'type'             => 'upi_terminal_onboarding',
                    'is_scheduled'     => false,
                    'upload_count'     => 0,
                    'total_count'      => 1,
                    'failure_count'    => 0,
                    'success_count'    => 0,
                    'amount'           => 0,
                    'attempts'         => 0,
                    'status'           => 'created',
                    'processed_amount' => 0
                ];
            });

        $this->startTest();
    }

    // tests route exosed to batch service
    public function testBulkUpiTerminalOnboardingForBatchService()
    {
        $this->ba->appAuth();

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));
    }
}
