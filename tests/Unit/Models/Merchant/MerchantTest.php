<?php

namespace Tests\Unit\Models\Merchant;


use Mockery;
use RZP\Models\Merchant\Detail\BusinessType;
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

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $submerchant->shouldReceive('setMaxPaymentAmount')->andReturn();

        $result = $this->merchantCore->setSubMerchantMaxPaymentAmount($this->merchantEntityMock,$submerchant,BusinessType::INDIVIDUAL);

        $submerchant->shouldHaveReceived('setMaxPaymentAmount')->once();

        $this->assertNull($result);
    }

    public function testSetSubMerchantMaxPaymentNotCalled()
    {
        $submerchant = $this->merchantEntityMock;

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('10000000000001');

        $submerchant->shouldReceive('setMaxPaymentAmount')->andReturn();

        $result = $this->merchantCore->setSubMerchantMaxPaymentAmount($this->merchantEntityMock,$submerchant,BusinessType::INDIVIDUAL);

        $submerchant->shouldNotHaveReceived('setMaxPaymentAmount');

        $this->assertNull($result);
    }

    public function createTestDependencyMocks()
    {
        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');

        $this->merchantCore = Mockery::mock('RZP\Models\Merchant\Core');

        $this->userEntityMock = Mockery::mock('RZP\Models\User\Entity');

        $this->basicAuthMock->shouldReceive('getUser')->andReturn($this->userEntityMock);

        $this->basicAuthMock->shouldReceive('getMerchant')->andReturn($this->merchantEntityMock);
    }
}
