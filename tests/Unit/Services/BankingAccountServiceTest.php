<?php

namespace Unit\Services;

use RZP\Services\BankingAccountService;
use RZP\Tests\TestCase;

class BankingAccountServiceTest extends TestCase
{
    protected $bankingAccountService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('applications.banking_account_service.url', 'https://banking-account.razorpay.com/v0.2');
        $this->app['config']->set('applications.banking_account_service.secret', 'DUMMY_SECRET');
        $this->app['config']->set('applications.banking_account_service.timeout', '30');

        $this->bankingAccountService = new BankingAccountService($this->app);
    }

    public function testAddQueryParamsToUrl()
    {
        $testCases = [
            [
                'path'            => 'admin/leads/search',
                'query_params'    => [
                    'merchant_id' => 'MID1',
                    'count'       => 20,
                    'skip'        => '10',
                ],
                'expected_result' => 'admin/leads/search?merchant_id=MID1&count=20&skip=10'
            ],
            [
                'path'            => 'admin/leads/search?count=20',
                'query_params'    => [
                    'merchant_id' => 'MID1',
                    'skip'        => '10',
                ],
                'expected_result' => 'admin/leads/search?count=20&merchant_id=MID1&skip=10'
            ],
            [
                'path'            => 'admin/leads/search?count=30',
                'query_params'    => [
                    'merchant_id' => 'MID1',
                    'count'       => 20,
                    'skip'        => '10',
                ],
                'expected_result' => 'admin/leads/search?count=20&merchant_id=MID1&skip=10'
            ]
        ];

        foreach ($testCases as $testCase)
        {
            $expectedUrl = $testCase['expected_result'];
            $actualUrl   = $this->bankingAccountService->addQueryParamsToUrl($testCase['path'], $testCase['query_params']);

            $this->assertEquals($expectedUrl, $actualUrl);
        }
    }
}
