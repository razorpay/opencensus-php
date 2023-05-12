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

class SocietyAutokycTest extends TestCase
{
    protected $splitzMock;

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

    protected function getSplitzMock()
    {
        if ($this->splitzMock === null)
        {
            $this->splitzMock = Mockery::mock(SplitzService::class, [$this->app])->makePartial();

            $this->app->instance('splitzService', $this->splitzMock);
        }

        return $this->splitzMock;
    }

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

    protected function createAndFetchFixtures($customMerchantAttributes, $customVerificationDetailAttributes, $customStakeHolderDetailAttributes = [])
    {
        $defaultMerchantAttributes = [
            'poi_verification_status'              => 'verified',
            'company_pan_verification_status'      => 'verified',
            'poa_verification_status'              => 'verified',
            'bank_details_verification_status'     => 'verified',
            'bank_details_doc_verification_status' => null,
            'business_type'                        => BusinessType::getIndexFromKey(BusinessType::SOCIETY)
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

    public function testAutoKycForSocietyIfTrustSocietyNgoBusinessCertificateIsVerified()
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

    public function testAutoKycForSocietyIfTrustSocietyNgoBusinessCertificateIsNotVerified()
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

    public function testAutoKycForSocietyIfBankAccountVerified()
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

    public function testAutoKycForSocietyIfBankAccountNotVerified()
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

    public function testAutoKycForSocietyIfPOIVerified()
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

    public function testAutoKycForSocietyIfPOINotVerified()
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

    public function testAutoKycForSocietyIfCompanyPanVerified()
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

    public function testAutoKycForSocietyIfCompanyNotVerified()
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

    public function testAutoKycForSocietyIfPoaVerifiedAndAadharEkycNotVerified()
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
        ], [], ['aadhaar_esign_status'             => 'failed',
            'aadhaar_verification_with_pan_status' => 'failed']);


        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertTrue($isAutoKycDone);

    }
    public function testAutoKycForSocietyIfPoaNotVerifiedAndAadharEkycVerified()
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

    public function testAutoKycForSocietyIfPoaNotVerifiedAndAadharEkycNotVerified()
    {
        $this->mockRazorxTreatment();

        $fixtures = $this->createAndFetchFixtures([
            Detail\Entity::POA_VERIFICATION_STATUS => 'failed'
        ], [] ,['aadhaar_esign_status'                 => 'failed',
            'aadhaar_verification_with_pan_status' => 'failed']);

        $core = new Detail\Core();

        $merchantDetail = $fixtures['merchant_detail'];
        $isAutoKycDone  = $core->isAutoKycDone($merchantDetail);
        $this->assertFalse($isAutoKycDone);

    }
}
