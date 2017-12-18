<?php

namespace RZP\Tests\Functional\Customer;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class CustomerTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CustomerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['tokens']);
    }

    public function testCreateCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerWithValidNames()
    {
        $this->ba->privateAuth();

        //
        // To create different unique customers. Uniqueness is asserted in code
        // by email & contact.
        //
        $validNameEmailMap = [
            'Sample name'        => 'test1@test.razorpay.com',
            'ABC Corp Pvt. Ltd.' => 'test2@test.razorpay.com',
            'ABC Corp (Pvt)'     => 'test3@test.razorpay.com',
            'Sample\'d name'     => 'test4@test.razorpay.com',
            'A & B pvt ltd'      => 'test5@test.razorpay.com',
            'A-B pvt Ltd'        => 'test6@test.razorpay.com',
            'A-B pvt (test) Ltd' => 'test7@test.razorpay.com',
            'M-dash–Name'        => 'test8@test.razorpay.com',                 //Names with m-dash should be valid (–)
            'Underscore_ABC'     => 'test9@test.razorpay.com',
        ];

        $testData = & $this->testData[__FUNCTION__];

        foreach ($validNameEmailMap as $name => $email)
        {
            $testData['request']['content']['name'] = $testData['response']['content']['name'] = $name;
            $testData['request']['content']['email'] = $testData['response']['content']['email'] = $email;

            $this->startTest();
        }
    }

    public function testCreateCustomerWithLeadingOrTrailingSpaces()
    {
        $this->ba->privateAuth();

        $validNameEmailMap = [
            '   Sample name'        => 'test1@test.razorpay.com',
            'Sample name   '        => 'test2@test.razorpay.com'
        ];

        $testData = & $this->testData[__FUNCTION__];

        foreach ($validNameEmailMap as $name => $email)
        {
            $testData['request']['content']['name']  = $name;
            $testData['response']['content']['name'] = trim($name); // In db it should get mutated(trimmed) before persistence

            $testData['request']['content']['email'] = $testData['response']['content']['email'] =  $email;

            $this->startTest();
        }

    }

    public function testCreateCustomerWithInvalidNames()
    {
        $this->ba->privateAuth();

        $invalidNameErrorMap = [
            'Sample"s name'                                       => 'The name format is invalid.',
            'A very big big big name off some big big big person' => 'The name may not be greater than 50 characters.',
            'A weird? name'                                       => 'The name format is invalid.',
            '-AB weird name'                                     => 'The name format is invalid.',
            '  -AB weird name'                                   => 'The name format is invalid.', // Validation must happens on trimmed value
        ];

        $testData = & $this->testData[__FUNCTION__];

        foreach ($invalidNameErrorMap as $name => $error)
        {
            $testData['request']['content']['name']                  = $name;
            $testData['response']['content']['error']['description'] = $error;

            $this->startTest();
        }
    }

    public function testCreateCustomerEmailOnly()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);

        $this->assertNotNull($customer);
    }

    public function testCreateCustomerUppercaseEmailOnly()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $customer = $this->getLastEntity('customer', true);
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

    public function testCreateCustomerDuplicateDontFail()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUpdateCustomer()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUpdateCustomerName()
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

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOtpFlowWithoutDeviceToken()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233323');

        $this->assertEquals($content['success'], 1);
    }

    public function testOtpFlowWithDeviceToken()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123');

        $this->assertEquals($content['success'], 1);
    }

    public function testOtpFlowForAndroidSdk()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123', true);

        $this->assertEquals($content['success'], 1);

        $this->assertNotEquals($content['tokens'], null);
    }

    public function testOtpFlowWithInvalidNumber()
    {
        $this->ba->publicAuth();

        $this->mockRaven();

        // send OTP
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->sendOtp('4637346743722');
        });
    }

    protected function sendOtp($contact)
    {
        $request = array(
            'url' => '/otp/create',
            'method' => 'post',
            'content' => [
                'contact' => $contact
            ],
        );

        $response = $this->sendRequest($request);

        return $response;
    }


    protected function verifyOtp($contact, $email, $otp, $deviceToken = null, $metadata=false)
    {
        $content = [
            'contact' => $contact,
            'email' => $email,
            'otp' => $otp
        ];

        if ($deviceToken !== null)
        {
            $content['device_token'] = $deviceToken;
        }

        if ($metadata)
        {
            $content['_']['platform'] = 'android';
            $content['_']['library'] = 'checkoutjs';
            $content['_']['version'] = '1.0.0';
        }

        $request = array(
            'url' => '/otp/verify',
            'method' => 'post',
            'content' => $content
        );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function mockRaven()
    {
        $raven = Mockery::mock('RZP\Services\Raven')->makePartial();

        $this->app->instance('raven', $raven);

        $raven->shouldReceive('sendRequest')
              ->with(Mockery::type('string'), 'post', Mockery::type('array'))
              ->andReturnUsing(function ($route, $method, $input)
                    {
                        $response = array(
                            'success' => true,
                        );

                        return $response;
                    });

        $this->app->instance('raven', $raven);
    }
}
