<?php


namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch;
use RZP\Jobs\Batch as BatchJob;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class McMastercardIINBatchTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/McMastercardIINBatchTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

//    public function testBulkIinMcUpdate()
//    {
//        $entries = $this->getFileEntries();
//
//        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);;
//
//        $this->ba->adminAuth();
//
//        $this->startTest();
//
//        $batch = $this->getLastEntity('batch', true);
//
//        $this->assertEquals(3, $batch['processed_count']);
//        $this->assertEquals(2, $batch['success_count']);
//        $this->assertEquals(1, $batch['failure_count']);
//        $this->assertEquals('processed', $batch['status']);
//
//        $iin = $this->getEntityById('iin', '230798', true);
//
//        $this->assertEquals("The Karur Vysya Bank LTD", $iin['issuer_name']);
//
//    }

    public function testBulkIinViaBatchService()
    {
        $this->ba->batchAppAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $data = $this->getFileEntries();

        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content'][$index] = $data[$index];
        }

        $this->testData[__FUNCTION__]['request']['content'][0]['idempotent_id'] = 'batch_abc123';
        $this->testData[__FUNCTION__]['request']['content'][1]['idempotent_id'] = 'batch_abc124';
        $this->testData[__FUNCTION__]['request']['content'][2]['idempotent_id'] = 'batch_abc125';


        $this->startTest();
    }

    protected function getFileEntries()
    {
        $data = [
            [
                Batch\Header::IIN_MC_MASTERCARD_COMPANY_ID          => "",
                Batch\Header::IIN_MC_MASTERCARD_COMPANY_NAME        => "Indian Bank",
                Batch\Header::IIN_MC_MASTERCARD_ACCOUNT_RANGE_FROM  => "5533870000",
                Batch\Header::IIN_MC_MASTERCARD_ACCOUNT_RANGE_TO    => "5533870000",
                Batch\Header::IIN_MC_MASTERCARD_BRAND_PRODUCT_CODE  => "MDS",
                Batch\Header::IIN_MC_MASTERCARD_BRAND_PRODUCT_NAME  => "MDS  -Debit MasterCard",
                Batch\Header::IIN_MC_MASTERCARD_ACCEPTANCE_BRAND    => "DMC",
                Batch\Header::IIN_MC_MASTERCARD_ICA                 => "20041",
                Batch\Header::IIN_MC_MASTERCARD_COUNTRY             => "IND",
                Batch\Header::IIN_MC_MASTERCARD_REGION              => "A/P",
            ],
            [
                Batch\Header::IIN_MC_MASTERCARD_COMPANY_ID          => "",
                Batch\Header::IIN_MC_MASTERCARD_COMPANY_NAME        => "The Karur Vysya Bank LTD",
                Batch\Header::IIN_MC_MASTERCARD_ACCOUNT_RANGE_FROM  => "2307980000",
                Batch\Header::IIN_MC_MASTERCARD_ACCOUNT_RANGE_TO    => "2307989999",
                Batch\Header::IIN_MC_MASTERCARD_BRAND_PRODUCT_CODE  => "ACQ",
                Batch\Header::IIN_MC_MASTERCARD_BRAND_PRODUCT_NAME  => "MCC - Mixed Product",
                Batch\Header::IIN_MC_MASTERCARD_ACCEPTANCE_BRAND    => "MCC",
                Batch\Header::IIN_MC_MASTERCARD_ICA                 => "19969",
                Batch\Header::IIN_MC_MASTERCARD_COUNTRY             => "",
                Batch\Header::IIN_MC_MASTERCARD_REGION              => "A/P",

            ],
            [
                Batch\Header::IIN_MC_MASTERCARD_COMPANY_ID          => "",
                Batch\Header::IIN_MC_MASTERCARD_COMPANY_NAME        => "The Karur Vysya Bank LTD",
                Batch\Header::IIN_MC_MASTERCARD_ACCOUNT_RANGE_FROM  => "2306200000",
                Batch\Header::IIN_MC_MASTERCARD_ACCOUNT_RANGE_TO    => "2306209999",
                Batch\Header::IIN_MC_MASTERCARD_BRAND_PRODUCT_CODE  => "ACQ",
                Batch\Header::IIN_MC_MASTERCARD_BRAND_PRODUCT_NAME  => "MCC - Mixed Product",
                Batch\Header::IIN_MC_MASTERCARD_ACCEPTANCE_BRAND    => "",
                Batch\Header::IIN_MC_MASTERCARD_ICA                 => "19969",
                Batch\Header::IIN_MC_MASTERCARD_COUNTRY             => "IND",
                Batch\Header::IIN_MC_MASTERCARD_REGION              => "A/P",
            ]
        ];

        return $data;
    }
}
