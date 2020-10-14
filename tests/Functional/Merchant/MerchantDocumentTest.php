<?php

namespace RZP\Tests\Functional\Merchant;

use Config;
use RZP\Constants\Mode;
use RZP\Services\RazorXClient;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Document\Source;
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
        $merchantDocument = $this->fixtures->create('merchant_document', [
            'document_type' => 'business_proof_url',
        ]);

        $this->fixtures->create('merchant_detail',['merchant_id' => '10000000000000']);

        //request edited
        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], 'doc_' . $merchantDocument['id']);

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->ba->proxyAuth('rzp_test_' . $merchantDocument['merchant_id']);

        $response = $this->startTest();

        $this->assertContains('business_proof_url', $response['verification']['required_fields']);
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

    public function testDocumentUpload()
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

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayNotHasKey('promoter_address_url',$content['verification']['required_fields']);

    }

    public function testDocumentUploadToUFH()
    {
        $this->testDocumentUpload();

        $merchantDocumentEntry = $this->getLastEntity('merchant_document', true, 'test');

        $this->assertEquals($merchantDocumentEntry['source'], Source::UFH);
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

    public function testDocUploadAndCheckOcrStatusSuccessForRegistered()
    {
        $this->uploadDocAndCheckOcrSuccess(1, 'success', 'verified');
    }

    public function testDocUploadAndCheckOcrStatusSuccessForUnregistered()
    {
        $this->uploadDocAndCheckOcrSuccess(2, 'success', 'verified');
    }

    public function testDocUploadAndCheckOcrStatusNotMatchedForRegistered()
    {
        $this->uploadDocAndCheckOcrSuccess(1, 'success', 'not_matched', 'random name');
    }

    public function testDocUploadAndCheckOcrStatusNotMatchedForUnregistered()
    {
        $this->uploadDocAndCheckOcrSuccess(2, 'success', 'not_matched', 'random name');
    }

    public function testDocUploadAndCheckOcrStatusIncorrectDetailsForRegistered()
    {
        $this->uploadDocAndCheckOcrSuccess(1, 'incorrect_details', 'incorrect_details');
    }

    public function testDocUploadAndCheckOcrStatusIncorrectDetailsForUnregistered()
    {
        $this->uploadDocAndCheckOcrSuccess(2, 'incorrect_details', 'incorrect_details');
    }

    public function uploadDocAndCheckOcrSuccess(int $businessType,
                                                string $octResponseStatus,
                                                string $ocrVerificationStatus,
                                                string $panName = 'ABCDE FGHIJ')
    {
        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'       => '10000000000000',
                'promoter_pan_name' => $panName,
                'business_type'     => $businessType,
            ]);

        $ocrResponseTypes = [
            Type::AADHAAR,
            Type::VOTERS_ID,
            Type::PASSPORT,
        ];

        $documentType = Constants::VOTER_ID_FRONT;

        $this->updateUploadDocumentData(__FUNCTION__);

        foreach ($ocrResponseTypes as $ocrDocumentType)
        {

            Config::set('applications.kyc.mock', true);
            Config::set('applications.kyc.poa_ocr_document_type', $ocrDocumentType);
            Config::set('applications.kyc.poa_ocr_response_status', $octResponseStatus);

            $testData = &$this->testData[__FUNCTION__];

            $testData['request']['content']['document_type'] = $documentType;

            $testData['response']['content']['documents'][$documentType] = [];

            $response = $this->startTest($testData);

            $merchantDocumentDb = $this->getDbEntityById('merchant_document', $response['documents'][$documentType][0]['id']);

            $this->assertEquals($merchantDocumentDb['ocr_verify'], $ocrVerificationStatus);
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

        $this->updateUploadDocumentData($testDataKeyName);

        Config::set('applications.kyc.mock', false);
        Config::set('applications.kyc.poa_ocr_response_status', Constants::FAILURE);

        $testData = &$this->testData[$testDataKeyName];

        $documentType = Constants::VOTER_ID_FRONT;

        $testData['request']['content']['document_type'] = $documentType;

        $testData['response']['content']['documents'][$documentType] = [];

        $response = $this->startTest($testData);

        $merchantDocumentDb = $this->getDbEntityById('merchant_document', $response['documents'][$documentType][0]['id']);

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

        $this->createMerchantDocumentAndFileStoreEntity();

        $this->startTest();
    }

    public function testFetchMerchantDocumentsByAdmin()
    {
        $this->createMerchantDocumentAndFileStoreEntity('test');

        $merchantId = '1cXSLlUU8V9sXl';

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        // allow admin to access merchant
        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth('test');

        $this->startTest();
    }

    protected function createMerchantDocumentAndFileStoreEntity(string $mode = 'live'): array
    {
        $document = $this->fixtures->on('live')->create(
            'merchant_document',
            [
                'merchant_id'   => '1cXSLlUU8V9sXl',
                'document_type' => 'Address_proof_url',
                'file_store_id' => 'DM6dXJfU4WzeAF',
                'entity_type'   => 'merchant'
            ]);

        $this->fixtures->on('live')->create(
            'merchant_document',
            [
                'merchant_id'   => '1cXSLlUU8V9sXl',
                'document_type' => 'Aadhar_back',
                'file_store_id' => 'DA6dXJfU4WzeAF',
                'entity_type'   => 'merchant',
                'source'        => Source::UFH,
            ]
        );

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

    public function mockRazorX(string $functionName, string $featureName, string $variant, $merchantId = '1cXSLlUU8V9sXl')
    {
        $testData = &$this->testData[$functionName];

        $uniqueLocalId = RazorXClient::getLocalUniqueId($merchantId, $featureName, Mode::TEST);

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => '{"' . $uniqueLocalId . '":"' . $variant . '"}'];
    }

    public function mockRazorXMultiFeature(string $functionName, array $featureVariantMap, $merchantId = '1cXSLlUU8V9sXl')
    {
        $testData = &$this->testData[$functionName];

        $localIdVariantMap = [];

        foreach ($featureVariantMap as $featureName => $variant)
        {
            $uniqueLocalId                     = RazorXClient::getLocalUniqueId($merchantId, $featureName, Mode::TEST);
            $localIdVariantMap[$uniqueLocalId] = $variant;
        }

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => json_encode($localIdVariantMap)];
    }
}
