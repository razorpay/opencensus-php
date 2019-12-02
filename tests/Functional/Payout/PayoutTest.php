<?php

namespace RZP\Tests\Functional\Payout;

use Mail;
use Config;

use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Banking\LowBalanceAlert;
use RZP\Exception\BadRequestException;
use Illuminate\Support\Facades\Artisan;
use RZP\Models\Admin\Permission as AdminPermission;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class PayoutTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;
    use SettlementTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use PayoutTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);
    }

    public function liveSetUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        $this->fixtures->on('live')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('live')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBankingLive(true, 10000000);
    }

    public function testCreatePayout(): array
    {
        Mail::fake();

        $this->ba->privateAuth();

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::LOW_BALANCE_RX_EMAIL => [
                    '10000000000000' =>
                        [
                            'low_balance_threshold' => 10000000000,
                            'email_ids'             => ['a@a.com', 'b@b.com']
                        ]
                ]
            ]);

        $this->startTest();

        Mail::assertQueued(LowBalanceAlert::class);

        $config = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::LOW_BALANCE_RX_EMAIL]);

        $this->assertArrayHasKey('notify_at', $config['10000000000000']);

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'yesbank');

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7dTcURsOr77",
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }

    public function testCreatePayoutWithoutFundAccountId()
    {
        $this->testCreatePayout();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutForVirtualAccountWhenModeIsNotPresent(): array
    {
        $this->ba->privateAuth();

        $this->fixtures->create(
            'bank_account',
            [
                'id'           => '1000000lvirtba',
                'ifsc'         => 'YESB0CMSNOC',
            ]);

        $this->fixtures->edit(
            'fund_account', '100000000000fa',
            [
                'account_type' => 'bank_account',
                'account_id'   => '1000000lvirtba'
            ]);

        $this->bankAccount->setIfsc('YESB0CMSNOC');

        // Setting the mock carbon timestamp to 1 minute less than today's ending timing i.e. 6:14 PM
        $endTime = Carbon::createFromDate(2019, 07, 11., Timezone::IST)
                           ->hour(18)
                           ->minute(14);

        Carbon::setTestNow($endTime);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lvirtba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'yesbank');

        //If Mode is not sent in the request NEFT will be the mode of attempt.
        $this->assertEquals('NEFT', $payoutAttempt['mode']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7dTcURsOr77",
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }

    public function testCreatePayoutForVirtualAccountWhenModeIsPresent(): array
    {
        $this->ba->privateAuth();

        $this->fixtures->create(
            'bank_account',
            [
                'id'           => '1000000lvirtba',
                'ifsc'         => 'YESB0CMSNOC',
            ]);

        $this->fixtures->edit(
            'fund_account', '100000000000fa',
            [
                'account_type' => 'bank_account',
                'account_id'   => '1000000lvirtba'
            ]);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lvirtba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'yesbank');

        //If Mode is sent in request the attempt should be in NEFT mode and Payout mode will remain the sent mode.
        $this->assertEquals('NEFT', $payoutAttempt['mode']);
        $this->assertEquals('IFT', $payout['mode']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7dTcURsOr77",
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }

    public function testPublicErrorCodeMapping()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'        => 'failed',
            'failure_reason'    => '',
            'bank_status_code'  => 'YB_NS_E1028'
        ]);

        $updatedPayout = $this->getDbEntityById('payout',$payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                    'IMPS is not enabled on Beneficiary Account');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS],Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);
    }

    public function testPublicErrorCodeMappingWithNonExistentBankStatusCode()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'        => 'failed',
            'failure_reason'    => '',
            'bank_status_code'  => 'YB_NS_E10282323'
        ]);

        $updatedPayout = $this->getDbEntityById('payout',$payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                    'Payout failed. Contact support for help');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS],Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);
    }

    public function testPublicErrorCodeMappingWithEmptyPublicError()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'        => 'failed',
            'failure_reason'    => 'Beneficiary bank\'s systems are down. Please retry after some time.',
            'bank_status_code'  => 'YB_SFMS_E59'
        ]);

        $updatedPayout = $this->getDbEntityById('payout',$payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON], 'Beneficiary bank\'s systems are down. Please retry after some time.');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS],Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);
    }

    public function testPublicErrorCodeMappingWhenBankStatusCodeNotSent()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'        => 'failed',
            'failure_reason'    => '',
        ]);

        $updatedPayout = $this->getDbEntityById('payout',$payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                    'Payout failed. Contact support for help');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS],Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);
    }

    public function testRxPayoutOnBankingHoliday(): array
    {
        $this->ba->privateAuth();

        // Setting current time as 15th Aug Independence day holiday
        $holidayDateTime = Carbon::createFromDate(2019, 8, 15., Timezone::IST)
                                ->hour(18)
                                ->minute(14);

        Carbon::setTestNow($holidayDateTime);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals($payout['channel'], 'yesbank');


        $this->assertEquals('NEFT', $payoutAttempt['mode']);
        //Attempt should be in created state as its an holiday
        $this->assertEquals('created', $payoutAttempt['status']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        return $payout;
    }

    public function testRxPayoutOnNonBankingHolidayBeforeNEFTtimings(): array
    {
        $this->ba->privateAuth();

        // Date time set as non banking holiday and inside NEFT timings
        $holidayDateTime = Carbon::createFromDate(2019, 8, 16., Timezone::IST)
            ->hour(17)
            ->minute(55);

        Carbon::setTestNow($holidayDateTime);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals($payout['channel'], 'yesbank');


        $this->assertEquals('NEFT', $payoutAttempt['mode']);
        $this->assertEquals('processed', $payoutAttempt['status']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        return $payout;
    }

    public function testRxPayoutOnNonBankingHolidayAfterNEFTtimings(): array
    {
        $this->ba->privateAuth();

        // Date time set as non banking holiday and outside NEFT timings
        $holidayDateTime = Carbon::createFromDate(2019, 8, 16., Timezone::IST)
            ->hour(19)
            ->minute(55);

        Carbon::setTestNow($holidayDateTime);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals($payout['channel'], 'yesbank');


        $this->assertEquals('NEFT', $payoutAttempt['mode']);
        $this->assertEquals('created', $payoutAttempt['status']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        return $payout;
    }

    public function testCreateMerchantPayoutOnDemand()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => 'yesbank']);

        $this->ba->proxyAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout',true);

        $txn = $this->getLastEntity('transaction',true);

        $this->assertEquals('payout', $txn['type']);

        $this->assertEquals(398, $txn['amount']);

        $this->assertEquals(602, $txn['fee']);

        $this->assertEquals(1000, $txn['debit']);

        return $payout;
    }

    public function testCreatePayoutForAmountLessThanMinFee()
    {
        // Minimum fee is INR 5, attempts and asserts success when creating payout for INR 1.
        $this->ba->privateAuth();
        $this->startTest();
    }

    protected function createQueuedOrPendingPayout(array $attributes = [])
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => $attributes["account_number"] ?? '2224440041626905',
                'amount'                => $attributes["amount"] ?? 10000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'queue_if_low_balance'  => $attributes["queue_if_low_balance"] ?? 0,
            ],
        ];

        $this->ba->privateAuth();

        $this->sendRequest($request);
    }

    public function testDashboardSummary()
    {

        // Create second Balance
        $balanceAttributes = [
            'balance' => 10000000,
            'balanceType' => 'direct',
            'channel' => 'rbl',
        ];

        $secondBankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"] ,
            $balanceAttributes["channel"]
        );

        // Create Second Bank Account

        $virtualAccount = $this->fixtures->create('virtual_account');
        $secondBankAccount    = $this->fixtures->create(
            'bank_account',
            [
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626906',
                'ifsc_code'      => 'RAZRB000000',
            ]);

        $virtualAccount->bankAccount()->associate($secondBankAccount);
        $virtualAccount->balance()->associate($secondBankingBalance);
        $virtualAccount->save();

        $secondBankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $secondBankingBalance->save();

        // Creating 2 banking accounts. First for the existing bankingBalance and second for the secondBankingBalance

        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626998',
            'balance_id'            =>  $this->bankingBalance->getId(),
            'account_type'          =>  'nodal',
        ];

        $bankingAccount = $this->createBankingAccount($bankingAccountAttributes);

        $secondBankingAccountAttributes = [
            'id'                    =>  'DEcba4321DEcba',
            'account_number'        =>  '2224440041626999',
            'balance_id'            =>  $secondBankingBalance->getId(),
            'account_type'          =>  'current',
        ];

        $secondBankingAccount = $this->createBankingAccount($secondBankingAccountAttributes);

        // Create two queued payouts

        $firstQueuedPayoutAttributes = [
            'account_number'        =>  '2224440041626905',
            'amount'                =>  20000099,
            'queue_if_low_balance'  =>  1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes);

        $secondQueuedPayoutAttributes = [
            'account_number'        =>  '2224440041626906',
            'amount'                =>  30000099,
            'queue_if_low_balance'  =>  1,
        ];

        $this->createQueuedOrPendingPayout($secondQueuedPayoutAttributes);

        // Setup the payout workflow

        $this->app['config']->set('heimdall.workflows.mock', false);
        $this->app['config']->set('heimdall.permissions.payouts.create_payout.assignable', true);

        $this->fixtures->merchant->addFeatures([Constants::PAYOUT_WORKFLOWS]);

        $workflow = $this->getDbLastEntity('workflow');

        $workflowDefaultPermissions = (new AdminPermission\Repository)
                                    ->retrieveIdsByNames([AdminPermission\Name::CREATE_PAYOUT]);

        // Attach permissions to the created workflow.
        $workflow->permissions()->sync($workflowDefaultPermissions);

        $this->fixtures->create('workflow_payout_amount_rules', ['workflow_id' => 'workflowId1000']);

        // Create 2 pending payouts

        $firstPendingPayoutAttributes = [
            'account_number'        =>  '2224440041626905',
            'amount'                =>  54321
        ];

        $this->createQueuedOrPendingPayout($firstPendingPayoutAttributes);

        $secondPendingPayoutAttributes = [
            'account_number'        =>  '2224440041626906',
            'amount'                =>  12345
        ];

        $this->createQueuedOrPendingPayout($secondPendingPayoutAttributes);

        $role = $this->getDbEntityById('role', 'RzpChekrRoleId');

        $user = $this->getDbEntityById('user','MerchantUser01');

        $user->roles()->attach($role);

        $this->ba->proxyAuth();

        $completeSummary = $this->startTest();

        $firstBankingAccountId = $bankingAccount->getPublicId();
        $secondBankingAccountId = $secondBankingAccount->getPublicId();

        $queuedSummaryFirstAccount = $completeSummary[$firstBankingAccountId][Payout\Status::QUEUED];
        $pendingSummaryFirstAccount = $completeSummary[$firstBankingAccountId][Payout\Status::PENDING];
        $queuedSummarySecondAccount = $completeSummary[$secondBankingAccountId][Payout\Status::QUEUED];
        $pendingSummarySecondAccount = $completeSummary[$secondBankingAccountId][Payout\Status::PENDING];


        $this->assertEquals($queuedSummaryFirstAccount['count'],1);
        $this->assertEquals($queuedSummaryFirstAccount['total_amount'],20000099);
        $this->assertEquals($queuedSummaryFirstAccount['balance'],"10000000");

        $this->assertEquals($pendingSummaryFirstAccount['count'],1);
        $this->assertEquals($pendingSummaryFirstAccount['total_amount'],54321);

        $this->assertEquals($queuedSummarySecondAccount['count'],1);
        $this->assertEquals($queuedSummarySecondAccount['total_amount'],30000099);
        $this->assertEquals($queuedSummarySecondAccount['balance'],"10000000");

        $this->assertEquals($pendingSummarySecondAccount['count'],1);
        $this->assertEquals($pendingSummarySecondAccount['total_amount'],12345);
    }

    public function testCreateQueuedPayout()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->createBankingAccount(['balance_id' => $balanceId]);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $currentBalance = $this->getDbLastEntity('balance');

        $response = $this->startTest();

        $newBalance = $this->getDbLastEntity('balance');

        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNull($fta);

        $this->startTest();

        $summary = $this->makePayoutSummaryRequest();

        $bankingAccountId = $bankingAccount->getPublicId();

        $this->assertEquals(2, $summary[$bankingAccountId]['queued']['count']);
        $this->assertEquals(20000002, $summary[$bankingAccountId]['queued']['total_amount']);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $this->assertEquals(2, $dispatchResponse[$newBalance['id']]['total_payout_count']);
        $this->assertEquals(10000000, $dispatchResponse[$newBalance['id']]['balance_remaining']);
        $this->assertEquals(10000000, $dispatchResponse[$newBalance['id']]['original_balance']);
        $this->assertEquals(0, $dispatchResponse[$newBalance['id']]['dispatched_payout_count']);
        $this->assertEquals(0, $dispatchResponse[$newBalance['id']]['dispatched_payout_amount']);

        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $this->assertEquals(2, $dispatchResponse[$newBalance['id']]['total_payout_count']);
        $this->assertEquals(998229, $dispatchResponse[$newBalance['id']]['balance_remaining']);
        $this->assertEquals(11000000, $dispatchResponse[$newBalance['id']]['original_balance']);
        $this->assertEquals(1, $dispatchResponse[$newBalance['id']]['dispatched_payout_count']);
        $this->assertEquals(10001771, $dispatchResponse[$newBalance['id']]['dispatched_payout_amount']);

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNotNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNotNull($fta);
    }

    public function testCreatePayoutToInactiveFundAccount()
    {
        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000001fa',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba',
                'active'       => 0,
            ]);

        $this->startTest();
    }

    public function testCreatePayoutToFundAccountWithoutContact()
    {
        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000004ff',
                'account_type' => 'card',
                'account_id'   => '100000000lcard',
                'active'       => 1,
            ]);

        $this->startTest();
    }

    public function testCreatePayoutToCardFundAccountUsingUpi()
    {
        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'card',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '10000000ICcard',
                'active'       => 1,
            ]);

        $this->startTest();
    }

    public function testCreatePayoutToCardFundAccount()
    {
        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'card',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '100000000lcard',
                'active'       => 1,
            ]);

        $this->startTest();
    }

    public function testCreatePayoutToInactiveContactFundAccount()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'active' => 0]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000001fa',
                'source_id'    => '1000000contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->startTest();
    }

    public function testCreatePayoutWithOtp()
    {
        $testData = $this->testData['testCreatePayoutWithOtp'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth();
        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals("MerchantUser01", $payout['user_id']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
    }

    public function testCreatePayoutWithInvalidOtp()
    {
        $testData = $this->testData['testCreatePayout'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '1234';

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_INCORRECT_OTP);

        $this->startTest();
    }

    public function testApprovePayoutWithOtp()
    {
        $this->markTestSkipped('Workflows test handling pending');

        $payout = $this->testCreatePayout();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::PENDING,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testApprovePayoutWithInvalidOtp()
    {
        $this->markTestSkipped('Workflows test handling pending');

        $payout = $this->testCreatePayout();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::PENDING,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testApproveBulkPayoutWithOtp()
    {
        $this->markTestSkipped('Workflows test handling pending');

        $payout1 = $this->testCreatePayout();
        $payout2 = $this->testCreatePayout();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        $this->fixtures->edit(
            'payout',
            $payout1['id'],
            [
                'status' => Payout\Status::PENDING,
            ]);

        $this->fixtures->edit(
            'payout',
            $payout2['id'],
            [
                'status' => Payout\Status::PENDING,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testRejectPayout()
    {
        $this->markTestSkipped('Workflows test handling pending');

        $payout = $this->testCreatePayout();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::PENDING,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBulkRejectPayouts()
    {
        $this->markTestSkipped('Workflows test handling pending');

        $payout1 = $this->testCreatePayout();
        $payout2 = $this->testCreatePayout();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        $this->fixtures->edit(
            'payout',
            $payout1['id'],
            [
                'status' => Payout\Status::PENDING,
            ]);

        $this->fixtures->edit(
            'payout',
            $payout2['id'],
            [
                'status' => Payout\Status::PENDING,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testRetryPayout(): array
    {
        $payout = $this->testCreatePayout();
        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::REVERSED
            ]);

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $payoutAttempt['id'],
            [
                'status' => Attempt\Status::FAILED
            ]);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($payout['transaction_id'], $txn['id']);

        $this->retryPayout($payout['id']);

        $newPayout = $this->getLastEntity('payout', true);

        $newPayoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Payout\Status::PROCESSED, $newPayout['status']);
        $this->assertEquals(Attempt\Status::PROCESSED, $payoutAttempt['status']);

        // Verify attempt entity
        $this->assertEquals($newPayout['attempts'], 1);
        $this->assertEquals($newPayout['id'], $newPayoutAttempt['source']);
        $this->assertEquals($newPayout['merchant_id'], $newPayoutAttempt['merchant_id']);
        $this->assertEquals($newPayout['fund_account_id'], 'fa_100000000000fa');
        $this->assertNotNull($newPayout['batch_fund_transfer_id']);
        $this->assertNotNull($newPayoutAttempt['batch_fund_transfer_id']);
        $this->assertEquals($newPayout['batch_fund_transfer_id'], $newPayoutAttempt['batch_fund_transfer_id']);

        // ----- End of testing payout retry for failed payouts ------ //

        return $newPayout;
    }

    public function testRetryMerchantOnDemandPayout()
    {
        $payout = $this->testCreateMerchantPayoutOnDemand();

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::REVERSED
            ]);

        $nodalBeneficiary = $this->getLastEntity('nodal_beneficiary', true);

        $this->fixtures->edit(
            'nodal_beneficiary',
            $nodalBeneficiary['id'],
            [
                'updated_at' => $nodalBeneficiary['updated_at'] - 70,
            ]);

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $payoutAttempt['id'],
            [
                'status' => Attempt\Status::FAILED
            ]);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($payout['transaction_id'], $txn['id']);

        $this->retryPayout($payout['id']);

        $newPayout = $this->getLastEntity('payout', true);

        $newPayoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Payout\Status::PROCESSED, $newPayout['status']);
        $this->assertEquals(Attempt\Status::PROCESSED, $newPayoutAttempt['status']);

        // Verify attempt entity
        $this->assertEquals($newPayout['attempts'], 1);
        $this->assertEquals($newPayout['id'], $newPayoutAttempt['source']);
        $this->assertEquals($newPayout['merchant_id'], $newPayoutAttempt['merchant_id']);
        $this->assertNull($newPayout['fund_account_id']);
        $this->assertNotNull($newPayout['batch_fund_transfer_id']);
        $this->assertNotNull($newPayoutAttempt['batch_fund_transfer_id']);
        $this->assertEquals($newPayout['batch_fund_transfer_id'], $newPayoutAttempt['batch_fund_transfer_id']);
        $this->assertEquals($payout['amount'], $newPayout['amount']);

        // ----- End of testing payout retry for failed payouts ------ //

        return $newPayout;
    }

    public function testCreateMerchantPayout()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutWithModulo()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutWithMinAmount()
    {
        $this->ba->appAuth();

        $this->startTest();
    }


    public function testCreatePayoutFundsOnHold()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->holdFunds();

        $this->startTest();
    }

    public function testCreatePayoutInsufficientBalance()
    {
        return $this->startTest();
    }

    public function testGetPayouts()
    {
        $this->createEsIndex();

        $payout = $this->testCreatePayout();

        $payout = $this->testCreatePayout();

        $this->ba->privateAuth();

        $payouts = $this->startTest();

        $this->assertEquals($payouts['entity'], 'collection');

        $this->assertEquals($payouts['count'], 2);

        $this->assertNotEquals($payouts['items'], null);
    }

    public function testGetPayoutsWithoutAccountNumber()
    {
        $this->testCreatePayout();

        $this->testCreatePayout();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetPayout()
    {
        $this->testCreatePayout();

        $payout = $this->getLastEntity('payout', true);

        $this->ba->privateAuth();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/'. $payout['id'];

        $payout2 = $this->startTest();

        $this->assertArraySelectiveEquals($payout2, $payout);
    }

    public function testCreatePaymentPayout(): array
    {
        $payment = $this->fixtures->create('payment:settled');

        $this->setPaymentPayoutUrl($payment, $this->testData[__FUNCTION__]['request']);

        $payout = $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1000, $payment['amount_paidout']);

        $payout2 = $this->getLastEntity('payout', true);

        $this->assertEquals($payout['id'], $payout2['id']);

        $this->assertEquals($payment['id'], 'pay_' . $payout2['payment_id']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // Verify attempt entity
        $this->assertEquals($payout2['id'], $payoutAttempt['source']);
        $this->assertEquals($payout2['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);

        return $payout;
    }

    public function testPaymentPayoutAmountGreaterThanCapture()
    {
        $payment = $this->fixtures->create('payment:settled', ['amount' => 2500]);

        $this->setPaymentPayoutUrl($payment, $this->testData[__FUNCTION__]['request']);

        $payout = $this->startTest();
    }

    public function testPaymentPayoutPartial()
    {
        $payment = $this->fixtures->create('payment:settled', ['amount' => 7000]);

        $this->setPaymentPayoutUrl($payment, $this->testData[__FUNCTION__]['request']);

        $payout1 = $this->startTest();

        $payout2 = $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount_paidout'], $payout1['amount'] + $payout2['amount']);

        $payoutAttempts = $this->getEntities('fund_transfer_attempt', [], true);

        $this->assertEquals(2, $payoutAttempts['count']);
    }

    public function testCreatePaymentPayoutNotSettledLiveMode()
    {
        $payment = $this->fixtures->on('live')->create('payment:captured');

        $this->setPaymentPayoutUrl($payment, $this->testData[__FUNCTION__]['request']);

        // Merchant needs to be activated to make live requests
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function setPaymentPayoutUrl($payment, & $request)
    {
        $request['url'] = '/payments/'. $payment->getPublicId() . '/payouts';
    }

    public function testCreatePayoutAttemptSuccess()
    {
        // FTA initiate happens via sync queue
        $this->ba->privateAuth();
        $p1 = $this->testCreatePayout();

        $this->ba->privateAuth();
        $p2 = $this->testCreatePaymentPayout();

        $createdAt = Carbon::today(Timezone::IST)->addDays(10);

        Carbon::setTestNow($createdAt);

        $this->ba->adminAuth();

        // Verify attempts
        $attempts = $this->getEntities('fund_transfer_attempt', [], true);

        $this->assertEquals(2, $attempts['count']);

        $attempts = $attempts['items'];

        foreach ($attempts as $attempt)
        {
            $this->assertTestResponse($attempt, 'testPayoutAttemptSuccess');

            $this->assertNotNull($attempt['utr']);

            $this->assertNotNull($attempt['batch_fund_transfer_id']);
        }

        // Verify payouts
        $payouts = $this->getEntities('payout', [], true);

        $this->assertEquals(2, $payouts['count']);

        $payouts = $payouts['items'];

        foreach ($payouts as $payout)
        {
            $this->assertTestResponse($payout, 'testPayoutEntitySuccess');

            $this->assertNotNull($payout['batch_fund_transfer_id']);

            $this->assertNotNull($payout['utr']);

            $this->assertNotNull($payout['processed_at']);
        }

        Carbon::setTestNow();
    }

    public function testCreateMerchantPayoutOnDemandOnLowBalance()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 100]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnHoldFunds()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['hold_funds' => true]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateMerchantPayoutOnMinAmount()
    {
        $this->fixtures->create('pricing:payout_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYz']);

        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOnDemandPayoutFetchFees()
    {
        $this->fixtures->merchant->addFeatures([Constants::ES_ON_DEMAND]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSearchPayoutByTransactionId()
    {
        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts?transaction_id=' . $payout['transaction_id'] . '&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByPayoutStatus()
    {
        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

//        $this->fixtures->edit(
//            'payout',
//            $payout['id'],
//            [
//                'status' => 'processed'
//            ]);

        $request['url'] = '/payouts?status=processed&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }


    public function testSearchPayoutByPayoutContactType()
    {
        $contact = $this->fixtures->create('contact', [
            'id' => '1000005contact', 'email' => 'test@test5.com',
            'contact' => '8888888888', 'name' => 'test user',
            'type' => 'customer'
        ]);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_type=customer&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByUtr()
    {
        $payout = $this->testCreatePayout();

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'utr' => '1234567890'
            ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?utr=1234567890&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByContactId()
    {
        $this->fixtures->create('contact', [
            'id' => '1000010contact', 'email' => 'test@test5.com',
            'contact' => '8888888888', 'name' => 'test user',
            'type' => 'customer'
        ]);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000010contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_id=cont_1000010contact&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByContactName()
    {
        $this->markTestSkipped();

        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test5.com', 'contact' => '8888888888', 'name' => 'test']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_name=test&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByContactPhone()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@test5.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);


        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_phone=8888888888&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByContactEmail()
    {
        $this->markTestSkipped();

        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@payout.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);


        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_email=test@payout.com&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByFundAccountId()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@payout.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id' => '1000005contact',
                'source_type' => 'contact',
            ]);


        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?fund_account_id=' . $payout['fund_account_id'] . '&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testFetchMultiplePayoutsWithBankingProductParameter()
    {
        $this->testCreatePayout();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testFetchMultiplePayoutsWithPrimaryProductParameter()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testBulkPayout()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayoutWithSameIdempotencyandBatchId()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function createEsIndex()
    {
        $esMock = Config::get('database.es_mock');

        if ($esMock === false)
        {
            Artisan::call(
                'rzp:index_create',
                [
                    'mode'         => 'test',
                    'entity'       => 'payout',
                    'index_prefix' => env('ES_ENTITY_INDEX_PREFIX'),
                    'type_prefix'  => env('ES_ENTITY_TYPE_PREFIX'),
                    '--reindex'    => true,
                ]);

            Artisan::call(
                'rzp:index_create',
                [
                    'mode'         => 'live',
                    'entity'       => 'payout',
                    'index_prefix' => env('ES_ENTITY_INDEX_PREFIX'),
                    'type_prefix'  => env('ES_ENTITY_TYPE_PREFIX'),
                    '--reindex'    => true,
                ]);

            Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'payout']);
            Artisan::call('rzp:index', ['mode' => 'live', 'entity' => 'payout']);
        }
    }

    protected function makePayoutSummaryRequest()
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/summary',
        ];

        $this->ba->proxyAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function dispatchQueuedPayouts()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/queued/process',
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    public function testRxPayoutForSlaExpiry(): array
    {
        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->liveSetUp();

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_SLA_FOR_IMPS_PAYOUT => 1]);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true, 'live');

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);

        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);

        $this->assertEquals($payout['channel'], 'yesbank');

        $this->assertEquals('IMPS', $payoutAttempt['mode']);

        $this->assertEquals('created', $payoutAttempt['status']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true, 'live');

        $this->assertEquals($payout['transaction_id'], $txn['id']);

        $this->assertNotNull($txn['balance_id']);

        return $payout;
    }

//    public function testCreatePayoutForRblDirectAccount(): array
//    {
//        $newBalance = $this->getDbLastEntity('balance');
//
//        $this->fixtures->balance->edit($newBalance['id'],
//            [
//                'balance'           => 10000000,
//                'account_type'      => 'direct',
//                'channel'           => 'rbl',
//            ]);
//
//        $this->ba->privateAuth();
//
//        $this->startTest();
//
//        $payout = $this->getLastEntity('payout', true);
//
//        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
//
//        // On private auth, payout.user_id should be null
//        $this->assertNull($payout['user_id']);
//
//        // Verify attempt entity
//        $this->assertEquals($payout['id'], $payoutAttempt['source']);
//        $this->assertEquals('Batman', $payoutAttempt['narration']);
//        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
//        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
//        $this->assertEquals($payout['channel'], 'yesbank');
//
//        // Verify transaction entity
//        $txn = $this->getLastEntity('transaction', true);
//        $txnId = str_after($txn['id'], 'txn_');
//
//        $this->assertEquals($payout['transaction_id'], $txn['id']);
//        $this->assertNotNull($txn['balance_id']);
//
//        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);
//
//        $expectedBreakup = [
//            'name'            => "payout",
//            'transaction_id'  => $txnId,
//            'pricing_rule_id' => "Bbg7fgaDwax04u",
//            'percentage'      => null,
//            'amount'          => 0,
//        ];
//
//        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);
//
//        return $payout;
//    }
//
//    public function testCreatePayoutForRblDirectWithSharedRules(): array
//    {
//        $pricingPlan = [
//            'plan_name'           => 'Banking merchant plan',
//            'product'             => 'primary',
//            'feature'             => 'payout',
//            'payment_method'      => 'fund_transfer',
//            'percent_rate'        => 0,
//            'fixed_rate'          => 500,
//            'amount_range_active' => 1,
//            'amount_range_min'    => 0,
//            'amount_range_max'    => 200000,
//            'org_id'              => '100000Razorpay',
//            'expired_at'          => null,
//            'created_at'          => time(),
//            'updated_at'          => time(),
//        ];
//
//        $pricingPlan = $this->fixtures->create('pricing', $pricingPlan);
//
//        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $pricingPlan['plan_id']]);
//
//        $newBalance = $this->getDbLastEntity('balance');
//
//        $this->fixtures->balance->edit($newBalance['id'],
//            [
//                'balance'           => 10000000,
//                'account_type'      => 'direct',
//                'channel'           => 'rbl',
//            ]);
//
//        $this->ba->privateAuth();
//
//        $this->startTest();
//
//        $payout = $this->getLastEntity('payout', true);
//
//        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
//
//        // On private auth, payout.user_id should be null
//        $this->assertNull($payout['user_id']);
//
//        // Verify attempt entity
//        $this->assertEquals($payout['id'], $payoutAttempt['source']);
//        $this->assertEquals('Batman', $payoutAttempt['narration']);
//        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
//        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
//        $this->assertEquals($payout['channel'], 'yesbank');
//
//        // Verify transaction entity
//        $txn = $this->getLastEntity('transaction', true);
//        $txnId = str_after($txn['id'], 'txn_');
//
//        $this->assertEquals($payout['transaction_id'], $txn['id']);
//        $this->assertNotNull($txn['balance_id']);
//
//        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);
//
//        $expectedBreakup = [
//            'name'            => "payout",
//            'transaction_id'  => $txnId,
//            'pricing_rule_id' => "Bbg7fgaDwax04u",
//            'percentage'      => null,
//            'amount'          => 0,
//        ];
//
//        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);
//
//        return $payout;
//    }
}
