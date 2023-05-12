<?php


namespace Unit\Models\Merchant\AutoKyc;

use Mockery;
use RZP\Models\Merchant\Detail;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class MsmeDocVerificationTest extends TestCase
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
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

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
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

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
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

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

    public function testAutoKycForProprietorshipIfMsmeIsNotVerifiedButShopEstablishmentIsVerifiedSignatoryVerified()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $fixtures = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP, [
            Detail\Entity::MSME_DOC_VERIFICATION_STATUS => 'failed',
            Detail\Entity::GSTIN_VERIFICATION_STATUS    => 'failed',
            Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => 'verified'
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

}
