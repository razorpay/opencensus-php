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
        $this->testDataFilePath = __DIR__.'/helpers/CustomerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->editFeatures('tokens');

        $this->fixtures->merchant->editFeatures('cardsaving');
    }

    public function testAddCustomerTokenCard()
    {
        $this->fixtures->create('card', ['id' => '10000savedcard']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testAddCustomerTokenWallet()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testAddCustomerTokenNetbanking()
    {
        $this->ba->proxyAuth();

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

    public function testDeleteCustomerTokenById()
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
        $this->mockSession();

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals(isset($response['email']), false);
    }

    public function testFetchSavedCustomerStatusWithDeviceToken()
    {
        $this->mockSession();

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
            'test_app_token'   => 'capp_1000000custapp',
            'test_checkcookie' => '1'
        );

        $this->session($data);
    }
}