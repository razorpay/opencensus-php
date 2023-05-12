<?php


namespace Unit\Models\Merchant\AutoKyc;

use Mockery;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Detail;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Merchant\VerificationDetail;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class SignatoryVerificationTest extends TestCase
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
                "name" => 'true',
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

    public function testAutoKycForProprietorshipIfGstCertificateDocIsVerifiedSignatoryVerified()
    {
        $this->mockRazorxTreatment();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $fixtures = $this->createAndFetchFixtures([], [
            VerificationDetail\Entity::STATUS => 'verified'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $this->fixtures->create(
            'merchant_verification_detail', [
                                              'merchant_id'         => $merchantDetail->getId(),
                                              'artefact_type'       => Constant::SIGNATORY_VALIDATION,
                                              'artefact_identifier' => 'number',
                                              'status'              => 'verified'
                                          ]
        );

        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);

        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForProprietorshipIfGstCertificateDocIsVerifiedSignatoryNotVerified()
    {
        $this->mockRazorxTreatment();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $fixtures = $this->createAndFetchFixtures([], [
            VerificationDetail\Entity::STATUS => 'verified'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $this->fixtures->create(
            'merchant_verification_detail', [
                                              'merchant_id'         => $merchantDetail->getId(),
                                              'artefact_type'       => Constant::SIGNATORY_VALIDATION,
                                              'artefact_identifier' => 'number',
                                              'status'              => 'failed'
                                          ]
        );

        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);

        $this->assertFalse($isAutoKycDone);

    }

    public function testAutoKycForProprietorshipIfGstCertificateDocIsNotVerifiedButSGstInIsVerifiedSignatoryVerified()
    {
        $this->mockRazorxTreatment();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
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

        $this->fixtures->create(
            'merchant_verification_detail', [
                                              'merchant_id'         => $merchantDetail->getId(),
                                              'artefact_type'       => Constant::SIGNATORY_VALIDATION,
                                              'artefact_identifier' => 'number',
                                              'status'              => 'verified'
                                          ]
        );

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertTrue($isAutoKycDone);
    }

    public function testPartnerAutoKycForProprietorshipIfGstCertificateDocIsVerifiedSignatoryVerified()
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

        $this->fixtures->create(
            'merchant_verification_detail', [
                                              'merchant_id'         => $merchantDetail->getId(),
                                              'artefact_type'       => Constant::SIGNATORY_VALIDATION,
                                              'artefact_identifier' => 'number',
                                              'status'              => 'verified'
                                          ]
        );

        // since POI and GSTIN verification failed, partner auto KYC is not done
        $this->assertFalse($isAutoKycDone);

    }
}
