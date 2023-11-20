<?php

namespace Unit\Services;

use RZP\Tests\TestCase;
use RZP\Services\BankingAccountService;
use RZP\Models\BankingAccountService\Service as BasService;

class BankingAccountServiceTest extends TestCase
{
    protected $bankingAccountService;

    protected $basService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('applications.banking_account_service.url', 'https://banking-account.razorpay.com/v0.2');
        $this->app['config']->set('applications.banking_account_service.secret', 'DUMMY_SECRET');
        $this->app['config']->set('applications.banking_account_service.timeout', '30');

        $this->bankingAccountService = new BankingAccountService($this->app);

        $this->basService = new BasService();
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

    public function testEncodeAccountNumberForCaTransfer()
    {
        $this->assertEquals('',$this->basService->encodeAccountNumberForCaTransfer(''));

        $this->assertEquals(null, $this->basService->encodeAccountNumberForCaTransfer(null));

        $this->assertEquals('1234E', $this->basService->encodeAccountNumberForCaTransfer('1234E'));

        $this->assertEquals('1234A', $this->basService->encodeAccountNumberForCaTransfer('12340'));

        $this->assertEquals('1234J', $this->basService->encodeAccountNumberForCaTransfer('12349'));
    }

    public function testDecodeAccountNumberForCaTransfer()
    {
        $this->assertEquals('',$this->basService->decodeAccountNumberForCaTransfer(''));

        $this->assertEquals(null, $this->basService->decodeAccountNumberForCaTransfer(null));

        $this->assertEquals('1234K', $this->basService->decodeAccountNumberForCaTransfer('1234K'));

        $this->assertEquals('12345', $this->basService->decodeAccountNumberForCaTransfer('12345'));

        $this->assertEquals('12340', $this->basService->decodeAccountNumberForCaTransfer('1234A'));

        $this->assertEquals('12349', $this->basService->decodeAccountNumberForCaTransfer('1234J'));
    }
}
