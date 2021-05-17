<?php


namespace Unit\Models\Merchant\AutoKyc;

use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Tests\Functional\TestCase;

class MsmeDocVerificationTest extends TestCase
{
    protected function createAndFetchFixtures($businessType, $customMerchantAttributes)
    {
        $defaultAttributes = [
            'poi_verification_status'               => 'verified',
            'bank_details_verification_status'      => 'verified',
            'bank_details_doc_verification_status'  => null,
            'business_type'                         => BusinessType::getIndexFromKey($businessType)
        ];

        $merchantAttributes = array_merge($defaultAttributes, $customMerchantAttributes);
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $merchantAttributes);

        $mid = $merchantDetail->getId();

        $defaultStakeHolderAttributes = [
            'merchant_id'                           => $mid,
            "aadhaar_linked"                        => 1,
            "aadhaar_esign_status"                  => 'verified',
            "aadhaar_verification_with_pan_status"  => 'verified'
        ];
        $stakeholder = $this->fixtures->create('stakeholder', $defaultStakeHolderAttributes);

        return [
            "merchant_detail"   => $merchantDetail,
            "stakeholder"       => $stakeholder
        ];
    }

    public function testAutoKycForProprietorshipIfMsmeIsVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP, [
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS => 'verified'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipIfMsmeIsNotVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP, [
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS => 'failed',
            Detail\Entity::GSTIN_VERIFICATION_STATUS    => 'failed',
            Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => 'failed'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipIfMsmeIsNotVerifiedButGstinIsVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP, [
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS => 'failed',
            Detail\Entity::GSTIN_VERIFICATION_STATUS    => 'verified',
            Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => 'failed'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipIfMsmeIsNotVerifiedButShopEstablishmentIsVerified()
    {
        $fixtures = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP, [
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS => 'failed',
            Detail\Entity::GSTIN_VERIFICATION_STATUS    => 'failed',
            Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => 'verified'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }
}
