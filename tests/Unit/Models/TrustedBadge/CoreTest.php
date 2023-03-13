<?php

namespace Unit\Models\TrustedBadge;

use Carbon\Carbon;
use RZP\Models\Dispute\Status as DisputeStatus;
use RZP\Models\Merchant\Account;
use RZP\Services\Mock\DataLakePresto as DataLakePrestoMock;
use RZP\Tests\Functional\TestCase;
use RZP\Models\TrustedBadge\Core as Core;

class CoreTest extends TestCase
{
    /** @var float Dispute Loss Rate Threshold. 0.1% */
    protected $threshold = 0.001; // 0.1%

    public function setUp(): void
    {
        parent::setUp();

        $this->fixtures->create('merchant', [
            'id' => Account::DEMO_ACCOUNT,
        ]);

        $this->fixtures->create('merchant', [
            'id' => Account::TEST_ACCOUNT_2,
        ]);
    }

    /**
     * This method tests if dispute/payment is counted even after 4 months,1 day have passed.
     *
     * @return void
     */
    public function testGetDisputedMerchantsWithDisputeExpired(): void
    {
        $fourMonthsOneDayAgo = Carbon::now()->subDays(121)->getTimestamp();

        // Valid payments count: TestAccount2 = 1
        $paymentsCount = [
            [
                'merchant_id' => Account::TEST_ACCOUNT_2,
                'payments_count' => 1,
            ],
        ];

        $this->mockPrestoService($paymentsCount);

        // Valid Dispute Count: TestAccount2 = 1, DemoAccount = 0
        $this->fixtures->create('dispute', [
                'merchant_id'         => Account::TEST_ACCOUNT_2,
                'status'              => 'closed',
                'created_at'          => time(),
        ]);

        $this->fixtures->create('dispute', [
            'merchant_id'         => Account::DEMO_ACCOUNT,
            'status'              => 'closed',
            'created_at'          => $fourMonthsOneDayAgo,
        ]);

        $core  = new Core;

        $response = $core->getMerchantsWithDisputeLossRateGreaterThanThreshold();

        $expected = [Account::TEST_ACCOUNT_2];

        $this->assertEqualsCanonicalizing($expected, $response);
    }

    /**
     * @dataProvider disputeStatusDataProvider
     */
    public function testGetMerchantsWithDisputesLostRateGreaterThanThreshold(string $disputeStatus, array $expected): void
    {
        $totalDisputes = 2;

        for ($i = 0; $i < $totalDisputes; ++$i) {
            $timestamp = Carbon::today()->subDays($i)->getTimestamp();

            $this->fixtures->create('dispute', [
                'merchant_id'         => Account::TEST_ACCOUNT_2,
                'status'              => $disputeStatus,
                'created_at'          => $timestamp,
            ]);

            $this->fixtures->create('dispute', [
                'merchant_id'         => Account::DEMO_ACCOUNT,
                'status'              => $disputeStatus,
                'created_at'          => $timestamp,
            ]);
        }

        $paymentsCount = [
            [
                'merchant_id' => Account::TEST_ACCOUNT_2,
                'payments_count' => ($totalDisputes / $this->threshold) + 1,
            ],
            [
                'merchant_id' => Account::DEMO_ACCOUNT,
                'payments_count' => ($totalDisputes / $this->threshold) - 1,
            ],
        ];

        $this->mockPrestoService($paymentsCount);

        $response = (new Core())->getMerchantsWithDisputeLossRateGreaterThanThreshold();

        $this->assertEqualsCanonicalizing($expected, $response);
    }

    public function disputeStatusDataProvider(): array
    {
        return [
            [DisputeStatus::CLOSED, [Account::DEMO_ACCOUNT]],
            [DisputeStatus::LOST, [Account::DEMO_ACCOUNT]],
            [DisputeStatus::OPEN, []],
            [DisputeStatus::WON, []],
        ];
    }

    protected function mockPrestoService(array $paymentsCount): void
    {
        $prestoService = $this->getMockBuilder(DataLakePrestoMock::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getDataFromDataLake'])
            ->getMock();

        $callback = static function ($query) use ($paymentsCount) {
            if (strpos($query, 'hive.realtime_hudi_api.payments') !== false) {
                return $paymentsCount;
            }

            return [];
        };

        $prestoService->method( 'getDataFromDataLake')
            ->willReturnCallback($callback);

        $this->app->instance('datalake.presto', $prestoService);
    }

    public function testisMerchantLiveOnRTB():void
    {
        $this->fixtures->create('trusted_badge', [
            'merchant_id'       => Account::TEST_ACCOUNT,
            'status'            => 'whitelist',
            'merchant_status'   => 'optout',
        ]);
        $this->fixtures->create('trusted_badge', [
            'merchant_id'       => Account::DEMO_ACCOUNT,
            'status'            => 'eligible',
            'merchant_status'   => '',
        ]);
        $this->fixtures->create('trusted_badge', [
            'merchant_id'       => Account::SHARED_ACCOUNT,
            'status'            => 'whitelist',
            'merchant_status'   => '',
        ]);

        $response = (new Core())->isTrustedBadgeLiveForMerchant(Account::TEST_ACCOUNT);
        $this->assertEquals(false, $response);

        $response = (new Core())->isTrustedBadgeLiveForMerchant(Account::DEMO_ACCOUNT);
        $this->assertEquals(true, $response);

        $response = (new Core())->isTrustedBadgeLiveForMerchant(Account::SHARED_ACCOUNT);
        $this->assertEquals(true, $response);
    }
}
