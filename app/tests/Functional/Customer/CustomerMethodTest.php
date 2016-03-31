<?php

namespace Tests\Functional\CustomerMethod;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class CustomerMethodTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/CustomerTestData.php';

        parent::setUp();
    }

    public function testAddCustomerMethodCard()
    {
        $this->fixtures->create('card', ['id' => '10000savedcard']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAddCustomerMethodWallet()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAddCustomerMethodNetbanking()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomerMethods()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomerMethod()
    {
        $this->ba->privateAuth();

        return $this->startTest();
    }

    public function testDeleteCustomerMethod()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }
}