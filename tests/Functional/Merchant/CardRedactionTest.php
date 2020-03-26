<?php

namespace Functional\Merchant;

use Illuminate\Routing\Router;
use RZP\Trace\ApiTraceProcessor;
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
}
