<?php

namespace Unit\Customer;

use Mockery;
use RZP\Http\Controllers\CustomerController;
use RZP\Models\Customer\Entity;
use RZP\Tests\TestCase;
use RZP\Trace\TraceCode;

class CustomerTest extends TestCase
{
//    public function __construct()
//    {
//        parent::__construct("CustomerTest");
////        parent::__construct("testRequestInvalidJson");
//    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->traceMock = $this->mockTrace();
    }

    protected function mockTrace()
    {
        $mock = Mockery::mock('\Razorpay\Trace\Logger');
        $this->app->instance('trace', $mock);
        return $mock;
    }

    public function testRequestInvalidJson()
    {
        $requestPayload = '{
            "name": "customer1",
            "contact": "69381791260",
        }';
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::CUSTOMER_CREATE_PAYLOAD_INFO, ['is_json_valid' => false]);
        (new CustomerController())->logInfoAboutRequestPayload($requestPayload);
    }

    public function testRequestValidJsonWithExpectedDatatypes()
    {
        $requestPayload = '
        {
            "name": "customer1",
            "contact": "69381791260",
            "fail_existing": "0",
            "shipping_address": {
                "name": "shippingaddr",
                "line1": "x",
                "line2": "y",
                "state": "ka",
                "country":"in",
                "primary": true
            },
            "billing_address": {
                "name": "billingaddr1",
                "line1": "x",
                "line2": "y",
                "state": "ka",
                "country":"in",
                "primary": true
            }
        }
        ';
        $this->traceMock->shouldReceive('info')->never()->with(TraceCode::CUSTOMER_CREATE_PAYLOAD_INFO, Mockery::any());
        (new CustomerController())->logInfoAboutRequestPayload($requestPayload);
    }

    public function testRequestValidJsonWithUnexpectedDatatypes1()
    {
        $requestPayload = '
        {
            "name": "customer1",
            "contact": 69381791260,
            "fail_existing": 0.00,
            "shipping_address": [],
            "billing_address": false
        }
        ';
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::CUSTOMER_CREATE_PAYLOAD_INFO, [
            'is_json_valid' => true,
            'data_types' => [
                'contact' => 'integer',
                'fail_existing' => 'double',
                'shipping_address' => 'array',
                'billing_address' => 'boolean'
            ]
        ]);
        (new CustomerController())->logInfoAboutRequestPayload($requestPayload);
    }

    public function testRequestValidJsonWithUnexpectedDatatypes2()
    {
        $requestPayload = '
        {
            "name": "customer1",
            "contact": 69381791260.742,
            "fail_existing": false,
            "shipping_address": {
                "name": "shippingaddr",
                "line1": "x",
                "line2": "y",
                "state": "ka",
                "country":"in",
                "primary": 0
            },
            "billing_address": 0
        }
        ';
        $this->traceMock->shouldReceive('info')->once()->with(TraceCode::CUSTOMER_CREATE_PAYLOAD_INFO, [
            'is_json_valid' => true,
            'data_types' => [
                'contact' => 'double',
                'fail_existing' => 'boolean',
                'shipping_address.primary' => 'integer',
                'billing_address' => 'integer'
            ]
        ]);
        (new CustomerController())->logInfoAboutRequestPayload($requestPayload);
    }

    public function testGlobalCustomerLazyRead()
    {
        $mockCustomerRepo = Mockery::mock('\RZP\Models\Customer\Repository', [$this->app]);
        $mockCustomerEntity = new Entity();
        $mockCustomerId = '100000customer';
        $mockCustomerEntity->fill([
            'id' => $mockCustomerId,
            'name' => 'RzpCustomerName',
            'email' => 'RzpCustomer@email.com',
            'contact' => '9999999999',
            'merchant_id' => '100000Razorpay'
        ]);
        $mockCustomerRepo->shouldReceive('find')->with($mockCustomerId)->andReturn($mockCustomerEntity);

        $mockRepoManager = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);
        $this->app->instance('repo', $mockRepoManager);
        $mockRepoManager->shouldReceive('driver')->with('customer')->andReturn($mockCustomerRepo);


        $customer = new Entity();
        $customer->setAttribute('global_customer_id', $mockCustomerId);
        $this->assertEquals($mockCustomerEntity, $customer->globalCustomer);
    }
}
