<?php

namespace RZP\Tests\Functional\Payout;

use DB;
use Mail;
use Hash;
use Queue;
use Redis;
use Config;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

use RZP\Constants;
use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Card\Type;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Banking\LowBalanceAlert;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Webhook\Event;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class PayoutTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;
    use WorkflowTrait;
    use SettlementTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use PayoutTrait;
    use WebhookTrait;
    use MocksDnsTrait;

    private $checkerRoleUser;

    private $ownerRoleUser;

    private $finL3RoleUser;

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

        $this->mockStorkService();
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
        $this->assertNotNull($txn['posted_at']);

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

    public function testCreatePayoutWithIKeyHeader($ikeyValue = 'check', $amount = null)
    {
        $headers = [
            'HTTP_' . RequestHeader::X_PAYOUT_IDEMPOTENCY    => $ikeyValue,
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        if (empty($amount) === false)
        {
            $this->testData[__FUNCTION__]['request']['content']['amount'] = $amount;
        }

        $this->ba->privateAuth();

        $payout = $this->startTest();

        $ikey = $this->getDbLastEntity(Constants\Entity::IDEMPOTENCY_KEY);

        $this->assertEquals($payout['id'], 'pout_' . $ikey->getSourceId());
        $this->assertEquals($ikeyValue, $ikey->getIdempotencyKey());

        return $payout;
    }

    public function testCreateTwoPayoutsWithoutIKey()
    {
        $payoutOne = $this->testCreatePayout();

        $payoutTwo = $this->testCreatePayout();

        $this->assertNotEquals($payoutTwo['id'], $payoutOne['id']);
    }

    public function testCreateTwoPayoutsWithSameIKey()
    {
        $payout1 = $this->testCreatePayoutWithIKeyHeader('samekey');

        $payout2 = $this->testCreatePayoutWithIKeyHeader('samekey');

        $this->assertEquals($payout1['id'], $payout2['id']);

        $ikeys = $this->getDbEntities(Constants\Entity::IDEMPOTENCY_KEY);

        $this->assertCount(1, $ikeys);
    }

    public function testCreateTwoPayoutsWithDiffIKey()
    {
        $payout1 = $this->testCreatePayoutWithIKeyHeader('key1');

        $payout2 = $this->testCreatePayoutWithIKeyHeader('anotherkey');

        $this->assertNotEquals($payout1['id'], $payout2['id']);

        $ikeys = $this->getDbEntities(Constants\Entity::IDEMPOTENCY_KEY);

        $this->assertCount(2, $ikeys);
    }

    public function testCreateTwoPayoutsWithSameIKeyDiffRequest()
    {
        $this->testCreatePayoutWithIKeyHeader('samekey');

        $headers = [
            'HTTP_' . RequestHeader::X_PAYOUT_IDEMPOTENCY    => 'samekey',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithoutFundAccountId()
    {
        $this->testCreatePayout();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithModeNotSet()
    {
        $this->startTest();
    }

    public function testCreatePayoutWithInvalidMode()
    {
        $this->startTest();
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


        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn['posted_at']);
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

        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn['posted_at']);
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

        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn['posted_at']);
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

        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn['posted_at']);
    }

    public function testRxPayoutOnBankingHoliday(): array
    {
        $this->markTestSkipped('Only IMPS on Yesbank');

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
        $this->markTestSkipped('Only IMPS on Yesbank');

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
        $this->assertEquals('created', $payoutAttempt['status']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        return $payout;
    }

    public function testRxPayoutOnNonBankingHolidayAfterNEFTtimings(): array
    {
        $this->markTestSkipped('Only IMPS on Yesbank');

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

    public function testCreatePayoutForAmountLessThanMinFee()
    {
        // Minimum fee is INR 5, attempts and asserts success when creating payout for INR 1.
        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testCreatePayoutForVpaFundAccountWithUnsupportedMode()
    {
        $contactId = $this->getDbLastEntity('contact')->getId();

        $this->fixtures->create('fund_account:vpa', [
            'id'            => '100000000003fa',
            'source_type'   => 'contact',
            'source_id'     => $contactId,
        ]);

        $this->startTest();
    }

    public function testDashboardSummary()
    {
        //TODO: Can be fixed. (Only IMPS on Yesbank)
        $this->markTestSkipped('Only IMPS on Yesbank');

        $this->liveSetUp();

        $secondBankingBalance = $this->createDirectBankingBalance();

        // Creating 2 banking accounts. First for the existing bankingBalance and second for the secondBankingBalance

        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626998',
            'balance_id'            =>  $this->bankingBalance->getId(),
            'account_type'          =>  'nodal',
        ];

        $bankingAccount = $this->createBankingAccount($bankingAccountAttributes, 'live');

        $secondBankingAccountAttributes = [
            'id'                    =>  'DEcba4321DEcba',
            'account_number'        =>  '2224440041626999',
            'balance_id'            =>  $secondBankingBalance->getId(),
            'account_type'          =>  'current',
        ];

        $secondBankingAccount = $this->createBankingAccount($secondBankingAccountAttributes, 'live');

        // Create two queued payouts

        $firstQueuedPayoutAttributes = [
            'account_number'        =>  '2224440041626905',
            'amount'                =>  20000099,
            'queue_if_low_balance'  =>  1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes, 'rzp_live_TheLiveAuthKey');

        $secondQueuedPayoutAttributes = [
            'account_number'        =>  '2224440041626906',
            'amount'                =>  30000099,
            'queue_if_low_balance'  =>  1,
        ];

        $this->createQueuedOrPendingPayout($secondQueuedPayoutAttributes, 'rzp_live_TheLiveAuthKey');

        // Setup the payout workflow

        $workflow = $this->setupWorkflowForLiveMode();

        $this->createPayoutWithWorkflow(
            $workflow,
            [
                'account_number'        =>  '2224440041626905',
                'amount'                =>  54321
            ],
            'rzp_live_TheLiveAuthKey');

        $this->createPayoutWithWorkflow(
            $workflow,
            [
                'account_number'        =>  '2224440041626906',
                'amount'                =>  12345
            ],
            'rzp_live_TheLiveAuthKey');

        $role = $this->getDbEntityById('role', 'RzpChekrRoleId', 'live');

        $user = $this->getDbEntityById('user','MerchantUser01', 'live');

        $user->roles()->attach($role);

        $this->ba->proxyAuth('rzp_live_10000000000000');

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
        $this->markTestSkipped('Only IMPS on Yesbank');

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

    public function testApprovePayoutWithComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $firstApprovalResponse = $this->startTest();

        // Validating first approval response
        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(2, $firstApprovalResponse['workflow_history']['current_level']);
        $this->assertEquals('pending', $firstApprovalResponse['status']);
        $this->assertEquals('Approving', $firstActionChecker['user_comment']);
        $this->assertEquals(true, $firstActionChecker['approved']);

        $this->app['config']->set('database.default', 'live');

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());
        $secondApprovalResponse = $this->startTest();

        // Validating second approval response
        $secondActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(2, $secondApprovalResponse['workflow_history']['current_level']);
        $this->assertEquals('processing', $secondApprovalResponse['status']);
        $this->assertEquals('Approving', $secondActionChecker['user_comment']);
        $this->assertEquals(true, $secondActionChecker['approved']);
    }

    public function testPayoutRejectWhenWorkflowEdit()
    {
        $this->setupRedisMock();

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->disableWorkflowMocks();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testApprovePayoutWithoutComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $approvalResponse = $this->startTest();

        // Validating first approval response
        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(2, $approvalResponse['workflow_history']['current_level']);
        $this->assertEquals(true, $actionChecker['approved']);
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

    public function testBulkApprovePayoutWithComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(true, $actionChecker['approved']);
        $this->assertEquals('Bulk Approving', $actionChecker['user_comment']);
    }

    public function testBulkApprovePayoutWithoutComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(true, $actionChecker['approved']);
        $this->assertEquals(null, $actionChecker['user_comment']);
    }

    public function testRejectPayoutWithComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment('yesbank', 'on');

        $this->createWebhook(['events' => ['payout.rejected' => '1']], [], 'live');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->setInfernoExpectations([$eventTestDataKey]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');

        $this->assertEquals(false, $actionChecker['approved']);
        $this->assertEquals('Rejecting', $actionChecker['user_comment']);
    }

    public function testRejectPayoutWithoutComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment('yesbank', 'on');

        $this->createWebhook(['events' => ['payout.rejected' => '1']], [], 'live');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->setInfernoExpectations([$eventTestDataKey]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');

        $this->assertEquals(false, $actionChecker['approved']);
        $this->assertEquals(null, $actionChecker['user_comment']);
    }

    public function testBulkRejectPayoutsWithComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');


        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        $this->mockRazorxTreatment('yesbank', 'on');

        $this->createWebhook(['events' => ['payout.rejected' => '1']], [], 'live');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->setInfernoExpectations([$eventTestDataKey, $eventTestDataKey]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(false, $actionChecker['approved']);
        $this->assertEquals('Bulk Rejecting', $actionChecker['user_comment']);
    }

    public function testBulkRejectPayoutsWithoutComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        $this->mockRazorxTreatment('yesbank', 'on');

        $this->createWebhook(['events' => ['payout.rejected' => '1']], [], 'live');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->setInfernoExpectations([$eventTestDataKey, $eventTestDataKey]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(false, $actionChecker['approved']);
        $this->assertEquals(null, $actionChecker['user_comment']);
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

        $this->assertEquals(Payout\Status::PROCESSING, $newPayout['status']);
        $this->assertEquals(Attempt\Status::CREATED, $payoutAttempt['status']);

        // Verify attempt entity
        $this->assertEquals($newPayout['attempts'], 1);
        $this->assertEquals($newPayout['id'], $newPayoutAttempt['source']);
        $this->assertEquals($newPayout['merchant_id'], $newPayoutAttempt['merchant_id']);
        $this->assertEquals($newPayout['fund_account_id'], 'fa_100000000000fa');
//        $this->assertNotNull($newPayout['batch_fund_transfer_id']);
//        $this->assertNotNull($newPayoutAttempt['batch_fund_transfer_id']);
//        $this->assertEquals($newPayout['batch_fund_transfer_id'], $newPayoutAttempt['batch_fund_transfer_id']);

        // ----- End of testing payout retry for failed payouts ------ //

        return $newPayout;
    }

    public function testCreatePayoutFundsOnHold()
    {
        $this->liveSetUp();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->merchant->holdFunds();

        $this->startTest();
    }

    public function testCreatePayoutFundsOnHoldOnTestMode()
    {
        $contactId = $this->getDbLastEntity('contact')->getId();

        $this->fixtures->create('fund_account:vpa', [
            'id'            => '100000000003fa',
            'source_type'   => 'contact',
            'source_id'     => $contactId,
        ]);

        $this->fixtures->merchant->holdFunds();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutInsufficientBalance()
    {
        $this->markTestSkipped('Only IMPS on Yesbank');

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
        $this->markTestSkipped();

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
        $this->markTestSkipped();

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

    public function testSearchPayoutByContactEmailExactMatch($email1='user1@payout.com',
                                                             $email2='user2@payout.com')
    {
        $contact1 = $this->fixtures->create('contact',
            ['id' => '1000005contact', 'email' => $email1, 'contact' => '8888888888', 'name' => 'test user1']);

        $contact2 = $this->fixtures->create('contact',
            ['id' => '1000006contact', 'email' => $email2, 'contact' => '8888888889', 'name' => 'test user2']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id'     => $contact1->id,
                'source_type'   => 'contact',
            ]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000001fa',
                'source_id'    => $contact2->id,
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $payout1 = $this->testCreatePayout();

        $payout2 = $this->testCreatePayout();

        $this->fixtures->edit(
            'payout',
            $payout1['id'],
            [
                'fund_account_id' => '100000000000fa'
            ]
        );

        $this->fixtures->edit(
            'payout',
            $payout2['id'],
            [
                'fund_account_id' => '100000000001fa'
            ]
        );

        $this->createEsIndex();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = "/payouts?contact_email={$email1}&account_number=2224440041626905";

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout1['id'], $responsePayout['id']);
    }

    public function testSearchPayoutByContactEmailDifferentDomainExactMatch()
    {
        $this->testSearchPayoutByContactEmailExactMatch(
            'user1@payout.com',
            'user1@pt.com'
        );
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
        $this->markTestSkipped('Only IMPS on Yesbank');

        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayoutWithSameContact()
    {
        $this->markTestSkipped('Only IMPS on Yesbank');

        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $response = $this->startTest();

        $this->assertEquals($response['items'][0]['fund_account']['contact_id'], $response['items'][1]['fund_account']['contact_id']);

        $contacts = $this->getEntities('contact');

        $this->assertEquals(2, count($contacts['items']));
    }

    public function testBulkPayoutWithSameFundAccount()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $response = $this->startTest();

        $this->assertEquals($response['items'][0]['fund_account']['id'], $response['items'][1]['fund_account']['id']);

        $contacts = $this->getEntities('fund_account');

        $this->assertEquals(2, count($contacts['items']));
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

    public function testPayoutStatusUpdate()
    {
        $this->createPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($payout->getStatus(), 'created');

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => 'yesbank',
                'source_id'                 => $payout->getId(),
                'bank_account_id'           => $payout->getDestinationId(),
                'merchant_id'               => $payout->getMerchantId(),
                'purpose'                   => Attempt\Purpose::REFUND,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::PAYOUT,
                'is_fts'                    => '1',
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]
        );

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout->getPublicId() . '/status';

        $this->ba->proxyAuth();
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($payout->getStatus(), 'processed');
        $this->assertEquals($payout->getUtr(), $payout->getId());
    }

    public function testMultiplePayoutStatusUpdate()
    {
        $this->testPayoutStatusUpdate();

        $this->fixtures->edit('contact', '1000010contact',
            [
                'id' => '1000011contact',
            ]);

        $this->testPayoutStatusUpdate();
    }

    public function testPayoutInvalidStatusUpdate()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout',
            $payout->getId(),
            [
                'status' => Payout\Status::QUEUED,
            ]);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout->getPublicId() . '/status';

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testPayoutStatusUpdateOnPrivateAuth()
    {
        $this->createPayout();

        $payout = $this->getDbLastEntity('payout');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout->getPublicId() . '/status';

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testPayoutStatusUpdateOnLiveMode()
    {
        $this->liveSetUp();

        $balance = $this->getDbLastEntity('balance', 'live');

        $this->fixtures->on('live')->create('payout', [
            'id'                =>  '12345678901234',
            'balance_id'        =>  $balance->getId(),
            'merchant_id'       =>  '10000000000000',
            'amount'            =>  1,
            'status'            =>  'created',
        ]);

        $payout = $this->getDbLastEntity('payout', 'live');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout->getPublicId() . '/status';

        $this->ba->proxyAuth('rzp_live_10000000000000');

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
        $this->markTestSkipped('Failing due to payouts blocked, to be fixed later');

        Queue::fake();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->liveSetUp();

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_SLA_FOR_IMPS_PAYOUT => 1]);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true, 'live');

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);

        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);

        $this->assertEquals($payout['channel'], 'icici');

        $this->assertEquals('IMPS', $payoutAttempt['mode']);

        $this->assertEquals('created', $payoutAttempt['status']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true, 'live');

        $this->assertEquals($payout['transaction_id'], $txn['id']);

        $this->assertNotNull($txn['balance_id']);

        return $payout;
    }

    public function testCreateRblPayoutWithModeNotSet()
    {
        $balanceAttributes = [
            'balance' => 10000000,
            'balanceType' => 'direct',
            'channel' => 'rbl',
        ];

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"] ,
            $balanceAttributes["channel"]
        );

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
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateRblPayoutToCard()
    {
        $balanceAttributes = [
            'balance' => 10000000,
            'balanceType' => 'direct',
            'channel' => 'rbl',
        ];

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"] ,
            $balanceAttributes["channel"]
        );

        $virtualAccount = $this->fixtures->create('virtual_account');
        $bankAccount    = $this->fixtures->create(
            'bank_account',
            [
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626906',
                'ifsc_code'      => 'RAZRB000000',
            ]);

        $virtualAccount->bankAccount()->associate($bankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

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

        $this->ba->privateAuth();

        $this->startTest();
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

    public function testCreatePayoutWithWrongFundAccountId()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutIMPSMoreThanMaxAmount()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutRTGSLessThanMinAmount()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutUPIMoreThanMaxAmount()
    {
        $contactId = $this->getDbLastEntity('contact')->getId();

        $this->fixtures->create('fund_account:vpa', [
            'id'            => '100000000003fa',
            'source_type'   => 'contact',
            'source_id'     => $contactId,
        ]);

        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testSearchPayoutByMode()
    {
        $payout = $this->testCreatePayout();

        $request = & $this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts?mode=' . $payout['mode'] . '&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByReferenceId()
    {
        $payout = $this->testCreatePayout();

        $this->fixtures->edit('payout', $payout['id'], ['reference_id' => 'WckD']);

        $request = & $this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts?account_number=2224440041626905&reference_id=WckD';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testCreatePayoutInvalidCurrency()
    {
        $this->startTest();
    }

    public function testGetAllPayoutPurposes()
    {
        $this->startTest();
    }

    public function testAddCustomPayoutPurpose()
    {
        $this->startTest();
    }

    public function testAddCustomPayoutPurposeWithWrongPurposeType()
    {
        $this->startTest();
    }

    public function testAddCustomPayoutPurposeThatAlreadyExists()
    {
        $this->testAddCustomPayoutPurpose();

        $this->startTest();
    }

    public function testAdd101CustomPayoutPurposes()
    {
        for ($count = 0; $count<100; $count++)
        {
            $this->addCustomPayoutPurpose('Give Bonus To Mehul '. $count, 'settlement');
        }

        $this->startTest();
    }

    protected function addCustomPayoutPurpose($purpose, $purposeType)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'        => $purpose,
                'purpose_type'   => $purposeType,
            ]
        ];

        $this->ba->privateAuth();

        $this->sendRequest($request);
    }

    public function testFiringOfWebhookOnUpdationOfUtr()
    {
        $this->setupMockDns();

        $this->mockRazorxTreatment('yesbank', 'on');

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $this->createWebhook(['events' => ['payout.updated' => '1']]);

        $eventTestDataKey = 'testFiringOfWebhookOnUpdationOfUtrEventData';

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => Payout\Status::PROCESSED,
                'utr'    => null,
            ]);

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'utr'    => null,
            ]);

        $this->setInfernoExpectations([$eventTestDataKey]);

        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     =>  '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => $payoutId,
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => '933815233814'
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $payout = $this->getDbEntityById('payout', $payoutId);
        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('933815233814', $payout->getUtr());
        $this->assertEquals('933815233814', $fta->getUtr());
    }

    public function testNotFiringOfWebhookOnNotUpdationOfUtr()
    {
        $this->setupMockDns();

        $this->mockRazorxTreatment('yesbank', 'on');

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $utr = $payout->getUtr();

        $this->createWebhook(['events' => ['payout.updated' => '1']]);

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::PROCESSED,
            ]);

        $this->setInfernoExpectations([]);

        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     =>  '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => $payoutId,
                'source_type'         => 'payout',
                'status'              => 'PROCESSED',
                'utr'                 => $utr
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }

    public function testNoFailureReasonBeforeFtaRecon()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        //Setting payout failure reason null as it is already set in create payout
        $payout[Payout\Entity::FAILURE_REASON] = null;

        $payoutId = $payout->getId();

        $utr = $payout->getUtr();

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'fta_status'        => 'failed',
            'failure_reason'    => '',
            'utr'               => $utr,
            'remarks'           => 'testing failed mapping',
        ]);

        $updatedPayout = $this->getDbEntityById('payout',$payoutId)->toArray();

        $this->assertNull($updatedPayout[Payout\Entity::FAILURE_REASON]);
    }

    public function testWithFailureReasonBeforeFtaRecon()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payout[Payout\Entity::FAILURE_REASON] = null;

        $payoutId = $payout->getId();

        $utr = $payout->getUtr();

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'fta_status'        => 'failed',
            'failure_reason'    => 'Beneficiary bank\'s systems are down. Please retry after some time.',
            'utr'               =>  $utr,
            'remarks'           => 'testing failed mapping',
        ]);

        $updatedPayout = $this->getDbEntityById('payout',$payoutId)->toArray();

        $this->assertNotNull($updatedPayout[Payout\Entity::FAILURE_REASON]);
    }

    public function testWorkflowTriggerForBankingRequest()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();
    }

    public function testGetPayoutMetaWorkflowProxyAuth()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('100000Razorpay');

        $this->ba->proxyAuth('rzp_test_100000Razorpay', $merchantUser->getId());

        $this->startTest();
    }

    public function testDefaultWorkflowBehaviourForAPIRequest()
    {
        //
        // default behaviour is if workflow is enabled, it should get triggerd
        // here, merchant doesn't want the workflow to be skipped for API request
        //
        $this->liveSetUp();
        $this->setupWorkflowForLiveMode();
        $this->disableWorkflowMocks();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testSkipWorkflowForAPIRequest()
    {
        //
        // Here workflows are enabled for create payouts,
        // However user wants to disable the workflow for API request
        //
        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_WORKFLOWS_FOR_API]);

        $this->liveSetUp();
        $this->setupWorkflowForLiveMode();
        $this->disableWorkflowMocks();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }


    public function testGetPayoutMetaWorkflowPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAddCustomPurposeRZPFees()
    {
        $this->startTest();
    }

    public function testCancelRZPFeesPayout()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout->getId(), [
            'status'    => 'queued',
            'purpose'   => 'rzp_fees',
        ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/' . $payout->getPublicId() .'/cancel';

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutToRzpFeesContact()
    {
        $this->createContact();

        $this->fixtures->edit('contact', $this->contact->getId(), ['type' => 'rzp_fees']);

        $this->createFundAccount();

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $this->startTest($data);
    }

    protected function createPayoutWithWorkflowHavingPayoutRules()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_WORKFLOWS]);

        $workflow = $this->getDbLastEntity('workflow');

        $this->fixtures->create('workflow_payout_amount_rules', ['workflow_id' => $workflow['id'],
            'min_amount' => '0', 'max_amount' => '5000000']);

        $this->createPayoutWithWorkflow($workflow);
    }

    // rzp_fees payout should get processed before others. Create 3 Queued payouts, have enough balance for only
    // one to go through. Assert that rzp_fees payout went through first
    public function testRZPFeesQueuedPayoutPriority()
    {
        $balance = $this->createDirectBankingBalance()->toArray();

        $bankingAccountParams = [
            'id' => 'xba00000000000',
            'merchant_id' => '10000000000000',
            'account_ifsc' => 'RATN0000088',
            'account_number' => '2224440041626906',
            'status' => 'active',
            'channel' => 'rbl',
            'balance_id' => $balance['id'],
        ];

        $bankingAccount = $this->createBankingAccount($bankingAccountParams);

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $balanceId = $balance['id'];

        $this->fixtures->edit('balance', $balanceId, ['balance' => 0]);

        $payoutData = [
            'queue_if_low_balance' => 1,
            'account_number'       => 2224440041626906,
        ];

        $this->createQueuedOrPendingPayout($payoutData);

        $payout1 = $this->getDbLastEntity('payout')->toArray();

        $this->createQueuedOrPendingPayout($payoutData);

        $payout2 = $this->getDbLastEntity('payout')->toArray();

        $this->createQueuedOrPendingPayout($payoutData);

        $payout3 = $this->getDbLastEntity('payout')->toArray();

        $this->assertEquals($payout1['status'], Payout\Status::QUEUED);
        $this->assertEquals($payout2['status'], Payout\Status::QUEUED);
        $this->assertEquals($payout3['status'], Payout\Status::QUEUED);

        $payout2Id = $payout2['id'];

        $this->fixtures->edit('payout', $payout2Id, ['purpose' => 'rzp_fees']);

        // Add enough balance for exactly one payout to go through
        $this->fixtures->edit('balance', $balanceId, ['balance' => 15000]);

        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        $this->fixtures->edit('banking_account', $bankingAccount->getId(),
                              [
                                  'balance_last_fetched_at' => $oldDateTime->getTimestamp()
                              ]);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(150);

        $this->ba->cronAuth();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content']['from'] = $currentTime - 10;
        $data['request']['content']['to'] = $currentTime + 10;

        $this->startTest();

        // Assert first payout still queued
        $payout1 = $this->getDbEntityById('payout', $payout1['id'])->toArray();
        $this->assertEquals($payout1['status'], Payout\Status::QUEUED);

        // Assert third payout still queued
        $payout3 = $this->getDbEntityById('payout', $payout3['id'])->toArray();
        $this->assertEquals($payout3['status'], Payout\Status::QUEUED);

        // Assert second payout status changed
        $payout2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();
        $this->assertNotEquals($payout2['status'], Payout\Status::QUEUED);
    }

    // If rzp_fees payout remains queued, all other payouts remain queued too.
    public function testRZPFeesQueuedPayoutNotEnoughBalance()
    {
        $balance = $this->createDirectBankingBalance()->toArray();

        $bankingAccountParams = [
            'id' => 'xba00000000000',
            'merchant_id' => '10000000000000',
            'account_ifsc' => 'RATN0000088',
            'account_number' => '2224440041626906',
            'status' => 'active',
            'channel' => 'rbl',
            'balance_id' => $balance['id'],
        ];

        $bankingAccount = $this->createBankingAccount($bankingAccountParams);

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $balanceId = $balance['id'];

        $this->fixtures->edit('balance', $balanceId, ['balance' => 0]);

        $payoutData = [
            'queue_if_low_balance' => 1,
            'account_number'        => 2224440041626906,
        ];

        $this->createQueuedOrPendingPayout($payoutData);

        $payout1 = $this->getDbLastEntity('payout')->toArray();

        $payoutDataHigherAmount = [
            'queue_if_low_balance'  => 1,
            'amount'                => 530000,
            'account_number'        => 2224440041626906,
        ];

        $this->createQueuedOrPendingPayout($payoutDataHigherAmount);

        $payout2 = $this->getDbLastEntity('payout')->toArray();

        $this->createQueuedOrPendingPayout($payoutData);

        $payout3 = $this->getDbLastEntity('payout')->toArray();

        $this->assertEquals($payout1['status'], Payout\Status::QUEUED);
        $this->assertEquals($payout2['status'], Payout\Status::QUEUED);
        $this->assertEquals($payout3['status'], Payout\Status::QUEUED);

        $payout2Id = $payout2['id'];

        $this->fixtures->edit('payout', $payout2Id, ['purpose' => 'rzp_fees']);

        // Add enough balance so that all payouts except the fee_recovery payout can get processed
        $this->fixtures->edit('balance', $balanceId, ['balance' => 520000]);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(5200);

        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        $this->fixtures->edit('banking_account', $bankingAccount->getId(),
                              ['balance_last_fetched_at' => $oldDateTime->getTimestamp()]);

        $this->ba->cronAuth();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content']['from'] = $currentTime - 10;
        $data['request']['content']['to'] = $currentTime + 10;

        $this->startTest();

        // Assert second still queued
        $payout2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();
        $this->assertEquals($payout2['status'], Payout\Status::QUEUED);

        // Assert first payout still queued although merchant had enough balance to process this
        $payout1 = $this->getDbEntityById('payout', $payout1['id'])->toArray();
        $this->assertEquals($payout1['status'], Payout\Status::QUEUED);

        // Assert third payout still queued although merchant had enough balance to process this
        $payout3 = $this->getDbEntityById('payout', $payout3['id'])->toArray();
        $this->assertEquals($payout3['status'], Payout\Status::QUEUED);
    }

    protected function createDirectBankingBalance()
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

        $balance = $this->getDbEntity('balance', [
            'merchant_id'   => '10000000000000',
            'account_type'  => 'direct'
        ]);

        return $balance;
    }


    public function testFiringOfWebhookOnCreationOfPendingPayout()
    {
        $this->liveSetUp();

        $this->setupMockDns();

        $this->mockRazorxTreatment('yesbank', 'on');

        $this->createWebhook(['events' => ['payout.pending' => '1']], [], 'live');

        $eventTestDataKey = 'testFiringOfWebhookOnCreationOfPendingPayoutEventData';

        $this->setInfernoExpectations([$eventTestDataKey]);

        $workflow = $this->setupWorkflowForLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->assertEquals('pending', $payout['status']);
    }

    public function testFiringOfWebhookPayoutStatusQueuedWithStork()
    {
        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $this->fixtures->merchant->addFeatures([Feature\Constants::BANKING_STORK_MIGRATION]);

        $payoutQueuedEventData = $this->testData['testFiringOfWebhookOnQueuedPayoutEventData'];

        $payoutInitiatedEventData = $this->testData['testFiringOfWebhookOnInitiatedPayoutEventData'];
        $payoutTransactionCreatedEventData = $this->testData['testFiringOfWebhookOnCreatedTransactionPayoutEventData'];

        $this->mockServiceStorkRequest(
            function ($path, $payload) use ($payoutQueuedEventData, $payoutInitiatedEventData, $payoutTransactionCreatedEventData)
            {
                $this->assertContains($payload['event']['name'],
                                      [
                                          'virtual_account.created',
                                          'payout.initiated',
                                          'payout.queued',
                                          'transaction.created'
                                      ]);

                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_INITIATED:
                        $this->validateStorkWebhookFireEvent('payout.initiated',
                                                             $payoutInitiatedEventData,
                                                             $payload);
                        break;

                    case Event::PAYOUT_QUEUED:
                        $this->validateStorkWebhookFireEvent('payout.queued', $payoutQueuedEventData, $payload);
                        break;

                    case Event::TRANSACTION_CREATED:
                        $this->validateStorkWebhookFireEvent('transaction.created',
                                                             $payoutTransactionCreatedEventData,
                                                             $payload);
                        break;
                }

                return new \Requests_Response();
            })->times(4);

        $this->testCreateQueuedPayout();
    }

    public function testFiringOfWebhookPayoutStatusUpdateWithStork()
    {
        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $this->fixtures->merchant->addFeatures([Feature\Constants::BANKING_STORK_MIGRATION]);

        $payoutUpdatedEventData = $this->testData['testFiringOfWebhookOnUpdateOfPayoutEventData'];
        $payoutProcessedEventData = $this->testData['testFiringOfWebhookOnProcessPayoutEventData'];

        $this->mockServiceStorkRequest(
            function ($path, $payload) use ($payoutUpdatedEventData, $payoutProcessedEventData)
            {
                $this->assertContains($payload['event']['name'], ['payout.updated', 'payout.processed']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payload);
                        break;

                    case Event::PAYOUT_PROCESSED:
                        $this->validateStorkWebhookFireEvent('payout.processed', $payoutProcessedEventData, $payload);
                        break;

                }

                return new \Requests_Response();
            })->times(2);

        $this->testPayoutStatusUpdate();
    }

    public function testFiringOfWebhookRejectPayoutWithStork()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $this->fixtures->merchant->addFeatures([Feature\Constants::BANKING_STORK_MIGRATION]);

        $eventData = $this->testData['testFiringOfWebhookOnRejectionOfPayoutEventData'];

        $this->mockServiceStorkRequest(
            function ($path, $payload) use ($eventData)
            {
                $this->validateStorkWebhookFireEvent('payout.rejected', $eventData, $payload, 'live');

                return new \Requests_Response();
            })->once();

        // Reject with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();
    }

    public function testFiringOfWebhookOnCreationOfPendingPayoutWithStork()
    {
        $this->liveSetUp();

        $this->setupMockDns();

        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $this->fixtures->merchant->addFeatures([Feature\Constants::BANKING_STORK_MIGRATION]);

        $testData = $this->testData['testFiringOfWebhookOnCreationOfPendingPayoutEventData'];

        $this->mockServiceStorkRequest(
            function ($path, $payload) use ($testData)
            {
                $this->validateStorkWebhookFireEvent('payout.pending', $testData, $payload, 'live');

                return new \Requests_Response();
            })->once();

        $workflow = $this->setupWorkflowForLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->assertEquals('pending', $payout['status']);
    }

    public function testApprovePayoutWithNonBankingRoleInWorkflow()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Checker role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->checkerRoleUser->getId());

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $this->startTest();
    }

    public function testPayoutToAmexCardWithNullIssuerSupportedMode()
    {
        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_1000001contact",
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "340169570990137",
                    "cvv"          => "2126",
                    "expiry_month" => 10,
                    "expiry_year"  => 21,
                ]
            ]
        ];

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->mockRazorxTreatment('yesbank', 'on' , 'off' , 'off', 'off', 'on');

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $this->assertEquals(null, $fundAccount['card']['issuer']);
        $this->assertEquals(Network::$fullName[Network::AMEX], $fundAccount['card']['network']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id']  = $fundAccount['id'];
        $testData['response']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testPayoutToAmexCardWithNullIssuerSupportedModeButFeatureDisabledOrRazorxTimeout()
    {
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->mockRazorxTreatment('yesbank', 'on' , 'off' , 'off', 'off', 'control');

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testPayoutToAmexCardWithNullIssuerNotSupportedMode()
    {
        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_1000001contact",
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "340169570990137",
                    "cvv"          => "2126",
                    "expiry_month" => 10,
                    "expiry_year"  => 21,
                ]
            ]
        ];

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->mockRazorxTreatment('yesbank', 'on' , 'off' , 'off', 'off', 'on');

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $this->assertEquals(null, $fundAccount['card']['issuer']);
        $this->assertEquals(Network::$fullName[Network::AMEX], $fundAccount['card']['network']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testPayoutToAmexCardWithSupportedIssuerSupportedMode()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::AMEX],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::SCBL
        ]);

        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_1000001contact",
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "340169570990137",
                    "cvv"          => "2126",
                    "expiry_month" => 10,
                    "expiry_year"  => 21,
                ]
            ]
        ];

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $this->assertEquals(Issuer::SCBL, $fundAccount['card']['issuer']);
        $this->assertEquals(Network::$fullName[Network::AMEX], $fundAccount['card']['network']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id']  = $fundAccount['id'];
        $testData['response']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testPayoutToAmexCardWithSupportedIssuerNotSupportedMode()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::AMEX],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::SCBL
        ]);

        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_1000001contact",
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "340169570990137",
                    "cvv"          => "2126",
                    "expiry_month" => 10,
                    "expiry_year"  => 21,
                ]
            ]
        ];

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $this->assertEquals(Issuer::SCBL, $fundAccount['card']['issuer']);
        $this->assertEquals(Network::$fullName[Network::AMEX], $fundAccount['card']['network']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testPayoutToAmexCardWithNotSupportedIssuer()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::AMEX],
            'type'    => Type::CREDIT,
            'issuer'  => 'ABCD',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testRejectPayoutWithSuperAdmin()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment('yesbank', 'on');

        $this->createWebhook(['events' => ['payout.rejected' => '1']], [], 'live');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->setInfernoExpectations([$eventTestDataKey]);

        $this->fixtures->on('live')->create('admin', [
            'id' => 'RzrpySprAdmnId',
            'org_id' => Org::RZP_ORG,
            'name' => 'test admin'
        ]);

        $this->app['config']->set('database.default', 'live');

        $this->ba->adminAuth('live');

        $this->startTest();

        $payout = $this->getDbEntityById('payout',$payout['id'],'live');
        $actionState = $this->getDbLastEntity('action_state','live');

        $this->assertEquals($payout['status'],'rejected');
        $this->assertEquals($actionState['admin_id'],'RzrpySprAdmnId');
        $this->assertEquals($actionState['name'],'rejected');
        $this->assertNull($actionState['merchant_id']);
        $this->assertNull($actionState['user_id']);
    }

    public function testRejectPayoutWithOrdinaryAdmin()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/payouts/' . $payout['id'] . '/reject';
        $testData['request']['server'][] = ['HTTP_X-Razorpay-Account' => 'acc_10000000000000'];

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $admin = $this->fixtures->on($liveMode)->create('admin', [
            'id' => 'RzrpyRndAdmnId',
            'org_id' => Org::RZP_ORG,
            'name' => 'test admin'
        ]);

        $this->fixtures->on($liveMode)->create('admin_token', [
            'id'        => 'AdminToken1234',
            'token'     => Hash::make('secondToken'),
            'admin_id'  => $admin->getId(),
        ]);

        $this->app['config']->set('database.default', 'live');

        // Reject with Random Admin
        $this->ba->adminAuth('live','secondTokenAdminToken1234');

        $this->startTest();
    }

    protected function validateStorkWebhookFireEvent($event, $testData, $storkPayload, $mode='test')
    {
        $this->assertEquals('rx-' . $mode, $storkPayload['event']['service']);
        $this->assertEquals($event, $storkPayload['event']['name']);
        $this->assertEquals('merchant', $storkPayload['event']['owner_type']);
        $this->assertEquals('10000000000000', $storkPayload['event']['owner_id']);
        $this->assertArraySelectiveEquals($testData, json_decode($storkPayload['event']['payload'], true));
    }

    protected function setupRedisMock()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['get'])
            ->getMock();

        Redis::shouldReceive('connection')
            ->andReturn($redisMock);

        $redisMock->method('get')->will($this->returnValue('true'));
    }
}
