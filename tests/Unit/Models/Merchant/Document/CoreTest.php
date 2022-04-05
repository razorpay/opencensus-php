<?php


namespace Unit\Models\Merchant\Document;


use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Document\Core as DocumentCore;

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
}
