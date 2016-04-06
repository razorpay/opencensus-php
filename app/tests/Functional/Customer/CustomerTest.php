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

    public function testOtpFlow()
    {
        $this->ba->publicAuth();

        // send OTP

        $request = array(
            'url' => '/otp/create',
            'method' => 'post',
            'content' => [
                "contact" => "1234567890"
            ],
        );

        $response = $this->makeRequest($request);

        // verify OTP
        $request = array(
            'url' => '/otp/verify',
            'method' => 'post',
            'content' => [
                'contact' => '1234567890',
                'otp' => '233323',
                'device_id' => 'rzp_device_id'
            ],
        );

        $response = $this->makeRequest($request);
    }
}