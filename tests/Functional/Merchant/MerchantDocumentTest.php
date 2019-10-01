<?php

namespace RZP\Tests\Functional\Merchant;

use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class MerchantDocumentTest Extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantDocumentTestData.php';

        parent::setUp();
    }

    public function testDeleteDocument()
    {
        $merchantDocument = $this->fixtures->create('merchant_document');

        //request edited
        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], 'doc_' . $merchantDocument['id']);

        $this->testData[__FUNCTION__]['request'] = $request;

        //response edited
        $response = $this->testData[__FUNCTION__]['response'];

        $response['content']['id'] = sprintf($response['content']['id'], 'doc_' . $merchantDocument['id']);

        $this->testData[__FUNCTION__]['response'] = $response;

        $this->ba->proxyAuth('rzp_test_' . $merchantDocument['merchant_id']);

        $this->startTest();
    }

    public function testDeleteDocumentIdNotValid()
    {
        $merchantDocument = $this->fixtures->create('merchant_document');

        $this->ba->proxyAuth('rzp_test_' . $merchantDocument['merchant_id']);

        $this->startTest();
    }

    public function testDeleteDocumentError()
    {
        $merchantDocument = $this->fixtures->create('merchant_document');

        $this->ba->proxyAuth('rzp_test_' . $merchantDocument['merchant_id']);

        $this->startTest();
    }

    public function testFileUpload()
    {
        $this->ba->proxyAuth('rzp_test_' . '1cXSLlUU8V9sXl');

        //Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '1cXSLlUU8V9sXl',
            ]);

        $this->updateUploadDocumentData(__FUNCTION__);

        $this->startTest();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayNotHasKey('promoter_address_url',$content['verification']['required_fields']);
    }

    public function testFileUploadFileNotExist()
    {
        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        //Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
            ]);

        $this->startTest();
    }

    public function testFileUploadFormLocked()
    {
        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        //Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'locked'      => true,
            ]);

        $this->updateUploadDocumentData(__FUNCTION__);

        $this->startTest();
    }

    public function testFileUploadFileTypeNotSupported()
    {
        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        //Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
            ]);

        $this->updateUploadXLSXDocument(__FUNCTION__);

        $this->startTest();
    }

    protected function updateUploadDocumentData(string $callee)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../Storage/a.png',
            'a.png',
            'image/png',
            filesize(__DIR__ . '/../Storage/a.png'),
            null,
            true);
    }

    protected function updateUploadXLSXDocument(string $callee)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../Batch/files/input.xlsx',
            'input.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            filesize(__DIR__ . '/../Batch/files/input.xlsx'),
            null,
            true);
    }

    public function testFetchMerchantDocuments()
    {
        $this->ba->proxyAuth('rzp_live_' . '1cXSLlUU8V9sXl');

        $this->createMerchantDocuementAndFIleStoreEntity();

        $this->startTest();
    }

    public function testFetchMerchantDocumentsByAdmin()
    {
        [$document, $fileStore] = $this->createMerchantDocuementAndFIleStoreEntity('test');

        $merchant = $document->merchant;

        // allow admin to access merchant
        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth('test');

        $this->startTest();
    }

    protected function createMerchantDocuementAndFIleStoreEntity(string $mode = 'live'): array
    {
        $document = $this->fixtures->on('live')->create('merchant_document', [
            'merchant_id'   => '1cXSLlUU8V9sXl',
            'document_type' => 'Address_proof_url',
            'file_store_id' => 'DM6dXJfU4WzeAF',
            'entity_type'   => 'merchant'
        ]);

        $fileStore = $this->fixtures->on($mode)->create('file_store', [
            'id'          => 'DM6dXJfU4WzeAF',
            'merchant_id' => '1cXSLlUU8V9sXl',
            'type'        => 'Address_proof_url',
            'entity_type' => null,
            'name'        => 'batch/validated/10000000000002',
            'location'    => 'batch/validated/10000000000002.pdf',
        ]);

        return [$document, $fileStore];
    }
}
