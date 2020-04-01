<?php

namespace Functional\Merchant;

use Illuminate\Routing\Router;
use RZP\Trace\ApiTraceProcessor;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class ScrubBankingSensitiveDetailsTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        parent::setUp();
    }

    protected function mockRouter($route)
    {
        $routerMock = $this->getMockBuilder(Router::class)
            ->setConstructorArgs([$this->app['events'], $this->app])
            ->setMethods(['currentRouteName'])
            ->getMock();

        $routerMock->method('currentRouteName')
            ->willReturn($route);

        $this->app->instance('router', $routerMock);
    }

    public function testSensitiveData()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('fund_account_create');

        $record = [
            'context' => [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc' => 'BKDN0611454',
                    'account_number' => '145410038647',
                    'name' => '4687796724004887',
                ],
            ]
        ];

        $updatedRecord = $trace($record);

        $expectedResponse = [
            'context' => [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc' => 'BKDN0611454',
                    'account_number' => 'SCRUBBED(12)',
                    'name' => 'CARD_NUMBER_SCRUBBED(16)',
                ],
            ]
        ];
        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testUnSensitiveData()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('fund_account_create');

        $record = [
            'context' => [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc' => 'BKDN0611454',
                    'bank_name' => 'YESBank',
                ],
                'merchantId' => '657',
            ]
        ];

        $updatedRecord = $trace($record);

        $expectedResponse = [
            'context' => [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc' => 'BKDN0611454',
                    'bank_name' => 'YESBank',
                ],
                'merchantId' => '657',
            ]
        ];
        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testMultipleAccountsScrubbing()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('fund_account_create');

        $record = [
            'context' => [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'account_number' => [
                        '145410038647',
                        '743697163173631',
                    ],
                ],
            ]
        ];

        $updatedRecord = $trace($record);

        $expectedResponse = [
            'context' => [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'account_number' => [
                        'SCRUBBED(12)',
                        'SCRUBBED(15)',
                    ],
                ],
            ]
        ];
        $this->assertArraySelectiveEquals($expectedResponse, $updatedRecord);
    }

    public function testingForNonBankingRoute()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('checkout');

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
    }
}
