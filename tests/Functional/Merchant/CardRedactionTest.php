<?php

namespace Functional\Merchant;

use Illuminate\Routing\Router;
use RZP\Trace\ApiTraceProcessor;
use RZP\Exception\LogicException;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class CardRedactionTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
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

    public function testVisaCardRedaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'account_number' => '4012888888881881',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number' => "CARD_NUMBER_SCRUBBED(16)"
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testMasterCardRedaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('payout_create');

        $record = [
            'context' => [
                'account_number'  => '5105105105105100',
                'account_number2' => '2224184047998409',
                'account_number3' => '2238741973989812',
                'account_number4' => '2700612396041683',
                'account_number5' => '2720650392744411',
                'account_number6' => '2719705517784880',
                'account_number7' => '271970551778',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number'  => "CARD_NUMBER_SCRUBBED(16)",
                'account_number2' => "CARD_NUMBER_SCRUBBED(16)",
                'account_number3' => "CARD_NUMBER_SCRUBBED(16)",
                'account_number4' => "CARD_NUMBER_SCRUBBED(16)",
                'account_number5' => "CARD_NUMBER_SCRUBBED(16)",
                'account_number6' => "CARD_NUMBER_SCRUBBED(16)",
                'account_number7' => "271970551778",
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
                'account_number'  => '6011111111111117',
                'account_number2' => '6543096311433360',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number'  => "CARD_NUMBER_SCRUBBED(16)",
                'account_number2' => "CARD_NUMBER_SCRUBBED(16)",
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
                'account_number' => '371449635398431',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number' => "CARD_NUMBER_SCRUBBED(15)"
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
                'account_number' => '341111111111111',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number' => "CARD_NUMBER_SCRUBBED(15)"
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
                'account_number'  => '38520000023237',
                'account_number2' => '30569309025904',
                'account_number3' => '36776789121015',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number'  => "CARD_NUMBER_SCRUBBED(14)",
                'account_number2' => "CARD_NUMBER_SCRUBBED(14)",
                'account_number3' => "CARD_NUMBER_SCRUBBED(14)",
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
                'account_number'  => '213153807879568',
                'account_number2' => '180046392961848',
                'account_number3' => '3551175593815246',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number'  => "CARD_NUMBER_SCRUBBED(15)",
                'account_number2' => "CARD_NUMBER_SCRUBBED(15)",
                'account_number3' => "CARD_NUMBER_SCRUBBED(16)",
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
                'account_number' => '4012888888881881',
            ]
        ];

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number' => "4012888888881881"
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
                'account_number' => '4012888888881881',  // visa card
            ]
        ];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number' => "4012888888881881"
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
                'account_number' => '371449635398431',  // amex card
            ]
        ];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number' => "CARD_NUMBER_SCRUBBED(15)"
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
                'account_number'  => '1234567891011',
                'account_number2' => 'hehehehwwkwk',
                'account_number3' => 'normalString',
                'account_number4' => '9834728',
                'visa card'       => '4012888888881881',
            ]
        ];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number'  => '1234567891011',
                'account_number2' => 'hehehehwwkwk',
                'account_number3' => 'normalString',
                'account_number4' => '9834728',
                'visa card'       => 'CARD_NUMBER_SCRUBBED(16)',
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
                    "#1 /app/app/Models/BankTransfer/Core.php(102)=> RZP\\Models\\VirtualAccount\\Processor->" .
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
                    "#1 /app/app/Models/BankTransfer/Core.php(102)=> RZP\\Models\\VirtualAccount\\Processor->" .
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
    public function testCardRedactionWhenScrubbingIsDisabled()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->setRegexViaRedis('off');

        $record = [
            'context' => [
                'account_number'  => '1234567891011',
                'account_number2' => 'hehehehwwkwk',
                'account_number3' => 'normalString',
                'account_number4' => '9834728',
                'visa card'       => '4012888888881881',
            ]
        ];

        $originalRouter = $this->app['router'];

        $this->mockRouter('payout_create');

        $updatedRecord =  $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number'  => '1234567891011',
                'account_number2' => 'hehehehwwkwk',
                'account_number3' => 'normalString',
                'account_number4' => '9834728',
                'visa card'       => '4012888888881881',
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
                'account_number'  => '1234567891011',
                'account_number2' => 'hehehehwwkwk',
                'account_number3' => 'normalString',
                'account_number4' => '9834728',
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

        $routerMock = $this->mockRouter('payout_create');

        $routerMock->method('currentRouteName')
                   ->willThrowException(new LogicException('some unhandled exceptions. maybe redis timeout.
                   In case such exception comes up we dont wanna fail the whole request because of logging'));

        $record = [
            'context' => [
                'account_number' => '4012888888881881',
            ]
        ];

        $updatedRecord = $trace($record);

        $expectedResponse = [
            'context' => [
                'account_number' => "4012888888881881"
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);

        $this->app->instance('router', $originalRouter);
    }
}
