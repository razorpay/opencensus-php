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
class TrustAutoKycTest extends TestCase
{
    protected $splitzMock;

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

    protected function mockRazorxTreatment()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');
    }

    protected function createAndFetchFixtures($customMerchantAttributes, $customVerificationDetailAttributes, $customStakeHolderDetailAttributes = [])
    {
        $defaultMerchantAttributes = [
            'poi_verification_status'              => 'verified',
            'company_pan_verification_status'      => 'verified',
            'poi_verification_status'              => 'verified',
            'personal_pan_doc_verification_status' => 'verified',
            'company_pan_doc_verification_status'  => 'verified',
            'poa_verification_status'              => 'verified',
            'bank_details_verification_status'     => 'verified',
            'bank_details_doc_verification_status' => null,
            'business_type'                        => BusinessType::getIndexFromKey(BusinessType::TRUST)
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

        $stakeholder = $this->fixtures->create('stakeholder', array_merge($defaultStakeholderAttributes, $customStakeHolderDetailAttributes));

        $defaultVerificationDetailAttributes = [
            'merchant_id'         => $mid,
            'artefact_type'       => Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE,
            'status'              => 'verified',
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

    public function testAutoKycForTrustIfTrustSocietyNgoBusinessCertificateIsVerified()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([], [
            VerificationDetail\Entity::STATUS => 'verified'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForTrustIfTrustSocietyNgoBusinessCertificateIsNotVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([], [
            VerificationDetail\Entity::STATUS => 'failed'
        ]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $isAutoKycDone = $core->isAutoKycDone($merchantDetail);

        $this->assertFalse($isAutoKycDone);

    }

    public function testAutoKycForTrustIfBankAccountVerified()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS => 'verified'
        ], []);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForTrustIfBankAccountNotVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS => 'failed'
        ], []);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);

    }

    public function testAutoKycForTrustIfPOIVerified()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::POI_VERIFICATION_STATUS => 'verified'
        ], []);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForTrustIfPOINotVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::POI_VERIFICATION_STATUS => 'failed'
        ], []);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);

    }

    public function testAutoKycForTrustIfCompanyPanVerified()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::COMPANY_PAN_VERIFICATION_STATUS => 'verified'
        ], []);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForTrustIfCompanyNotVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::COMPANY_PAN_VERIFICATION_STATUS => 'failed'
        ], []);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);

    }

    public function testAutoKycForTrustIfPoaVerifiedAndAadharEkycNotVerified()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::POA_VERIFICATION_STATUS => 'verified'
        ], [],['aadhaar_esign_status'              => 'failed',
            'aadhaar_verification_with_pan_status' => 'failed']);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForTrustIfPoaNotVerifiedAndAadharEkycVerified()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::POA_VERIFICATION_STATUS => 'failed'
        ], [], ['aadhaar_esign_status'             => 'verified',
            'aadhaar_verification_with_pan_status' => 'verified']);


        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }

    public function testAutoKycForTrustIfPoaNotVerifiedAndAadharEkycNotVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::POA_VERIFICATION_STATUS => 'failed'
        ], [],['aadhaar_esign_status'              => 'failed',
            'aadhaar_verification_with_pan_status' => 'failed']);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);

    }

    public function testAutoKycForTrustIfPoaNotVerifiedAndAadharEkycVerifiedSignatoryVerified()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::POA_VERIFICATION_STATUS => 'failed'
                                                  ], [], ['aadhaar_esign_status'             => 'verified',
                                                          'aadhaar_verification_with_pan_status' => 'verified']);


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

    public function testAutoKycForTrustIfPoaVerifiedAndAadharEkycNotVerifiedSignatoryNotVerified()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::POA_VERIFICATION_STATUS => 'verified'
                                                  ], [],['aadhaar_esign_status'              => 'failed',
                                                         'aadhaar_verification_with_pan_status' => 'failed']);

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

    public function testAutoKycForTrustIfCompanyPanDocIsNotVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::POA_VERIFICATION_STATUS => 'verified',
                                                      Detail\Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS => 'failed'
                                                  ], [],[]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $input = [
            "experiment_id" => "NrGpRcz62UlBKl",
            "id"            => $merchantDetail->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

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
    public function testAutoKycForAllBusinessTypesWhichApplicableForAutoKycIfPanDocIsVerified()
    {

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::POA_VERIFICATION_STATUS => 'verified',
                                                      "cin_verification_status" => "verified",
                                                      "shop_establishment_verification_status" => "verified"
                                                  ], [],[]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $input = [
            "experiment_id" => "NrGpRcz62UlBKl",
            "id"            => $merchantDetail->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);


        $this->fixtures->create(
            'merchant_verification_detail', [
                                              'merchant_id'         => $merchantDetail->getId(),
                                              'artefact_type'       => Constant::SIGNATORY_VALIDATION,
                                              'artefact_identifier' => 'number',
                                              'status'              => 'verified'
                                          ]
        );
        $this->fixtures->create(
            'merchant_verification_detail', [
                                              'merchant_id'         => $merchantDetail->getId(),
                                              'artefact_type'       => Constant::PARTNERSHIP_DEED,
                                              'artefact_identifier' => 'doc',
                                              'status'              => 'verified'
                                          ]
        );
        foreach ([5,4,6,11,13,2,10,9,1,3] as $businessType)
        {
            $merchantDetail = $this->fixtures->edit('merchant_detail', $merchantDetail->getId(), ["business_type"=>$businessType]);

            $isAutoKycDone = $core->isAutoKycDone($merchantDetail);
            $this->assertTrue($isAutoKycDone);
        }

    }
    public function testAutoKycForTrustIfPanDocIsNotVerifiedAndExpOff()
    {

        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
                                                      Detail\Entity::POA_VERIFICATION_STATUS => 'verified',
                                                      Detail\Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS => 'failed'
                                                  ], [],[]);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];

        $input = [
            "experiment_id" => "NrGpRcz62UlBKl",
            "id"            => $merchantDetail->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => '',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);


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
}
