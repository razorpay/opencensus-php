<?php


namespace Functional\TrustedBadge;

use Carbon\Carbon;
use RZP\Models\Merchant\Account;
use RZP\Models\Terminal\Category;
use RZP\Models\TrustedBadge\Constants;
use RZP\Models\TrustedBadge\Repository;
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

    public function testEligibilityCronWithDryRunSetAsTrue(): void
    {
        $fiveMonthAgo = Carbon::now()->subMonths(5);

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

        $callback = static function ($query) {
            $standardCheckoutEligibleMerchants = [
                [
                    'merchant_id' => Account::TEST_ACCOUNT,
                ],
            ];

            if ($query === Repository::STANDARD_CHECKOUT_ELIGIBLE_QUERY) {
                return $standardCheckoutEligibleMerchants;
            }

            return [];
        };

        $this->mockPrestoService($callback);

        $request = array(
            'url'     => '/trusted_badge/eligibility_cron',
            'method'  => 'POST',
            'content' => [
                'dry_run' => true,
            ]
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

        $callback = static function ($query) {
            $standardCheckoutEligibleMerchants = [
                [
                    'merchant_id' => Account::TEST_ACCOUNT,
                ],
            ];

            if ($query === Repository::STANDARD_CHECKOUT_ELIGIBLE_QUERY) {
                return $standardCheckoutEligibleMerchants;
            }

            return [];
        };

        $this->mockPrestoService($callback);

        $this->fixtures->edit('merchant', Account::TEST_ACCOUNT, [
            'category2' => Category::SECURITIES,
            'activated_at' => $ninetyOneDaysAgo,
            'category' => '6211',
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
        $this->assertEquals(true, $response['is_live']);
    }

    /** This method tests for merchants who have less transactions but would still be eligible for RTB
     * if they pass additional special checks. here we are testing if refund rate and
     * its Z value is calculated properly.
     *
     * @return void
     */
    public function testGetLowTransactionsButRTBEligibleMids(): void
    {
        $ninetyOneDaysAgo = Carbon::today()->subDays(91)->getTimestamp();

        $lowTransactionsMerchantsData = [
            [
                'merchant_id' => Account::DEMO_ACCOUNT,
                'refund_rate' => 0,
            ],
            [
                'merchant_id' => Account::TEST_ACCOUNT,
                'refund_rate' => 0,
            ],
            [
                'merchant_id' => Account::TEST_ACCOUNT_2,
                'refund_rate' => 0,
            ],
        ];
        // we know -0.5 <= Z <= 1.5 so substituting Z with formula, we get -0.5 <= ((refund rate - mean)/SD) <= 1.5
        // by solving that equation, we get the following case if we want Z value to be acceptable
        //  mean - (0.5 * SD) <= refund rate <= mean + (1.5 * SD). Also refund rate = refund count / payment count
        // so idea here is to create 3 merchants having low, acceptable, high refund counts respectively
        // we are keeping successful payments a constant. i.e 99 for easy calculation of refund rate.
        // Demo account - low Z value, Test account - acceptable, Test account 2 - high Z value

        $categoryData = Constants::CATEGORY_WISE_STATISTICAL_DATA[Category::SOCIAL];

        $lowTransactionsMerchantsData[0]['refund_rate'] =
            ($categoryData['mean'] - (0.5* $categoryData['standard_deviation'])) - 0.001 ;

        $lowTransactionsMerchantsData[1]['refund_rate'] =
            ($categoryData['mean'] - (0.5* $categoryData['standard_deviation']));

        $lowTransactionsMerchantsData[2]['refund_rate'] =
            ($categoryData['mean'] + (1.5* $categoryData['standard_deviation'])) + 0.001 ;

        $callback = static function ($query) use ($lowTransactionsMerchantsData) {
            if ($query === Repository::LOW_TRANSACTIONS_MERCHANTS_QUERY) {
                return $lowTransactionsMerchantsData;
            }

            return [];
        };

        $this->mockPrestoService($callback);

        $this->fixtures->edit('merchant', Account::TEST_ACCOUNT, [
            'category2' => Category::SOCIAL,
            'activated_at' => $ninetyOneDaysAgo,
        ]);
        $this->fixtures->create('merchant', [
            'id' => Account::DEMO_ACCOUNT,
            'category2' => Category::SOCIAL,
            'activated_at' => $ninetyOneDaysAgo,
        ]);
        $this->fixtures->create('merchant', [
            'id' => Account::TEST_ACCOUNT_2,
            'category2' => Category::SOCIAL,
            'activated_at' => $ninetyOneDaysAgo,
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => Account::TEST_ACCOUNT,
            'business_type' => 4,
            'activation_status' => 'activated',
        ]);
        $demoMerchantDetail = $this->fixtures->merchant_detail->createEntity('merchant_detail',[
            'merchant_id' => Account::DEMO_ACCOUNT,
            'business_type' => 4,
            'activation_status' => 'activated',
        ]);
        $testMerchantDetail = $this->fixtures->merchant_detail->createEntity('merchant_detail',[
            'merchant_id' => Account::TEST_ACCOUNT_2,
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

        $request = array(
            'url' => '/trusted_badge',
            'method' => 'GET',
            'content' => [],
        );

        $this->ba->proxyAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('eligible', $response['status']);
        $this->assertEquals(true, $response['is_live']);

        $demoMerchantUser = $this->fixtures->user->createUserForMerchant(Account::DEMO_ACCOUNT);
        $this->ba->proxyAuth('rzp_test_' . Account::DEMO_ACCOUNT, $demoMerchantUser['id']);
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('ineligible', $response['status']);
        $this->assertEquals(false, $response['is_live']);

        $testMerchantUser = $this->fixtures->user->createUserForMerchant(Account::TEST_ACCOUNT_2);
        $this->ba->proxyAuth('rzp_test_' . Account::TEST_ACCOUNT_2, $testMerchantUser['id']);
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('ineligible', $response['status']);
        $this->assertEquals(false, $response['is_live']);
    }

    public function testMerchantsWithRiskTagsAreNotEligibleForRTB(): void
    {
        $ninetyOneDaysAgo = Carbon::today()->subDays(91)->getTimestamp();

        $callback = static function ($query) {
            $standardCheckoutEligibleMerchants = [
                [
                    'merchant_id' => Account::DEMO_ACCOUNT,
                ],
                [
                    'merchant_id' => Account::TEST_ACCOUNT,
                ],
            ];

            if ($query === Repository::STANDARD_CHECKOUT_ELIGIBLE_QUERY) {
                return $standardCheckoutEligibleMerchants;
            }

            return [];
        };

        $this->mockPrestoService($callback);

        $this->fixtures->edit('merchant', Account::TEST_ACCOUNT, [
            'category2' => Category::SOCIAL,
            'activated_at' => $ninetyOneDaysAgo,
        ]);
        $this->fixtures->create('merchant', [
            'id' => Account::DEMO_ACCOUNT,
            'category2' => Category::SOCIAL,
            'activated_at' => $ninetyOneDaysAgo,
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => Account::TEST_ACCOUNT,
            'business_type' => 4,
            'activation_status' => 'activated',
            'fraud_type' => 'suspended',
        ]);
        $this->fixtures->merchant_detail->createEntity('merchant_detail',[
            'merchant_id' => Account::DEMO_ACCOUNT,
            'business_type' => 4,
            'activation_status' => 'activated',
            'fraud_type' => '',
        ]);

        $request = array(
            'url' => '/trusted_badge/eligibility_cron',
            'method' => 'POST'
        );

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['success']);

        $request = array(
            'url' => '/trusted_badge',
            'method' => 'GET',
            'content' => [],
        );

        // Though test account is standard checkout eligible, they are ineligible because of fraud_type

        $this->ba->proxyAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('ineligible', $response['status']);
        $this->assertEquals(false, $response['is_live']);

        $demoMerchantUser = $this->fixtures->user->createUserForMerchant(Account::DEMO_ACCOUNT);
        $this->ba->proxyAuth('rzp_test_' . Account::DEMO_ACCOUNT, $demoMerchantUser['id']);
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('eligible', $response['status']);
        $this->assertEquals(true, $response['is_live']);
    }

    /** This method tests if merchants having gmv > 20 lakhs are eligible for RTB or not.
     *
     * @return void
     */
    public function testGetHighTransactingVolumeMids(): void
    {
        $ninetyOneDaysAgo = Carbon::today()->subDays(91)->getTimestamp();

        $callback = static function ($query) {
            $highTransactingVolumeMerchantsData = [
                [
                    'merchant_id' => Account::TEST_ACCOUNT
                ],
            ];

            if ($query === Repository::HIGH_TRANSACTING_VOLUME_MERCHANTS_QUERY) {
                return $highTransactingVolumeMerchantsData;
            }

            return [];
        };

        $this->mockPrestoService($callback);

        $this->fixtures->edit('merchant', Account::TEST_ACCOUNT, [
            'category2' => Category::SOCIAL,
            'activated_at' => $ninetyOneDaysAgo,
            'category' => '8699',
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

    protected function mockPrestoService($callback): void
    {
        $prestoService = $this->getMockBuilder(DataLakePrestoMock::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getDataFromDataLake'])
            ->getMock();

        $prestoService->method( 'getDataFromDataLake')
            ->willReturnCallback($callback);

        $this->app->instance('datalake.presto', $prestoService);
    }

    public function testTrustedBadgeDetailsWithEntry(): void
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('trusted_badge', [
            'merchant_status' => ''
        ]);

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

    public function testAddToTrustedBadgeWhitelist(): void
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRemoveFromTrustedBadgeWhitelist(): void
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
            'url'     => '/trusted_badge/status',
            'method'  => 'PUT',
            'content' => [
                'merchant_ids' => ['10000000000000'],
                'status' =>  'blacklist',
                'action' => 'add',
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
        $this->assertEquals(false, $response['is_live']);

        // remove merchant from blacklist
        $this->ba->adminAuth();

        $request = array(
            'url'     => '/trusted_badge/status',
            'method'  => 'PUT',
            'content' => [
                'merchant_ids' => ['10000000000000'],
                'status' =>  'blacklist',
                'action' => 'remove',
            ],
            'convertContentToString' => false,
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $response['success']);

        $this->ba->proxyAuth();

        // fetch and check status, is delisted atleast once
        $this->startTest();
    }

    public function testTrustedBadgeWhitelistWithStatusCheck(): void
    {
        $this->ba->adminAuth();

        $this->fixtures->create('trusted_badge');
        $this->fixtures->create('trusted_badge_history');

        // whitelist merchant
        $request = array(
            'url'     => '/trusted_badge/status',
            'method'  => 'PUT',
            'content' => [
                'merchant_ids' => ['10000000000000'],
                'status' =>  'whitelist',
                'action' => 'add',
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

        $this->assertEquals('whitelist', $response['status']);
        $this->assertEquals(false, $response['is_live']);

        // remove merchant from whitelist
        $this->ba->adminAuth();

        $request = array(
            'url'     => '/trusted_badge/status',
            'method'  => 'PUT',
            'content' => [
                'merchant_ids' => ['10000000000000'],
                'status' =>  'whitelist',
                'action' => 'remove',
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
