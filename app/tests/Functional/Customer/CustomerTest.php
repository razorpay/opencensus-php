<?php

namespace Tests\Functional\Customer;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class CustomerTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/CustomerTestData.php';

        parent::setUp();

        $customer = $this->fixtures->create('customer:default_customers');
    }

    public function testCreateCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        assert($customer !== null);
    }

    public function testUpdateCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();        
    }

    public function testDeleteCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }
}