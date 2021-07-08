<?php


namespace Unit\Models\Merchant\AutoKyc;

use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Detail;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\VerificationDetail;
use RZP\Models\Merchant\Detail\BusinessType;

class ShopEstbDocVerificationTest extends TestCase
{
    protected function mockRazorxTreatment()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');
    }

    protected function createAndFetchFixtures($customMerchantAttributes, $customVerificationDetailAttributes)
    {
        $defaultMerchantAttributes = [
            'poi_verification_status'               => 'verified',
            'bank_details_verification_status'      => 'verified',
            'bank_details_doc_verification_status'  => null,
            'business_type'                         => BusinessType::getIndexFromKey(BusinessType::PROPRIETORSHIP)
        ];

        $merchantDetail = $this->fixtures->create(
            'merchant_detail:valid_fields',
            array_merge($defaultMerchantAttributes, $customMerchantAttributes));

        $mid = $merchantDetail->getId();

        $defaultStakeholderAttributes = [
            'merchant_id'                           => $mid,
            'aadhaar_linked'                        => 1,
            'aadhaar_esign_status'                  => 'verified',
            'aadhaar_verification_with_pan_status'  => 'verified'
        ];

        $stakeholder = $this->fixtures->create('stakeholder', $defaultStakeholderAttributes);

        $defaultVerificationDetailAttributes = [
            'merchant_id'          => $mid,
            'artefact_type'        => 'shop_establishment',
            'artefact_identifier'  => 'doc',
        ];

        $verificationDetail = $this->fixtures->create(
            'merchant_verification_detail',
            array_merge($defaultVerificationDetailAttributes, $customVerificationDetailAttributes));

        return [
            'merchant_detail'       => $merchantDetail,
            'stakeholder'           => $stakeholder,
            'verificationDetail'    => $verificationDetail
        ];
    }

    public function testAutoKycForProprietorshipIfShopEstbDocIsVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([], [
            VerificationDetail\Entity::STATUS  => 'verified'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipIfShopEstbDocIsNotVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS             => 'failed',
            Detail\Entity::GSTIN_VERIFICATION_STATUS                => 'failed',
            Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => 'failed'
        ], [
            VerificationDetail\Entity::STATUS                       => 'failed'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertFalse($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipIfShopEstbDocIsNotVerifiedButGstinIsVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS             => 'failed',
            Detail\Entity::GSTIN_VERIFICATION_STATUS                => 'verified',
            Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => 'failed'
        ], [
            VerificationDetail\Entity::STATUS                       => 'failed'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForProprietorshipIfShopEstbDocIsNotVerifiedButMsmeDocIsVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS             => 'verified',
            Detail\Entity::GSTIN_VERIFICATION_STATUS                => 'failed',
            Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => 'failed'
        ], [
            VerificationDetail\Entity::STATUS                       => 'failed'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipIfShopEstbDocIsNotVerifiedButShopEstbIsVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS             => 'failed',
            Detail\Entity::GSTIN_VERIFICATION_STATUS                => 'failed',
            Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => 'verified'
        ], [
            VerificationDetail\Entity::STATUS                       => 'failed'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipIfShopEstbDocExpIsOff()
    {
        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS             => 'failed',
            Detail\Entity::GSTIN_VERIFICATION_STATUS                => 'verified',
            Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => 'failed',
        ], []);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertTrue($isAutoKycDone);
    }
}
