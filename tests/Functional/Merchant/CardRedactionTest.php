<?php

namespace Functional\Merchant;

use Illuminate\Routing\Router;
use RZP\Trace\ApiTraceProcessor;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class CardRedactionTest extends TestCase
{
    use DbEntityFetchTrait;

    public function setUp()
    {
        parent::setUp();
    }

    protected function mockRouter()
    {
        $routerMock = $this->getMockBuilder(Router::class)
                           ->setConstructorArgs([$this->app['events'],$this->app])
                           ->setMethods(['currentRouteName'])
                           ->getMock();

        $routerMock->method('currentRouteName')
                   ->willReturn('payout_create');

        $this->app->instance('router', $routerMock);
    }

    public function testVisaCardRedaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter();

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

        $this->mockRouter();

        $record = [
            'context' => [
                'account_number'  => '5105105105105100',
                'account_number2' => '2224184047998409',
                'account_number3' => '2238741973989812',
                'account_number4' => '2700612396041683',
                'account_number5' => '2720650392744411',
                'account_number6' => '2719705517784880',
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
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testDiscoverCardRedaction()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter();

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

        $this->mockRouter();

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

        $this->mockRouter();

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

        $this->mockRouter();

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

        $this->mockRouter();

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
}
