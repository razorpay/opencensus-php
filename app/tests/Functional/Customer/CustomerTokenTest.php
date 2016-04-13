<?php

namespace Tests\Functional\CustomerToken;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class CustomerTokenTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/CustomerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->editFeatures("tokens");
    }

    public function testAddCustomerTokenCard()
    {
        $this->fixtures->create('card', ['id' => '10000savedcard']);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testAddCustomerTokenWallet()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testAddCustomerTokenNetbanking()
    {
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
        $this->ba->proxyAuth();

        return $this->startTest();
    }

    public function testDeleteCustomerToken()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomerTokensByAppId()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusSaved()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchSavedTokensStatusNotSaved()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testDeleteAppToken()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }
}