<?php


namespace Unit\Models\Merchant\AutoKyc;

use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\BusinessType;

class AadhaarVerificationTest extends TestCase
{
    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->exactly(2))
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        return [
          "merchantCoreMock"    => $mockMC
        ];
    }

    protected function createAndFetchFixtures($businessType, $customMerchantAttributes, $customerStakeholderAttributes)
    {
        $defaultAttributes = [
            'poi_verification_status'               => 'verified',
            'bank_details_verification_status'      => 'verified',
            'gstin_verification_status'             => 'verified',
            'bank_details_doc_verification_status'  => null,
            'business_type'                         => BusinessType::getIndexFromKey($businessType)
        ];

        $merchantAttributes = array_merge($defaultAttributes, $customMerchantAttributes);
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $merchantAttributes);

        $mid = $merchantDetail->getId();

        $defaultStakeHolderAttributes = [
            'merchant_id'                          => $mid,
        ];
        $stakeholderAttributes = array_merge($defaultStakeHolderAttributes, $customerStakeholderAttributes);
        $stakeholder = $this->fixtures->create('stakeholder', $stakeholderAttributes);

        return [
            "merchant_detail"   => $merchantDetail,
            "stakeholder"       => $stakeholder
        ];
    }

    public function testAutoKycForUnregisteredAadhaarNotLinked()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
                "aadhaar_linked"        => 0,
                "aadhaar_esign_status"  => null
        ]);
        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForUnregisteredAadhaarLinkedAndNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"        => 1,
            "aadhaar_esign_status"  => null
        ]);
        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForUnregisteredAadhaarLinkedAndXmlNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"        => 1,
            "aadhaar_esign_status"  => 'verified',
            "aadhaar_verification_with_pan_status"  => null
        ]);
        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForUnregisteredAadhaarLinkedAndVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"        => 1,
            "aadhaar_esign_status"  => 'verified',
            "aadhaar_verification_with_pan_status"  => 'verified'
        ]);
        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipAadhaarNotLinked()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"        => 0,
            "aadhaar_esign_status"  => null
        ]);
        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipAadhaarLinkedAndNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"        => 1,
            "aadhaar_esign_status"  => null
        ]);
        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipAadhaarLinkedAndXmlNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"        => 1,
            "aadhaar_esign_status"  => 'verified',
            "aadhaar_verification_with_pan_status"  => null
        ]);
        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipAadhaarLinkedAndVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"        => 1,
            "aadhaar_esign_status"  => 'verified',
            "aadhaar_verification_with_pan_status"  => 'verified'
        ]);
        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }
}
