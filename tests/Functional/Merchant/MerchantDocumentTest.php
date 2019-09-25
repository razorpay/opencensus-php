<?php

namespace RZP\Tests\Functional\Merchant;

use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class MerchantDocumentTest Extends TestCase
{
    use RequestResponseFlowTrait;

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
        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        //Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
            ]);

        $this->updateUploadDocumentData(__FUNCTION__);

        $this->startTest();
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
}
