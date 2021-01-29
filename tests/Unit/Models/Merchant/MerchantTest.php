<?php

namespace Tests\Unit\Models\Merchant;


use Mockery;
use RZP\Models\Merchant\Detail\Repository;
use RZP\Models\Merchant\Service;
use RZP\Tests\Functional\Fixtures\Entity\MerchantDetail;
use Tests\Unit\TestCase;

class UserTest extends TestCase
{
    protected $merchantService;

    protected $userEntityMock;

    protected $merchantEntityMock;

    protected $merchantRepoMock;


    public function setUp()
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

    public function createTestDependencyMocks()
    {
        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');

        $this->userEntityMock = Mockery::mock('RZP\Models\User\Entity');

        $this->basicAuthMock->shouldReceive('getUser')->andReturn($this->userEntityMock);

        $this->basicAuthMock->shouldReceive('getMerchant')->andReturn($this->merchantEntityMock);
    }
}
