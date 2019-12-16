<?php

namespace RZP\Tests\Functional\Merchant;

use Config;
use RZP\Constants\Mode;
use RZP\Services\RazorXClient;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\Constants;
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

        $this->fixtures->create('merchant_detail',['merchant_id' => '10000000000000']);

        //request edited
        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], 'doc_' . $merchantDocument['id']);

        $this->testData[__FUNCTION__]['request'] = $request;

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
                'promoter_pan_name' => 'XYZ',
            ]);

        $this->updateUploadDocumentData(__FUNCTION__);

        $this->startTest();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayNotHasKey('promoter_address_url',$content['verification']['required_fields']);

        $merchantDocumentEntry = $this->getLastEntity('merchant_document', true, 'test');

        $fileStoreEntry = $this->getDbEntityById('file_store', $merchantDocumentEntry['file_store_id'], 'test');

        $this->assertTrue(substr($fileStoreEntry->getName(), -2) === "/a");
    }

    public function testFileUploadDocumentTypeInvalid()
    {
        $this->ba->proxyAuth('rzp_test_' . '1cXSLlUU8V9sXl');

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

    public function testDocumentUploadAndCheckOcrVerificationStatusSuccess()
    {
        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'       => '10000000000000',
                'promoter_pan_name' => 'ABCDE FGHIJ',
                'business_type'     => 2,
            ]);

        $ocrResponseTypes = [
            Constants::PASSPORT_FRONT,
            Constants::AADHAR_FRONT,
            Constants::VOTER_ID_FRONT,
            Constants::AADHAAR_FRONT_COMPLETE
        ];

        $this->mockRazorX(__FUNCTION__, 'non_registered_onboarding', 'on');

        $documentType = Constants::VOTER_ID_FRONT;

        $this->updateUploadDocumentData(__FUNCTION__);

        foreach ($ocrResponseTypes as $ocrResponseType)
        {
            Config::set('applications.mozart.poa_ocr_response_type', $ocrResponseType);

            $testData = &$this->testData[__FUNCTION__];

            $testData['request']['content']['document_type'] = $documentType;

            $testData['response']['content']['documents'][$documentType] = [];

            $response = $this->startTest($testData);

            $merchantDocumentDb = $this->getDbEntityById('merchant_document', $response['documents'][$documentType][0]['id']);

            $this->assertEquals($merchantDocumentDb['ocr_verify'], 'verified');
        }
    }

    public function testDocumentUploadAndCheckOcrVerificationStatusFailedForException()
    {

        $testDataKeyName = 'testDocumentUploadAndCheckOcrVerificationStatusSuccess';

        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'       => '10000000000000',
                'promoter_pan_name' => 'ABCDE FGHIJ',
                'business_type'     => 2,
            ]);

        $this->mockRazorX($testDataKeyName, 'non_registered_onboarding', 'on');

        $this->updateUploadDocumentData($testDataKeyName);

        Config::set('applications.mozart.poa_ocr_response_type', Constants::FAILURE);

        $testData = &$this->testData[$testDataKeyName];

        $documentType = Constants::VOTER_ID_FRONT;

        $testData['request']['content']['document_type'] = $documentType;

        $testData['response']['content']['documents'][$documentType] = [];

        $response = $this->startTest($testData);

        $merchantDocumentDb = $this->getDbEntityById('merchant_document', $response['documents'][$documentType][0]['id']);

        $this->assertEquals($merchantDocumentDb['ocr_verify'], 'failed');
    }

    public function testDocumentUploadAndCheckOcrVerificationStatusFailed()
    {
        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'       => '10000000000000',
                'promoter_pan_name' => 'XYZ',
                'business_type'     => 11,
            ]);

        $this->mockRazorX(__FUNCTION__, 'non_registered_onboarding', 'on');

        $this->updateUploadDocumentData(__FUNCTION__);

        $response = $this->startTest();

        $merchantDocumentDb = $this->getDbEntityById('merchant_document', $response['documents']['aadhar_front'][0]['id']);

        $this->assertEquals($merchantDocumentDb['ocr_verify'], 'failed');
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

    public function mockRazorX(string $functionName, string $featureName, string $variant)
    {
        $testData = &$this->testData[$functionName];

        $uniqueLocalId = RazorXClient::getLocalUniqueId('10000000000000', $featureName, Mode::TEST);

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => '{"' . $uniqueLocalId . '":"' . $variant . '"}'];
    }
}
