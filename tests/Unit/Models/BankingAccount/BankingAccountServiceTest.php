<?php

namespace Unit\Models\BankingAccount;

use Mockery;
use RZP\Tests\TestCase;
use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount\Service;
use RZP\Models\BankingAccount\Activation\Detail\Region;

class BankingAccountServiceTest extends TestCase
{

    protected $bankingAccountService;

    protected $bankingAccountCoreMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('applications.pincodesearcher.mock', true);
        $this->app['config']->set('applications.banking_account.mock', false);

        $this->bankingAccountCoreMock = Mockery::mock('RZP\Models\BankingAccount\Core')->makePartial();

        $this->bankingAccountService = new Service($this->app['pincodesearch'], $this->bankingAccountCoreMock);
    }

    public function testCheckServiceableByRBLValid()
    {
        $content = "110020";

        $requestResponse = [28.5388479, 77.2753728, null];

        $this->bankingAccountCoreMock->shouldReceive('getLocationFromPincode')->andReturn($requestResponse);

        $response = $this->bankingAccountService->CheckServiceableByRBL($content);

        $this->assertTrue($response['serviceability']);
    }

    public function testCheckServiceableByRBLInvalid()
    {
        $content = "110";

        $expected = ['serviceability' => false,
                     'errorMessage'   => "PINCODE is not valid"];

        $response = $this->bankingAccountService->CheckServiceableByRBL($content);

        $this->assertEquals($expected, $response);
    }

    public function testCheckServiceableByRBLGMapApiFailure()
    {
        $content = "110020";

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_INTEGRATION_ERROR);

        $response = (new Service($this->app['pincodesearch']))->CheckServiceableByRBL($content);

        $this->assertTrue($response['serviceability']);
    }

    public function testStateToRegionMapping()
    {
        // case-sensitive check
        $region = (new Region)->getRegionFromState(Region::Maharashtra);

        $this->assertEquals(Region::WEST, $region);

        // case-insensitive check
        $region = (new Region)->getRegionFromState('maHarasHTrA');

        $this->assertEquals(Region::WEST, $region);
    }
}
