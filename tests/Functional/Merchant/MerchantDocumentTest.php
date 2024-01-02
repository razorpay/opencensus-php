<?php

namespace RZP\Tests\Functional\Merchant;

use Queue;
use Config;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use Illuminate\Http\UploadedFile;
use RZP\Constants\Timezone;
use RZP\Services\UfhService;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\BadRequestException;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Exception\BadRequestValidationFailureException;


class MerchantDocumentTest Extends TestCase
{
    use RazorxTrait;
    use MocksSplitz;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $ufh;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantDocumentTestData.php';

        parent::setUp();

        $this->ufh = (new UfhService($this->app));
    }

    public function testDeleteDocument()
    {
        $merchantDocument = $this->fixtures->create('merchant_document', [
            'document_type' => 'business_proof_url',
        ]);

        $this->fixtures->create('merchant_detail:filled_entity',['merchant_id' => '10000000000000']);

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
        $merchantUser = $this->fixtures->user->createUserForMerchant('1cXSLlUU8V9sXl');

        $this->ba->proxyAuth('rzp_test_' . '1cXSLlUU8V9sXl', $merchantUser['id']);

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

    public function testDocumentUploadForPartnerKyc()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('1cXSLlUU8V9sXl');

        $this->ba->proxyAuth('rzp_test_' . '1cXSLlUU8V9sXl', $merchantUser['id']);

        //Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '1cXSLlUU8V9sXl',
                'promoter_pan_name' => 'XYZ',
            ]);

        $this->fixtures->create(
            'partner_activation',
            [
                'merchant_id' => '1cXSLlUU8V9sXl',
                'locked'      => false,
            ]);

        $this->updateUploadDocumentData(__FUNCTION__);

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayNotHasKey('promoter_address_url',$content['partner_activation']['verification']['required_fields']);
    }

    public function testUploadFilesByAgent()
    {
        $this->ba->adminAuth();

        $this->updateUploadDocumentData(__FUNCTION__);

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('id',$content);

        $this->assertArrayHasKey('file_store_id',$content);

        $this->assertArrayHasKey('merchant_id',$content);

        $this->assertArrayHasKey('upload_by_admin_id',$content);
    }

    public function testGetDocumentTypes()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testDocumentUploadToUFH()
    {
        $this->testDocumentUpload();

        $merchantDocumentEntry = $this->getLastEntity('merchant_document', true, 'test');

        $this->assertEquals($merchantDocumentEntry['source'], Source::UFH);
    }

    public function testFileUploadDocumentTypeInvalid()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('1cXSLlUU8V9sXl');

        $this->ba->proxyAuth('rzp_test_' . '1cXSLlUU8V9sXl', $merchantUser['id']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '1cXSLlUU8V9sXl',
            'contact_name'=> 'Aditya',
            'business_type' => 2
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

    public function testVideoFileUpload()
    {
        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        //Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
            ]);

        $this->updateUploadCmp4Video(__FUNCTION__);

        $this->startTest();
    }


    public function testOnlyVideoFileUploadSupported()
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

    public function testVideoUploadForUnsupportedDocuments()
    {
        $this->ba->proxyAuth('rzp_test_' . '10000000000000');

        //Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
            ]);

        $this->updateUploadInputmp4Video(__FUNCTION__);

        $this->startTest();
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

    protected function updateUploadDocumentData(string $callee)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../Storage/a.png',
            'a.png',
            'image/png',
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
            null,
            true);
    }

    protected function updateUploadInputmp4Video(string $callee)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../Batch/files/input.mp4',
            'input.mp4',
            'video/mp4',
            null,
            true);
    }

    protected function updateUploadCmp4Video(string $callee)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../Batch/files/c.mp4',
            'c.mp4',
            'video/mp4',
            null,
            true);
    }

    public function testFetchMerchantDocuments()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '1cXSLlUU8V9sXl',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant('1cXSLlUU8V9sXl', [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' . '1cXSLlUU8V9sXl', $merchantUser['id']);

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

    public function testFetchMerchantDocumentsByAdminForCommonMerchantId()
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

        $merchant = $this->fixtures->create(
            'merchant',
            [   'id'    => '100000razorpay',
            ]
        );
        $fileStore = $this->fixtures->on('test')->create('file_store', [
            'id'          => 'DM6dXJfU4WzeAF',
            'merchant_id' => '100000Razorpay',
            'type'        => 'Address_proof_url',
            'entity_type' => null,
            'name'        => 'batch/validated/10000000000002',
            'location'    => 'batch/validated/10000000000002.pdf',
        ]);


        $merchantId = '1cXSLlUU8V9sXl';

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        // allow admin to access merchant
        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth('test');

        $response = $this->startTest();

        foreach ($response as $documentType => $documents)
        {
            foreach ($documents as $document)
            {

                    $this->assertNotNull($document['signed_url']);

            }
        }


    }

    public function testFetchMerchantDocumentsByAdminForWrongMerchantId()
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

        $merchant  = $this->fixtures->create(
            'merchant',
            ['id' => '1cXSLlUU8V9sXa',
            ]
        );
        $fileStore = $this->fixtures->on('test')->create('file_store', [
            'id'          => 'DM6dXJfU4WzeAF',
            'merchant_id' => '1cXSLlUU8V9sXa',
            'type'        => 'Address_proof_url',
            'entity_type' => null,
            'name'        => 'batch/validated/10000000000002',
            'location'    => 'batch/validated/10000000000002.pdf',
        ]);

        $merchantId = '1cXSLlUU8V9sXl';

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        // allow admin to access merchant
        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth('test');
        try
        {
            $this->startTest();
        }
        catch (\Throwable $ex)
        {
            $this->assertExceptionClass($ex, BadRequestException::class);
        }
    }

    public function testUpdateDocumentVerifyPendingVerificationStatusForPersonalPan()
    {
        $this->mockRazorX('testDocumentUpload', 'bvs_personal_pan_ocr', 'on');

        $this->uploadDocument('personal_pan', 'personal_pan_doc_verification_status');

        $stakeholder = $this->getDbEntities('stakeholder', ['merchant_id' => '1cXSLlUU8V9sXl'])->first();
        $this->assertNotNull($stakeholder);
        $this->assertEquals('pending', $stakeholder->getPanDocStatus());
    }

    public function testUpdateDocumentVerifyPendingVerificationStatusForBusinessPan()
    {
        $this->mockRazorX('testDocumentUpload', 'bvs_business_pan_ocr', 'on');

        $this->uploadDocument('business_pan_url', 'company_pan_doc_verification_status');
    }

    public function testUpdateDocumentVerifyPendingVerificationStatusForCancelledCheque()
    {
        $this->mockRazorX('testDocumentUpload', 'bvs_cancelled_cheque_ocr', 'on');

        $this->uploadDocument('cancelled_cheque', 'bank_details_doc_verification_status');
    }

    public function testSignatoryChangeForBankAccountDocType()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        Config::set('services.bvs.mock', true);

        Config::set('services.bvs.response', 'success');

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->mockRazorX('testDocumentUpload', 'bvs_cancelled_cheque_ocr', 'on');

        $this->uploadDocument('cancelled_cheque', 'bank_details_doc_verification_status');

        $bvsValidation = $this->fixtures->create('bvs_validation',
                                                 [
                                                     'owner_id'        => $merchantId,
                                                     'artefact_type'   => 'bank_account',
                                                     'validation_unit' => 'proof',
                                                 ]);

        $kafkaEventPayload = [
            'data' => [
                'validation_id'       => $bvsValidation->getValidationId(),
                'status'              => 'success',
                'error_description'   => '',
                'error_code'          => '',
                'rule_execution_list' => ''
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-validation-result-events', $kafkaEventPayload, 'live');

        $attribute = ['business_type'                        => 4,
                      'bank_details_doc_verification_status' => 'pending'];

        $this->fixtures->edit('merchant_detail', $merchantId, $attribute);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'bank_account', 'merchant_id' => $merchantId]);

        $bvsValidation = $this->getDbLastEntity('bvs_validation')->toArray();

        $merchantDetails = $this->getDbLastEntity('merchant_detail')->toArray();

        $expectedMetadata = ["signatory_validation_status" => "verified", 'bvs_validation_id' => $bvsValidation['validation_id']];

        $this->assertEquals($expectedMetadata, $merchantVerificationDetail['metadata']);

        $this->assertEquals('verified', $merchantDetails['bank_details_doc_verification_status']);
    }

    public function testProprietorshipBusinessProofDocumentNotUploadedCanSubmitFlagFalse()
    {
        $this->checkCanSubmitForPropBusinessBusinessProofUpload("personal_pan", false);
    }

    public function testProprietorshipBusinessProofDocumentShopEstabCanSubmitFlagFalse()
    {
        $this->checkCanSubmitForPropBusinessBusinessProofUpload("shop_establishment_certificate");
    }

    public function testProprietorshipBusinessProofDocumentGSTCertCanSubmitFlagFalse()
    {
        $this->checkCanSubmitForPropBusinessBusinessProofUpload("gst_certificate");
    }

    public function testProprietorshipBusinessProofDocumentMsmeCertCanSubmitFlagFalse()
    {
        $this->checkCanSubmitForPropBusinessBusinessProofUpload("msme_certificate");
    }

    public function testProprietorshipBusinessProofDocumentBusinessProofUrlCanSubmitFlagFalse()
    {
        $this->checkCanSubmitForPropBusinessBusinessProofUpload("business_proof_url");
    }

    protected function checkCanSubmitForPropBusinessBusinessProofUpload(string $documentKey,
                                                                        bool $expectedCanSubmitFlag = true)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:filled_entity', ["business_type" => "1"]);

        $this->createDocumentEntities($merchantDetail["merchant_id"],
                                      [
                                          'personal_pan',
                                          'promoter_address_url',
                                      ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->updateUploadDocumentData('testDocumentUpload');

        $request = $this->testData['testDocumentUpload']['request'];

        $request['content']['document_type'] = $documentKey;

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($expectedCanSubmitFlag, $content["can_submit"]);
    }

    private function createDocumentEntities(string $merchantId, array $documentTypes, array $attributes = [])
    {
        $data = [
            'document_types' => $documentTypes,
            'attributes'     => [
                'merchant_id'   => $merchantId,
                'file_store_id' => 'abcdefgh12345',]
        ];

        $data['attributes'] = array_merge($data['attributes'], $attributes);

        $this->fixtures->create('merchant_document:multiple', $data);
    }

    protected function uploadDocument(string $documentKey, string $documentVerificationKey, $mid = '1cXSLlUU8V9sXl')
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant($mid);

        $this->ba->proxyAuth('rzp_test_' . $mid, $merchantUser['id']);

        //Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'       => $mid,
                'promoter_pan_name' => 'XYZ',
            ]);

        $this->updateUploadDocumentData('testDocumentUpload');

        $request = $this->testData['testDocumentUpload']['request'];

        $request['content']['document_type'] = $documentKey;

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals('pending', $content[$documentVerificationKey]);
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


    public function testUploadFilesByAgentTypeFfmcLicense($merchantId = null)
    {
        $this->ba->adminAuth();
        $mid = $merchantId ?? 'KqsQEszAud2PqZ';
        Config::set('pgos.proxy.request.mock', true);

        $merchant = $this->fixtures->create('merchant', [
            'id'            => $mid,
            'website'       => null,
            'name'          => null,
            'email'         => null,
            'billing_label' => null,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'      => $mid,
            'contact_email'    => null,
            'business_website' => null]);

        $this->fixtures->create('stakeholder', ['name' => 'stakeholder name', 'percentage_ownership' => 90, 'merchant_id' => $mid]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($mid);

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $mid,
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding',
            'metadata'        => [
                'service' => 'pgos'
            ]
        ]);

        $this->updateUploadDocumentData(__FUNCTION__);

        $request                           = $this->testData[__FUNCTION__]['request'];
        $request['content']['merchant_id'] = $mid;

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayHasKey('id', $content);

        $this->assertArrayHasKey('file_store_id', $content);

        $this->assertArrayHasKey('merchant_id', $content);

        $this->assertArrayHasKey('upload_by_admin_id', $content);
    }

    public function testFetchMerchantDocumentsWithExpiryDate()
    {
        $this->ba->adminAuth();
        Mail::fake();
        Config::set('pgos.proxy.request.mock', true);

        $this->testUploadFilesByAgentTypeFfmcLicense();

        $response = $this->startTest();

        // Assert that the 'items' key exists within the 'data' array
        $this->assertArrayHasKey('items', $response);

        $foundExpiryDate = false;
        foreach ($response['items'] as $item)
        {
            if (isset($item['metadata']['expiry_date']))
            {
                $foundExpiryDate = true;
                break; // Found at least one item with 'expiry_date' in 'metadata'
            }
        }

        // Assert that at least one item has 'expiry_date' in 'metadata'
        $this->assertTrue($foundExpiryDate);

    }


}

