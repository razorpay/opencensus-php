<?php

namespace Unit\Customer;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Entity;
use RZP\Services\CMS\Service;
use RZP\Services\SplitzService;
use RZP\Models\Customer\Core;
use RZP\Tests\TestCase;
use Mockery;
use RZP\Trace\TraceCode;
use RZP\Exception;

class CMSTest extends TestCase
{
    protected $splitzMock;
    protected function setUp(): void
    {
        parent::setUp();
        $this->traceMock = $this->createTraceMock();
        $this->mockInstanceV2 = $this->getMockBuilder(Service::class)
            ->onlyMethods(['sendCMSRequest','transformV1CreateOptionsToV2CreateOptions','sendRequest'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->app['config']->set(include 'config/applications.php');

        $this->mockInstance = $this->getMockBuilder(Service::class)
            ->onlyMethods(['sendCMSRequest'])
            ->setConstructorArgs([$this->app])->getMock();

        $reflection = new \ReflectionClass(get_parent_class($this->mockInstance));
        $property = $reflection->getProperty('trace'); // Access protected 'trace'
        $property->setAccessible(true);
        $property->setValue($this->mockInstance, $this->traceMock); // Set the value to $traceMock


        $this->app->singleton('rzp.mode', function () {
            return 'test'; // Set the mode you want to test
        });

        $this->coreMock = $this->getMockBuilder(Core::class)
            ->onlyMethods([
                'isAddressPresentInRequest',
                'isCreateOverrideToCmsEnabled',
                'createCustomerAddressesIfValuesSetInInput',
                'verifyUniqueCustomer'
            ])
            ->setConstructorArgs([$this->traceMock])
            ->getMock();

    }
    protected function mockTrace()
    {
        $mock = Mockery::mock('\Razorpay\Trace\Logger');
        $this->app->instance('trace', $mock);
        return $mock;
    }
    protected static function getProtectedMethod($method,$class) {
        $reflection = new \ReflectionClass($class);
        $method = $reflection->getMethod($method);

        // Make the method accessible if it is protected or private
        $method->setAccessible(true);

        return $method;
    }


    public function testIsSplitzOn()
    {
        #T1 starts - isSplitzOn - True
        $output = [
            'status_code' => 200,
            'response' => [
                'id' => 'O2Z3vbS94pgFs8',
                'project_id' => 'P0HqKztn6Z3ulA',
                'experiment' => [
                    'id' => 'PLF7pETYSbsXnO',
                    'name' => 'cms_create_override_test',
                    'exclusion_group_id' => '',
                ],
                'variant' => [
                    'id' => 'PLF7pEctDSEBhk',
                    'name' => 'enabled',
                    'variables' => [
                        [
                            'key' => 'result',
                            'value' => true,
                        ],
                    ],
                    'experiment_id' => 'PLF7pETYSbsXnO',
                    'weight' => 100,
                ],
                'reason' => 'bucketer',
                'steps' => [
                    'sampler',
                    'exclusion',
                    'audience',
                    'assign_bucket',
                ],
            ]
        ];
        $properties = [
            'id'            => 'O2Z3vbS94pgFs8',
            'experiment_id' => "PLF7pETYSbsXnO",
            'request_data'  => json_encode([
                'merchantId' => 'O2Z3vbS94pgFs8',
                'internal_app_name' => 'payment_links',
                'mode' => 'test',
                'route_name' => 'customer_create',
                'country' => 'in',
            ]),
        ];

        // Create a mock for Splitz and define its behavior
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->once())
            ->method('evaluateRequest')
            ->with($properties)
            ->willReturn($output);
        $splitzHelper = new Core();

        $inputMerchant = new Entity();
        $inputMerchant->fill(['id' => 'O2Z3vbS94pgFs8', 'country_code' => 'in']);


        $result = $splitzHelper->isCreateOverrideToCmsEnabled($inputMerchant, 'test', 'payment_links', 'customer_create');
        $this->assertTrue($result);
        #T1 ends

        #T2 starts - isSplitzOn - False - status code 400
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $output = [
            'status_code' => 400,
            'response' => [
            ]
        ];

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())
            ->method('evaluateRequest')
            ->willReturn($output);
        $splitzHelper = new Core();
        $result = $splitzHelper->isCreateOverrideToCmsEnabled($inputMerchant, 'test', 'payment_links', 'customer_create');
        $this->assertFalse($result);
        #T2 ends

        #T3 starts - isSplitzOn - False - status code 200
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $output = [
            "status_code" => 200,
            "response" => [
                "id" => "O2Z3vbS94pgFs8",
                "project_id" => "",
                "experiment" => [
                    "id" => "PL7pETYSbsXnO",
                    "name" => "",
                    "exclusion_group_id" => ""
                ],
                "variant" => null,
                "Reason" => "",
                "steps" => []
            ]
        ];

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())
            ->method('evaluateRequest')
            ->willReturn($output);
        $splitzHelper = new Core();
        $result = $splitzHelper->isCreateOverrideToCmsEnabled($inputMerchant, 'test', 'payment_links', 'customer_create');
        $this->assertFalse($result);
        #T3 ends

        #T4 starts - isSplitzOn - False - Exception occurred while evaluating splitz
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->once())
            ->method('traceException')
            ->with(
                $this->anything(),
                $this->anything(),
                TraceCode::CMS_REQUEST_SPLITZ_ERROR
            );

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())
            ->method('evaluateRequest')
            ->willThrowException(new \Exception('some error encountered while calling splitz'));

        $splitzHelper = new Core();
        $result = $splitzHelper->isCreateOverrideToCmsEnabled($inputMerchant, 'test', 'payment_links', 'customer_create');
        $this->assertFalse($result);
        #T4 ends
    }
    public function testIsAddressPresentInRequest_AddressPresent()
    {
        $scenarios = [
            'All Fields Provided' => [
                'input' => [
                    "name" => "Manish Walia",
                    "contact" => "9123456780",
                    "email" => "manish@gmail.com",
                    "fail_existing" => "0",
                    "notes" => [
                        "notes_key_1" => "Tea, Earl Grey, Hot",
                        "notes_key_2" => "Tea, Earl Grey… decaf."
                    ],
                    "shipping_address" => [
                        "name" => "shippingaddr",
                        "line1" => "x",
                        "line2" => "y",
                        "state" => "ka",
                        "country" => "in",
                        "primary" => true
                    ],
                    "billing_address" => true
                ],
                'expected' => [
                    'result' => true
                ],
            ],
            'Missing Shipping Address' => [
                'input' => [
                    "name" => "Manish Walia",
                    "contact" => "9123456780",
                    "email" => "manish@gmail.com",
                    "fail_existing" => "0",
                    "notes" => [
                        "notes_key_1" => "Tea, Earl Grey, Hot",
                        "notes_key_2" => "Tea, Earl Grey… decaf."
                    ],
                    "billing_address" => true
                ],
                'expected' => [
                    'result' => true
                ],
            ],
            'Missing billing_address' => [
                'input' => [
                    "name" => "Manish Walia",
                    "contact" => "9123456780",
                    "email" => "manish@gmail.com",
                    "fail_existing" => "0",
                    "notes" => [
                        "notes_key_1" => "Tea, Earl Grey, Hot",
                        "notes_key_2" => "Tea, Earl Grey… decaf."
                    ],
                    "shipping_address" => [
                        "name" => "shippingaddr",
                        "line1" => "x",
                        "line2" => "y",
                        "state" => "ka",
                        "country" => "in",
                        "primary" => true
                    ]
                ],
                'expected' => [
                    'result' => true
                ],
            ],
            'Missing_addresses' => [
                'input' => [
                    "name" => "Manish Walia",
                    "contact" => "9123456780",
                    "email" => "manish@gmail.com",
                    "fail_existing" => "0",
                    "notes" => [
                        "notes_key_1" => "Tea, Earl Grey, Hot",
                        "notes_key_2" => "Tea, Earl Grey… decaf."
                    ]
                ],
                'expected' => [
                    'result' => false
                ],
            ],
        ];

        // Call the method
        foreach ($scenarios as $scenario => $data) {
            $core = new Core();
            $result = $core->isAddressPresentInRequest($data['input']);
            $this->assertSame($data['expected']['result'], $result, "Failed on scenario: $scenario");
        }
    }
    public function testTransformFunction()
    {
        $scenarios = [
            'All Fields Provided' => [
                'input' => [
                    'opt' => [
                        'name' => 'Manish Walia',
                        'contact' => '923123456780',
                        'email' => 'waliamanish@gmail.com',
                        'notes' => [
                            'notes_key_1' => 'Tea, Earl Grey, Hot',
                            'notes_key_2' => 'Tea, Earl Grey… decaf.',
                        ],
                        'gstin' => '22ABCDE1234F2Z5',
                        "global_customer_id" => "5QVhegZI7z2qjQ"
                    ],
                    'merchantId' => 'merchant123',
                ],
                'expected' => [
                    'salutation'      => null,
                    'first_name'      => 'Manish Walia',
                    'middle_name'     => null,
                    'last_name'       => null,
                    'email'           => 'waliamanish@gmail.com',
                    'contact'         => '923123456780',
                    'notes'           => (object)[
                        'notes_key_1' => 'Tea, Earl Grey, Hot',
                        'notes_key_2' => 'Tea, Earl Grey… decaf.',
                    ],
                    'gender'          => null,
                    'dob'             => null,
                    'tax_details'     => [
                        [
                        'value' => '22ABCDE1234F2Z5',
                        'type'  => 'IN_GST'
                            ]
                    ],
                    'merchant_id'     => 'merchant123',
                    'custom_data'     => (object)[
                        'global_customer_id' => '5QVhegZI7z2qjQ'
                    ],
                ],
            ],
            'Missing Optional Fields' => [
                'input' => [
                    'opt' => [
                        'name' => 'manish walia',
                        'email' => 'waliamanish@gmail.com',
                        'notes'           => [
                            "Tea, Earl Grey, Hot",
                            67
                        ],
                    ],
                    'merchantId' => 'merchant12',
                ],
                'expected' => [
                    'salutation'      => null,
                    'first_name'      => "manish walia",
                    'middle_name'     => null,
                    'last_name'       => null,
                    'email'           => 'waliamanish@gmail.com',
                    'contact'         => null,
                    'notes'           => (object)[
                        "0" => "Tea, Earl Grey, Hot",
                        "1" => 67
                    ],
                    'gender'          => null,
                    'dob'             => null,
                    'tax_details'     => null,
                    'merchant_id'     => 'merchant12',
                    'custom_data'     => (object)[
                    ],
                ],
            ],
            'Empty Input' => [
                'input' => [
                    'opt' => [
                        'name' => "manish",
                        'notes'           => [
                            'notes_key_0' => "Tea, Earl Grey, Hot",
                            'notes_key_1' => 67
                        ],
                        'email' => 'waliamanish@gmail.com',
                    ],
                    'merchantId' => 'merchant12345',
                ],
                'expected' => [
                    'salutation'      => null,
                    'first_name'      => 'manish',
                    'middle_name'     => null,
                    'last_name'       => null,
                    'email'           => 'waliamanish@gmail.com',
                    'contact'         => null,
                    'notes'           => (object)[
                        'notes_key_0' => "Tea, Earl Grey, Hot",
                        'notes_key_1' => 67
                    ],
                    'gender'          => null,
                    'dob'             => null,
                    'tax_details'     => null,
                    'merchant_id'     => 'merchant12345',
                    'custom_data'     => (object)[],
                ],
            ],
            'No Notes Provided' => [
                'input' => [
                    'opt' => [
                        'name' => 'Manish Walia',
                        'contact' => '912345236780',
                        'email' => 'manish@gmail.com',
                        'gstin' => '22ABCDE1234F2Z5',
                    ],
                    'merchantId' => 'merchant123',
                ],
                'expected' => [
                    'salutation'      => null,
                    'first_name'      => 'Manish Walia',
                    'middle_name'     => null,
                    'last_name'       => null,
                    'email'           => 'manish@gmail.com',
                    'contact'         => '912345236780',
                    'notes'           => (object)[], // No notes provided
                    'gender'          => null,
                    'dob'             => null,
                    'tax_details'     => [
                        [
                        'value' => '22ABCDE1234F2Z5',
                        'type'  => 'IN_GST'
                        ]
                    ],
                    'merchant_id'     => 'merchant123',
                    'custom_data'      => (object)[

                    ],
                ],
            ]
        ];
        foreach ($scenarios as $scenario => $data) {
            $input = $data['input'];
            $expected = $data['expected'];

            $service = new Service($this->app);
            $result = $service->transformV1CreateOptionsToV2CreateOptions($input['opt'], $input['merchantId']);

            // Assert that the result matches the expected output
            $this->assertEquals($expected, $result, "Failed on scenario: $scenario");
        }
    }
    public function testSendCMSRequestSuccessIsTrue()
    {
        //1st Test : When Success is true
        $url = "v2/internal/customers";
        $method = "post";
        $mockResponse = new class {
            public $status_code = 200;
            public $body = null;
        };

        $mockResponse->body = json_encode(['message' => 'Success', 'data' => ['name' => "Manish Walia"]]);
        $this->mockInstance->expects($this->once())
            ->method('sendCMSRequest')
            ->with($this->isType('array'), $this->isType('bool'))
            ->willReturn($mockResponse);

        $this->traceMock->expects($this->never())
            ->method('error')
            ->with(
                TraceCode::CMS_REQUEST_ERROR,
                [
                    'body'        =>  $mockResponse->body,
                    'status_code' => $mockResponse->status_code
                ]
            );

        $this->traceMock->expects($this->never())
            ->method('error')
            ->with(
                TraceCode::CMS_INVALID_JSON_RESPONSE,
                [
                    'body'        =>  $mockResponse->body,
                    'status_code' => $mockResponse->status_code
                ]
            );

        $response = $this->mockInstance->sendRequest($url, $method, ['name' => 'Manish Walia']);
        $this->assertEquals('Success', $response['message'], 'Message should be "Success"');
        $this->assertEquals("Manish Walia", $response['data']['name'], 'Data ID should be 123');
    }

    public function testSendRequestThrowsExceptionWhenInvalidStatusCode(){
        //2nd Test invalid status Code:
        $mockInstance = $this->getMockBuilder(Service::class)
            ->onlyMethods(['sendCMSRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResponse = new class {
            public $status_code = 400;
            public $body = [
                "message" => "hello World"
            ];
        };


        $this->mockInstance->expects($this->once())
            ->method('sendCMSRequest')
            ->with($this->isType('array'), $this->isType('bool'))
            ->willReturn($mockResponse);

        $traceMock = $this->createTraceMock();
        $this->traceMock->expects($this->once())
            ->method('error')
            ->with(
                TraceCode::CMS_REQUEST_ERROR,
                [
                    'body'        =>  $mockResponse->body,
                    'status_code' =>  $mockResponse->status_code
                ]
            );
        $this->expectException('RZP\Exception\ServerErrorException');
        $this->expectExceptionMessage("Request Failed to CMS");
        $this->expectExceptionCode(\RZP\Error\ErrorCode::SERVER_ERROR_INVALID_RESPONSE);
        $this->mockInstance->sendRequest('/test-url', 'POST', []);

        $this->mockInstance->sendRequest('/test-url', 'POST', []);
    }

    public function testSendRequestThrowsExceptionWhenInvalid_Json(){
        $mockResponse = new class {
            public $status_code = 200;
            public $body = '{"message": "hellggvo world"'; // Not a valid JSON string
        };

        $this->mockInstance->expects($this->once())
            ->method('sendCMSRequest')
            ->with($this->isType('array'), $this->isType('bool'))
            ->willReturn($mockResponse);

        $this->traceMock->expects($this->once())
            ->method('error')
            ->with(
                TraceCode::CMS_INVALID_JSON_RESPONSE,
                [
                    'body'        =>  $mockResponse->body,
                    'status_code' => $mockResponse->status_code
                ]
            );
        $this->expectException('RZP\Exception\ServerErrorException');
        $this->expectExceptionMessage("Request Failed to CMS");
        $this->expectExceptionCode(\RZP\Error\ErrorCode::SERVER_ERROR_INVALID_RESPONSE);
        $this->mockInstance->sendRequest('/test-url', 'POST', []);
    }
    public function testCreateCustomerV2Success()
    {
        $input = [
            'name' => 'Manish',
            'email' => 'manish@gmail.com',
        ];
        $merchantId = '12345';

        $this->mockInstanceV2->expects($this->once())
            ->method('transformV1CreateOptionsToV2CreateOptions')
            ->with($input, $merchantId)
            ->willReturn($input);

        $mockResponse = (object) [
            'status_code' => 200,
            'body' => '{"message": "Customer created successfully"}',
        ];
        $this->mockInstanceV2->expects($this->once())
            ->method('sendRequest')
            ->with("v2/internal/customers", 'post', $input)
            ->willReturn($mockResponse);

        $result = $this->mockInstanceV2->createCustomerV2($input, $merchantId);

        $this->assertEquals($mockResponse, $result);
    }

    public function testTransformV1CreateOptionsToV2CreateOptionsWithIntegerContact()
    {
        $input = [
            'contact' => 900000
        ];

        $merchantId = '12345';
        $output = (new Service($this->app))->transformV1CreateOptionsToV2CreateOptions($input, $merchantId);
        $this->assertEquals((string)$input['contact'], $output['contact']);
    }

    public function testTransformV1CreateOptionsToV2CreateOptionsWithNullContact()
    {
        $input = [
            'contact' => null
        ];

        $merchantId = '12345';
        $output = (new Service($this->app))->transformV1CreateOptionsToV2CreateOptions($input, $merchantId);
        $this->assertNull($output['contact']);
    }

    public function testCreateCustomerV2Failure()
    {
        $input = [
            'name' => 'Manish',
            'email' => 'manish@gmail.com',
        ];
        $merchantId = '12345';

        $this->mockInstanceV2->expects($this->once())
            ->method('transformV1CreateOptionsToV2CreateOptions')
            ->with($input, $merchantId)
            ->willReturn($input);

        $this->mockInstanceV2->expects($this->once())
            ->method('sendRequest')
            ->with("v2/internal/customers", 'post', $input)
            ->will($this->throwException(new Exception\ServerErrorException("Request Failed to CMS", \RZP\Error\ErrorCode::SERVER_ERROR_INVALID_RESPONSE,[])));

        $this->expectException(Exception\ServerErrorException::class);
        $this->expectExceptionMessage('Request Failed to CMS');
        $this->expectExceptionCode(\RZP\Error\ErrorCode::SERVER_ERROR_INVALID_RESPONSE);

        $this->mockInstanceV2->createCustomerV2($input, $merchantId);
    }
    protected function createSplitzMock(array $methods = ['evaluateRequest'])
    {
        $splitzMock = $this->getMockBuilder(SplitzService::class)
            ->onlyMethods($methods)
            ->getMock();

        $this->app->instance('splitzService', $splitzMock);

        return $splitzMock;
    }
    protected function createTraceMock()
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }

}

