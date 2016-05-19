<?php

namespace Tests\Functional\Customer;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class CustomerTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/CustomerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->editFeatures("tokens");
    }

    public function testCreateCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerEmailOnly()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerContactOnly()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerDuplicatePhone()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerDuplicateEmail()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerDuplicate()
    {
        $this->ba->privateAuth();

        $this->startTest();
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
        $this->testCreateCustomer();

        $customer = $this->getLastEntity('customer', true);

        $request = &$this->testData['testDeleteCustomer']['request'];
        $request['url'] = '/customers/'.$customer['id'];

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testOtpFlow()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

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
                'otp' => '233323'
            ],
        );

        $content = $this->makeRequestAndGetContent($request);
        assert(empty($content['app_id']) === false);
    }

    protected function mockRaven()
    {
        $raven = Mockery::mock('Services\Raven')->makePartial();

        $this->app->instance('raven', $raven);

        $raven->shouldReceive('sendRequest')
              ->with(Mockery::type('string'), 'post', Mockery::type('array'))
              ->andReturnUsing(function ($route, $method, $input)
                    {
                        $response = array(
                            "success" => true,
                        );

                        switch ($route)
                        {
                            case 'sms/send-otp':
                                $response['message'] = 'message queued.';
                                break;

                            case 'sms/verify-otp':
                                break;
                        }

                        return $response;
                    });

        $this->app->instance('raven', $raven);
    }
}