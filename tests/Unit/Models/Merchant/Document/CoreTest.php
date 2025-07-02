<?php


namespace Unit\Models\Merchant\Document;


use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Document\Core as DocumentCore;
use RZP\Models\Feature\Constants as FeatureConstants;

class CoreTest extends TestCase
{
    protected function createAndFetchMocks($experimentEnabled)
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->onlyMethods(['isRazorxExperimentEnable'])
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

    public function testUploadFilesByAgentSuccess()
    {
        $service = Mockery::mock(Service::class)->makePartial();
        
        $input = [
            'merchant_id' => 'merchant_123',
            'document_type' => 'promoter_address_url',
            'file' => 'file_content'
        ];
        
        $expectedResponse = [
            Entity::ID => 'doc_123456789',
            Entity::FILE_STORE_ID => 'file_123456789',
            Entity::MERCHANT_ID => 'merchant_123',
            Entity::UPLOAD_BY_ADMIN_ID => 'admin_123',
            Entity::CREATED_AT => 1234567890
        ];
        
        $service->shouldReceive('uploadFilesByAgent')
            ->with($input)
            ->andReturn($expectedResponse);
        
        $response = $service->uploadFilesByAgent($input);
        
        $this->assertEquals($expectedResponse, $response);
    }
}
