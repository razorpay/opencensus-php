<?php


namespace Unit\Models\Merchant\Document;


use Mockery;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Document\Core as DocumentCore;
use RZP\Models\Merchant\Document\Entity;
use RZP\Models\Merchant\Document\Service;
use RZP\Models\Merchant\Document\Validator;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Document\Constants as DocumentConstants;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Constants\Entity as E;

class CoreTest extends TestCase
{
    protected function createAndFetchMocks($experimentEnabled)
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn($experimentEnabled);

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    protected function getMerchantDetailFixture($businessType, $extraAttributes = [])
    {
        $merchantAttributes = [
            'business_type'     => $businessType,
        ];

        $merchantAttributes = array_merge($merchantAttributes, $extraAttributes);

        return $this->fixtures->create('merchant_detail:valid_fields', $merchantAttributes);
    }

    public function testShouldPerformOcrForAadharBackDocumentTypeAndExperimentIsEnabled()
    {
        $merchantDetail = $this->getMerchantDetailFixture(11);
        $document = $this->fixtures->create('merchant_document', [
            'document_type' => Type::AADHAR_BACK,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $documentCore = new DocumentCore();
        $shouldPerformOCR = $documentCore->shouldPerfomOcrOnDocumentUpload($document, $merchantDetail->merchant, $merchantDetail);
        $this->assertTrue($shouldPerformOCR);
    }

    public function testShouldSkipOCRIfExperimentEnabled()
    {
        $merchantDetail = $this->getMerchantDetailFixture(11);
        $document = $this->fixtures->create('merchant_document', [
            'document_type' => Type::AADHAR_BACK,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $documentCore = new DocumentCore();
        $this->fixtures->org->addFeatures([FeatureConstants::KYC_BLOCK_OCR_FOR_VAS],'100000razorpay');
        $shouldPerformOCR = $documentCore->shouldPerfomOcrOnDocumentUpload($document, $merchantDetail->merchant, $merchantDetail);
        $this->assertFalse($shouldPerformOCR);
    }

    public function testShouldPerformOcrForGstCertificateDocumentTypeAndExperimentIsEnabled()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);
        $document = $this->fixtures->create('merchant_document', [
            'document_type' => Type::GST_CERTIFICATE,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $documentCore = new DocumentCore();
        $shouldPerformOCR = $documentCore->shouldPerfomOcrOnDocumentUpload($document, $merchantDetail->merchant, $merchantDetail);
        $this->assertTrue($shouldPerformOCR);
    }

    public function testShouldPerformOcrForPartnerShipDocumentTypeAndExperimentIsEnabled()
    {
        $merchantDetail = $this->getMerchantDetailFixture(3);
        $document = $this->fixtures->create('merchant_document', [
            'document_type' => Type::BUSINESS_PROOF_URL,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $documentCore = new DocumentCore();
        $shouldPerformOCR = $documentCore->shouldPerfomOcrOnDocumentUpload($document, $merchantDetail->merchant, $merchantDetail);
        $this->assertTrue($shouldPerformOCR);
    }

    public function testShouldPerformOcrForCertificateOfIncorporationDocumentType()
    {
        $merchantDetail = $this->getMerchantDetailFixture(6);

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => Type::BUSINESS_PROOF_URL,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $documentCore = new DocumentCore();
        $shouldPerformOCR = $documentCore->shouldPerfomOcrOnDocumentUpload($document, $merchantDetail->merchant, $merchantDetail);
        $this->assertTrue($shouldPerformOCR);
    }

    public function testShouldPerformOcrForTrustSocietyNgoBusinessCertificateDocument()
    {
        $merchantDetail = $this->getMerchantDetailFixture(9);

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => Type::BUSINESS_PROOF_URL,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $documentCore = new DocumentCore();
        $shouldPerformOCR = $documentCore->shouldPerfomOcrOnDocumentUpload($document, $merchantDetail->merchant, $merchantDetail);
        $this->assertTrue($shouldPerformOCR);
    }

    public function testShouldPerformOcrForMsmeDocumentTypeAndExperimentIsEnabled()
    {
        $mocks = $this->createAndFetchMocks(true);

        $merchantDetail = $this->getMerchantDetailFixture(11);
        $document = $this->fixtures->create('merchant_document', [
            'document_type' => Type::MSME_CERTIFICATE,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $documentCore = new DocumentCore();
        $documentCore->setMerchantCore($mocks['merchantCoreMock']);

        $shouldPerformOCR = $documentCore->shouldPerfomOcrOnDocumentUpload($document, $merchantDetail->merchant, $merchantDetail);

        $this->assertTrue($shouldPerformOCR);
    }

    public function testValidateLockForPos()
    {
        $input = [
            "document_type"   => "shop_front"
        ];

        $documentCore = new DocumentCore();

        $shouldValidateLock =  $documentCore->shouldValidateLock($input, true);

        $this->assertEquals( false, $shouldValidateLock );
    }

    public function testValidateLockForPos1()
    {
        $input = [
            "document_type"   => "mmtc_pamp_license"
        ];

        $documentCore = new DocumentCore();

        $shouldValidateLock =  $documentCore->shouldValidateLock($input, true);

        $this->assertEquals( true, $shouldValidateLock );
    }
    
    public function testUploadFilesByAgent()
    {
        $merchant = $this->fixtures->create('merchant');
        $merchantEntity = Mockery::mock(Merchant\Entity::class);
        $merchantEntity->shouldReceive('getId')->andReturn($merchant['id']);
        
        $admin = $this->fixtures->create('admin');
        $adminEntity = Mockery::mock('RZP\Models\Admin\Entity');
        $adminEntity->shouldReceive('getId')->andReturn($admin['id']);
        
        $documentEntity = Mockery::mock(Entity::class);
        $documentId = 'doc_' . uniqid();
        $fileStoreId = 'file_' . uniqid();
        
        $documentEntity->shouldReceive('getId')->andReturn($documentId);
        $documentEntity->shouldReceive('getFileStoreId')->andReturn($fileStoreId);
        $documentEntity->shouldReceive('getMerchantId')->andReturn($merchant['id']);
        $documentEntity->shouldReceive('getUploadByAdminId')->andReturn($admin['id']);
        $documentEntity->shouldReceive('getCreatedAt')->andReturn(date('Y-m-d H:i:s'));
        $documentEntity->shouldReceive('generateId')->andReturn($documentEntity);
        $documentEntity->shouldReceive('setUploadByAdminId')->once()->with($admin['id'])->andReturnSelf();
        $documentEntity->shouldReceive('merchant->associate')->once()->with($merchantEntity)->andReturnSelf();
        $documentEntity->shouldReceive('entity->associate')->once()->with($merchantEntity)->andReturnSelf();
        $documentEntity->shouldReceive('setFileStoreId')->once()->with($fileStoreId)->andReturnSelf();
        $documentEntity->shouldReceive('setAttribute')->times(3)->andReturnSelf();
        
        $input = [
            'merchant_id' => $merchant['id'],
            'document_type' => 'promoter_address_url',
            'file' => 'file_content'
        ];
        
        $documentType = $input['document_type'];
        
        $fileAttributes = [
            $documentType => [
                DocumentConstants::FILE_ID => $fileStoreId,
                DocumentConstants::SOURCE => 'web',
                DocumentConstants::ORIGINAL_FILE_NAME => 'test.jpg'
            ]
        ];
        
        $expectedResponse = [
            E::ID => $documentId,
            E::FILE_STORE_ID => $fileStoreId,
            E::MERCHANT_ID => $merchant['id'],
            E::UPLOAD_BY_ADMIN_ID => $admin['id'],
            E::CREATED_AT => date('Y-m-d H:i:s')
        ];
        
        $mockRepo = Mockery::mock('RZP\Models\Base\Repository');
        $mockAuth = Mockery::mock('RZP\Auth\Auth');
        $mockTrace = Mockery::mock('Razorpay\Trace\Logger');
        $mockCore = Mockery::mock(DocumentCore::class);
        $mockPgosProxyController = Mockery::mock(MerchantOnboardingProxyController::class);
        $mockValidator = Mockery::mock(Validator::class);
        $mockDetailCore = Mockery::mock('RZP\Models\Merchant\Detail\Core');
        $mockMerchantCore = Mockery::mock('RZP\Models\Merchant\Core');
        $mockDetailService = Mockery::mock('RZP\Models\Merchant\Detail\Service');
        $mockApp = Mockery::mock('Illuminate\Container\Container');
        
        $response = $service->uploadFilesByAgent($input);
        
        $this->assertEquals($expectedResponse, $response);
    }
}
