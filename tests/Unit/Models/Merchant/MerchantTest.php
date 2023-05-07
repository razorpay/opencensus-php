<?php

namespace Tests\Unit\Models\Merchant;


use Mockery;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Service;
use RZP\Tests\Functional\Fixtures\Entity\MerchantDetail;
use Tests\Unit\TestCase;

class UserTest extends TestCase
{
    protected $merchantService;

    protected $userEntityMock;

    protected $merchantEntityMock;

    protected $merchantRepoMock;

    protected $merchantCore;

    protected $partnerSubMerchantConfigCoreMock;

    protected $partnerConfigEntityMock;


    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestDependencyMocks();

        $this->merchantService = new Service();
    }

    public function testEnableBusinessBankingIfApplicable()
    {
        $r = new \ReflectionMethod('RZP\Models\Merchant\Service', 'enableBusinessBankingIfApplicable');

        $r->setAccessible(true);

        $this->basicAuthMock->shouldReceive('isProductBanking')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('isBusinessBankingEnabled')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('setBusinessBanking')->andReturn();

        $response = $r->invoke($this->merchantService, $this->merchantEntityMock);

        $this->assertEquals(true, $response);
    }

    public function testSetSubMerchantMaxPaymentCalled()
    {
        $submerchant = $this->merchantEntityMock;

        $this->partnerSubMerchantConfigCoreMock->shouldReceive('fetchPartnerSubMerchantConfig')
                                    ->andReturn([["value"=>"20000000","business_type"=>"individual"]]);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $submerchant->shouldReceive('setMaxPaymentAmount')->andReturn();

        $submerchant->shouldReceive('isPartner')->andReturn(true);

        $result = $this->merchantCore->setSubMerchantMaxPaymentAmount($this->merchantEntityMock,$submerchant,BusinessType::INDIVIDUAL);

        $submerchant->shouldHaveReceived('setMaxPaymentAmount')->once();

        $this->assertNull($result);
    }

    public function testSetSubMerchantMaxPaymentNotCalled()
    {
        $submerchant = $this->merchantEntityMock;

        $this->partnerSubMerchantConfigCoreMock->shouldReceive('fetchPartnerSubMerchantConfig')
                                    ->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('10000000000001');

        $submerchant->shouldReceive('setMaxPaymentAmount')->andReturn();

        $submerchant->shouldReceive('isPartner')->andReturn(true);

        $result = $this->merchantCore->setSubMerchantMaxPaymentAmount($this->merchantEntityMock,$submerchant,BusinessType::INDIVIDUAL);

        $submerchant->shouldNotHaveReceived('setMaxPaymentAmount');

        $this->assertNull($result);
    }

    public function testAddPartnerAddedFeaturesToSubmerchantWhenPartnerIsNull()
    {
        $coreMock = $this->merchantCore->makePartial();
        $submerchant = $this->merchantEntityMock;

        $result = $coreMock->addPartnerAddedFeaturesToSubmerchant($submerchant, null);

        $coreMock->shouldNotHaveReceived("isRazorxExperimentEnable");
        $coreMock->shouldNotHaveReceived("addPartnerAddedFeaturesToSubmerchantOnMode");
        $this->assertNull($result);
    }

    public function testAddPartnerAddedFeaturesToSubmerchantWhenPartnerIsNotNull()
    {
        $coreMock = $this->merchantCore->makePartial();
        $submerchant = $this->merchantEntityMock;
        $partner = $this->merchantEntityMock;
        $partner->shouldReceive('getId')->andReturn('10000000000001');

        $coreMock->shouldReceive('addPartnerAddedFeaturesToSubmerchantOnMode')
                 ->with($submerchant, $partner, Mode::TEST)->andReturn();
        $coreMock->shouldReceive('addPartnerAddedFeaturesToSubmerchantOnMode')
                 ->with($submerchant, $partner, Mode::LIVE)->andReturn();

        $result = $coreMock->addPartnerAddedFeaturesToSubmerchant($submerchant, $this->merchantEntityMock);

        $coreMock->shouldHaveReceived("addPartnerAddedFeaturesToSubmerchantOnMode")->twice();
        $this->assertNull($result);
    }

    public function createTestDependencyMocks()
    {
        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');

        $this->merchantCore = Mockery::mock('RZP\Models\Merchant\Core')->makePartial();

        $this->userEntityMock = Mockery::mock('RZP\Models\User\Entity');

        $this->partnerSubMerchantConfigCoreMock = Mockery::mock('overload:\RZP\Models\Partner\Config\SubMerchantConfig\Core');

        $this->partnerConfigEntityMock = Mockery::mock('\RZP\Models\Partner\Config\Entity');

        $this->basicAuthMock->shouldReceive('getUser')->andReturn($this->userEntityMock);

        $this->basicAuthMock->shouldReceive('getMerchant')->andReturn($this->merchantEntityMock);
    }
}
