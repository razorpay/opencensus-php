<?php


namespace Unit\Models\Merchant\AutoKyc;

use RZP\Models\Merchant\Detail\Entity;
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

        $mockMC->expects($this->any())
               ->method('isRazorxExperimentEnable')
               ->willReturn(true);

        return [
            "merchantCoreMock" => $mockMC
        ];
    }

    protected function createAndFetchFixtures($businessType, $customMerchantAttributes, $customerStakeholderAttributes)
    {
        $defaultAttributes = [
            'poi_verification_status'              => 'verified',
            'bank_details_verification_status'     => 'verified',
            'gstin_verification_status'            => 'verified',
            'company_pan_verification_status'      => 'verified',
            'bank_details_doc_verification_status' => null,
            Entity::CIN_VERIFICATION_STATUS  => 'verified',
            'business_type'                        => BusinessType::getIndexFromKey($businessType)
        ];

        $merchantAttributes = array_merge($defaultAttributes, $customMerchantAttributes);
        $merchantDetail     = $this->fixtures->create('merchant_detail:valid_fields', $merchantAttributes);

        $mid = $merchantDetail->getId();

        $defaultStakeHolderAttributes = [
            'merchant_id' => $mid,
        ];
        $stakeholderAttributes        = array_merge($defaultStakeHolderAttributes, $customerStakeholderAttributes);
        $stakeholder                  = $this->fixtures->create('stakeholder', $stakeholderAttributes);

        return [
            "merchant_detail" => $merchantDetail,
            "stakeholder"     => $stakeholder
        ];
    }

    public function testAutoKycForUnregAadhaarNotVerifiedPoaVerified()
    {
        $fixtures = $this->createAndFetchFixtures(
            BusinessType::NOT_YET_REGISTERED,
            [
                "poa_verification_status" => 'verified'
            ],
            [
                "aadhaar_linked"                       => 0,
                "aadhaar_esign_status"                 => null,
                "aadhaar_verification_with_pan_status" => null,
            ]);

        $mocks = $this->createAndFetchMocks();
        $core  = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycForUnregisteredAadhaarNotLinked()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"       => 0,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForUnregisteredAadhaarLinkedAndNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"       => 1,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForUnregisteredAadhaarLinkedAndXmlNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForUnregisteredAadhaarLinkedAndVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::NOT_YET_REGISTERED, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => 'verified'
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipAadhaarNotLinked()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP, [], [
            "aadhaar_linked"       => 0,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipAadhaarLinkedAndNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP, [], [
            "aadhaar_linked"       => 1,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipAadhaarLinkedAndXmlNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipAadhaarLinkedAndVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => 'verified'
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    /*
     * Scenario:
     *  - proprietorship business type
     *  - aadhaar is not verified but poa is verified
     */
    public function testAutoKycForProprietorshipAadhaarNotVerifiedPoaVerified()
    {
        $fixtures = $this->createAndFetchFixtures(
            BusinessType::PROPRIETORSHIP,
            [
                "poa_verification_status" => 'verified'
            ],
            [
                "aadhaar_linked"                       => 0,
                "aadhaar_esign_status"                 => null,
                "aadhaar_verification_with_pan_status" => null,
            ]);

        $mocks = $this->createAndFetchMocks();
        $core  = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    //public
    public function testAutoKycForPublicAadhaarNotLinked()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PUBLIC_LIMITED, [], [
            "aadhaar_linked"       => 0,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForPublicAadhaarLinkedAndNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PUBLIC_LIMITED, [], [
            "aadhaar_linked"       => 1,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForPublicAadhaarLinkedAndXmlNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PUBLIC_LIMITED, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForPublicAadhaarLinkedAndVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PUBLIC_LIMITED, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => 'verified'
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    /*
     * Scenario:
     *  - PUBLIC_LIMITED business type
     *  - aadhaar is not verified but poa is verified
     */
    public function testAutoKycForPublicAadhaarNotVerifiedPoaVerified()
    {
        $fixtures = $this->createAndFetchFixtures(
            BusinessType::PUBLIC_LIMITED,
            [
                "poa_verification_status" => 'verified'
            ],
            [
                "aadhaar_linked"                       => 0,
                "aadhaar_esign_status"                 => null,
                "aadhaar_verification_with_pan_status" => null,
            ]);

        $mocks = $this->createAndFetchMocks();
        $core  = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    //private
    public function testAutoKycForPrivateAadhaarNotLinked()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED, [], [
            "aadhaar_linked"       => 0,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForPrivateAadhaarLinkedAndNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED, [], [
            "aadhaar_linked"       => 1,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForPrivateAadhaarLinkedAndXmlNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForPrivateAadhaarLinkedAndVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => 'verified'
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    /*
     * Scenario:
     *  - PRIVATE_LIMITED business type
     *  - aadhaar is not verified but poa is verified
     */
    public function testAutoKycForPrivateAadhaarNotVerifiedPoaVerified()
    {
        $fixtures = $this->createAndFetchFixtures(
            BusinessType::PRIVATE_LIMITED,
            [
                "poa_verification_status" => 'verified'
            ],
            [
                "aadhaar_linked"                       => 0,
                "aadhaar_esign_status"                 => null,
                "aadhaar_verification_with_pan_status" => null,
            ]);

        $mocks = $this->createAndFetchMocks();
        $core  = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    //llp
    public function testAutoKycForLLPAadhaarNotLinked()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::LLP, [], [
            "aadhaar_linked"       => 0,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForLLPAadhaarLinkedAndNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::LLP, [], [
            "aadhaar_linked"       => 1,
            "aadhaar_esign_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForLLPAadhaarLinkedAndXmlNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::LLP, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => null
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForLLPAadhaarLinkedAndVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::LLP, [], [
            "aadhaar_linked"                       => 1,
            "aadhaar_esign_status"                 => 'verified',
            "aadhaar_verification_with_pan_status" => 'verified'
        ]);
        $mocks    = $this->createAndFetchMocks();
        $core     = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    /*
     * Scenario:
     *  - LLP business type
     *  - aadhaar is not verified but poa is verified
     */
    public function testAutoKycForLLPAadhaarNotVerifiedPoaVerified()
    {
        $fixtures = $this->createAndFetchFixtures(
            BusinessType::LLP,
            [
                "poa_verification_status" => 'verified'
            ],
            [
                "aadhaar_linked"                       => 0,
                "aadhaar_esign_status"                 => null,
                "aadhaar_verification_with_pan_status" => null,
            ]);

        $mocks = $this->createAndFetchMocks();
        $core  = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }
}
