<?php

namespace Functional\Payout;

use Cache;
use Mockery;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Card\Type;
use RZP\Constants\Timezone;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Entity;
use RZP\Models\Card\Network;
use RZP\Models\BankingAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Detail;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception\ServerErrorException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Payout\Metric as PayoutMetric;
use RZP\Models\Payout\Status as PayoutStatus;
use RZP\Models\Admin\Service as AdminService;
use RZP\Services\FTS\Constants as FTSConstants;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Tests\Functional\Helpers\EntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use RZP\Models\Payout\Configurations\DirectAccounts\PayoutModeConfig;

class PayoutSmartRoutingTest extends TestCase
{
    use PayoutTrait;
    use TestsMetrics;
    use WorkflowTrait;
    use EntityFetchTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected $ftsMock = null;

    protected $harvesterMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutSmartRoutingTestData.php';

        parent::setUp();

        $this->fixtures->create('contact', [
            'id'     => '1000001contact',
            'active' => 1
        ]);

        $this->fixtures->create('bank_account', [
            'id'               => '100000000000ba',
            'ifsc_code'        => 'ORBC0101685',
            'account_number'   => '2224440041626999',
            'beneficiary_name' => 'Ambar',
            'type'             => 'contact',
            'entity_id'        => '1000001contact',
        ]);

        $this->fixtures->create('fund_account', [
            'id'           => '100000000000fa',
            'source_id'    => '1000001contact',
            'source_type'  => 'contact',
            'account_type' => 'bank_account',
            'account_id'   => '100000000000ba'
        ]);

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->fixtures->merchant->addFeatures([Features::ENABLE_SMART_ROUTING]);

        $this->app['config']->set('applications.ledger.enabled', false);

        $this->app['rzp.mode'] = Mode::TEST;

        $this->ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])
                                ->shouldAllowMockingProtectedMethods()->makePartial();

        $this->setupHarvesterMock();

        $this->flushCache();
    }

    // Setups And Mocks
    protected function setupHarvesterMock(): void
    {

        $this->harvesterMock = Mockery::mock('RZP\Services\Mock\HarvesterClient')->makePartial();

        //$this->harvesterMock->shouldAllowMockingProtectedMethods();

        $this->app['eventManager'] = $this->harvesterMock;
    }

    public function setupLiteAndDirectAccountsForMerchants(
        $liteAccountCountCreation = 0,
        $directAccountCountCreation = 0,
        $mode = Mode::TEST)
    {
        // Activate merchant with business_banking flag set to true.
        $this->fixtures->on($mode)->merchant->edit('10000000000000', ['business_banking' => 1]);
        $this->fixtures->on($mode)->merchant->activate();

        $this->fixtures->on($mode)->merchant->addFeatures(['virtual_accounts', 'payout']);

        $directBalances = [];
        $liteBalances   = [];

        // Create Lite Accounts for merchant
        while ($liteAccountCountCreation > 0)
        {
            $accountNumber = '222444004162' . random_integer(4);

            $bankingBalance = $this->fixtures->on($mode)->merchant->createBalanceOfBankingType(
                1000000000, '10000000000000', AccountType::SHARED, Channel::YESBANK);
            $bankingBalance->save();

            // Need to create a Banking Account since we send this data to ledger in ledger calls
            $bankingAccountAttributes = [
                'id'             => 'ABCde1234ABCd' . $liteAccountCountCreation,
                'account_number' => $accountNumber,
                'balance_id'     => $bankingBalance['id'],
                'account_type'   => 'current',
                'channel'        => $bankingBalance['channel'],
            ];

            $this->createBankingAccount($bankingAccountAttributes, $mode);

            // Creates virtual account, its bank account receiver on new banking balance.
            $virtualAccount = $this->fixtures->on($mode)->create('virtual_account');
            $bankAccount    = $this->fixtures->on($mode)->create(
                'bank_account',
                [
                    'type'           => 'virtual_account',
                    'entity_id'      => $virtualAccount->getId(),
                    'account_number' => $accountNumber,
                    'ifsc_code'      => 'RAZRB000000',
                ]);
            $virtualAccount->bankAccount()->associate($bankAccount);
            $virtualAccount->balance()->associate($bankingBalance);
            $virtualAccount->save();

            // Updates banking balance's account number after bank account creation.
            $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
            $bankingBalance->save();

            $defaultFreePayoutsCount = $this->getDefaultFreePayoutsCount($bankingBalance);

            $this->fixtures->create('counter', [
                'account_type'          => AccountType::SHARED,
                'balance_id'            => $bankingBalance->getId(),
                'free_payouts_consumed' => $defaultFreePayoutsCount,
            ]);

            $this->setupRedisConfigKeysForTerminalSelection();

            // Additionally, creates a terminal for bank transfer on banking balance.
            $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

            $liteBalances[] = $bankingBalance;

            $liteAccountCountCreation--;
        }

        // Create Direct Accounts for merchant
        while ($directAccountCountCreation > 0)
        {
            $accountNumber = "409" . random_integer(9);

            $channel = (($directAccountCountCreation % 2) !== 0) ? Channel::RBL: Channel::ICICI;

            $directBankingBalance = $this->fixtures->on($mode)->merchant->createBalanceOfBankingType(
                1000000, '10000000000000', AccountType::DIRECT, $channel);

            $directBankingBalance->setAccountNumber($accountNumber);
            $directBankingBalance->save();

            if ($channel === Channel::RBL)
            {
                $directBankingAccountParams = [
                    'id'                  => 'xba0000000000' . $directAccountCountCreation,
                    'merchant_id'         => '10000000000000',
                    'account_ifsc'        => 'RATN0000088',
                    'account_number'      => $accountNumber,
                    'account_type'        => BankingAccount\AccountType::CURRENT,
                    'status'              => 'activated',
                    'channel'             => $channel,
                    'balance_id'          => $directBankingBalance->getId(),
                    'fts_fund_account_id' => $directAccountCountCreation + 10000,
                ];

                $this->fixtures->on($mode)->create('banking_account', $directBankingAccountParams);
            }

            $this->fixtures->on($mode)->create('banking_account_statement_details', [
                Details\Entity::ID                        => 'xbasd00000000' . $directAccountCountCreation,
                Details\Entity::MERCHANT_ID               => '10000000000000',
                Details\Entity::BALANCE_ID                => $directBankingBalance->getId(),
                Details\Entity::ACCOUNT_NUMBER            => $accountNumber,
                Details\Entity::CHANNEL                   => $channel,
                Details\Entity::STATUS                    => Details\Status::ACTIVE,
                Details\Entity::GATEWAY_BALANCE           => 900000000,
                Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->getTimestamp()
            ]);

            $this->fixtures->on($mode)->create('counter', [
                'account_type'          => AccountType::DIRECT,
                'balance_id'            => $directBankingBalance->getId(),
                'free_payouts_consumed' => 0,
            ]);

            $this->setFreePayoutsCountInAdminKey(AccountType::DIRECT, $channel);

            $directBalances[] = $directBankingBalance;

            $directAccountCountCreation--;
        }

        return [$liteBalances, $directBalances];
    }

    public function mockFtsTransfer($ftsMock, &$ftsTransferSuccess, $throwError = false, $times = 1)
    {
        if ($throwError === false)
        {
            $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                    ->andReturn([true, 'Dummy']);

            $ftsTransferSuccess = false;

            $ftsMock->shouldReceive('createAndSendRequest')
                    ->andReturnUsing(function(string $endpoint, string $method, array $input) use (&$ftsTransferSuccess) {

                        self::assertEquals('/transfer', $endpoint);
                        self::assertEquals('POST', $method);

                        $ftsTransferSuccess = true;

                        return [
                            FTSConstants::BODY => [
                                FTSConstants::STATUS           => FTSConstants::STATUS_CREATED,
                                FTSConstants::MESSAGE          => 'fund transfer sent to fts.',
                                FTSConstants::FUND_TRANSFER_ID => random_integer(2),
                                FTSConstants::FUND_ACCOUNT_ID  => random_integer(2),
                            ]
                        ];
                    })->times($times);
        }
        else
        {
            $ftsMock->shouldReceive('createAndSendRequest')
                    ->andThrowExceptions([new \Exception("Server Error")])->times($times);
        }

        $this->app->instance('fts_fund_transfer', $ftsMock);

        return $ftsMock;
    }

    public function mockFtsSmartRouting(
        $ftsMock,
        &$ftsSmartRoutingSuccess,
        $times = 1,
        $mockedFetchModeResponse = [],
        $throwError = false)
    {
        if ($throwError === false)
        {
            $ftsMock->shouldReceive('createAndSendRequest')
                    ->andReturnUsing(function(string $endpoint, string $method, array $input) use ($mockedFetchModeResponse, &$ftsSmartRoutingSuccess) {

                        self::assertEquals('/routing/route_selection', $endpoint);
                        self::assertEquals('POST', $method);

                        self::assertArrayHasKey(AccountType::DIRECT, $input);
                        self::assertArrayHasKey(PayoutEntity::AMOUNT, $input);
                        self::assertArrayHasKey(PayoutEntity::MODE, $input);
                        self::assertArrayHasKey(PayoutEntity::MERCHANT_ID, $input);

                        $ftsSmartRoutingSuccess = true;

                        return [
                            'body' => $mockedFetchModeResponse,
                            'code' => 200,
                        ];
                    })->times($times);
        }
        else
        {
            $ftsMock->shouldReceive('createAndSendRequest')
                    ->andThrowExceptions([new \Exception("Server Error")])->times($times);
        }

        $this->app->instance('fts_fund_transfer', $ftsMock);

        return $ftsMock;
    }

    public function mockCountMetric($metricName, &$boolMetricCaptured, $metricExpectedDimensions = [])
    {
        $metricsMock = $this->createMetricsMock();

        $this->mockAndCaptureCountMetric(
            $metricName,
            $metricsMock,
            $boolMetricCaptured,
            $metricExpectedDimensions
        );
    }

    public function mockBasFetch(&$basFetchSuccess, $throwError = false, $times = 1)
    {
        $this->app['config']->set('applications.banking_account_service.mock', false);

        $this->app['rzp.mode'] = Mode::TEST;

        $basMock = Mockery::mock('RZP\Services\BankingAccountService', [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        if ($throwError === false)
        {
            $basMock->shouldReceive('fetchFtsFundAccountIdFromBas')
                    ->andReturnUsing(function(string $merchantId, string $channel, string $accountNumber) use (&$basFetchSuccess) {

                        self::assertEquals('10000000000000', $merchantId);
                        self::assertEquals('rbl', $channel);

                        $basFetchSuccess = true;

                        return random_integer(5);
                    })->times($times);
        }
        else
        {
            $basMock->shouldReceive('fetchFtsFundAccountIdFromBas')
                    ->andThrowExceptions([new \Exception("Server Error")])->times($times);
        }

        $this->app->instance('banking_account_service', $basMock);

        return $basMock;
    }

    // Tests for Smart Routing

    /**
     * Active Accounts Config: Lite: 1, direct: 0
     * Smart Routing Verdict: FAILURE
     *
     * Smart Routing failure due to amount less than 100 paisa
     */
    public function testSmartRouting_CreatePayout_RoutingFailureDueToAmountValidation()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess, false, 0);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($liteBalances)->getAccountNumber();

        $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no payout got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue($boolFailureMetricCaptured);
        $this->assertFalse($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 0
     * Smart Routing Verdict: FAILURE
     *
     * Smart Routing failure due to amount greater than 10cr
     */
    public function testSmartRouting_CreatePayout_RoutingFailureDueToAmountBreachingUpperThreshold()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess, false, 0);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($liteBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no payout got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue($boolFailureMetricCaptured);
        $this->assertFalse($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 0
     * Smart Routing Verdict: NA
     *
     * Smart Routing doesn't happen due to missing account number in payout request. The request fails
     * due to validation failure.
     */
    public function testSmartRouting_CreatePayoutFailureDueToMissingAccountNumber()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess, false, 0);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $this->ba->privateAuth();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no payout got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertFalse($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 0, direct: 1 (Not Valid)
     * Smart Routing Verdict: FAILURE
     *
     * Smart Routing failure due to no valid accounts present for merchants for routing. Payout creation happens
     * with default account number sent
     */
    public function testSmartRouting_CreatePayoutWithNoValidAccountsForRouting()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(0, 1);

        $basDetails = $this->getDbEntity('banking_account_statement_details', [
            'balance_id' => array_first($directBalances)->getId(),
        ]);

        $this->fixtures->edit('balance', array_first($directBalances)->getId(), [
            'channel' => 'icici',
        ]);

        $this->fixtures->edit('banking_account_statement_details', $basDetails->getId(), [
            'status'  => 'archived',
            'channel' => 'icici',
        ]);

        $bankingAccount = $this->getDbEntity('banking_account', [
            'balance_id' => array_first($directBalances)->getId(),
        ]);

        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'status'              => 'archived',
            'channel'             => 'icici',
            'fts_fund_account_id' => null
        ]);

        $this->setFreePayoutsCountInAdminKey(AccountType::DIRECT, BankingAccount\Channel::ICICI);

        $this->app['config']->set('applications.banking_account_service.mock', false);

        $basFetchSuccess = false;

        $this->mockBasFetch($basFetchSuccess, true, 0);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess, false, 0);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = $basDetails->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue($boolFailureMetricCaptured);
        $this->assertFalse($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
        $this->assertFalse($basFetchSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1 (Not Valid)
     * Smart Routing Verdict: SUCCESS. Picks Lite account by default
     */
    public function testSmartRouting_CreatePayoutWithRoutingToDefaultLiteAccount()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $basDetails = $this->getDbEntity('banking_account_statement_details', [
            'balance_id' => array_first($directBalances)->getId(),
        ]);

        $this->fixtures->edit('banking_account_statement_details', $basDetails->getId(), [
            'status' => 'archived'
        ]);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_WithRoutingChoosingLiteAccount']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 0, direct: 1
     * Smart Routing Verdict: SUCCESS. Picks direct account by default
     */
    public function testSmartRouting_CreatePayoutWithRoutingToDefaultDirectAccount()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(0, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_WithRoutingChoosingDirectAccount']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($directBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 0, direct: 2
     * Smart Routing Verdict: SUCCESS. Picks one of the direct accounts
     */
    public function testSmartRouting_CreatePayoutWithRoutingBetweenMultipleDirectAccounts()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(0, 2);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $ftsRoutingMockedResponse = [
            BalanceEntity::ACCOUNT_TYPE => AccountType::DIRECT,
            PayoutEntity::BALANCE_ID    => $directBalances[1]->getId(),
        ];

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 1, $ftsRoutingMockedResponse);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_WithRoutingChoosingDirectAccount']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals($directBalances[1]->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertTrue($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 2, direct: 0
     * Smart Routing Verdict: FAILURE.
     *
     * This scenario is not supported by smart routing module currently. Payout creation happens
     * with default account number sent.
     */
    public function testSmartRouting_CreatePayoutWithRoutingBetweenMultipleLiteAccounts()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(2);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($liteBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_WithRoutingChoosingLiteAccount']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertTrue($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: FAILURE.
     *
     * FTS sends us invalid balanceId in routing response, leading to module failure. Payout creation happens
     * with default account number sent.
     */
    public function testSmartRouting_CreatePayoutWithIncorrectResponseFromFtsWhileRouting()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $ftsRoutingMockedResponse = [
            BalanceEntity::ACCOUNT_TYPE => AccountType::DIRECT,
            PayoutEntity::BALANCE_ID    => UniqueIdEntity::generateUniqueId(),
        ];

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 1, $ftsRoutingMockedResponse);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($liteBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_WithRoutingChoosingLiteAccount']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertTrue($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertTrue($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: FAILURE.
     *
     * FTS sends us invalid balanceId in routing response, leading to module failure. Payout creation happens
     * with default account number sent.
     */
    public function testSmartRouting_CreatePayoutFtsResponseValidationFailureWhileRouting()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $ftsRoutingMockedResponse = [
            BalanceEntity::ACCOUNT_TYPE => AccountType::DIRECT,
            PayoutEntity::BALANCE_ID    => null,
        ];

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 1, $ftsRoutingMockedResponse);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($liteBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_WithRoutingChoosingLiteAccount']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertTrue($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertTrue($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: FAILURE.
     *
     * FTS sends failure response, leading to module failure. Payout creation happens
     * with default account number sent.
     */
    public function testSmartRouting_CreatePayoutFtsSmartRoutingFailure()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 1, [], true);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_WithRoutingChoosingDirectAccount']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($directBalances)->getId(), $payout->getBalanceId());

        $this->assertTrue($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 2
     * Smart Routing Verdict: FAILURE.
     *
     * BAS Fetch Failure happens while finding valid direct accounts, leading to module failure.
     * Payout creation happens with default account number sent.
     */
    public function testSmartRouting_CreatePayoutBasFetchFtsFundAccountIdFailure()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 2);

        $basFetchSuccess = false;

        $this->mockBasFetch($basFetchSuccess, true);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($liteBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_WithRoutingChoosingLiteAccount']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertTrue($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
        $this->assertFalse($basFetchSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: FAILURE. (Payouts with UPI mode)
     *
     * Smart Routing fails in payout with UPI mode, due to DCS failure for checking feasibility.
     * Payout creation happens with default account number sent.
     */
    public function testSmartRouting_CheckSmartRoutingFailureForPayoutsToFundAccountTypeVpaDueToDcsFailure()
    {
        $contact = $this->getDbLastEntity('contact');

        $this->fixtures->create('fund_account:vpa', [
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contact->getId(),
        ]);

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $this->fixtures->edit('balance', array_first($directBalances)->getId(), [
            'channel' => Channel::AXIS,
        ]);

        $basDetails = $this->getDbEntity('banking_account_statement_details', ['balance_id' => array_first($directBalances)->getId()]);

        $this->fixtures->edit('banking_account_statement_details', $basDetails->getId(), [
            'channel' => Channel::AXIS,
        ]);

        $bankingAccount = $this->getDbEntity('banking_account', ['balance_id' => array_first($directBalances)->getId()]);

        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'channel' => Channel::AXIS,
        ]);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $dcsConfigServiceMock = $this->getMockBuilder( DcsConfigService::class)
                                     ->setConstructorArgs([$this->app])
                                     ->getMock();

        $dcsCallCounter = 0;

        $dcsConfigServiceMock
            ->method('fetchConfiguration')
            ->willReturnCallback(function() use (&$dcsCallCounter) {
                if ($dcsCallCounter === 0)
                {
                    $dcsCallCounter++;

                    throw new ServerErrorException(
                        'error',
                        ErrorCode::SERVER_ERROR_DCS_SERVICE_PAYOUT_MODE_CONFIG_FETCH_FAILURE
                    );
                }
                else
                {
                    return [
                        PayoutModeConfig\Constants::ALLOWED_UPI_CHANNELS => ["axis"],
                    ];
                }
            });

        $this->app->instance('dcs_config_service', $dcsConfigServiceMock);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_CheckSmartRoutingForPayoutsToFundAccountTypeVpa']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($directBalances)->getId(), $payout->getBalanceId());

        $this->assertTrue($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SUCCESS. (Lite is chosen)
     */
    public function testSmartRouting_CreatePayoutWithRoutingBetweenMultipleAccountsWithLiteChosen()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $ftsRoutingMockedResponse = [
            BalanceEntity::ACCOUNT_TYPE => AccountType::SHARED,
            PayoutEntity::BALANCE_ID    => null
        ];

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 1, $ftsRoutingMockedResponse);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($this->testData['testSmartRouting_WithRoutingChoosingLiteAccount']);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertTrue($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1 (Not Valid)
     * Smart Routing Verdict: SUCCESS. (Payouts with UPI mode)
     */
    public function testSmartRouting_CheckSmartRoutingForPayoutsToFundAccountTypeVpa()
    {
        $contact = $this->getDbLastEntity('contact');

        $this->fixtures->create('fund_account:vpa', [
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contact->getId(),
        ]);

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SUCCESS. (Payouts with UPI mode)
     */
    public function testSmartRouting_CheckSmartRoutingForPayoutsToFundAccountTypeVpaWithDirectAccount()
    {
        $contact = $this->getDbLastEntity('contact');

        $this->fixtures->create('fund_account:vpa', [
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contact->getId(),
        ]);

        $this->fixtures->merchant->addFeatures([Features::RBL_CA_UPI]);

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $ftsRoutingMockedResponse = [
            BalanceEntity::ACCOUNT_TYPE => AccountType::DIRECT,
            PayoutEntity::BALANCE_ID    => array_first($directBalances)->getId()
        ];

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 1, $ftsRoutingMockedResponse);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $testData = $this->testData['testSmartRouting_CheckSmartRoutingForPayoutsToFundAccountTypeVpa'];

        $testData['request']['content']['account_number'] = array_first($liteBalances)->getAccountNumber();
        $testData['response']['content']['fees'] = 0;
        $testData['response']['content']['tax'] = 0;

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest($testData);

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($directBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertTrue($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SKIPPED. (For Dashboard Payouts)
     */
    public function testSmartRouting_CreatePayoutWithRoutingForDashboardPayouts()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $ftsRoutingMockedResponse = [
            BalanceEntity::ACCOUNT_TYPE => AccountType::SHARED,
            PayoutEntity::BALANCE_ID    => null
        ];

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0, $ftsRoutingMockedResponse);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($directBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SUCCESS. (For Composite Payouts)
     */
    public function testSmartRouting_CreateCompositePayoutWithRouting()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $ftsRoutingMockedResponse = [
            BalanceEntity::ACCOUNT_TYPE => AccountType::SHARED,
            PayoutEntity::BALANCE_ID    => null
        ];

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 1, $ftsRoutingMockedResponse);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertTrue($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SKIPPED. (For Internal Payouts)
     *
     * Payout creation happens with default account number sent.
     */
    public function testSmartRouting_CheckSmartRoutingForInternalPayouts()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->setMockRazorxTreatment([RazorxTreatment::USE_GATEWAY_BALANCE => 'on']);

        $this->ba->appAuthTest($this->config['applications.xpayroll.secret']);

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($directBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SKIPPED. (For Scheduled Payouts)
     *
     * Payout creation happens with default account number sent.
     */
    public function testSmartRouting_CheckSmartRoutingForScheduledPayouts()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess, false, 0);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($directBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::SCHEDULED, $payout->getStatus());
        $this->assertEquals(array_first($directBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertFalse($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SKIPPED. (For Payouts to cards)
     *
     * Payout creation happens with default account number sent.
     */
    public function testSmartRouting_CheckSmartRoutingForPayoutToCards()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->fixtures->merchant->addFeatures([Features::PAYOUT_TO_CARDS,
                                                Features::S2S,
                                                Features::ALLOW_NON_SAVED_CARDS]);

        (new AdminService)->setConfigKeys([ConfigKey::SET_CARD_METADATA_NULL => true]);

        $this->setMockRazorxTreatment([RazorxTreatment::ENABLE_MCS_TRANSFER => 'on']);

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::YESB
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'                                 => '10000000000000',
            Detail\Entity::BUSINESS_REGISTERED_ADDRESS    => "Line 1 Address",
            Detail\Entity::BUSINESS_REGISTERED_ADDRESS_L2 => "Line 2 Address",
            Detail\Entity::BUSINESS_REGISTERED_CITY       => "Bhubaneswar",
            Detail\Entity::BUSINESS_REGISTERED_PIN        => "751490",
        ]);

        $callable = function($route, $method, $input) {
            $response = [
                'error'   => '',
                'success' => true,
            ];

            switch ($route)
            {
                case 'tokenize':
                    $response['token']       = 'pay_44f3d176b38b4cd2a588f243e3ff7b20';
                    $response['fingerprint'] = null;
                    $response['scheme']      = '0';
                    break;

                case 'cards/metadata/fetch':
                    $response['token']        = $input['token'];
                    $response['iin']          = '340169';
                    $response['expiry_month'] = '08';
                    $response['expiry_year']  = '2025';
                    $response['name']         = 'chirag';
                    break;

                case 'cards/metadata':
                    self::assertArrayKeysExist($input, [
                        Entity::TOKEN,
                        Entity::NAME,
                        Entity::EXPIRY_YEAR,
                        Entity::EXPIRY_MONTH,
                        Entity::IIN
                    ]);

                    self::assertEquals(5, count($input));
                    break;

                case 'delete/token':
                    self::assertEquals('pay_44f3d176b38b4cd2a588f243e3ff7b20', $input['token']);

                // Nodal beneficiary registration
                case 'detokenize':
                    self::assertEquals('pay_44f3d176b38b4cd2a588f243e3ff7b20', $input['token']);
                    $response['value'] = '340169570990137';

                    break;
            }

            return $response;
        };

        $this->mockCardVault($callable);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($liteBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SKIPPED. (For Payouts to amazonPay wallets)
     *
     * Payout creation happens with default account number sent.
     */
    public function testSmartRouting_CheckSmartRoutingForAmazonPayWalletPayout()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['account_number'] = array_first($liteBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SKIPPED. (For Workflow Payouts)
     *
     * Payout creation happens with default account number sent.
     */
    public function testSmartRouting_CheckSmartRoutingForPayoutWorkflows()
    {
        $this->fixtures->on(Mode::LIVE)->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on(Mode::LIVE)->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->fixtures->on(Mode::LIVE)->create('bank_account', [
            'id'               => '1000000lcustba',
            'ifsc_code'        => 'ORBC0101685',
            'account_number'   => '2224440041626999',
            'beneficiary_name' => 'Ambar',
            'type'             => 'contact',
            'entity_id'        => '1000001contact',
        ]);

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1, Mode::LIVE);

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess, false, 0);

        $initialPayoutCount = count($this->getDbEntities('payout', [], Mode::LIVE));

        $response = $this->createPayoutWithWorkflow(['account_number'  => array_first($liteBalances)->getAccountNumber(),
                                                     'fund_account_id' => 'fa_100000000000fa'], 'rzp_live_TheLiveAuthKey');

        $finalPayoutCount = count($this->getDbEntities('payout', [], Mode::LIVE));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['id'])], Mode::LIVE);

        $this->assertEquals(PayoutStatus::PENDING, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertFalse($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SKIPPED. (For Bulk Payouts)
     *
     * Payout creation happens with default account number sent.
     */
    public function testSmartRouting_CheckSmartRoutingForBulkPayouts()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess);

        $this->ba->batchAuth();

        $this->testData[__FUNCTION__]['request']['content'][0]['razorpayx_account_number'] = array_first($liteBalances)->getAccountNumber();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $response = $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $payout = $this->getDbEntity('payout', ['id' => PayoutEntity::verifyIdAndStripSign($response['items'][0]['id'])]);

        $this->assertEquals(PayoutStatus::INITIATED, $payout->getStatus());
        $this->assertEquals(array_first($liteBalances)->getId(), $payout->getBalanceId());

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertTrue($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SKIPPED. (For Merchant Payouts)
     */
    public function testSmartRouting_CheckSmartRoutingForMerchantPayouts()
    {
        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess, false, 0);

        $this->ba->cronAuth();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertFalse($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    /**
     * Active Accounts Config: Lite: 1, direct: 1
     * Smart Routing Verdict: SKIPPED. (For Customer wallet Payouts)
     */
    public function testSmartRouting_CheckSmartRoutingForCustomerWalletPayouts()
    {
        $this->mockRazorxTreatment();

        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $boolFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::PAYOUTS_SMART_ROUTING_FAILURES_COUNT, $boolFailureMetricCaptured);

        $ftsSmartRoutingSuccess = false;

        $this->mockFtsSmartRouting($this->ftsMock, $ftsSmartRoutingSuccess, 0);

        $ftsTransferSuccess = false;

        $this->mockFtsTransfer($this->ftsMock, $ftsTransferSuccess, false, 0);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures
            ->on(Mode::LIVE)
            ->create('bank_account',
                [
                    'ifsc_code' => 'ORBC0101685',
                    'account_number' => '2224440041626905',
                    'beneficiary_name' => 'Ambar',
                    'type' => 'customer',
                    'entity_id' => 'GHz4VlBkkiUBwh',
                ]);

        $bankAccount = $this->getDbLastEntity('bank_account', Mode::LIVE);

        $this->fixtures->on(Mode::LIVE)
            ->edit('fund_account', '100000000000fa',
                [
                    'account_id' => $bankAccount->getId()
                ]);

        $this->fixtures->on(Mode::LIVE)->create('customer_balance', ['customer_id' => '100000customer', 'balance' => 1000]);

        $this->fixtures->on(Mode::LIVE)->edit('balance', '10000000000000', ['balance' => 1000]);

        $initialPayoutCount = count($this->getDbEntities('payout', [], Mode::LIVE));

        $this->startTest();

        $finalPayoutCount = count($this->getDbEntities('payout', [], Mode::LIVE));

        // Asserting that payout got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $this->assertFalse($boolFailureMetricCaptured);
        $this->assertFalse($ftsTransferSuccess);
        $this->assertFalse($ftsSmartRoutingSuccess);
    }

    public function testSmartRoutingSummary_ModeIMPS_DirectPriority()
    {
        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 2);

        $this->ba->privateAuth();

        $testDataRequest = $this->testData[__FUNCTION__]['request']['content'];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $ftsGetPriorityChannelSuccess = false;

        $ftsRoutingMockedResponse = [
            PayoutEntity::ACCOUNT_TYPE => AccountType::DIRECT,
            PayoutEntity::BALANCE_ID  => [
                $directBalances[0]->getId() => [
                    ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time'] ]
                ],
            ],
        ];

        $this->mockFtsGetPriorityChannel($this->ftsMock, $ftsGetPriorityChannelSuccess, 1, $ftsRoutingMockedResponse);


        //Get list of Balances
        foreach ($directBalances as $directBalance)
        {
            $listBalanceIds[] = $directBalance->getId();
        }
        foreach ($liteBalances as $liteBalance)
        {
            $listBalanceIds[] = $liteBalance->getId();
        }
        sort($listBalanceIds);

        $queryBuilderSuccess = false;
        $timeRangesList = [ ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time']] ];
        $expectedQuery = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, $testDataRequest['mode'], $timeRangesList, $queryBuilderSuccess);

        $harvesterResponse = [];
        foreach ($listBalanceIds as $balanceId)
        {
            $harvesterResponse[] = [
                'count' => 5,
                'balance_id' => $balanceId,
                'status' => "PROCESSED",
                'amount' => 500
            ];
        }

        $harvesterServiceSuccess = false;
        $this->mockHarvesterService([$expectedQuery], $harvesterResponse, $harvesterServiceSuccess);

        $this->startTest();

        $this->assertTrue($ftsGetPriorityChannelSuccess);
        $this->assertTrue($harvesterServiceSuccess);
        $this->assertTrue($queryBuilderSuccess);

    }

    public function testSmartRoutingSummary_ModeIMPS_SharedPriority()
    {
        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 2);

        $this->ba->privateAuth();

        $testDataRequest = $this->testData[__FUNCTION__]['request']['content'];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $ftsGetPriorityChannelSuccess = false;

        $ftsRoutingMockedResponse = [
            PayoutEntity::ACCOUNT_TYPE => AccountType::SHARED,
            PayoutEntity::BALANCE_ID  => [],
        ];

        $this->mockFtsGetPriorityChannel($this->ftsMock, $ftsGetPriorityChannelSuccess, 1, $ftsRoutingMockedResponse);


        //Get list of Balances
        foreach ($directBalances as $directBalance)
        {
            $listBalanceIds[] = $directBalance->getId();
        }
        foreach ($liteBalances as $liteBalance)
        {
            $listBalanceIds[] = $liteBalance->getId();
        }
        sort($listBalanceIds);

        $queryBuilderSuccess = false;
        $timeRangesList = [ ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time']] ];
        $expectedQuery = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, $testDataRequest['mode'], $timeRangesList, $queryBuilderSuccess);

        $harvesterResponse = [];
        foreach ($listBalanceIds as $balanceId)
        {
            $harvesterResponse[] = [
                'count' => 5,
                'balance_id' => $balanceId,
                'status' => "PROCESSED",
                'amount' => 500
            ];
        }

        $harvesterServiceSuccess = false;
        $this->mockHarvesterService([$expectedQuery], $harvesterResponse, $harvesterServiceSuccess);

        $this->startTest();

        $this->assertTrue($ftsGetPriorityChannelSuccess);
        $this->assertTrue($harvesterServiceSuccess);
        $this->assertTrue($queryBuilderSuccess);

    }

    public function testSmartRoutingSummary_ModeAll_DirectPriority()
    {
        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Features::RBL_CA_UPI]);

        $testDataRequest = $this->testData[__FUNCTION__]['request']['content'];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $ftsGetPriorityChannelSuccess = false;

        $ftsRoutingMockedResponse = [
            PayoutEntity::ACCOUNT_TYPE => AccountType::DIRECT,
            PayoutEntity::BALANCE_ID  => [
                $directBalances[0]->getId() => [
                    ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time'] ]
                ],
            ],
        ];

        $this->mockFtsGetPriorityChannel($this->ftsMock, $ftsGetPriorityChannelSuccess, 2, $ftsRoutingMockedResponse);


        //Get list of Balances
        foreach ($directBalances as $directBalance)
        {
            $listBalanceIds[] = $directBalance->getId();
        }
        foreach ($liteBalances as $liteBalance)
        {
            $listBalanceIds[] = $liteBalance->getId();
        }
        sort($listBalanceIds);

        $queryBuilderSuccess = false;
        $timeRangesList = [ ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time']] ];
        if($testDataRequest['mode'] == "ALL")
        {
            $expectedQuery1 = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, "IMPS", $timeRangesList, $queryBuilderSuccess);
            $expectedQuery2 = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, "UPI", $timeRangesList, $queryBuilderSuccess);
            $expectedQuery = [$expectedQuery1, $expectedQuery2];
        }
        else
        {
            $expectedQuery = [$this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, $testDataRequest['mode'], $timeRangesList, $queryBuilderSuccess)];
        }
        $harvesterResponse = [];
        foreach ($listBalanceIds as $balanceId)
        {
            $harvesterResponse[] = [
                'count' => 5,
                'balance_id' => $balanceId,
                'status' => "PROCESSED",
                'amount' => 500
            ];
        }

        $harvesterServiceSuccess = false;
        $this->mockHarvesterService($expectedQuery, $harvesterResponse, $harvesterServiceSuccess, 2);

        $this->startTest();

        $this->assertTrue($ftsGetPriorityChannelSuccess);
        $this->assertTrue($harvesterServiceSuccess);
        $this->assertTrue($queryBuilderSuccess);

    }

    public function testSmartRoutingSummary_ModeAll_SharedPriority()
    {
        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Features::RBL_CA_UPI]);

        $testDataRequest = $this->testData[__FUNCTION__]['request']['content'];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $ftsGetPriorityChannelSuccess = false;

        $ftsRoutingMockedResponse = [
            PayoutEntity::ACCOUNT_TYPE => AccountType::SHARED,
            PayoutEntity::BALANCE_ID  => [],
        ];

        $this->mockFtsGetPriorityChannel($this->ftsMock, $ftsGetPriorityChannelSuccess, 2, $ftsRoutingMockedResponse);


        //Get list of Balances
        foreach ($directBalances as $directBalance)
        {
            $listBalanceIds[] = $directBalance->getId();
        }
        foreach ($liteBalances as $liteBalance)
        {
            $listBalanceIds[] = $liteBalance->getId();
        }
        sort($listBalanceIds);

        $queryBuilderSuccess = false;
        $timeRangesList = [ ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time']] ];
        if($testDataRequest['mode'] == "ALL")
        {
            $expectedQuery1 = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, "IMPS", $timeRangesList, $queryBuilderSuccess);
            $expectedQuery2 = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, "UPI", $timeRangesList, $queryBuilderSuccess);
            $expectedQuery = [$expectedQuery1, $expectedQuery2];
        }
        else
        {
            $expectedQuery = [$this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, $testDataRequest['mode'], $timeRangesList, $queryBuilderSuccess)];
        }

        $harvesterResponse = [];
        foreach ($listBalanceIds as $balanceId)
        {
            $harvesterResponse[] = [
                'count' => 5,
                'balance_id' => $balanceId,
                'status' => "PROCESSED",
                'amount' => 500
            ];
        }

        $harvesterServiceSuccess = false;
        $this->mockHarvesterService($expectedQuery, $harvesterResponse, $harvesterServiceSuccess,2);

        $this->startTest();

        $this->assertTrue($ftsGetPriorityChannelSuccess);
        $this->assertTrue($harvesterServiceSuccess);
        $this->assertTrue($queryBuilderSuccess);

    }
    public function testSmartRoutingSummary_ModeUPI_DirectPriority()
    {
        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Features::RBL_CA_UPI]);

        $testDataRequest = $this->testData[__FUNCTION__]['request']['content'];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $ftsGetPriorityChannelSuccess = false;

        $ftsRoutingMockedResponse = [
            PayoutEntity::ACCOUNT_TYPE => AccountType::DIRECT,
            PayoutEntity::BALANCE_ID  => [
                $directBalances[0]->getId() => [
                    ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time'] ]
                ],
            ],
        ];

        $this->mockFtsGetPriorityChannel($this->ftsMock, $ftsGetPriorityChannelSuccess, 1, $ftsRoutingMockedResponse);


        //Get list of Balances
        foreach ($directBalances as $directBalance)
        {
            $listBalanceIds[] = $directBalance->getId();
        }
        foreach ($liteBalances as $liteBalance)
        {
            $listBalanceIds[] = $liteBalance->getId();
        }
        sort($listBalanceIds);

        $queryBuilderSuccess = false;
        $timeRangesList = [ ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time']] ];
        $expectedQuery = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, $testDataRequest['mode'], $timeRangesList, $queryBuilderSuccess);

        $harvesterResponse = [];
        foreach ($listBalanceIds as $balanceId)
        {
            $harvesterResponse[] = [
                'count' => 5,
                'balance_id' => $balanceId,
                'status' => "PROCESSED",
                'amount' => 500
            ];
        }

        $harvesterServiceSuccess = false;
        $this->mockHarvesterService([$expectedQuery], $harvesterResponse, $harvesterServiceSuccess);

        $this->startTest();

        $this->assertTrue($ftsGetPriorityChannelSuccess);
        $this->assertTrue($harvesterServiceSuccess);
        $this->assertTrue($queryBuilderSuccess);

    }

    public function testSmartRoutingSummary_ModeUPI_SharedPriority()
    {
        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Features::RBL_CA_UPI]);

        $testDataRequest = $this->testData[__FUNCTION__]['request']['content'];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $ftsGetPriorityChannelSuccess = false;

        $ftsRoutingMockedResponse = [
            PayoutEntity::ACCOUNT_TYPE => AccountType::SHARED,
        ];

        $this->mockFtsGetPriorityChannel($this->ftsMock, $ftsGetPriorityChannelSuccess, 1, $ftsRoutingMockedResponse);


        //Get list of Balances
        foreach ($directBalances as $directBalance)
        {
            $listBalanceIds[] = $directBalance->getId();
        }
        foreach ($liteBalances as $liteBalance)
        {
            $listBalanceIds[] = $liteBalance->getId();
        }
        sort($listBalanceIds);

        $queryBuilderSuccess = false;
        $timeRangesList = [ ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time']] ];
        $expectedQuery = $this->payoutSummaryHarvesterQueryBuilder($listBalanceIds, $testDataRequest['mode'], $timeRangesList, $queryBuilderSuccess);

        $harvesterResponse = [];
        foreach ($listBalanceIds as $balanceId)
        {
            $harvesterResponse[] = [
                'count' => 5,
                'balance_id' => $balanceId,
                'status' => "PROCESSED",
                'amount' => 500
            ];
        }

        $harvesterServiceSuccess = false;
        $this->mockHarvesterService([$expectedQuery], $harvesterResponse, $harvesterServiceSuccess);

        $this->startTest();

        $this->assertTrue($ftsGetPriorityChannelSuccess);
        $this->assertTrue($harvesterServiceSuccess);
        $this->assertTrue($queryBuilderSuccess);

    }
    public function testSmartRoutingSummary_FailureDueToInvalidMode()
    {
        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $this->ba->privateAuth();

        $testDataRequest = $this->testData[__FUNCTION__]['request']['content'];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $ftsGetPriorityChannelSuccess = false;

        $ftsRoutingMockedResponse = [];

        $this->mockFtsGetPriorityChannel($this->ftsMock, $ftsGetPriorityChannelSuccess, 0, $ftsRoutingMockedResponse);

        $this->startTest();

        $this->assertFalse($ftsGetPriorityChannelSuccess);

    }

    public function testSmartRoutingSummary_FailureFtsServerError()
    {
        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $this->ba->privateAuth();

        $testDataRequest = $this->testData[__FUNCTION__]['request']['content'];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $ftsGetPriorityChannelSuccess = false;

        $ftsRoutingMockedResponse = [];

        $this->mockFtsGetPriorityChannel($this->ftsMock, $ftsGetPriorityChannelSuccess, 1, $ftsRoutingMockedResponse, true);

        $this->startTest();

        $this->assertFalse($ftsGetPriorityChannelSuccess);

    }

    public function testSmartRoutingSummary_FailureDueToInvalidFTSResponse()
    {
        $this->liveSetUp();

        list($liteBalances, $directBalances) = $this->setupLiteAndDirectAccountsForMerchants(1, 1);

        $this->ba->privateAuth();

        $testDataRequest = $this->testData[__FUNCTION__]['request']['content'];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $ftsGetPriorityChannelSuccess = false;

        $ftsRoutingMockedResponse = [
            PayoutEntity::ACCOUNT_TYPE => "INVALID_ACCOUNT_TYPE",
            PayoutEntity::BALANCE_ID  => [
                $directBalances[0]->getId() => [
                    ["start_time" => $testDataRequest['start_time'], "end_time" => $testDataRequest['end_time'] ]
                ],
            ],
        ];

        $this->mockFtsGetPriorityChannel($this->ftsMock, $ftsGetPriorityChannelSuccess, 1, $ftsRoutingMockedResponse, false);

        $this->startTest();

        $this->assertTrue($ftsGetPriorityChannelSuccess);
    }

    protected function mockHarvesterService($expectedContents, $response, &$harvesterServiceSuccess, $times = 1): void
    {
        $index = 0;
        $this->harvesterMock->shouldReceive('getDataFromPinot')
            ->times($times)
            ->with(Mockery::on(function($request) use ($expectedContents, &$index) {
                $expectedContent = $expectedContents[$index] ?? null;
                $index++;
                return $request == $expectedContent;
            }))
            ->andReturnUsing(function() use ($response) {
                return $response;
            });
        $harvesterServiceSuccess = true;
    }
    public function mockFtsGetPriorityChannel(
        $ftsMock,
        &$ftsGetPriorityChannelSuccess,
        $times = 1,
        $mockedFetchModeResponse = [],
        $throwError = false)
    {
        if ($throwError === false)
        {
            $ftsMock->shouldReceive('createAndSendRequest')
                ->andReturnUsing(function(string $endpoint, string $method, array $input) use ($mockedFetchModeResponse, &$ftsGetPriorityChannelSuccess) {

                    self::assertEquals('/routing/priority_route', $endpoint);
                    self::assertEquals('POST', $method);
                    self::assertArrayHasKey("merchant_id",$input);
                    self::assertArrayHasKey("balance_id",$input);
                    self::assertArrayHasKey("mode",$input);
                    self::assertArrayHasKey("start_time",$input);
                    self::assertArrayHasKey("end_time",$input);

                    $ftsGetPriorityChannelSuccess = true;

                    return [
                        'body' => $mockedFetchModeResponse,
                        'code' => 200,
                    ];
                })->times($times);
        }
        else
        {
            $ftsMock->shouldReceive('createAndSendRequest')
                ->andThrowExceptions([new \Exception("Server Error")])->times($times);
        }

        $this->app->instance('fts_fund_transfer', $ftsMock);

        return $ftsMock;
    }
    public function payoutSummaryHarvesterQueryBuilder($balancePriorityList, $mode, $timeRangesList, &$queryBuilderSuccess): array
    {
        $balance = array_values($balancePriorityList);
        $balanceString = "'" . implode("','", $balance) . "'";

        $conditions = [];
        foreach ($timeRangesList as $range) {
            $startTime = $range['start_time'];
            $endTime = $range['end_time'];

            $conditions[] = "(created_at > $startTime AND created_at <= $endTime)";
        }
        $conditions = implode(' AND ', $conditions);

        $query = "SELECT balance_id, status, COUNT(*) as count, SUM(amount) as amount
              FROM {{table}}
              WHERE balance_id IN ($balanceString)
                AND mode = '$mode'
                AND ($conditions)
              GROUP BY balance_id, status;";

        $queryBuilderSuccess = true;

        return [
            PayoutEntity::QUERY => $query,
            PayoutEntity::TABLE => PayoutEntity::PAYOUTS_TABLE,
            PayoutEntity::BACKEND => PayoutEntity::PINOT_BACKEND
        ];
    }
}
