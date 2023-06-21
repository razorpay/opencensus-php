<?php

namespace Functional\Payout;

use Queue;
use Mockery;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Contact;
use RZP\Services\Mozart;
use RZP\Services\Ledger;
use RZP\Constants\Timezone;
use RZP\Models\Payout\Purpose;
use RZP\Models\BankingAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\BankingAccountTpv;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Payout\Mode as PayoutMode;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Payout\Metric as PayoutMetric;
use RZP\Models\Payout\Status as PayoutStatus;
use RZP\Services\FTS\Constants as FTSConstants;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Tests\Functional\Helpers\EntityFetchTrait;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Jobs\FundManagementPayouts\FundManagementPayoutCheck;
use RZP\Jobs\FundManagementPayouts\FundManagementPayoutInitiate;

class FundManagementPayoutTest extends TestCase
{
    use PayoutTrait;
    use TestsMetrics;
    use EntityFetchTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected $directBalance;

    protected $directBankingAccount;

    protected $basDetails;

    protected $directCounter;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FundManagementPayoutTestData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 10000000, AccountType::SHARED, Channel::YESBANK);

        $this->bankAccount = $this->fixtures->edit('bank_account', $this->bankAccount->getId(), ['ifsc_code' => 'YESB0CMSNOC']);

        $this->directBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            30000000, '10000000000000', AccountType::DIRECT, Channel::RBL);

        $directBankingAccountParams = [
            'id'                  => 'xba00000000000',
            'merchant_id'         => '10000000000000',
            'account_ifsc'        => 'RATN0000088',
            'account_number'      => '409001996618',
            'account_type'        => BankingAccount\AccountType::CURRENT,
            'status'              => 'activated',
            'channel'             => Channel::RBL,
            'balance_id'          => $this->directBalance->getId(),
            'fts_fund_account_id' => '1234567',
        ];

        $this->directBankingAccount = $this->fixtures->create('banking_account', $directBankingAccountParams);

        $this->basDetails = $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbasd000000001',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->directBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '409001996618',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $defaultFreePayoutsCount = $this->getDefaultFreePayoutsCount($this->directBalance);

        $this->directCounter = $this->fixtures->create('counter', [
            'account_type'          => AccountType::DIRECT,
            'balance_id'            => $this->directBalance->getId(),
            'free_payouts_consumed' => $defaultFreePayoutsCount,
        ]);

        $this->fixtures->create('counter', [
            'account_type'          => AccountType::DIRECT,
            'balance_id'            => $this->directBalance->getId(),
            'free_payouts_consumed' => $defaultFreePayoutsCount,
        ]);

        $bankingAccountTpvParams = [
            BankingAccountTpv\Entity::MERCHANT_ID          => $this->basDetails->getMerchantId(),
            BankingAccountTpv\Entity::BALANCE_ID           => $this->basDetails->getBalanceId(),
            BankingAccountTpv\Entity::STATUS               => BankingAccountTpv\Status::APPROVED,
            BankingAccountTpv\Entity::PAYER_NAME           => 'Razorpay',
            BankingAccountTpv\Entity::PAYER_ACCOUNT_NUMBER => $this->basDetails->getAccountNumber(),
            BankingAccountTpv\Entity::PAYER_IFSC           => 'CITI0000006',
            BankingAccountTpv\Entity::CREATED_BY           => 'test',
            BankingAccountTpv\Entity::TYPE                 => BankingAccountTpv\Type::BANK_ACCOUNT,
            BankingAccountTpv\Entity::IS_ACTIVE            => true,
        ];

        $this->bankingAccountTpv = $this->fixtures->create('banking_account_tpv', $bankingAccountTpvParams);

        $this->flushCache();

        $this->app['config']->set('applications.banking_account_service.mock', true);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::FUND_MANAGEMENT_PAYOUTS_RETRIEVAL_THRESHOLD => 21600]);
    }

    protected function getFundManagementPayoutCheckQueueParams($input = null)
    {
        return [
            "channel"     => $input[PayoutEntity::CHANNEL] ?? "rbl",
            "merchant_id" => $input[PayoutEntity::MERCHANT_ID] ?? "10000000000000",
            "thresholds"  => [
                "neft_threshold"              => $input[PayoutConstants::NEFT_THRESHOLD] ?? 50000000,
                "lite_balance_threshold"      => $input[PayoutConstants::LITE_BALANCE_THRESHOLD] ?? 30000000,
                "lite_deficit_allowed"        => $input[PayoutConstants::LITE_DEFICIT_ALLOWED] ?? 500,     // Percentage
                "fmp_consideration_threshold" => $input[PayoutConstants::FMP_CONSIDERATION_THRESHOLD] ?? 9000, // In secs (2 hr 30 mins)
                "total_amount_threshold"      => $input[PayoutConstants::TOTAL_AMOUNT_THRESHOLD] ?? 30000000,
            ],
        ];
    }

    protected function getFundManagementPayoutInitiateQueueParams($input = null)
    {
        return [
            "channel"               => $input[PayoutEntity::CHANNEL] ?? "rbl",
            "merchant_id"           => $input[PayoutEntity::MERCHANT_ID] ?? "10000000000000",
            "fmp_unique_identifier" => UniqueIdEntity::generateUniqueId(),
            "payout_create_input"   => [
                "balance_id"           => $this->directBalance->getId(),
                "currency"             => "INR",
                "amount"               => 350000,
                "mode"                 => $input[PayoutEntity::MODE] ?? 'IMPS',
                "purpose"              => Purpose::RZP_FUND_MANAGEMENT,
                "narration"            => Purpose::RZP_FUND_MANAGEMENT,
                "queue_if_low_balance" => false,
                "fund_account"         => [
                    "account_type" => "bank_account",
                    "bank_account" => [
                        "name"           => $input['bank_account_name'] ?? $this->bankAccount->getName(),
                        "ifsc"           => $input['bank_account_ifsc'] ?? $this->bankAccount->getIfscCode(),
                        "account_number" => $input['bank_account_number'] ?? $this->bankAccount->getAccountNumber(),
                    ],
                    "contact"      => [
                        "name" => $input['contact_name'] ?? $this->basDetails->merchant->getBillingLabel(),
                        "type" => Contact\Type::SELF,
                    ]
                ],
            ],
        ];
    }

    protected function createFundManagementPayouts($fmpInputs)
    {
        $fundManagementPayouts = [];

        foreach ($fmpInputs as $fmpInput)
        {
            /* @var PayoutEntity $fundManagementPayout */
            $fundManagementPayouts[] = $this->fixtures->create(
                'payout',
                [
                    'merchant_id'     => '10000000000000',
                    'balance_id'      => $this->directBalance->getId(),
                    'transaction_id'  => null,
                    'utr'             => '211708954836',
                    'channel'         => $this->directBalance->getChannel(),
                    'mode'            => 'IMPS',
                    'pricing_rule_id' => 'Bbg7fgaDwax04u',
                    'purpose'         => Purpose::RZP_FUND_MANAGEMENT,
                ] + $fmpInput);
        }

        return $fundManagementPayouts;
    }

    public function mockMozartFetchGatewayBalance($amount, &$success, $throwError = false, $times = 1)
    {
        $this->app['rzp.mode'] = Mode::TEST;

        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mockedMissingStatementResponse = [
            'success' => true,
            'data'    => [
                Rbl\Fields::GET_ACCOUNT_BALANCE => [
                    Rbl\Fields::BODY => [
                        Rbl\Fields::BAL_AMOUNT => [
                            Rbl\Fields::AMOUNT_VALUE => $amount
                        ]
                    ]
                ]
            ]
        ];

        if ($throwError === false)
        {
            $mozartServiceMock->shouldReceive('sendRawRequest')
                              ->andReturnUsing(function(array $request) use ($mockedMissingStatementResponse, &$success) {

                                  self::assertTrue(str_contains($request['url'], 'razorpayx/rbl/v1/account_balance'));
                                  self::assertEquals('POST', $request['method']);

                                  $content = json_decode($request['content'], true);
                                  self::assertEquals($this->basDetails->getAccountNumber(), $content['entities']['source_account']['account_number']);

                                  $success = true;

                                  return json_encode($mockedMissingStatementResponse);
                              })->times($times);
        }
        else
        {
            $mozartServiceMock->shouldReceive('sendRawRequest')
                              ->andThrowExceptions([new \Exception("Server Error")])->times($times);
        }


        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function mockFTSFetchMode($mode, &$ftsSuccess, $throwError = false, $times = 1)
    {
        $this->app['rzp.mode'] = Mode::TEST;

        $ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mockedFetchModeResponse = [
            FTSConstants::SELECTED_MODE => $mode,
        ];

        if ($throwError === false)
        {
            $ftsMock->shouldReceive('createAndSendRequest')
                    ->andReturnUsing(function(string $endpoint, string $method, array $input) use ($mockedFetchModeResponse, &$ftsSuccess) {

                        self::assertEquals('/routing/mode_selection', $endpoint);
                        self::assertEquals('POST', $method);

                        self::assertArrayHasKey(FTSConstants::OFFSET_AMOUNT, $input);
                        self::assertArrayHasKey(Attempt\Entity::SOURCE_ACCOUNT_ID, $input);
                        self::assertEquals('10000000000000', $input[PayoutEntity::MERCHANT_ID]);
                        self::assertEquals(PayoutConstants::FUND_MANAGEMENT_PAYOUT, $input[FTSConstants::ACTION]);

                        $ftsSuccess = true;

                        return $mockedFetchModeResponse;
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

    public function mockLedgerFetchLiteBalance($balance, $throwError =  false, $times = 1)
    {
        $this->app['rzp.mode'] = Mode::TEST;

        $ledgerServiceMock = Mockery::mock('RZP\Services\Ledger', [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mockedFetchMerchantAccountsResponse = [
            'code' => 200,
            'body' => [
                "merchant_id"      => "10000000000000",
                "merchant_balance" => [
                    "balance"      => $balance,
                    "min_balance"  => "10000.000000"
                ],
                "reward_balance"  => [
                    "balance"     => "20.000000",
                    "min_balance" => "-20.000000"
                ],
            ],
        ];

        if ($throwError === false)
        {
            $ledgerServiceMock->shouldReceive('sendRequest')
                              ->andReturnUsing(
                                  function(string $endpoint,
                                           string $method,
                                           array  $body = [],
                                           array  $headers = [],
                                           bool   $throwExceptionOnFailure = false) use ($mockedFetchMerchantAccountsResponse, $throwError) {

                                      self::assertEquals('/twirp/rzp.ledger.dashboard.v1.DashboardAPI/FetchMerchantAccounts', $endpoint);
                                      self::assertEquals('POST', $method);

                                      $expectedRequest = [
                                          Ledger::TENANT             => 'X',
                                          PayoutEntity::MERCHANT_ID  => '10000000000000',
                                          Ledger::BANKING_ACCOUNT_ID => 'bacc_ABCde1234ABCde',
                                      ];

                                      self::assertArraySubset($expectedRequest, $body);

                                      return $mockedFetchMerchantAccountsResponse;
                                  })->times($times);
        }
        else
        {
            $ledgerServiceMock->shouldReceive('sendRequest')
                              ->andThrowExceptions([new \Exception("Server Error")])->times($times);
        }



        $this->app->instance('ledger', $ledgerServiceMock);
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

    // Tests for FundManagementPayoutCheck Queue

    /**
     * FMP check failing due to merchant not activated on X
     */
    public function testFundManagementPayoutCheck_FailureDueToMerchantNotActivatedOnX()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['live' => 0]);

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured,[
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => 'BAD_REQUEST_VALIDATION_FAILURE',
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
    }

    /**
     * FMP check failing due to no active virtual accounts on X
     */
    public function testFundManagementPayoutCheck_NoActiveVirtualAccounts()
    {
        $this->fixtures->edit('virtual_account', $this->virtualAccount->getId(), ['status' => 'closed']);

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured,[
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => 'BAD_REQUEST_VALIDATION_FAILURE',
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
    }

    /**
     * FMP check failing due to active virtual accounts present but not suitable bank_accounts for fund loading
     */
    public function testFundManagementPayoutCheck_BankAccountValidationFailure()
    {
        $this->fixtures->edit('bank_account', $this->bankAccount->getId(), ['ifsc' => 'RAZRB00001']);

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured,[
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => 'BAD_REQUEST_VALIDATION_FAILURE',
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
    }

    /**
     * FMP check failing due to no bas_details for the merchant
     */
    public function testFundManagementPayoutCheck_BasDetailsNotFound()
    {
        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), ['channel' => 'icici']);

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured,[
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => 'BAD_REQUEST_VALIDATION_FAILURE',
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
    }

    /**
     * FMP check failing due to banking Account TPV not configured on CA Account Number
     */
    public function testFundManagementPayoutCheck_BankingAccountTpvNotConfigured()
    {
        $this->fixtures->edit('banking_account_tpv', $this->bankingAccountTpv->getId(), [
            BankingAccountTpv\Entity::IS_ACTIVE => 0,
        ]);

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(
            PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT,
            $boolJobFailureMetricCaptured, [
                PayoutEntity::CHANNEL => Channel::RBL,
                'error_message'       => 'BAD_REQUEST_VALIDATION_FAILURE',
            ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
    }

    /**
     * FMP check failing due to failure of getting lite balance from Ledger
     */
    public function testFundManagementPayoutCheck_LiteBalanceFetchFromLedgerFailed()
    {
        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("100.00", true);

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(2000, $mozartSuccess, false, 0);

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured,[
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => 'BAD_REQUEST_BALANCE_DOES_NOT_EXIST',
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
    }

    /**
     * FMP check failing due to Lite Balance being greater than threshold
     */
    public function testFundManagementPayoutCheck_LiteBalanceGreaterThanThreshold()
    {
        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("28500000.00");

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(2000, $mozartSuccess, false, 0);

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured,[
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => PayoutConstants::LITE_BALANCE_IS_ABOVE_THRESHOLD,
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
    }

    /**
     * FMP check failing due to invalid offset Amount
     */
    public function testFundManagementPayoutCheck_InvalidOffSetAmount()
    {
        $oldDateTime = Carbon::create(2023, 6, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("15000000.00");

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(2000, $mozartSuccess, false, 0);

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured, [
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => PayoutConstants::INVALID_OFFSET_AMOUNT_FOR_FMP,
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams();

        $fmpInputs = [
            [
                PayoutEntity::AMOUNT       => 4500000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->clone()->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(100)->getTimestamp(),
            ],
            // Even though considered, it's not included in the calculation of the offset amount
            [
                PayoutEntity::AMOUNT       => 2000000,
                PayoutEntity::STATUS       => PayoutStatus::REVERSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
                PayoutEntity::REVERSED_AT  => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 7000000,
                PayoutEntity::STATUS       => PayoutStatus::ON_HOLD,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::ON_HOLD_AT   => Carbon::now(Timezone::IST)->subMinutes(39)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 8500000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
            ],
            // Should not consider these payouts for offset calculation, as it has breached the threshold for consideration
            [
                PayoutEntity::AMOUNT       => 1500000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(160)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(160)->getTimestamp(),
            ],
            // Should not consider these payouts for offset calculation
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::FAILED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(100)->getTimestamp(),
            ],
            // Payout stuck in created due to pod crash, which should not be considered while offset calculation
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::CREATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
            ],
            // Should not consider these payouts for offset calculation, as it has breached the threshold for retrieval
            [
                PayoutEntity::AMOUNT       => 3000000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(420)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(420)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(420)->getTimestamp(),
            ],
        ];

        $this->createFundManagementPayouts($fmpInputs);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);

        Carbon::setTestNow();
    }

    /**
     * FMP check failing due to low gateway balance on CA. CA balance here is fetched from DB
     */
    public function testFundManagementPayoutCheck_CaGatewayBalanceNotEnough()
    {
        $oldDateTime = Carbon::create(2023, 6, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("15000000.00");

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured, [
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => PayoutConstants::CA_BALANCE_NOT_ENOUGH_FOR_FMP,
        ]);

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(2000, $mozartSuccess, false, 0);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(5)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(5)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams([PayoutConstants::TOTAL_AMOUNT_THRESHOLD => 19000000]);

        $fmpInputs = [
            [
                PayoutEntity::AMOUNT       => 4500000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->clone()->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(100)->getTimestamp(),
            ],
            // Even though considered, it's not included in the calculation of the offset amount
            [
                PayoutEntity::AMOUNT       => 2000000,
                PayoutEntity::STATUS       => PayoutStatus::REVERSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
                PayoutEntity::REVERSED_AT  => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 4000000,
                PayoutEntity::STATUS       => PayoutStatus::ON_HOLD,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::ON_HOLD_AT   => Carbon::now(Timezone::IST)->subMinutes(39)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
            ],
            // Should not consider these payouts for offset calculation, as it has breached the threshold for consideration
            [
                PayoutEntity::AMOUNT       => 1500000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(160)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(160)->getTimestamp(),
            ],
            // Should not consider these payouts for offset calculation
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::FAILED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(100)->getTimestamp(),
            ],
            // Payout stuck in created due to pod crash, which should not be considered while offset calculation
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::CREATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
            ],
            // Should not consider these payouts for offset calculation, as it has breached the threshold for retrieval
            [
                PayoutEntity::AMOUNT       => 3000000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(420)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(420)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(420)->getTimestamp(),
            ],
        ];

        $this->createFundManagementPayouts($fmpInputs);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        $basDetails = $this->basDetails->reload();

        // Gateway Balance fetched from DB
        $this->assertEquals(200000, $basDetails->getGatewayBalance());

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);

        Carbon::setTestNow();
    }

    /**
     * FMP check failing due to low gateway balance on CA. CA balance here is fetched from Bank, since FMP had happened
     * recently
     */
    public function testFundManagementPayoutCheck_CaGatewayBalanceFetchedFromBankIsNotEnough()
    {
        $oldDateTime = Carbon::create(2023, 6, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("15000000.00");

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured, [
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => PayoutConstants::CA_BALANCE_NOT_ENOUGH_FOR_FMP,
        ]);

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(4000,  $mozartSuccess);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams([PayoutConstants::TOTAL_AMOUNT_THRESHOLD => 19000000]);

        $fmpInputs = [
            [
                PayoutEntity::AMOUNT       => 4500000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->clone()->subMinutes(10)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(9)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(4)->getTimestamp(),
            ],
            // Even though considered, it's not included in the calculation of the offset amount
            [
                PayoutEntity::AMOUNT       => 2000000,
                PayoutEntity::STATUS       => PayoutStatus::REVERSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
                PayoutEntity::REVERSED_AT  => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 4000000,
                PayoutEntity::STATUS       => PayoutStatus::ON_HOLD,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::ON_HOLD_AT   => Carbon::now(Timezone::IST)->subMinutes(39)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
            ],
        ];

        $this->createFundManagementPayouts($fmpInputs);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        $basDetails = $this->basDetails->reload();

        // Gateway balance was fetched from bank and bas_details was updated
        $this->assertEquals(400000, $basDetails->getGatewayBalance());
        $this->assertNotEquals($basDetails->getGatewayBalanceChangeAt(), Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp());

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
        $this->assertTrue((bool) $mozartSuccess);

        Carbon::setTestNow();
    }

    /**
     * FMP check failing due to low gateway balance on CA. CA balance here is fetched from DB as there was a failure in fetching from Bank
     */
    public function testFundManagementPayoutCheck_CaGatewayBalanceFetchFailureFromBank()
    {
        $oldDateTime = Carbon::create(2023, 6, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("15000000.00");

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured, [
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => PayoutConstants::CA_BALANCE_NOT_ENOUGH_FOR_FMP,
        ]);

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(4000,  $mozartSuccess, true);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams([PayoutConstants::TOTAL_AMOUNT_THRESHOLD => 19000000]);

        $fmpInputs = [
            [
                PayoutEntity::AMOUNT       => 4500000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->clone()->subMinutes(10)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(9)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(4)->getTimestamp(),
            ],
            // Even though considered, it's not included in the calculation of the offset amount
            [
                PayoutEntity::AMOUNT       => 2000000,
                PayoutEntity::STATUS       => PayoutStatus::REVERSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
                PayoutEntity::REVERSED_AT  => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 4000000,
                PayoutEntity::STATUS       => PayoutStatus::ON_HOLD,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::ON_HOLD_AT   => Carbon::now(Timezone::IST)->subMinutes(39)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
            ],
        ];

        $this->createFundManagementPayouts($fmpInputs);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        $basDetails = $this->basDetails->reload();

        // Gateway Balance fetched from DB
        $this->assertEquals(200000, $basDetails->getGatewayBalance());

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
        $this->assertFalse((bool) $mozartSuccess);

        Carbon::setTestNow();
    }

    /**
     * FTS Mode Fetch Failure. IMPS is picked up as default
     */
    public function testFundManagementPayoutCheck_FtsFetchModeFailure()
    {
        $oldDateTime = Carbon::create(2023, 6, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        Queue::fake();

        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("15000000.00");

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured, [
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => "",
        ]);

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(100000,  $mozartSuccess);

        $ftsSuccess = false;

        $this->mockFTSFetchMode(PayoutMode::NEFT,  $ftsSuccess, true);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams([PayoutConstants::TOTAL_AMOUNT_THRESHOLD => 19000000]);

        $fmpInputs = [
            [
                PayoutEntity::AMOUNT       => 4500000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->clone()->subMinutes(10)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(9)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(4)->getTimestamp(),
            ],
            // Even though considered, it's not included in the calculation of the offset amount
            [
                PayoutEntity::AMOUNT       => 2000000,
                PayoutEntity::STATUS       => PayoutStatus::REVERSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
                PayoutEntity::REVERSED_AT  => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 4000000,
                PayoutEntity::STATUS       => PayoutStatus::ON_HOLD,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::ON_HOLD_AT   => Carbon::now(Timezone::IST)->subMinutes(39)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
            ],
        ];

        $this->createFundManagementPayouts($fmpInputs);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        $basDetails = $this->basDetails->reload();

        // Gateway balance was fetched from bank and bas_details was updated
        $this->assertEquals(10000000, $basDetails->getGatewayBalance());
        $this->assertNotEquals($basDetails->getGatewayBalanceChangeAt(), Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp());

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertFalse((bool) $boolJobFailureMetricCaptured);
        $this->assertTrue((bool) $mozartSuccess);
        $this->assertFalse((bool) $ftsSuccess);

        $expectedParams = [
            "payout_create_input"   => [
                "balance_id"     => $this->directBalance->getId(),
                "amount"         => 9500000,
                "currency"       => "INR",
                "mode"           => "IMPS",
                "purpose"        => "RZP Fund Management",
                "fund_account"   => [
                    "account_type" => "bank_account",
                    "bank_account" => [
                        "name"           => $this->bankAccount->getName(),
                        "ifsc"           => $this->bankAccount->getIfscCode(),
                        "account_number" => $this->bankAccount->getAccountNumber(),
                    ],
                    "contact"      => [
                        "name" =>  $this->basDetails->merchant->getBillingLabel(),
                        "type" => Contact\Type::SELF,
                    ]
                ],
            ],
            "merchant_id"           => "10000000000000",
            "channel"               => Channel::RBL,
        ];

        Queue::assertPushed(FundManagementPayoutInitiate::class, function($job) use ($expectedParams) {
            $this->assertArraySubset($expectedParams, $job->getParams());
            $this->assertArrayHasKey(PayoutConstants::FMP_UNIQUE_IDENTIFIER, $job->getParams());

            return true;
        });

        $this->assertEquals(1, Queue::Pushed(FundManagementPayoutInitiate::class)->count());

        Carbon::setTestNow();
    }

    /**
     * FTS Mode Fetch Validation Failure happens as it sends a RTGS Mode. IMPS is picked up as default.
     */
    public function testFundManagementPayoutCheck_FtsFetchModeValidationFailure()
    {
        $oldDateTime = Carbon::create(2023, 6, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        Queue::fake();

        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("15000000.00");

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured, [
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => "",
        ]);

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(100000,  $mozartSuccess);

        $ftsSuccess = false;

        $this->mockFTSFetchMode(PayoutMode::RTGS,  $ftsSuccess);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams([PayoutConstants::TOTAL_AMOUNT_THRESHOLD => 19000000]);

        $fmpInputs = [
            [
                PayoutEntity::AMOUNT       => 4500000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->clone()->subMinutes(10)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(9)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(4)->getTimestamp(),
            ],
            // Even though considered, it's not included in the calculation of the offset amount
            [
                PayoutEntity::AMOUNT       => 2000000,
                PayoutEntity::STATUS       => PayoutStatus::REVERSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
                PayoutEntity::REVERSED_AT  => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 4000000,
                PayoutEntity::STATUS       => PayoutStatus::ON_HOLD,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::ON_HOLD_AT   => Carbon::now(Timezone::IST)->subMinutes(39)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
            ],
        ];

        $this->createFundManagementPayouts($fmpInputs);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        $basDetails = $this->basDetails->reload();

        // Gateway balance was fetched from bank and bas_details was updated
        $this->assertEquals(10000000, $basDetails->getGatewayBalance());
        $this->assertNotEquals($basDetails->getGatewayBalanceChangeAt(), Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp());

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertFalse((bool) $boolJobFailureMetricCaptured);
        $this->assertTrue((bool) $mozartSuccess);
        $this->assertTrue((bool) $ftsSuccess);

        $expectedParams = [
            "payout_create_input"   => [
                "balance_id"     => $this->directBalance->getId(),
                "amount"         => 9500000,
                "currency"       => "INR",
                "mode"           => "IMPS",
                "purpose"        => "RZP Fund Management",
                "fund_account"   => [
                    "account_type" => "bank_account",
                    "bank_account" => [
                        "name"           => $this->bankAccount->getName(),
                        "ifsc"           => $this->bankAccount->getIfscCode(),
                        "account_number" => $this->bankAccount->getAccountNumber(),
                    ],
                    "contact"      => [
                        "name" => $this->basDetails->merchant->getBillingLabel(),
                        "type" => Contact\Type::SELF,
                    ]
                ],
            ],
            "merchant_id"           => "10000000000000",
            "channel"               => Channel::RBL,
        ];

        Queue::assertPushed(FundManagementPayoutInitiate::class, function($job) use ($expectedParams) {
            $this->assertArraySubset($expectedParams, $job->getParams());
            $this->assertArrayHasKey(PayoutConstants::FMP_UNIQUE_IDENTIFIER, $job->getParams());

            return true;
        });

        $this->assertEquals(1, Queue::Pushed(FundManagementPayoutInitiate::class)->count());

        Carbon::setTestNow();
    }

    /**
     * Dispatch 2 FMPs since, offset amount is greater than NEFT Threshold
     */
    public function testFundManagementPayoutCheck_DispatchFmpsSuccess()
    {
        $oldDateTime = Carbon::create(2023, 6, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        Queue::fake();

        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("15000000.00");

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured, [
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => "",
        ]);

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(100000,  $mozartSuccess);

        $ftsSuccess = false;

        $this->mockFTSFetchMode(PayoutMode::NEFT,  $ftsSuccess);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams(
            [
                PayoutConstants::TOTAL_AMOUNT_THRESHOLD => 19000000,
                PayoutConstants::NEFT_THRESHOLD         => 5000000
            ]);

        $fmpInputs = [
            [
                PayoutEntity::AMOUNT       => 4500000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->clone()->subMinutes(10)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(9)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(4)->getTimestamp(),
            ],
            // Even though considered, it's not included in the calculation of the offset amount
            [
                PayoutEntity::AMOUNT       => 2000000,
                PayoutEntity::STATUS       => PayoutStatus::REVERSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
                PayoutEntity::REVERSED_AT  => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 4000000,
                PayoutEntity::STATUS       => PayoutStatus::ON_HOLD,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::ON_HOLD_AT   => Carbon::now(Timezone::IST)->subMinutes(39)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
            ],
        ];

        $this->createFundManagementPayouts($fmpInputs);

        $this->fixtures->edit('merchant', $this->basDetails->getMerchantId(), [
            'name'          => 'test_merchant',
            'billing_label' => 'test_merchant $additional_%characters_/#*greater_than_fifty',
        ]);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        $basDetails = $this->basDetails->reload();

        // Gateway balance was fetched from bank and bas_details was updated
        $this->assertEquals(10000000, $basDetails->getGatewayBalance());
        $this->assertNotEquals($basDetails->getGatewayBalanceChangeAt(), Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp());

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertFalse((bool) $boolJobFailureMetricCaptured);
        $this->assertTrue((bool) $mozartSuccess);
        $this->assertTrue((bool) $ftsSuccess);

        $expectedParams = [
            "payout_create_input"   => [
                "balance_id"     => $this->directBalance->getId(),
                "currency"       => "INR",
                "mode"           => "NEFT",
                "purpose"        => "RZP Fund Management",
                "fund_account"   => [
                    "account_type" => "bank_account",
                    "bank_account" => [
                        "name"           => $this->bankAccount->getName(),
                        "ifsc"           => $this->bankAccount->getIfscCode(),
                        "account_number" => $this->bankAccount->getAccountNumber(),
                    ],
                    "contact"      => [
                        "name" => 'testmerchant additionalcharactersgreaterthanfifty',
                        "type" => Contact\Type::SELF,
                    ]
                ],
            ],
            "merchant_id"           => "10000000000000",
            "channel"               => Channel::RBL,
        ];

        $totalAmountOfFmps = 0;

        Queue::assertPushed(FundManagementPayoutInitiate::class, function($job) use ($expectedParams, &$totalAmountOfFmps) {
            $this->assertArraySubset($expectedParams, $job->getParams());
            $this->assertArrayHasKey(PayoutConstants::FMP_UNIQUE_IDENTIFIER, $job->getParams());

            $totalAmountOfFmps = $totalAmountOfFmps + $job->getParams()[PayoutConstants::PAYOUT_CREATE_INPUT][PayoutEntity::AMOUNT];

            return true;
        });

        $this->assertEquals(2, Queue::Pushed(FundManagementPayoutInitiate::class)->count());
        $this->assertEquals(9500000, $totalAmountOfFmps);

        Carbon::setTestNow();
    }

    /**
     * Create a fund management payout
     */
    public function testFundManagementPayoutCheck_FundManagementPayoutCreationSuccess()
    {
        Queue::fake();

        $oldDateTime = Carbon::create(2023, 6, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerFetchLiteBalance("15000000.00");

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(PayoutMetric::FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT, $boolJobFailureMetricCaptured, [
            PayoutEntity::CHANNEL => Channel::RBL,
            'error_message'       => "",
        ]);

        $mozartSuccess = false;

        $this->mockMozartFetchGatewayBalance(100000,  $mozartSuccess);

        $ftsSuccess = false;

        $ftsMock = $this->mockFTSFetchMode(PayoutMode::IMPS,  $ftsSuccess);

        $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                ->andReturn([true, 'Dummy']);

        $ftsTransferSuccess = false;

        $ftsMock->shouldReceive('createAndSendRequest')
                ->andReturnUsing(function(string $endpoint, string $method, array $input) use(&$ftsTransferSuccess) {

                    self::assertEquals('/transfer', $endpoint);
                    self::assertEquals('POST', $method);

                    self::assertEquals('payout', $input[FTSConstants::PRODUCT]);
                    self::assertEquals('rbl', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_CHANNEL]);
                    self::assertEquals('IMPS', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_MODE]);
                    self::assertEquals($this->directBankingAccount->getFtsFundAccountId(),
                                       $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_SOURCE_ACCOUNT_ID]);

                    $ftsTransferSuccess = true;

                    return [
                        FTSConstants::BODY => [
                            FTSConstants::STATUS           => FTSConstants::STATUS_CREATED,
                            FTSConstants::MESSAGE          => 'fund transfer sent to fts.',
                            FTSConstants::FUND_TRANSFER_ID => random_integer(2),
                            FTSConstants::FUND_ACCOUNT_ID  => random_integer(2),
                        ]
                    ];
                })->once();

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutCheckQueueParams([PayoutConstants::TOTAL_AMOUNT_THRESHOLD => 19000000]);

        $fmpInputs = [
            [
                PayoutEntity::AMOUNT       => 4500000,
                PayoutEntity::STATUS       => PayoutStatus::PROCESSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->clone()->subMinutes(10)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(9)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->clone()->subMinutes(4)->getTimestamp(),
            ],
            // Even though considered, it's not included in the calculation of the offset amount
            [
                PayoutEntity::AMOUNT       => 2000000,
                PayoutEntity::STATUS       => PayoutStatus::REVERSED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(120)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(119)->getTimestamp(),
                PayoutEntity::PROCESSED_AT => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
                PayoutEntity::REVERSED_AT  => Carbon::now(Timezone::IST)->subMinutes(60)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 4000000,
                PayoutEntity::STATUS       => PayoutStatus::ON_HOLD,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::ON_HOLD_AT   => Carbon::now(Timezone::IST)->subMinutes(39)->getTimestamp(),
            ],
            [
                PayoutEntity::AMOUNT       => 1000000,
                PayoutEntity::STATUS       => PayoutStatus::INITIATED,
                PayoutEntity::CREATED_AT   => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
                PayoutEntity::INITIATED_AT => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
            ],
        ];

        $this->createFundManagementPayouts($fmpInputs);

        $this->fixtures->edit('merchant', $this->basDetails->getMerchantId(), [
            'name'          => 'test_merchant$%%sample@@',
            'billing_label' => null,
        ]);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        Queue::except(FundManagementPayoutInitiate::class);

        (new FundManagementPayoutCheck(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        $basDetails = $this->basDetails->reload();

        // Gateway balance was fetched from bank and bas_details was updated
        $this->assertEquals(10000000, $basDetails->getGatewayBalance());
        $this->assertNotEquals($basDetails->getGatewayBalanceChangeAt(), Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp());

        // Asserting that no FMP got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $this->assertFalse((bool) $boolJobFailureMetricCaptured);
        $this->assertTrue((bool) $ftsTransferSuccess);
        $this->assertTrue((bool) $mozartSuccess);
        $this->assertTrue((bool) $ftsSuccess);

        $fundManagementPayout = $this->getDBLastEntity('payout');

        $expectedPayoutAttributes = [
            PayoutEntity::AMOUNT          => 9500000,
            PayoutEntity::STATUS          => PayoutStatus::INITIATED,
            PayoutEntity::PURPOSE         => Purpose::RZP_FUND_MANAGEMENT,
            PayoutEntity::PURPOSE_TYPE    => Attempt\Purpose::RZP_FUND_MANAGEMENT,
            PayoutEntity::MERCHANT_ID     => $this->basDetails->getMerchantId(),
            PayoutEntity::CHANNEL         => Channel::RBL,
            PayoutEntity::MODE            => PayoutMode::IMPS,
            PayoutEntity::NARRATION       => Purpose::RZP_FUND_MANAGEMENT,
            PayoutEntity::FEES            => 1770,
            PayoutEntity::TAX             => 270,
            PayoutEntity::PRICING_RULE_ID => 'Bbg7e4oKCgaube',
        ];

        $this->assertEquals('testmerchantsample', $fundManagementPayout->fundAccount->source->getName());

        $this->assertArraySubset($expectedPayoutAttributes, $fundManagementPayout->toArray());

        Carbon::setTestNow();
    }

    // Tests for FundManagementPayoutInitiate Queue

    /**
     * fund management payout creation fail due to wrong bank account details
     */
    public function testFundManagementPayoutInitiate_FmpCreationFailureDueToWrongBankAccountDetails()
    {
        Queue::fake();

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(
            PayoutMetric::FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_FAILURES_COUNT,
            $boolJobFailureMetricCaptured, [
                PayoutEntity::CHANNEL => Channel::RBL,
            ]);

        $this->app['rzp.mode'] = Mode::TEST;

        $ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                ->andReturn([true, 'Dummy']);

        $ftsTransferSuccess = false;

        $ftsMock->shouldReceive('createAndSendRequest')
                ->andReturnUsing(function(string $endpoint, string $method, array $input) use(&$ftsTransferSuccess) {

                    self::assertEquals('/transfer', $endpoint);
                    self::assertEquals('POST', $method);

                    self::assertEquals('payout', $input[FTSConstants::PRODUCT]);
                    self::assertEquals('rbl', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_CHANNEL]);
                    self::assertEquals('IMPS', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_MODE]);
                    self::assertEquals($this->directBankingAccount->getFtsFundAccountId(),
                                       $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_SOURCE_ACCOUNT_ID]);

                    $ftsTransferSuccess = true;

                    return [
                        FTSConstants::BODY => [
                            FTSConstants::STATUS           => FTSConstants::STATUS_CREATED,
                            FTSConstants::MESSAGE          => 'fund transfer sent to fts.',
                            FTSConstants::FUND_TRANSFER_ID => random_integer(2),
                            FTSConstants::FUND_ACCOUNT_ID  => random_integer(2),
                        ]
                    ];
                })->times(0);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutInitiateQueueParams(['bank_account_name' => 'razorpay']);

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutInitiate(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
        $this->assertFalse((bool) $ftsTransferSuccess);
    }

    /**
     * fund management payout creation fail due to vanilla payout params
     */
    public function testFundManagementPayoutInitiate_FmpCreationFailureDueToVanillaPayoutParams()
    {
        Queue::fake();

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(
            PayoutMetric::FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_FAILURES_COUNT,
            $boolJobFailureMetricCaptured, [
                PayoutEntity::CHANNEL => Channel::RBL,
            ]);

        $this->app['rzp.mode'] = Mode::TEST;

        $ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                ->andReturn([true, 'Dummy']);

        $ftsTransferSuccess = false;

        $ftsMock->shouldReceive('createAndSendRequest')
                ->andReturnUsing(function(string $endpoint, string $method, array $input) use(&$ftsTransferSuccess) {

                    self::assertEquals('/transfer', $endpoint);
                    self::assertEquals('POST', $method);

                    self::assertEquals('payout', $input[FTSConstants::PRODUCT]);
                    self::assertEquals('rbl', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_CHANNEL]);
                    self::assertEquals('IMPS', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_MODE]);
                    self::assertEquals($this->directBankingAccount->getFtsFundAccountId(),
                                       $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_SOURCE_ACCOUNT_ID]);

                    $ftsTransferSuccess = true;

                    return [
                        FTSConstants::BODY => [
                            FTSConstants::STATUS           => FTSConstants::STATUS_CREATED,
                            FTSConstants::MESSAGE          => 'fund transfer sent to fts.',
                            FTSConstants::FUND_TRANSFER_ID => random_integer(2),
                            FTSConstants::FUND_ACCOUNT_ID  => random_integer(2),
                        ]
                    ];
                })->times(0);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutInitiateQueueParams(['bank_account_name' => 'razorpay']);

        unset($queueParams[PayoutConstants::PAYOUT_CREATE_INPUT][PayoutEntity::FUND_ACCOUNT]);

        $queueParams[PayoutConstants::PAYOUT_CREATE_INPUT][PayoutEntity::FUND_ACCOUNT_ID] = UniqueIdEntity::generateUniqueId();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutInitiate(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
        $this->assertFalse((bool) $ftsTransferSuccess);
    }

    /**
     * fund management payout creation fail due to balance and merchant entities not being associate entities
     */
    public function testFundManagementPayoutInitiate_FmpCreationFailureDueToInvalidBalanceId()
    {
        Queue::fake();

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(
            PayoutMetric::FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_FAILURES_COUNT,
            $boolJobFailureMetricCaptured, [
                PayoutEntity::CHANNEL => Channel::RBL,
            ]);

        $this->app['rzp.mode'] = Mode::TEST;

        $ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                ->andReturn([true, 'Dummy']);

        $ftsTransferSuccess = false;

        $ftsMock->shouldReceive('createAndSendRequest')
                ->andReturnUsing(function(string $endpoint, string $method, array $input) use(&$ftsTransferSuccess) {

                    self::assertEquals('/transfer', $endpoint);
                    self::assertEquals('POST', $method);

                    self::assertEquals('payout', $input[FTSConstants::PRODUCT]);
                    self::assertEquals('rbl', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_CHANNEL]);
                    self::assertEquals('IMPS', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_MODE]);
                    self::assertEquals($this->directBankingAccount->getFtsFundAccountId(),
                                       $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_SOURCE_ACCOUNT_ID]);

                    $ftsTransferSuccess = true;

                    return [
                        FTSConstants::BODY => [
                            FTSConstants::STATUS           => FTSConstants::STATUS_CREATED,
                            FTSConstants::MESSAGE          => 'fund transfer sent to fts.',
                            FTSConstants::FUND_TRANSFER_ID => random_integer(2),
                            FTSConstants::FUND_ACCOUNT_ID  => random_integer(2),
                        ]
                    ];
                })->times(0);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::BALANCE_ID                => '1x000000000bId',
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutInitiateQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutInitiate(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(0, $finalPayoutCount - $initialPayoutCount);

        $this->assertTrue((bool) $boolJobFailureMetricCaptured);
        $this->assertFalse((bool) $ftsTransferSuccess);
    }

    /**
     * fund management payout creation success with on_hold state
     */
    public function testFundManagementPayoutInitiate_OnHoldFmpCreated()
    {
        Queue::fake();

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(
            PayoutMetric::FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_FAILURES_COUNT,
            $boolJobFailureMetricCaptured, [
                PayoutEntity::CHANNEL => Channel::RBL,
            ]);

        $this->app['rzp.mode'] = Mode::TEST;

        $ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                ->andReturn([true, 'Dummy']);

        $ftsTransferSuccess = false;

        $ftsMock->shouldReceive('createAndSendRequest')
                ->andReturnUsing(function(string $endpoint, string $method, array $input) use(&$ftsTransferSuccess) {

                    self::assertEquals('/transfer', $endpoint);
                    self::assertEquals('POST', $method);

                    self::assertEquals('payout', $input[FTSConstants::PRODUCT]);
                    self::assertEquals('rbl', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_CHANNEL]);
                    self::assertEquals('IMPS', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_MODE]);
                    self::assertEquals($this->directBankingAccount->getFtsFundAccountId(),
                                       $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_SOURCE_ACCOUNT_ID]);

                    $ftsTransferSuccess = true;

                    return [
                        FTSConstants::BODY => [
                            FTSConstants::STATUS           => FTSConstants::STATUS_CREATED,
                            FTSConstants::MESSAGE          => 'fund transfer sent to fts.',
                            FTSConstants::FUND_TRANSFER_ID => random_integer(2),
                            FTSConstants::FUND_ACCOUNT_ID  => random_integer(2),
                        ]
                    ];
                })->times(0);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $this->fixtures->merchant->addFeatures([Features::PAYOUTS_ON_HOLD]);

        $benebankConfig = [
            "BENEFICIARY" => [
                "YESB"    => [
                    "status" => "started",
                ],
                "RZPB"    => [
                    "status" => "started",
                ],
                "default" => "started",
            ]
        ];

        (new AdminService)->setConfigKeys([ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        // Assert that zero free payout has been consumed when payout is on_hold
        $this->assertEquals(500, $this->directCounter->getFreePayoutsConsumed());

        $queueParams = $this->getFundManagementPayoutInitiateQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutInitiate(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $this->assertFalse((bool) $boolJobFailureMetricCaptured);
        $this->assertFalse((bool) $ftsTransferSuccess);

        $fundManagementPayout = $this->getDBLastEntity('payout');

        $expectedPayoutAttributes = [
            PayoutEntity::AMOUNT          => 350000,
            PayoutEntity::STATUS          => PayoutStatus::ON_HOLD,
            PayoutEntity::PURPOSE         => Purpose::RZP_FUND_MANAGEMENT,
            PayoutEntity::PURPOSE_TYPE    => Attempt\Purpose::RZP_FUND_MANAGEMENT,
            PayoutEntity::MERCHANT_ID     => $this->basDetails->getMerchantId(),
            PayoutEntity::CHANNEL         => Channel::RBL,
            PayoutEntity::MODE            => PayoutMode::IMPS,
            PayoutEntity::NARRATION       => Purpose::RZP_FUND_MANAGEMENT,
            PayoutEntity::FEES            => 0,
            PayoutEntity::TAX             => 0,
            PayoutEntity::PRICING_RULE_ID => null,
        ];

        $this->assertArraySubset($expectedPayoutAttributes, $fundManagementPayout->toArray());
    }

    /**
     * fund management payout creation with fund account dedupe
     */
    public function testFundManagementPayoutInitiate_fmpCreationSuccessWithFundAccountDedupe()
    {
        Queue::fake();

        $boolJobFailureMetricCaptured = false;

        $this->mockCountMetric(
            PayoutMetric::FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_FAILURES_COUNT,
            $boolJobFailureMetricCaptured, [
                PayoutEntity::CHANNEL => Channel::RBL,
            ]);

        $this->fixtures->create('contact', [
            'id'          => '1000000contact',
            'name'        => $this->basDetails->merchant->getBillingLabel(),
            'merchant_id' => $this->basDetails->getMerchantId(),
            'type'        => 'self',
            'active'      => 1,
            'contact'     => null,
            'email'       => null,
            'reference_id'=> null,
        ]);

        $bankAccount = $this->fixtures->create('bank_account', [
            'id'               => '100000000000ba',
            'type'             => 'contact',
            'entity_id'        => '1000000contact',
            'beneficiary_name' => $this->bankAccount->getName(),
            'ifsc_code'        => $this->bankAccount->getIfscCode(),
            'account_number'   => $this->bankAccount->getAccountNumber(),
        ]);

        $this->fixtures->create('fund_account', [
            'id'           => '100000000000fa',
            'source_type'  => 'contact',
            'source_id'    => '1000000contact',
            'merchant_id'  => $this->basDetails->getMerchantId(),
            'account_type' => 'bank_account',
            'account_id'   => $bankAccount->getId(),
            'active'       => 1,
            'unique_hash'  => '4608a3efa1a515d8992385dcc6be7b57b50e4bca6b16fce58e44ca7271a7126a',
        ]);

        $this->app['rzp.mode'] = Mode::TEST;

        $ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                ->andReturn([true, 'Dummy']);

        $ftsTransferSuccess = false;

        $ftsMock->shouldReceive('createAndSendRequest')
                ->andReturnUsing(function(string $endpoint, string $method, array $input) use(&$ftsTransferSuccess) {

                    self::assertEquals('/transfer', $endpoint);
                    self::assertEquals('POST', $method);

                    self::assertEquals('payout', $input[FTSConstants::PRODUCT]);
                    self::assertEquals('rbl', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_CHANNEL]);
                    self::assertEquals('IMPS', $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_MODE]);
                    self::assertEquals($this->directBankingAccount->getFtsFundAccountId(),
                                       $input[FTSConstants::TRANSFER][FTSConstants::PREFERRED_SOURCE_ACCOUNT_ID]);

                    $ftsTransferSuccess = true;

                    return [
                        FTSConstants::BODY => [
                            FTSConstants::STATUS           => FTSConstants::STATUS_CREATED,
                            FTSConstants::MESSAGE          => 'fund transfer sent to fts.',
                            FTSConstants::FUND_TRANSFER_ID => random_integer(2),
                            FTSConstants::FUND_ACCOUNT_ID  => random_integer(2),
                        ]
                    ];
                })->times(1);

        $this->app->instance('fts_fund_transfer', $ftsMock);

        $this->fixtures->edit('banking_account_statement_details', $this->basDetails->getId(), [
            Details\Entity::GATEWAY_BALANCE           => 200000,
            Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            Details\Entity::BALANCE_LAST_FETCHED_AT   => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
        ]);

        $queueParams = $this->getFundManagementPayoutInitiateQueueParams();

        $initialPayoutCount = count($this->getDbEntities('payout'));

        (new FundManagementPayoutInitiate(Mode::TEST, $queueParams))->handle();

        $finalPayoutCount = count($this->getDbEntities('payout'));

        // Asserting that no FMP got created
        $this->assertEquals(1, $finalPayoutCount - $initialPayoutCount);

        $this->assertFalse((bool) $boolJobFailureMetricCaptured);
        $this->assertTrue((bool) $ftsTransferSuccess);

        $fundManagementPayout = $this->getDBLastEntity('payout');

        $expectedPayoutAttributes = [
            PayoutEntity::AMOUNT          => 350000,
            PayoutEntity::STATUS          => PayoutStatus::INITIATED,
            PayoutEntity::PURPOSE         => Purpose::RZP_FUND_MANAGEMENT,
            PayoutEntity::PURPOSE_TYPE    => Attempt\Purpose::RZP_FUND_MANAGEMENT,
            PayoutEntity::MERCHANT_ID     => $this->basDetails->getMerchantId(),
            PayoutEntity::CHANNEL         => Channel::RBL,
            PayoutEntity::MODE            => PayoutMode::IMPS,
            PayoutEntity::NARRATION       => Purpose::RZP_FUND_MANAGEMENT,
            PayoutEntity::FEES            => 1062,
            PayoutEntity::TAX             => 162,
            PayoutEntity::PRICING_RULE_ID => 'Bbg7dTcURsOr78',
            PayoutEntity::FUND_ACCOUNT_ID => '100000000000fa',
        ];

        $this->assertArraySubset($expectedPayoutAttributes, $fundManagementPayout->toArray());
    }
}
