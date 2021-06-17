<?php

namespace Functional\Merchant;

use Illuminate\Routing\Router;

use RZP\Constants\Product;
use RZP\Http\BasicAuth\Type;
use RZP\Trace\ApiTraceProcessor;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Tests\Functional\TestCase;
use RZP\Http\Middleware\UserAccess;
use \RZP\Gateway\Utility as RZPUtility;
use RZP\Tests\Unit\Request\Traits\MocksRequest;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\PrivateMethodTrait;

class CardRedactionTest extends TestCase
{
    use PrivateMethodTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use MocksRequest;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function setRegexViaRedis($regex)
    {
        $this->ba->adminAuth();

        $request = [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                "config:credit_card_regex_for_redacting" => $regex
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function setEmailPhoneNumberCVVRegexViaRedis($email, $phone, $cvv)
    {
        $this->ba->adminAuth();

        $request = [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                "config:email_regex_for_redacting" => $email,
                "config:phone_number_regex_for_redacting" => $phone,
                "config:cvv_regex_for_redacting" => $cvv

            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function mockRouter($route)
    {
        $routerMock = $this->getMockBuilder(Router::class)
                           ->setConstructorArgs([$this->app['events'],$this->app])
                           ->setMethods(['currentRouteName'])
                           ->getMock();

        $routerMock->method('currentRouteName')
                   ->willReturn($route);

        $this->app->instance('router', $routerMock);

        return $routerMock;
    }

    protected function mockBasicAuth(string $product = Product::PRIMARY, string $baType = null)
    {
        $authMock = $this->getMockBuilder(BasicAuth::class)
                         ->setConstructorArgs([$this->app])
                         ->setMethods(['getProduct'])
                         ->getMock();

        $authMock->method('getProduct')
                 ->willReturn($product);

        if (empty($baType) === false)
        {
            $this->invokePrivateMethod($authMock, BasicAuth::class, 'setType', [$baType]);
        }

        $this->app->instance('basicauth', $authMock);

        return $authMock;
    }

    public function testVisaCardRedaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'cc_number' => '4012888888881881',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number' => "CARD_NUMBER_SCRUBBED(16)"
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testScrubCardDetails()
    {
        $record = [
            'context' => [
                'cc_number' => '4012888888881881',
                'payee_vpa' => 'ccpay.4315810629729001@icici',
            ]
        ];

        RZPUtility::scrubCardDetails($record, $this->app);

        $expectedResponse = [
            'context' => [
                'cc_number' => "CARD_NUMBER_SCRUBBED(16)",
                'payee_vpa' => 'ccpay.CARD_NUMBER_SCRUBBED(16)@icici',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $record);
    }

    public function testMasterCardRedaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'cc_number'  => '5105105105105100',
                'cc_number2' => '2224184047998409',
                'cc_number3' => '2238741973989812',
                'cc_number4' => '2700612396041683',
                'cc_number5' => '2720650392744411',
                'cc_number6' => '2719705517784880',
                'cc_number7' => '271970551778',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number'  => "CARD_NUMBER_SCRUBBED(16)",
                'cc_number2' => "CARD_NUMBER_SCRUBBED(16)",
                'cc_number3' => "CARD_NUMBER_SCRUBBED(16)",
                'cc_number4' => "CARD_NUMBER_SCRUBBED(16)",
                'cc_number5' => "CARD_NUMBER_SCRUBBED(16)",
                'cc_number6' => "CARD_NUMBER_SCRUBBED(16)",
                'cc_number7' => "271970551778",
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testDiscoverCardRedaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'cc_number'  => '6011111111111117',
                'cc_number2' => '6543096311433360',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number'  => "CARD_NUMBER_SCRUBBED(16)",
                'cc_number2' => "CARD_NUMBER_SCRUBBED(16)",
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testAmexCardWithSeries37Redaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'cc_number' => '371449635398431',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number' => "CARD_NUMBER_SCRUBBED(15)"
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testAmexCardWithSeries34Redaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'cc_number' => '341111111111111',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number' => "CARD_NUMBER_SCRUBBED(15)"
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testDinnersClubCardRedaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'cc_number'  => '38520000023237',
                'cc_number2' => '30569309025904',
                'cc_number3' => '36776789121015',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number'  => "CARD_NUMBER_SCRUBBED(14)",
                'cc_number2' => "CARD_NUMBER_SCRUBBED(14)",
                'cc_number3' => "CARD_NUMBER_SCRUBBED(14)",
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testJCBCardRedaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'cc_number'  => '213153807879568',
                'cc_number2' => '180046392961848',
                'cc_number3' => '3551175593815246',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number'  => "CARD_NUMBER_SCRUBBED(15)",
                'cc_number2' => "CARD_NUMBER_SCRUBBED(15)",
                'cc_number3' => "CARD_NUMBER_SCRUBBED(16)",
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testVisaCardRedactionForNonBankingRoute()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('checkout');

        $record = [
            'context' => [
                'cc_number' => '4012888888881881',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number' => "4012888888881881"
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testRedactionViaRegexFromRedisWithRegexBeingDifferentThanCCNumberSeries()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        // regex is for amex card
        $regex = "/^3[47][0-9]{13}$/";

        $response = $this->setRegexViaRedis($regex);

        $record = [
            'context' => [
                'cc_number' => '4012888888881881',  // visa card
            ]
        ];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number' => "CARD_NUMBER_SCRUBBED(16)"
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testRedactionViaRegexFromRedisWithRegexBeingSameAsCCNumberSeries()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        // regex is for amex card
        $regex = "/^3[47][0-9]{13}$/";

        $response = $this->setRegexViaRedis($regex);

        $record = [
            'context' => [
                'cc_number' => '371449635398431',  // amex card
            ]
        ];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number' => "CARD_NUMBER_SCRUBBED(15)"
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testRedactionForNormalStrings()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $record = [
            'context' => [
                'cc_number'  => '1234567891011',
                'cc_number2' => 'hehehehwwkwk',
                'cc_number3' => 'normalString',
                'cc_number4' => '9834728',
                'visa card'       => '4012888888881881',
            ]
        ];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number'  => '1234567891011',
                'cc_number2' => 'hehehehwwkwk',
                'cc_number3' => 'normalString',
                'cc_number4' => '9834728',
                'visa card'  => 'CARD_NUMBER_SCRUBBED(16)',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testCardRedactionInExceptionData()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            "timestamp" => "2020-03-27T07:08:13.893",
            "code"      => "BANK_TRANSFER_PROCESSING_FAILED",
            "message"   => "BANK_TRANSFER_PROCESSING_FAILED",
            "context"   => [
                "class"   => "RZP\\Exception\\GatewayErrorException",
                "code"    => "BAD_REQUEST_PAYMENT_FAILED",
                "message" => "Payment failed\nGateway Error Code=> \nGateway Error Desc=> ",
                "data"    => [
                    "payer_account"  => "4012888888881881",
                    "payer_ifsc"     => "HDFC0000001",
                    "mode"           => "neft",
                    "transaction_id" => "AYDIC1O4JPXPLBPTTUOOQ9",
                    "time"           => 1543052014,
                    "amount"         => 10000,
                    "description"    => "Test bank transfer",
                    "payee_account"  => "371449635398431",
                    "payee_ifsc"     => "RAZRB000000"
                ],
                "stack"   => [
                    "#0 /app/app/Models/VirtualAccount/Processor.php(70)=>" .
                     "RZP\\Models\\BankTransfer\\Processor->isDuplicate(Object(RZP\\Models\\BankTransfer\\Entity))",
                    "#1 /app/app/Models/BankTransfer/Core.php(02)=> RZP\\Models\\VirtualAccount\\Processor->" .
                    "process(Object(RZP\\Models\\BankTransfer\\Entity))",
                ]
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            "context" => [
                "class"   => "RZP\\Exception\\GatewayErrorException",
                "code"    => "BAD_REQUEST_PAYMENT_FAILED",
                "message" => "Payment failed\nGateway Error Code=> \nGateway Error Desc=> ",
                "data"    => [
                    "payer_account"  => "CARD_NUMBER_SCRUBBED(16)",
                    "payer_ifsc"     => "HDFC0000001",
                    "mode"           => "neft",
                    "transaction_id" => "AYDIC1O4JPXPLBPTTUOOQ9",
                    "time"           => 1543052014,
                    "amount"         => 10000,
                    "description"    => "Test bank transfer",
                    "payee_account"  => "CARD_NUMBER_SCRUBBED(15)",
                    "payee_ifsc"     => "RAZRB000000"
                ],
                "stack"   => [
                    "#0 /app/app/Models/VirtualAccount/Processor.php(70)=>" .
                    "RZP\\Models\\BankTransfer\\Processor->isDuplicate(Object(RZP\\Models\\BankTransfer\\Entity))",
                    "#1 /app/app/Models/BankTransfer/Core.php(02)=> RZP\\Models\\VirtualAccount\\Processor->" .
                    "process(Object(RZP\\Models\\BankTransfer\\Entity))",
                ]
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testCardRedactionInExceptionStackTrace()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            "timestamp" => "2020-03-26T14:43:12.457",
            "code"      => "ERROR_EXCEPTION",
            "message"   => "Unhandled critical exception occured",
            "context"   => [
                "class"   => "RZP\\Exception\\GatewayErrorException",
                "code"    => "GATEWAY_ERROR_UNKNOWN_ERROR",
                "message" => "Payment processing failed due to error at bank or wallet gateway\nGateway Error Code=> \nGateway Error Desc: ",
                "data"    => [],
                "stack"   => [
                    "#0 /app/app/Http/Controllers/BankTransferController.php(25): RZP\\Models\\BankTransfer\\Service" .
                    "->process1(371449635398431, 4012888888881881, NormalText, 37144963539)",
                    "#1 [internal function]: RZP\\Http\\Controllers\\BankTransferController->processBankTransfer()",
                    "#2 /app/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): call_user_func_array(Array, Array)",
                ]
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            "timestamp" => "2020-03-26T14:43:12.457",
            "code"      => "ERROR_EXCEPTION",
            "message"   => "Unhandled critical exception occured",
            "context"   => [
                "class"   => "RZP\\Exception\\GatewayErrorException",
                "code"    => "GATEWAY_ERROR_UNKNOWN_ERROR",
                "message" => "Payment processing failed due to error at bank or wallet gateway\nGateway Error Code=> \nGateway Error Desc: ",
                "data"    => [],
                "stack"   => [
                    "#0 /app/app/Http/Controllers/BankTransferController.php(25): RZP\\Models\\BankTransfer\\Service" .
                    "->process1(CARD_NUMBER_SCRUBBED(15), CARD_NUMBER_SCRUBBED(16), NormalText, 37144963539)",
                    "#1 [internal function]: RZP\\Http\\Controllers\\BankTransferController->processBankTransfer()",
                    "#2 /app/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): call_user_func_array(Array, Array)",
                ]
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    // In this test first scrubbing is disabled and then enabled again
    // The regex value is now moved to config hence the test case is not
    // going to working as expected
    public function testCardRedactionWhenScrubbingIsDisabled()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->setRegexViaRedis('off');

        $record = [
            'context' => [
                'cc_number'  => '1234567891011',
                'cc_number2' => 'hehehehwwkwk',
                'cc_number3' => 'normalString',
                'cc_number4' => '9834728',
                'visa card'       => '4012888888881881',
            ]
        ];

        $originalRouter = $this->app['router'];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number'  => '1234567891011',
                'cc_number2' => 'hehehehwwkwk',
                'cc_number3' => 'normalString',
                'cc_number4' => '9834728',
                'visa card'       => 'CARD_NUMBER_SCRUBBED(16)',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);

        // re-enabling scrubbing again
        $regex = "/\\b(?:4[0-9]{12}(?:[0-9]{3})?|(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}" .
                 "|27[01][0-9]|2720)[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|6(?:011|5[0-9]{2})[0-9]{12}|" .
                 "(?:2131|1800|35\\d{3})\\d{11})\\b/";

        $this->app->instance('router', $originalRouter);

        $this->setRegexViaRedis($regex);

        $this->mockRouter('payout_create');

        $updatedRecord1 =  $trace($record);

        $expectedResponse1 = [
            'context' => [
                'cc_number'  => '1234567891011',
                'cc_number2' => 'hehehehwwkwk',
                'cc_number3' => 'normalString',
                'cc_number4' => '9834728',
                'visa card'       => 'CARD_NUMBER_SCRUBBED(16)',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse1, $updatedRecord1);
    }

    public function testCardRedactionWhenExceptionComesWhileScrubbing()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $originalRouter = $this->app['router'];

        $routerMock = $this->getMockBuilder(Router::class)
                           ->setConstructorArgs([$this->app['events'],$this->app])
                           ->setMethods(['currentRouteName'])
                           ->getMock();

        $routerMock->method('currentRouteName')
                   ->willReturn(function () {
                       static $counter = 0;

                       switch ($counter++) {
                           case 0:
                               return 'payout_create';
                               break;
                           case 1:
                               throw new \Exception();
                               break;
                           default:
                               return 'payout_create';
                               break;
                       }
                   }
         );

        $this->app->instance('router', $routerMock);

        $record = [
            'context' => [
                'cc_number' => '4012888888881881',
            ]
        ];

        $updatedRecord = $trace($record);

        $expectedResponse = [
            'context' => [
                'cc_number' => "4012888888881881"
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);

        $this->app->instance('router', $originalRouter);
    }

    // The regex value is now moved to config hence the test case is not
    // going to working as expected
    public function testEmailCvvMobile()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'email' => 'xyz@razorpay.com',
                'mobile' => '6302839647',
                'cvv' => '921',
                'cc_number' => "4012888888881881",
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'email' => 'EMAIL_SCRUBBED(16)',
                'mobile' => 'PHONE_NUMBER_SCRUBBED(10)',
                'cvv' => '921',
                'cc_number' => 'CARD_NUMBER_SCRUBBED(16)',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testScrubbingForBankingRoute()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('banking_account_webhook_account_info');

        $record = [
            'context' => [
                'account_ifsc' => 'RATN0000057',
                'account_number' => '758123260280',
                "client_secret"  => "TSULZXMONAARZUQOOOXPERQQIFBXADANSPBZTPPUOEFEQELBVO",
                'beneficiary_email' => 'qa.testing@razorpay.com',
                'password' => "Welcome123",
                'beneficiary_mobile' => "1234567890",
                "details"    => [
                    "client_id"      => "K7V3PG6JWHVOA5FVAUF6RGCZDE0W7DV7VVRN",
                    "client_secret"  => "TSULZXMONAARZUQOOOXPERQQIFBXADANSPBZTPPUOEFEQELBVO",
                ],
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_ifsc' => 'SCRUBBED(11)',
                'account_number' => 'SCRUBBED(12)',
                "client_secret"  => "SCRUBBED(50)",
                'beneficiary_email' => 'EMAIL_SCRUBBED(23)',
                'password' => "SCRUBBED(10)",
                'beneficiary_mobile' => "PHONE_NUMBER_SCRUBBED(10)",
                "details"    => [
                    "client_id"      => "K7V3PG6JWHVOA5FVAUF6RGCZDE0W7DV7VVRN",
                    "client_secret"  => "SCRUBBED(50)",
                ],
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testEmailCvvMobileForNonBankingRoute()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('checkout');

        $record = [
            'context' => [
                'email' => 'xyz@razorpay.com',
                'mobile' => '6302839647',
                'cvv' => '921',
                'cc_number' => "4012888888881881",
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'email' => 'xyz@razorpay.com',
                'mobile' => '6302839647',
                'cvv' => '921',
                'cc_number' => "4012888888881881",
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testInvalidEmailCvvMobile()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'email' => 'xyzrazorpay.com',
                'mobile' => '6302839647123',
                'cvv' => '92155',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'email' => 'xyzrazorpay.com',
                'mobile' => '6302839647123',
                'cvv' => '92155',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    // The regex value is now moved to config hence the test case is not
    // going to working as expected
    public function testEmailCVVNumberForExceptionData()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            "timestamp" => "2020-03-27T07:08:13.893",
            "code"      => "BANK_TRANSFER_PROCESSING_FAILED",
            "message"   => "BANK_TRANSFER_PROCESSING_FAILED",
            "context"   => [
                "class"   => "RZP\\Exception\\GatewayErrorException",
                "code"    => "BAD_REQUEST_PAYMENT_FAILED",
                "message" => "Payment failed\nGateway Error Code=> \nGateway Error Desc=> ",
                "data"    => [
                    "payer_account"  => "4012888888881881",
                    "payer_ifsc"     => "HDFC0000001",
                    "mode"           => "neft",
                    "transaction_id" => "AYDIC1O4JPXPLBPTTUOOQ9",
                    "time"           => 1543052014,
                    "amount"         => 10000,
                    "description"    => "Test bank transfer",
                    "payee_account"  => "371449635398431",
                    "payee_ifsc"     => "RAZRB000000",
                    "payee_email"    => "xyz@razorpay.com",
                    "payee_phone"    => "6302839641",
                    "payee_cvv"      => "564",
                ],
                "stack"   => [
                    "#0 /app/app/Models/VirtualAccount/Processor.php(170)=>" .
                    "RZP\\Models\\BankTransfer\\Processor->isDuplicate(Object(RZP\\Models\\BankTransfer\\Entity))",
                    "#1 /app/app/Models/BankTransfer/Core.php(10)=> RZP\\Models\\VirtualAccount\\Processor->" .
                    "process(Object(RZP\\Models\\BankTransfer\\Entity))",
                ]
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            "context" => [
                "class"   => "RZP\\Exception\\GatewayErrorException",
                "code"    => "BAD_REQUEST_PAYMENT_FAILED",
                "message" => "Payment failed\nGateway Error Code=> \nGateway Error Desc=> ",
                "data"    => [
                    "payer_account"  => "CARD_NUMBER_SCRUBBED(16)",
                    "payer_ifsc"     => "HDFC0000001",
                    "mode"           => "neft",
                    "transaction_id" => "AYDIC1O4JPXPLBPTTUOOQ9",
                    "time"           => 1543052014,
                    "amount"         => 10000,
                    "description"    => "Test bank transfer",
                    "payee_account"  => "CARD_NUMBER_SCRUBBED(15)",
                    "payee_ifsc"     => "RAZRB000000",
                    "payee_email"    => "EMAIL_SCRUBBED(16)",
                    "payee_phone"    => "PHONE_NUMBER_SCRUBBED(10)",
                    "payee_cvv"      => "564",
                ],
                "stack"   => [
                    "#0 /app/app/Models/VirtualAccount/Processor.php(170)=>" .
                    "RZP\\Models\\BankTransfer\\Processor->isDuplicate(Object(RZP\\Models\\BankTransfer\\Entity))",
                    "#1 /app/app/Models/BankTransfer/Core.php(10)=> RZP\\Models\\VirtualAccount\\Processor->" .
                    "process(Object(RZP\\Models\\BankTransfer\\Entity))",
                ]
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    // The regex value is now moved to config hence the test case is not
    // going to working as expected
    public function testEmailCVVPhoneInfoInExceptionStackTrace()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = $this->getExceptionRecord();;

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            "timestamp" => "2020-03-26T14:43:12.457",
            "code"      => "ERROR_EXCEPTION",
            "message"   => "Unhandled critical exception occured",
            "context"   => [
                "class"   => "RZP\\Exception\\GatewayErrorException",
                "code"    => "GATEWAY_ERROR_UNKNOWN_ERROR",
                "message" => "Payment processing failed due to error at bank or wallet gateway\nGateway Error Code=> \nGateway Error Desc: ",
                "data"    => [],
                "stack"   => [
                    "#0 /app/app/Http/Controllers/BankTransferController.php(125): RZP\\Models\\BankTransfer\\Service" .
                    "->process1(CARD_NUMBER_SCRUBBED(15), CARD_NUMBER_SCRUBBED(16), NormalText, 37144963539, EMAIL_SCRUBBED(16), Random, PHONE_NUMBER_SCRUBBED(10), 567, EMAIL_SCRUBBED(20))",
                    "#1 [internal function]: RZP\\Http\\Controllers\\BankTransferController->processBankTransfer()",
                    "#2 /app/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): call_user_func_array(Array, Array)",
                ]
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    // The regex value is now moved to config hence the test case is not
    // going to working as expected
    public function testRedactionViaRegexFromRedisWithInvalidRegex()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $emailRegex = "/^3[47][0-9]{13}$/";
        $cvvRegex   = "/^3[4][0-9]/";
        $phoneRegex   = "/^\d{1-9}/";

        $this->setEmailPhoneNumberCVVRegexViaRedis($emailRegex, $phoneRegex, $cvvRegex);

        $record = [
            'context' => [
                'email' => 'xyz@razorpay.com',
                'cvv'   => '567',
                'phone' => '9177278066',
            ]
        ];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'email' => 'EMAIL_SCRUBBED(16)',
                'cvv'   => '567',
                'phone' => 'PHONE_NUMBER_SCRUBBED(10)',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    // In this test first scrubbing is disabled and then enabled again
    // The regex value is now moved to config hence the test case is not
    // going to working as expected
    public function testDisablingAndEnablingRegex()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->setEmailPhoneNumberCVVRegexViaRedis('off', 'off', 'off');

        $record = [
            'context' => [
                'email' => 'xyz@razorpay.com',
                'cvv'   => '567',
                'phone' => '9177278066',
            ]
        ];

        $originalRouter = $this->app['router'];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'email' => 'EMAIL_SCRUBBED(16)',
                'cvv'   => '567',
                'phone' => 'PHONE_NUMBER_SCRUBBED(10)',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);

        $this->app->instance('router', $originalRouter);

        $this->setEmailPhoneNumberCVVRegexViaRedis('/([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+/', '/(\b|\+91)\d{10}\b/', '/\b(?<!\.php\()\d{3,4}\b/');

        $this->mockRouter('payout_create');

        $updatedRecord1 =  $trace($record);

        $expectedResponse1 = [
            'context' => [
                'email' => 'EMAIL_SCRUBBED(16)',
                'cvv'   => '567',
                'phone' => 'PHONE_NUMBER_SCRUBBED(10)',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse1, $updatedRecord1);
    }

    // The regex value is now moved to config hence the test case is not
    // going to working as expected
    public function testMultipleEmailCvvMobile()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'email' => 'xyz@razorpay.com',
                'mobile' => '6302839647',
                'mobile1' => '6302839647739191',
                'mobile2' => '+916302839647',
                'cvv' => '921',
                'cc_number' => "4012888888881881",
                'email2' => 'xyzrazorpay.com',
                'email3' => 'x\"y\"z@razorpay.com',
                'email4' => 'x  y "z@razorpay.com',
                'email5' => 'xyz@razorpay.com   ',
                'email6' => 'ABC@razorpay.com',
                'email7' => 'test.email.with+!$symbol@domain.com',
                'email8' => 'id-with-dash@domain.com',
                'email10' => 'example-abc@abc-domain.com',
                'email11' => 'admin@mailserver1',
                'email13' => 'admin@domain.org',
                'email15' => '0123456789xyz@example.com',
                'email16' => 'ks@subdomain.domain.com',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'email' => 'EMAIL_SCRUBBED(16)',
                'mobile' => 'PHONE_NUMBER_SCRUBBED(10)',
                'mobile1' => '6302839647739191',
                'mobile2' => 'PHONE_NUMBER_SCRUBBED(13)',
                'cvv' => '921',
                'cc_number' => 'CARD_NUMBER_SCRUBBED(16)',
                'email2' => 'xyzrazorpay.com',
                'email3' => 'x\"y\"EMAIL_SCRUBBED(14)',
                'email4' => 'x  y "EMAIL_SCRUBBED(14)',
                'email5' => 'EMAIL_SCRUBBED(16)   ',
                'email6' => 'EMAIL_SCRUBBED(16)',
                'email7' => 'EMAIL_SCRUBBED(35)',
                'email8' => 'EMAIL_SCRUBBED(23)',
                'email10' => 'EMAIL_SCRUBBED(26)',
                'email11' => 'admin@mailserver1',
                'email13' => 'EMAIL_SCRUBBED(16)',
                'email15' => 'EMAIL_SCRUBBED(25)',
                'email16' => 'EMAIL_SCRUBBED(23)',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testPayerNameInExceptionData()
    {
        $this->markTestSkipped();
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('bank_transfer_process_rbl');

        $record = [
            "timestamp" => "2020-03-11T11:09:33.175",
            "code"      => "BANK_TRANSFER_PAYER_BANK_ACCOUNT_SKIPPED",
            "message"   => "BANK_TRANSFER_PAYER_BANK_ACCOUNT_SKIPPED",
            "context"   => [
                "class"   => "RZP\\Exception\\BadRequestValidationFailureException",
                "code"    => "BAD_REQUEST_VALIDATION_FAILURE",
                "message" => "The account number field is required.",
                "data"    => [
                    "payer_account"  => "4012888888881881",
                    "payer_name"     => "AXI2b63f52d070743b6bd163f564e4379eb",
                    "payer_ifsc"     => "HDFC0000001",
                    "mode"           => "neft",
                    "transaction_id" => "AYDIC1O4JPXPLBPTTUOOQ9",
                    "time"           => 1543052014,
                    "amount"         => 10000,
                    "description"    => "Test bank transfer",
                    "payee_account"  => "371449635398431",
                    "payee_ifsc"     => "RAZRB000000"
                ],
                "stack"   => [
                    "#0 /app/app/Models/VirtualAccount/Processor.php(70)=>" .
                    "RZP\\Models\\BankTransfer\\Processor->isDuplicate(Object(RZP\\Models\\BankTransfer\\Entity))",
                    "#1 /app/app/Models/BankTransfer/Core.php(02)=> RZP\\Models\\VirtualAccount\\Processor->" .
                    "process(Object(RZP\\Models\\BankTransfer\\Entity))",
                ]
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            "context" => [
                "class"   => "RZP\\Exception\\BadRequestValidationFailureException",
                "code"    => "BAD_REQUEST_VALIDATION_FAILURE",
                "message" => "The account number field is required.",
                "data"    => [
                    "payer_account"  => "CARD_NUMBER_SCRUBBED(16)",
                    "payer_name"     => "SCRUBBED(35)",
                    "payer_ifsc"     => "HDFC0000001",
                    "mode"           => "neft",
                    "transaction_id" => "AYDIC1O4JPXPLBPTTUOOQ9",
                    "time"           => 1543052014,
                    "amount"         => 10000,
                    "description"    => "Test bank transfer",
                    "payee_account"  => "CARD_NUMBER_SCRUBBED(15)",
                    "payee_ifsc"     => "RAZRB000000"
                ],
                "stack"   => [
                    "#0 /app/app/Models/VirtualAccount/Processor.php(70)=>" .
                    "RZP\\Models\\BankTransfer\\Processor->isDuplicate(Object(RZP\\Models\\BankTransfer\\Entity))",
                    "#1 /app/app/Models/BankTransfer/Core.php(02)=> RZP\\Models\\VirtualAccount\\Processor->" .
                    "process(Object(RZP\\Models\\BankTransfer\\Entity))",
                ]
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testPayerName()
    {
        $this->markTestSkipped();
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('bank_transfer_process_rbl');

        $record = [
            'context' => [
                "payer_account"  => "4012888888881881",
                "payer_name"     => "AXI2b63f52d070743b6bd163f564e4379eb",
                "payer_ifsc"     => "HDFC0000001",
            ]
        ];

        $updatedRecord = $trace($record);

        $expectedResponse = [
            'context' => [
                "payer_account"  => "CARD_NUMBER_SCRUBBED(16)",
                "payer_name"     => "SCRUBBED(35)",
                "payer_ifsc"     => "HDFC0000001",
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testBankingProductInExceptionStackTrace()
    {
        $record = $this->getExceptionRecord();

        $updatedRecord =  $this->getUpdatedTrace('payment_create', $record, Product::BANKING);

        $this->assertEquals($updatedRecord['request']['product'], Product::BANKING);
    }

    public function testPrimaryProductPresentInExceptionStackTrace()
    {
        $record = $this->getExceptionRecord();

        $updatedRecord =  $this->getUpdatedTrace('payment_create', $record, Product::PRIMARY);

        $this->assertEquals($updatedRecord['request']['product'], Product::PRIMARY);
    }

    public function testPrimaryProductPresentInTrace()
    {
        $record = [
            'context' => [
                'email' => 'xyz@razorpay.com',
            ]
        ];

        $updatedRecord =  $this->getUpdatedTrace('payment_create', $record, Product::PRIMARY);

        $this->assertEquals($updatedRecord['request']['product'], Product::PRIMARY);
    }

    public function testBankingProductPresentInTrace()
    {
        $record = [
            'context' => [
                'email' => 'xyz@razorpay.com',
            ]
        ];

        $updatedRecord =  $this->getUpdatedTrace('payout_create', $record, Product::BANKING);

        $this->assertEquals($updatedRecord['request']['product'], Product::BANKING);
    }

    public function testBankingProductPresentInTracePrivateAuth()
    {
        $record = [
            'context' => [
                'email' => 'xyz@razorpay.com',
            ]
        ];

        $updatedRecord =  $this->getUpdatedTrace('payout_create', $record, Product::BANKING, Type::PRIVATE_AUTH);

        $this->assertEquals($updatedRecord['request']['product'], Product::BANKING);
    }

    public function testPrimaryProductPresentInTracePrivateAuth()
    {
        $record = [
            'context' => [
                'email' => 'xyz@razorpay.com',
            ]
        ];

        $updatedRecord =  $this->getUpdatedTrace('payment_create', $record, Product::PRIMARY, Type::PRIVATE_AUTH);

        $this->assertEquals($updatedRecord['request']['product'], Product::PRIMARY);
    }

    public function testPrimaryProductPresentInTraceDashboardAppAuth()
    {
        // Given
        // BasicAuth resolves to Dashboard app
        $authMock = $this->getMockBuilder(BasicAuth::class)
                         ->setConstructorArgs([$this->app])
                         ->setMethods(['isDashboardApp','setProduct'])
                         ->getMock();

        $authMock->method('isDashboardApp')
                 ->willReturn(true);

        // Then
        // expecting product to be set to primary
        $authMock->expects($this->once())
                 ->method('setProduct')
                 ->with('primary');
        $this->app->instance('basicauth', $authMock);

        // When
        // request origin is dashboard.razorpay.com
        $requestMock = $this->mockRouteRequest('user_fetch',
            'users/20000000000000', [], [], [], [],
            ['HTTP_X-Request-Origin' => config('applications.dashboard.url')]
        );

        $productIdentifier = new \RZP\Http\Middleware\ProductIdentifier($this->app);
        $productIdentifierReflectionObj = new \ReflectionObject($productIdentifier);
        $method = $productIdentifierReflectionObj->getMethod('deriveAndSetProductFromRequest');
        $method->setAccessible(true);
        $method->invoke($productIdentifier, $requestMock);
    }

    protected function getUpdatedTrace(string $routeName,
                                       array $record,
                                       string $product = null,
                                       string $authType = null)
    {
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter($routeName);

        $this->mockBasicAuth($product ?? Product::PRIMARY, $authType);

        return $trace($record);
    }

    protected function getExceptionRecord()
    {
        return [
            "timestamp" => "2020-03-26T14:43:12.457",
            "code"      => "ERROR_EXCEPTION",
            "message"   => "Unhandled critical exception occured",
            "context"   => [
                "class"   => "RZP\\Exception\\GatewayErrorException",
                "code"    => "GATEWAY_ERROR_UNKNOWN_ERROR",
                "message" => "Payment processing failed due to error at bank or wallet gateway\nGateway Error Code=> \nGateway Error Desc: ",
                "data"    => [],
                "stack"   => [
                    "#0 /app/app/Http/Controllers/BankTransferController.php(125): RZP\\Models\\BankTransfer\\Service" .
                    "->process1(371449635398431, 4012888888881881, NormalText, 37144963539, xyz@razorpay.com, Random, 6302839641, 567, abcd123@razorpay.com)",
                    "#1 [internal function]: RZP\\Http\\Controllers\\BankTransferController->processBankTransfer()",
                    "#2 /app/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): call_user_func_array(Array, Array)",
                ]
            ]
        ];
    }
}
