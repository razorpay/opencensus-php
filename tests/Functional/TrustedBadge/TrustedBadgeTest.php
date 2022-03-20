<?php


namespace Functional\TrustedBadge;

use Carbon\Carbon;
use RZP\Models\Merchant\Account;
use RZP\Models\Terminal\Category;
use RZP\Models\TrustedBadge\Core;
use RZP\Services\Mock\DataLakePresto as DataLakePrestoMock;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class TrustedBadgeTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TrustedBadgeTestData.php';

        parent::setUp();
    }

    public function testEligibilityCron(): void
    {
        $fiveMonthAgo = Carbon::now()->subMonths(5);

        $this->fixtures->create('trusted_badge', [
            'status'            => 'eligible',
            'merchant_status'   => 'optout'
        ]);

        $this->ba->cronAuth();

        $this->fixtures->edit('merchant', '10000000000000', [
            'category2'     => 'ecommerce',
            'activated_at'  => $fiveMonthAgo->getTimestamp(),
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'business_type' => 4,
            'activation_status' => 'activated'
        ]);

        $request = array(
            'url'     => '/trusted_badge/eligibility_cron',
            'method'  => 'POST'
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['success']);

        $this->ba->proxyAuth();

        $request = array(
            'url'     => '/trusted_badge',
            'method'  => 'GET',
            'content' => []
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('ineligible', $response['status']);
    }

    public function testMerchantsActivatedMoreThanThreeMonthsAgoAreEligibleForRTB(): void
    {
        $ninetyOneDaysAgo = Carbon::today()->subDays(91)->getTimestamp();

        $prestoService = $this->getMockBuilder(DataLakePrestoMock::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getDataFromDataLake'])
            ->getMock();

        $callback = static function ($query) {
            $standardCheckoutEligibleMerchants = [
                [
                    'merchant_id' => Account::TEST_ACCOUNT,
                ],
            ];

            if ($query === 'select * from hive.aggregate_pa.rtb_eligibility_trxn_check_v1') {
                return $standardCheckoutEligibleMerchants;
            }

            return [];
        };

        $prestoService->method( 'getDataFromDataLake')
            ->willReturnCallback($callback);

        $this->app->instance('datalake.presto', $prestoService);

        $this->fixtures->edit('merchant', Account::TEST_ACCOUNT, [
            'category2' => Category::SECURITIES,
            'activated_at' => $ninetyOneDaysAgo,
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => Account::TEST_ACCOUNT,
            'business_type' => 4,
            'activation_status' => 'activated',
        ]);

        $request = array(
            'url' => '/trusted_badge/eligibility_cron',
            'method' => 'POST'
        );

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['success']);

        $this->ba->proxyAuth();

        $request = array(
            'url' => '/trusted_badge',
            'method' => 'GET',
            'content' => [],
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('eligible', $response['status']);
    }

    public function testTrustedBadgeDetailsWithEntry(): void
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('trusted_badge');

        $this->startTest();
    }

    public function testTrustedBadgeDetailsWithoutEntry(): void
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testIsDelistedAtleastOnce(): void
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('trusted_badge_history', [
            'status' => 'ineligible',
            'merchant_status' => 'waitlist',
            'created_at' => 1632550775,
        ]);

        $this->fixtures->create('trusted_badge_history', [
            'status' => 'eligible',
            'merchant_status' => 'waitlist',
            'created_at' => 1632550776,
        ]);

        $this->fixtures->create('trusted_badge_history', [
            'status' => 'ineligible',
            'merchant_status' => 'waitlist',
            'created_at' => 1632550777,
        ]);

        $this->fixtures->create('trusted_badge', [
            'status' => 'ineligible',
            'merchant_status' => 'waitlist'
        ]);

        $this->startTest();
    }

    public function testAddToTrustedBadgeBlacklist(): void
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRemoveFromTrustedBadgeBlacklist(): void
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testTrustedBadgeBlacklistWithStatusCheck(): void
    {
        $this->ba->adminAuth();

        $this->fixtures->create('trusted_badge');
        $this->fixtures->create('trusted_badge_history');

        // blacklist merchant
        $request = array(
            'url'     => '/trusted_badge/blacklist',
            'method'  => 'POST',
            'content' => [
                'merchant_ids' => ['10000000000000'],
                'blacklist'    => true
            ],
            'convertContentToString' => false, //to prevent converting boolean value to string
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $response['success']);

        $this->ba->proxyAuth();

        $request = array(
            'url'     => '/trusted_badge',
            'method'  => 'GET',
            'content' => []
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('blacklist', $response['status']);

        // remove merchant from blacklist
        $this->ba->adminAuth();

        $request = array(
            'url'     => '/trusted_badge/blacklist',
            'method'  => 'POST',
            'content' => [
                'merchant_ids' => ['10000000000000'],
                'blacklist'    => false,
            ],
            'convertContentToString' => false,
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $response['success']);

        $this->ba->proxyAuth();

        // fetch and check status, is delisted atleast once
        $this->startTest();
    }

    public function testUpdateMerchantStatus(): void
    {
        /**
         * Make call to update merchant status
         * Then compare by making trusted badge api call
         * First check with no entry in table, try waitlist
         * Then assert waitlist with get status call
         * Second use api to optout
         * Then assert optout with get status call
         */
        $this->ba->proxyAuth();

        $request = array(
            'url'     => '/trusted_badge/merchant_status',
            'method'  => 'PUT',
            'content' => [
                'merchant_status' => 'waitlist',
            ],
        );

        $response = $this->makeRequestAndGetRawContent($request);

        $expectedResponse = ['response'=>['status_code'=>204]];

        $this->processAndAssertStatusCode($expectedResponse, $response);

        $request = array(
            'url'     => '/trusted_badge',
            'method'  => 'GET',
            'content' => []
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('waitlist', $response['merchant_status']);

        $request = array(
            'url'     => '/trusted_badge/merchant_status',
            'method'  => 'PUT',
            'content' => [
                'merchant_status' => 'optout',
            ],
        );

        $response = $this->makeRequestAndGetRawContent($request);

        $expectedResponse = ['response'=>['status_code'=>204]];

        $this->processAndAssertStatusCode($expectedResponse, $response);

        $request = array(
            'url'     => '/trusted_badge',
            'method'  => 'GET',
            'content' => []
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('optout', $response['merchant_status']);
    }

    public function testUpdateMerchantStatusWithWrongStatus(): void
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
}
