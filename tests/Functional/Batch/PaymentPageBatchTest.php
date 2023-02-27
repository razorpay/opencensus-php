<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class PaymentPageBatchTest extends Testcase
{
    use TestsWebhookEvents;
    use VirtualAccountTrait;
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PaymentPageBatchTestData.php';

        parent::setUp();
    }

    public function createAndPutExcelFileInRequest(array $entries, string $callee)
    {
        $url = $this->writeToExcelFile($entries, 'file', 'files/batch');

        $uploadedFile = $this->createUploadedFile($url);

        $this->testData[$callee]['request']['files']['file'] = $uploadedFile;
    }

    public function createPaymentPageForBatchFileUpload()
    {
        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);
        $resp = $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'file_upload_page');

        return $resp['id'];
    }

    public function testValidatePaymentPage()
    {

        $resp = $this->createPaymentPageForBatchFileUpload();

        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $this->testData[__FUNCTION__]['request']['content']['config']['payment_page_id'] = $resp;

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testValidatePaymentPageMissingData()
    {

        $resp = $this->createPaymentPageForBatchFileUpload();

        $this->ba->proxyAuth();

        $entries = $this->getFileEntriesWithMissingData();

        $this->testData[__FUNCTION__]['request']['content']['config']['payment_page_id'] = $resp;

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }


    protected function getDefaultFileEntries()
    {
        return [
            [
                'Email' => 'paridhi@gmail.com',
                'Phone' => '1234567890',
                'Roll No' => '123',
                'amount' => 10,
            ],
            [
                'Email' => 'paridhi@gmail.com',
                'Phone' => '1234567890',
                'Roll No' => '123',
                'amount' => 20,
            ],
        ];
    }

    protected function getFileEntriesWithMissingData()
    {
        return [
            [
                'Email' => 'paridhi@gmail.com',
                'Phone' => '1234567890',
                'Roll No' => '',
            ],
            [
                'Email' => 'paridhi@gmail.com',
                'Phone' => '1234567890',
                'Roll No' => '123',
            ],
        ];
    }

    public function testPaymentPageBatchCreate()
    {

        $resp = $this->createPaymentPageForBatchFileUpload();

        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $this->testData[__FUNCTION__]['request']['content']['config']['payment_page_id'] = $resp;

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $resp = $this->startTest();
    }


}
