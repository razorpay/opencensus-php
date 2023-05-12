<?php


namespace Unit\Models\Merchant\AutoKyc;

use Mockery;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Detail;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\VerificationDetail;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Partner\Core as PartnerCore;
class GstCertificateDocVerificationTest extends TestCase
{
    protected $splitzMock;

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

    protected function getSplitzMock()
    {
        if ($this->splitzMock === null)
        {
            $this->splitzMock = Mockery::mock(SplitzService::class, [$this->app])->makePartial();

            $this->app->instance('splitzService', $this->splitzMock);
        }

        return $this->splitzMock;
    }

    protected function mockSplitzTreatment($input = [], $output = [])
    {
        return $this->getSplitzMock()
                    ->shouldReceive('evaluateRequest')
                    ->atLeast()
                    ->once()
                    ->with($input)
                    ->andReturn($output);
    }

    protected function mockAllSplitzTreatment($output = [
        "response" => [
            "variant" => [
                "name" => 'enable',
            ]
        ]
    ])
    {
        return $this->getSplitzMock()
                    ->shouldReceive('evaluateRequest')
                    ->andReturn($output);
    }

    protected function createAndFetchFixtures($customMerchantAttributes, $customVerificationDetailAttributes)
    {
        $defaultMerchantAttributes = [
            'poi_verification_status'              => 'verified',
            'bank_details_verification_status'     => 'verified',
            'bank_details_doc_verification_status' => null,
            'business_type'                        => BusinessType::getIndexFromKey(BusinessType::PROPRIETORSHIP)
        ];

        $merchantDetail = $this->fixtures->create(
            'merchant_detail:valid_fields',
            array_merge($defaultMerchantAttributes, $customMerchantAttributes));

        $mid = $merchantDetail->getId();

        $defaultStakeholderAttributes = [
            'merchant_id'                          => $mid,
            'aadhaar_linked'                       => 1,
            'aadhaar_esign_status'                 => 'verified',
            'aadhaar_verification_with_pan_status' => 'verified'
        ];

        $stakeholder = $this->fixtures->create('stakeholder', $defaultStakeholderAttributes);

        $defaultVerificationDetailAttributes = [
            'merchant_id'         => $mid,
            'artefact_type'       => Constant::GSTIN,
            'artefact_identifier' => 'doc',
        ];

        $verificationDetail = $this->fixtures->create(
            'merchant_verification_detail',
            array_merge($defaultVerificationDetailAttributes, $customVerificationDetailAttributes));

        return [
            'merchant_detail'    => $merchantDetail,
            'stakeholder'        => $stakeholder,
            'verificationDetail' => $verificationDetail
        ];
    }

    protected function createAndFetchFixturesForPartner($customMerchantAttributes, $customVerificationDetailAttributes)
    {
        $defaultMerchantAttributes = [
            'poi_verification_status'              => null,
            'bank_details_verification_status'     => 'verified',
            'bank_details_doc_verification_status' => null,
            'business_type'                        => BusinessType::getIndexFromKey(BusinessType::PROPRIETORSHIP)
        ];

        $merchantDetail = $this->fixtures->create(
            'merchant_detail:valid_fields',
            array_merge($defaultMerchantAttributes, $customMerchantAttributes));

        $mid                                 = $merchantDetail->getId();
        $defaultVerificationDetailAttributes = [
            'merchant_id'         => $mid,
            'artefact_type'       => Constant::GSTIN,
            'artefact_identifier' => 'doc',
        ];

        $verificationDetail = $this->fixtures->create(
            'merchant_verification_detail',
            array_merge($defaultVerificationDetailAttributes, $customVerificationDetailAttributes));

        return [
            'merchant_detail'    => $merchantDetail,
            'verificationDetail' => $verificationDetail
        ];
    }

    public function testAutoKycForProprietorshipIfGstCertificateDocIsVerified()
    {
        $this->mockRazorxTreatment();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $fixtures = $this->createAndFetchFixtures([], [
            VerificationDetail\Entity::STATUS => 'verified'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForProprietorshipIfGstCertificateIsNotVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::MSME_DOC_VERIFICATION_STATUS           => 'failed',
                                                      Detail\Entity::GSTIN_VERIFICATION_STATUS              => 'failed',
                                                      Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS => 'failed'
                                                  ], [
                                                      VerificationDetail\Entity::STATUS => 'failed'
                                                  ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertFalse($isAutoKycDone);

    }

    public function testAutoKycForProprietorshipIfShopEstbDocIsnotVerifiedButGstinIsVerified()
    {
        $this->mockRazorxTreatment();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::MSME_DOC_VERIFICATION_STATUS           => 'failed',
                                                      Detail\Entity::GSTIN_VERIFICATION_STATUS              => 'verified',
                                                      Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS => 'failed'
                                                  ], [
                                                      VerificationDetail\Entity::STATUS => 'failed'
                                                  ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForProprietorshipIfGstCertificateDocIsNotVerifiedButMsmeDocIsVerified()
    {
        $this->mockRazorxTreatment();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::MSME_DOC_VERIFICATION_STATUS           => 'verified',
                                                      Detail\Entity::GSTIN_VERIFICATION_STATUS              => 'failed',
                                                      Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS => 'failed'
                                                  ], [
                                                      VerificationDetail\Entity::STATUS => 'failed'
                                                  ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertTrue($isAutoKycDone);
    }

    public function testAutoKycForProprietorshipIfGstCertificateDocIsNotVerifiedButSGstInIsVerified()
    {
        $this->mockRazorxTreatment();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::MSME_DOC_VERIFICATION_STATUS           => 'failed',
                                                      Detail\Entity::GSTIN_VERIFICATION_STATUS              => 'verified',
                                                      Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS => 'failed'
                                                  ], [
                                                      VerificationDetail\Entity::STATUS => 'failed'
                                                  ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertTrue($isAutoKycDone);
    }

    public function testPartnerAutoKycForProprietorshipIfGstCertificateDocIsVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::COMPANY_PAN_VERIFICATION_STATUS => 'failed',
                                                      Detail\Entity::GSTIN_VERIFICATION_STATUS       => 'failed',
                                                      Detail\Entity::POI_VERIFICATION_STATUS         => 'failed'
                                                  ], [
                                                      VerificationDetail\Entity::STATUS => 'verified'
                                                  ]);

        $core = new PartnerCore();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isPartnerKycDone($merchantDetail);

        // since POI and GSTIN verification failed, partner auto KYC is not done
        $this->assertFalse($isAutoKycDone);

    }

    public function testPartnerAutoKycForProprietorshipIfGstCertificateDocIsnotVerifiedGstInIsVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::COMPANY_PAN_VERIFICATION_STATUS => 'failed',
                                                      Detail\Entity::GSTIN_VERIFICATION_STATUS       => 'verified',
                                                      Detail\Entity::POI_VERIFICATION_STATUS         => 'failed'
                                                  ], [
                                                      VerificationDetail\Entity::STATUS => 'failed'
                                                  ]);
        $core     = new PartnerCore();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isPartnerKycDone($merchantDetail);

        // since POI verification failed, partner auto kyc is not done
        $this->assertFalse($isAutoKycDone);

    }

    public function testPartnerAutoKycForProprietorshipIfGstCertificateDocIsnotVerifiedPoiIsVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::COMPANY_PAN_VERIFICATION_STATUS => 'failed',
                                                      Detail\Entity::GSTIN_VERIFICATION_STATUS       => 'failed',
                                                      Detail\Entity::POI_VERIFICATION_STATUS         => 'verified'
                                                  ], [
                                                      VerificationDetail\Entity::STATUS => 'failed'
                                                  ]);
        $core     = new PartnerCore();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isPartnerKycDone($merchantDetail);

        //since GSTIN verification failed, partner auto kyc is not done
        $this->assertFalse($isAutoKycDone);
    }

    public function testPartnerAutoKycForProprietorshipIfGstinAndPoiAreVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                     Detail\Entity::GSTIN_VERIFICATION_STATUS       => 'verified',
                                                     Detail\Entity::POI_VERIFICATION_STATUS         => 'verified',
                                                  ], [
                                                    VerificationDetail\Entity::STATUS => 'failed'
                                                  ]);
        $core     = new PartnerCore();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isPartnerKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    public function testPartnerAutoKycForProprietorshipIfGstinIsNullAndPoiIsVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                     Detail\Entity::GSTIN                           => null,
                                                     Detail\Entity::GSTIN_VERIFICATION_STATUS       => null,
                                                     Detail\Entity::POI_VERIFICATION_STATUS         => 'verified',
                                                  ], [
                                                        VerificationDetail\Entity::STATUS => 'failed'
                                                  ]);
        $core     = new PartnerCore();

        $merchantDetail = $fixtures['merchant_detail'];

        // since GSTIN is not provided and it is optional, we will ignore GSTIN verification status
        $isAutoKycDone  = $core->isPartnerKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }

    public function testPartnerAutoKycForPvtLtdIfGstinAndCompanyPanAreVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                     Detail\Entity::GSTIN_VERIFICATION_STATUS       => 'verified',
                                                     Detail\Entity::COMPANY_PAN_VERIFICATION_STATUS => 'verified',
                                                     Detail\Entity::BUSINESS_TYPE                   => 4
                                                  ], [
                                                        VerificationDetail\Entity::STATUS => 'failed'
                                                  ]);
        $core     = new PartnerCore();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone  = $core->isPartnerKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);
    }
}
