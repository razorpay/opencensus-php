<?php

namespace RZP\Tests\Functional\CustomerToken;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class CustomerTokenTest extends TestCase
{
    use PaymentTrait;
    use InteractsWithSession;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/CustomerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->editFeatures("tokens,cardsaving");
    }

    public function testAddCustomerTokenCard()
    {
        $this->ba->proxyAuth();

        $this->mockTokenex();

        $this->startTest();
    }

    public function testAddCustomerTokenWallet()
    {
        $this->markTestSkipped();

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testAddCustomerTokenNetbanking()
    {
        $this->markTestSkipped();

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGetCustomerTokens()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomerToken()
    {
        $this->ba->privateAuth();

        return $this->startTest();
    }

    public function testDeleteCustomerToken()
    {
        $this->mockSession();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomerTokensByAppToken()
    {
        $this->mockSession();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusSaved()
    {
        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals(isset($response['email']), false);
    }

    public function testFetchSavedCustomerStatusWithDeviceToken()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusNotSaved()
    {
        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals(isset($response['email']), false);
    }

    public function testDeleteAppToken()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testLogoutFromApp()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testLogoutFromDevice()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testLogoutFromAllDevices()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    protected function mockSession()
    {
        $data = array(
            'test_app_token' => 'capp_1000000custapp'
        );

        $this->session($data);
    }
}