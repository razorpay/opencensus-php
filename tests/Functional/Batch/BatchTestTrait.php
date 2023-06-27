<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Http\UploadedFile;

use RZP\Models\FileStore;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Header;
use RZP\Models\Batch as BatchModel;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

trait BatchTestTrait
{
    use PaymentTrait;
    use FileHandlerTrait;
    use DbEntityFetchTrait;

    public function createAndPutExcelFileInRequest(array $entries, string $callee)
    {
        $url = $this->writeToExcelFile($entries, 'file', 'files/batch');

        $uploadedFile = $this->createUploadedFile($url);

        $this->testData[$callee]['request']['files']['file'] = $uploadedFile;
    }

    public function createAndPutCsvFileInRequest(array $entries, string $callee)
    {
        $url = $this->writeToCsvFile($entries, 'file', null, 'files/batch');

        $uploadedFile = $this->createUploadedFileCsv($url);

        $this->testData[$callee]['request']['files']['file'] = $uploadedFile;
    }

    public function createAndPutTxtFileInRequest(string $name, string $text, string $callee)
    {
        $url = $this->writeToTextFile($name, $text);

        $uploadedFile = $this->createUploadedFile($url);

        // IRCTC input has nested data payload
        if($name == 'irctc.txt'){
            $this->testData[$callee]['request']['content']['data']['refund'] = $uploadedFile;
        }else{
            $this->testData[$callee]['request']['files']['file'] = $uploadedFile;
        }
    }

    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
                        $url,
                        $fileName,
                        $mime,
                        null,
                        true);
    }

    public function createUploadedFileCsv(string $url, $fileName = 'file.csv'): UploadedFile
    {
        $mime = 'text/csv';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }

    public function assertInputFileExistsForBatch(string $id)
    {
        return $this->assertFileExistsForBatchOfType($id, FileStore\Type::BATCH_INPUT);
    }

    public function assertOutputFileExistsForBatch(string $id)
    {
        return $this->assertFileExistsForBatchOfType($id, FileStore\Type::BATCH_OUTPUT);
    }

    public function assertValidatedFileExistsForBatch(string $id)
    {
        return $this->assertFileExistsForBatchOfType($id, FileStore\Type::BATCH_VALIDATED);
    }

    public function assertFileExistsForBatchOfType(string $id, string $type)
    {
        $file = $this->getFileForBatchOfType($id, $type);

        $this->assertNotNull($file);

        return $file;
    }

    protected function getFileForBatchOfType(string $id, string $type)
    {
        BatchModel\Entity::verifyIdAndSilentlyStripSign($id);

        $file = FileStore\Entity::where(FileStore\Entity::TYPE, $type)
                                ->where(FileStore\Entity::ENTITY_TYPE, 'batch')
                                ->where(FileStore\Entity::ENTITY_ID, $id)
                                ->first();

        return $file;
    }

    /*
     * Retries failed batch by Id.
     */
    protected function retryFailedBatch($id)
    {
        $this->ba->adminAuth();

        $request = [
            'method' => 'POST',
            'url'    => '/batches/' . $id . '/process'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * Assert the status of processed batch.
     */
    protected function assertBatchStatus(string $expected)
    {
        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($expected, $batch['status']);

        return $batch;
    }
    public function createAndPutTwoSheetsExcelFileInRequest(array $entries, string $callee)
    {
        $url = $this->writeToExcelFile($entries, 'file', 'files/batch',['Sheet 1', 'Sheet 2'], 'xlsx');
        $uploadedFile = $this->createUploadedFile($url);
        $this->testData[$callee]['request']['files']['file'] = $uploadedFile;
    }

    public function getPartnerSubmerchantInviteCapitalBulkEntries(): array
    {
        return [
            [
                Header::BUSINESS_NAME           => "Erebor Travels",
                Header::ACCOUNT_NAME            => "Erebor Travels",
                Header::CONTACT_MOBILE          => "9999999999",
                Header::EMAIL                   => "testing.capital@razorpay.com",
                Header::ANNUAL_TURNOVER_MIN     => "100000",
                Header::ANNUAL_TURNOVER_MAX     => "1000000",
                Header::COMPANY_ADDRESS_LINE_1  => "Erebor Travels Pvt. Ltd.",
                Header::COMPANY_ADDRESS_LINE_2  => "Major Industry Area",
                Header::COMPANY_ADDRESS_CITY    => "Akola",
                Header::COMPANY_ADDRESS_STATE   => "Maharashtra",
                Header::COMPANY_ADDRESS_COUNTRY => "IN",
                Header::COMPANY_ADDRESS_PINCODE => "444001",
                Header::BUSINESS_TYPE           => "PROPRIETORSHIP",
                Header::BUSINESS_VINTAGE        => "BETWEEN_6MONTHS_12MONTHS",
                Header::GSTIN                   => "37ABCBS1234N1Z1",
                Header::PROMOTER_PAN            => "ABCPS1234N",
            ]
        ];
    }

    public function getFormBuilderBatchEntries(): array
    {
        return [
            [
                'Email' => 'test@test.com',
                'Phone' => '1231231233',
                'contact' => '1231231233',
                'DOB' => '1231323',
                'item1' => '121',
                'testName2' => '123',
                'Address 2' => '123'
            ]
        ];
    }

    public function getRecurringAxisChargeBatch()
    {
        return [
            [
                Header::RECURRING_CHARGE_AXIS_SLNO                      => '1',
                Header::RECURRING_CHARGE_AXIS_URNNO                     => '11223344',
                Header::RECURRING_CHARGE_AXIS_FOLIO_NO                  => '91000xxxxxx',
                Header:: RECURRING_CHARGE_AXIS_SCHEMECODE               => 'AF',
                Header:: RECURRING_CHARGE_AXIS_TRANSACTION_NO           => '86XXX',
                Header:: RECURRING_CHARGE_AXIS_INVESTOR_NAME            => 'Srinivas M',
                Header:: RECURRING_CHARGE_AXIS_PURCHASE_DAY             => '8',
                Header:: RECURRING_CHARGE_AXIS_PUR_AMOUNT               => '1000',
                Header:: RECURRING_CHARGE_AXIS_BANK_ACCOUNTNO           => '02951XXXXXXX',
                Header:: RECURRING_CHARGE_AXIS_PURCHASE_DATE            => '2/15/21',
                Header:: RECURRING_CHARGE_AXIS_BATCH_REF_NUMBER         => '1',
                Header:: RECURRING_CHARGE_AXIS_BRANCH                   => 'RPXX',
                Header:: RECURRING_CHARGE_AXIS_TR_TYPE                  => 'SIN',
                Header:: RECURRING_CHARGE_AXIS_UMRNNO_OR_TOKENID        => 'HDFC60000XXXXXXXX',
                Header:: RECURRING_CHARGE_AXIS_CREDIT_ACCOUNT_NO        => '91602XXXXXX',
            ],
        ];
    }
}
