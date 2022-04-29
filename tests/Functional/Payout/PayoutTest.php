<?php

namespace RZP\Tests\Functional\Payout;

use DB;
use Mail;
use Hash;
use Queue;
use Redis;
use Config;
use Mockery;
use Requests_Response;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Models\PayoutOutbox\Constants as PayoutOutboxConstants;
use RZP\Services\DiagClient;
use RZP\Services\Raven;
use RZP\Jobs\Transactions;
use RZP\Exception\RuntimeException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Artisan;

use RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Admin;
use RZP\Models\Batch;

use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Http\BasicAuth;
use RZP\Error\ErrorCode;
use RZP\Models\Settings;
use RZP\Models\Card\Type;
use Razorpay\OAuth\Client;
use RZP\Models\FileStore;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\Payout\Status;
use RZP\Services\RazorXClient;
use RZP\Models\PayoutsDetails;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\FTS\FundTransfer;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\OnHoldPayoutsProcess;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Mail\Payout\PendingApprovals;
use RZP\Models\FundTransfer\Attempt;
use RZP\Jobs\PayoutSourceUpdaterJob;
use RZP\Jobs\PayoutPostCreateProcess;
use RZP\Models\Base\PublicCollection;
use RZP\Mail\Banking\LowBalanceAlert;
use RZP\Services\Mock\WorkflowService;
use RZP\Models\Payout\WorkflowFeature;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Webhook\Event;
use RZP\Models\Payout\ErrorCodeMapping;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Models\Merchant\Balance as Balance;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Service as AdminService;
use RZP\Mail\Transaction\Payout as PayoutMail;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Jobs\PayoutPostCreateProcessLowPriority;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransferJob;
use RZP\Tests\Functional\Helpers\PayoutAttachmentTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payout\PayoutsIntermediateTransactions;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Mail\Payout\PayoutProcessedContactCommunication;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Models\PayoutSource\Entity as PayoutSourceEntity;
use RZP\Services\PayoutService\OnHoldBeneEvent as OnHoldBeneEventService;
use RZP\Models\Payout\Notifications\PayoutProcessedContactCommunication as PayoutProcessedNotification;

class PayoutTest extends OAuthTestCase
{
    use OAuthTrait;
    use PayoutTrait;
    use WebhookTrait;
    use PaymentTrait;
    use TestsMetrics;
    use HeimdallTrait;
    use WorkflowTrait;
    use SettlementTrait;
    use PayoutAttachmentTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    private   $checkerRoleUser;

    private   $ownerRoleUser;

    private   $finL1RoleUser;

    private   $finL2RoleUser;

    private   $finL3RoleUser;

    protected $slackApp;

    protected $payoutService;

    private   $unitTestCase;

    protected function setUp(): void
    {
        $this->unitTestCase = new \Tests\Unit\TestCase();

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

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testCreatePayoutAndCheckTransferredAtColumn()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $currentTime = Carbon::now()->getTimestamp();

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]
        );

        $payout = $this->getDbLastEntity('payout');

        self::assertNotNull($payout->transferred_at);

        $transferredAt = $payout->transferred_at;

        self::assertEquals(true, $transferredAt >= $currentTime);
    }

    public function testCreatePayout(): array
    {
        $this->ba->privateAuth();

        $this->startTest();

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
        $txn   = $this->getLastEntity('transaction', true);
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

    public function testCreatePayoutWithPayoutLimitFeatureFlagEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::INCREASE_PAYOUT_LIMIT]);

        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => 100000000000]);

        $this->ba->privateAuth();

        $this->startTest();

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
        $this->assertEquals($payout['amount'], 49000000000);

        // Verify transaction entity
        $txn   = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7e4oKCgaubd",
            'percentage'      => null,
            'amount'          => 1500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);
    }

    public function testCreatePayoutWithPayoutLimitExceededFeatureFlagEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::INCREASE_PAYOUT_LIMIT]);

        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => 100000000000]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    // In testcases the values in request payload gets converted to string. Hence we can write testcase to test amount with
    // upto certain decimal places. We need to test more decimal places and bool values for amount on stage env or local.
    public function testCreatePayoutWithDecimalAmount()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertNull($payout);
    }

    public function testUpdatePayout()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__]['request']['url'] = '/payouts_service/payout/' . $payout->getPublicId() . '/update';

        $this->ba->payoutInternalAppAuth();
        $this->startTest();
    }

    public function testStatusSummaryObjectInGetPayout()
    {
        $this->setmockRazorxTreatment(['status_details_timeline_view' => 'on'], 'control');

        $this->testCreatePayout();

        $payout = $this->getLastEntity('payout', true);

        $payout1 = $this->getDbLastEntity('payout');
        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout1, [
            'source_type'      => 'payout',
            'source_id'        => $payout1->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'mode'             => 'RTGS',
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'beneficiary_bank_confirmation_pending',
                'parameters' => [
                    'processed_by_time' => '1636481743',
                ],
            ],
        ]);

        $this->ba->proxyAuth();
        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts/' . $payout['id'];

        $payout2 = $this->startTest();
        $this->assertArrayHasKey(Payout\Entity::STATUS_SUMMARY, $payout2);
        $this->assertEquals('beneficiary_bank_confirmation_pending', $payout2['status_summary']['processing'][0]['reason']);
        $this->assertEquals('Confirmation of credit to the beneficiary is pending from beneficiary bank. Please check the status after 09th November 2021', $payout2['status_summary']['processing'][0]['description']);
    }

    public function testPayoutStatusReasonMapping()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotNull($response);
    }

    public function testErrorDescriptionForMinimumTransactionAmount()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testConditionInVpaTypeFundAccount()
    {
        $this->ba->privateAuth();

        $this->createContact();

        $this->fundAccount = $this->createVpaFundAccount();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => 104,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'mode'            => 'UPI',
            'notes'           => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => $content
        ];

        $this->makeRequestAndGetContent($request);

        $payout1  = $this->getDbLastEntity('payout');
        $beneBank = $payout1->provideBeneBankName();
        $this->assertEquals('beneficiary bank', $beneBank);
    }

    public function testCreatePayoutWithNarrationNull()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::NULL_NARRATION_ALLOWED]);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals(null, $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'yesbank');
    }

    public function testCreatePayoutWithNarrationNullTypeWithFeatureEnabled()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::NULL_NARRATION_ALLOWED]);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals(null, $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'yesbank');
    }

    /*
     * This test creates a payout with narration as null types, which is replaced by the default message
     * in the response since null types are treated as null values
     */
    public function testCreatePayoutWithNarrationNullType()
    {
        $this->testData[__FUNCTION__]['request']['content']['narration'] = "null";

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);

        $this->testData[__FUNCTION__]['request']['content']['narration'] = "none";

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);

        $this->testData[__FUNCTION__]['request']['content']['narration'] = "empty";

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
    }

    public function testCreatePayoutWithASyncFtsTransferCall()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_ASYNC_FTS_TRANSFER]);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mock = Mockery::mock(FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive([
                                 'shouldAllowTransfersViaFts' => [true, 'Dummy'],
                             ]);

        $this->app->instance('fts_fund_transfer', $mock);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNull($payout['transferred_at']);

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status is created (FTS async call).
        $this->assertEquals('created', $fta->getStatus());
    }

    public function testCreatePayoutOnLiveMode(): array
    {
        $this->liveSetUp();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getLastEntity('payout', true, 'live');

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'icici');

        // Verify transaction entity
        $txn   = $this->getLastEntity('transaction', true, 'live');
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true, 'live');

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

    /**
     *  The below test cases just checks that if the merchant has no credits
     *  then also if we enable this credits_new_flow feature on the merchant,
     *  the fees and tax will be calculated as expected, this is to just check
     *  that fees and tax logic works in normal scenario so later on we can
     *  just remove the flag and calculate fees and tax of payouts before
     *  itself
     */
    public function testCreatePayoutWithNewCreditsFlow()
    {
        $bankingBalance = $this->getDbLastEntity('balance');

        $balance = $bankingBalance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

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
        $txn   = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);
        $this->assertEquals(1062, $txn['fee']);
        $this->assertEquals(162, $txn['tax']);

        $bankingBalance = $this->getDbLastEntity('balance');

        $this->assertEquals($balance - 2001062, $bankingBalance['balance']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7dTcURsOr77",
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);
    }

    public function testCreatePayoutWithIKeyHeader($ikeyValue = 'check', $amount = null)
    {
        $headers = [
            'HTTP_' . RequestHeader::X_PAYOUT_IDEMPOTENCY => $ikeyValue,
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

    public function testCreatePartnerPayout()
    {
        $this->app['basicauth']->setPartnerMerchantId('10000000000000');

        $this->app['basicauth']->setOAuthApplicationId('8ckeirnw84ifke');

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);

        $this->fixtures->merchant->create(['id' => '10000000000009']);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->privateAuth();

        $payout = $this->startTest();

        $this->assertTrue(isset($payout['meta']) === false);

        return $payout;

    }

    public function testPayoutFetchByIdCreatedByPartner()
    {
        $payout = $this->testCreatePartnerPayout();

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/' . $payout['id'];

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals($response['meta']['partner_application']['merchant_id'], '10000000000000');

        $this->assertEquals($response['meta']['partner_application']['id'], '8ckeirnw84ifke');

        $this->assertEquals($response['meta']['partner_application']['name'], 'Internal');
    }

    public function testCreateTwoPayoutsWithSameIKey()
    {
        // Not asserting the data, just the count.
        $this->mockLedgerSns(1);

        $payout1 = $this->testCreatePayoutWithIKeyHeader('samekey');

        $payout2 = $this->testCreatePayoutWithIKeyHeader('samekey');

        $this->assertEquals($payout1['id'], $payout2['id']);

        $ikeys = $this->getDbEntities(Constants\Entity::IDEMPOTENCY_KEY);

        $this->assertCount(1, $ikeys);
    }

    public function testCreateTwoPayoutsWithDiffIKey()
    {
        // Not asserting the data, just the count.
        $this->mockLedgerSns(2);

        $payout1 = $this->testCreatePayoutWithIKeyHeader('key1');

        $payout2 = $this->testCreatePayoutWithIKeyHeader('anotherkey');

        $this->assertNotEquals($payout1['id'], $payout2['id']);

        $ikeys = $this->getDbEntities(Constants\Entity::IDEMPOTENCY_KEY);

        $this->assertCount(2, $ikeys);
    }

    public function testCreateTwoPayoutsWithSameIKeyDiffRequest()
    {
        // Not asserting the data, just the count.
        $this->mockLedgerSns(1);

        $this->testCreatePayoutWithIKeyHeader('samekey');

        $headers = [
            'HTTP_' . RequestHeader::X_PAYOUT_IDEMPOTENCY => 'samekey',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithNarrationAsArray()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCompositePayoutWithNarrationAsArray()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateCompositePayoutWithOldIfsc()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCustomerWalletPayoutWithNarrationAsArray()
    {

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create('customer_balance', ['customer_id' => '100000customer', 'balance' => 1000]);

        $this->startTest();
    }

    public function testCreateInterAccountPayout()
    {
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::RZP_INTERNAL_ACCOUNTS => [
                    [
                        "merchant_id"    => "10000000000000",
                        "account_number" => "10000000000000",
                        "entity"         => "RZPX",
                    ],
                ],
            ]);

        $ledgerSnsPayloadArray = [];

        // During payout creation, there has been push to SNS topic for creating this transaction in Ledger service.
        // Mocking ledger sns because call to ledger is currently async via SNS. Once it is in sync, this will be removed.
        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $this->ba->privateAuth();

        $this->startTest();

        $payoutsCreated = $this->getDbEntities('payout');

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1062', $ledgerRequestPayload['commission']);
            $this->assertEquals('162', $ledgerRequestPayload['tax']);
            $this->assertEquals('inter_account_payout_initiated', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testCreateInterAccountPayoutByNonFinopsMerchant()
    {
        $ledgerSnsPayloadArray = [];

        // During payout creation, there has been push to SNS topic for creating this transaction in Ledger service.
        // Mocking ledger sns because call to ledger is currently async via SNS. Once it is in sync, this will be removed.
        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $this->testCreatePayout();

        $this->ba->privateAuth();

        $this->startTest();

        $payoutsCreated = $this->getDbEntities('payout');

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1062', $ledgerRequestPayload['commission']);
            $this->assertEquals('162', $ledgerRequestPayload['tax']);
            $this->assertEquals('payout_initiated', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testInterAccountPayoutReversal()
    {
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::RZP_INTERNAL_ACCOUNTS => [
                    [
                        "merchant_id"    => "10000000000000",
                        "account_number" => "10000000000000",
                        "entity"         => "RZPX",
                    ],
                ],
            ]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E10282323'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                            'Payout failed. Contact support for help.');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $reversal = $this->getLastEntity('reversal', true);
        $this->assertEquals(2001062, $reversal['amount']);

        $payoutCreated = $this->getDbLastEntity('payout');

        $reversalCreated = $this->getDbLastEntity('reversal');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'inter_account_payout_initiated',
            'inter_account_payout_failed',
        ];

        // The first event passes the payout Id, the reversal event passes the reversal Id
        $transactorIdArray = [
            $payoutCreated->getPublicId(),
            $reversalCreated->getPublicId(),
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1062', $ledgerRequestPayload['commission']);
            $this->assertEquals('162', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
        }

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);

        // Not passed in payout initiated payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);

        // Not passed in payout failed payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[1]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[1]['identifiers']);
    }

    public function testCreatePayoutWithoutFundAccountId()
    {
        $ledgerSnsPayloadArray = [];

        // During payout creation, there has been push to SNS topic for creating this transaction in Ledger service.
        // Mocking ledger sns because call to ledger is currently async via SNS. Once it is in sync, this will be removed.
        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $this->testCreatePayout();

        $this->ba->privateAuth();

        $this->startTest();

        $payoutsCreated = $this->getDbEntities('payout');

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1062', $ledgerRequestPayload['commission']);
            $this->assertEquals('162', $ledgerRequestPayload['tax']);
            $this->assertEquals('payout_initiated', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testCreatePayoutWithOtpOutOfIMPSLimit()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithModeNotSet()
    {
        // Not asserting the data, just the count.
        $this->mockLedgerSns(0);

        $this->startTest();
    }

    public function testCreatePayoutWithInvalidMode()
    {
        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000003fa',
                'account_type' => 'bank_account',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '100000000001ba',
                'active'       => 1,
            ]);

        $this->fixtures->create('bank_account', ['id' => '100000000000ba']);

        // Not asserting the data, just the count.
        $this->mockLedgerSns(0);

        $this->startTest();
    }

    public function testPublicErrorCodeMapping()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E1028'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                            'IMPS is not enabled on Beneficiary Account');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
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
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E10282323'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                            'Payout failed. Contact support for help.');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn['posted_at']);
    }

    public function testPayoutReversalWithRewards()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 1500, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);
        $this->assertNull($payout['user_id']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals(900, $payout['fees']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(900, $txn['fee']);
        $this->assertEquals('reward_fee', $txn['credit_type']);
        $this->assertEquals(900, $txn['fee_credits']);

        $balance = $this->getLastEntity('balance', true);
        $this->assertEquals('shared', $balance['account_type']);

        $this->assertEquals($balanceBefore - 2000000, $balance['balance']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(900, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true);
        $this->assertEquals('payout', $creditTxnEntity['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntity['entity_id']);
        $this->assertEquals(900, $creditTxnEntity['credits_used']);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E10282323'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                            'Payout failed. Contact support for help.');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn['posted_at']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(0, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true);
        $this->assertEquals('reversal', $creditTxnEntity['entity_type']);
        $this->assertEquals(-900, $creditTxnEntity['credits_used']);

        $reversal = $this->getLastEntity('reversal', true);
        $this->assertEquals(2000000, $reversal['amount']);

        $payoutCreated = $this->getDbLastEntity('payout');

        $reversalCreated = $this->getDbLastEntity('reversal');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'payout_initiated',
            'payout_failed',
        ];

        // The first event passes the payout Id, the reversal event passes the reversal Id
        $transactorIdArray = [
            $payoutCreated->getPublicId(),
            $reversalCreated->getPublicId()
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('900', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertEquals('reward', $ledgerRequestPayload['additional_params']['fee_accounting']);
        }

        //
        // Assertions for fts_fund_account_id and fts_account_type
        //

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);

        // Not passed in payout initiated payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);

        // Not passed in payout failed payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[1]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[1]['identifiers']);
    }

    public function testPayoutReversalWithMultipleRewards()
    {
        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 1400, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);
        $this->assertNull($payout['user_id']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals(900, $payout['fees']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(900, $txn['fee']);
        $this->assertEquals('reward_fee', $txn['credit_type']);
        $this->assertEquals(900, $txn['fee_credits']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(800, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);
        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(800, $creditTxnEntities[1]['credits_used']);

        $balance = $this->getLastEntity('balance', true);
        $this->assertEquals('shared', $balance['account_type']);
        $this->assertEquals($balanceBefore - 2000000, $balance['balance']);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E10282323'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                            'Payout failed. Contact support for help.');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn['posted_at']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');

        $this->assertEquals('reversal', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);
        $this->assertEquals('reversal', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals(-800, $creditTxnEntities[3]['credits_used']);
    }

    public function testPublicErrorCodeMappingWithEmptyPublicError()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => 'Beneficiary bank\'s systems are down. Please retry after some time.',
            'bank_status_code' => 'YB_SFMS_E59'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON], 'Beneficiary bank\'s systems are down. Please retry after some time.');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
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
            'fta_status'     => 'failed',
            'failure_reason' => '',
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                            'Payout failed. Contact support for help.');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
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
        $txn   = $this->getLastEntity('transaction', true);
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
        $txn   = $this->getLastEntity('transaction', true);
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
        $txn   = $this->getLastEntity('transaction', true);
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
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contactId,
        ]);

        $this->startTest();
    }

    public function createCustomerWalletPayout()
    {

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create('customer_balance', ['customer_id' => '100000customer', 'balance' => 1000]);

        $this->fixtures->on('live')->edit('balance', '10000000000000', ['balance' => 1000]);

        $payout = $this->startTest();

        $payout = $this->getDbEntityById('payout', $payout['id']);

        return $payout;
    }

    public function testCreateCustomerWalletPayoutWithOldNewIfsc()
    {
        $this->mockRazorxTreatment();

        $this->liveSetUp();

        $this->fixtures
            ->on('live')
            ->create('bank_account',
                     [
                         'ifsc_code'        => 'ORBC0101685',
                         'account_number'   => '2224440041626905',
                         'beneficiary_name' => 'Ambar',
                         'type'             => 'customer',
                         'entity_id'        => 'GHz4VlBkkiUBwh',
                     ]);

        $bankAccount = $this->getDbLastEntity('bank_account', 'live');

        $this->fixtures->on('live')
                       ->edit('fund_account', '100000000000fa',
                              [
                                  'account_id' => $bankAccount->getId()
                              ]);

        // creating payout from a primary balance in queued state
        $payout = $this->createCustomerWalletPayout();

        $newBankAccount = $this->getDbLastEntity('bank_account', 'live');
        $this->assertEquals('PUNB0168510', $newBankAccount['ifsc_code']);
        $this->assertEquals('customer', $bankAccount['type']);
        $this->assertEquals('GHz4VlBkkiUBwh', $bankAccount['entity_id']);
    }

    public function testDashboardSummaryForPayoutsOnNonBankingBalance()
    {
        $this->liveSetUp();

        // creating payout from a primary balance in queued state
        $payout = $this->createCustomerWalletPayout();

        $this->fixtures->on('live')->edit('payout', $payout['id'], [
            'status'    => 'queued',
            'queued_at' => time()
        ]);

        // creating payout in pending state
        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->edit('banking_account', '1000000lcustba',
                              ['status' => 'activated']);

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $completeSummary = $this->startTest();

        return $completeSummary;
    }

    public function testEmailNotificationForPayoutPendingOnApproval()
    {
        Mail::fake();

        $this->liveSetUp();

        $bankingAccountAttributes = [
            'id'             => 'ABCde1234ABCde',
            'account_number' => '2224440041626998',
            'balance_id'     => $this->bankingBalance->getId(),
            'account_type'   => 'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->createPayoutWithWorkflowEntities(12345, '2224440041626905', Payout\Purpose::CASHBACK, 'FXMwu4HMK7ZT0C');
        $this->createPayoutWithWorkflowEntities(23456, '2224440041626905', Payout\Purpose::CASHBACK, 'FXMwu4HMK7ZT0D');
        $this->createPayoutWithWorkflowEntities(11111, '2224440041626905', Payout\Purpose::SALARY, 'FXMwu4HMK7ZT0F');
        $this->createPayoutWithWorkflowEntities(50000, '2224440041626905', Payout\Purpose::SALARY, 'FXMwu4HMK7ZT0G');
        $this->createPayoutWithWorkflowEntities(65432, '2224440041626905', Payout\Purpose::REFUND, 'FXMwu4HMK7ZT0H');

        $this->ba->cronAuth('live');

        $this->startTest();

        Mail::assertQueued(PendingApprovals::class, function($mail) {
            $this->assertArrayHasKey('user_id', $mail->viewData);

            $this->assertArrayHasKey('merchant_id', $mail->viewData);

            $this->assertArrayHasKey('email', $mail->viewData);

            $this->assertArrayHasKey('data', $mail->viewData);

            $mail->hasTo('merchantuser01@razorpay.com');

            $this->assertArrayHasKey('refund', $mail->viewData['data']);
            $this->assertArrayHasKey('cashback', $mail->viewData['data']);
            $this->assertArrayHasKey('salary', $mail->viewData['data']);

            $this->assertEquals(count($mail->viewData['data']['refund']), 1);
            $this->assertEquals(count($mail->viewData['data']['cashback']), 2);
            $this->assertEquals(count($mail->viewData['data']['salary']), 2);

            return true;
        });

    }

    public function testReminderNotificationForPayoutPendingOnApproval()
    {
        $this->liveSetUp();

        $bankingAccountAttributes = [
            'id'             => 'ABCde1234ABCde',
            'account_number' => '2224440041626998',
            'balance_id'     => $this->bankingBalance->getId(),
            'account_type'   => 'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->createPayoutWithWorkflowEntities(12345, '2224440041626905', Payout\Purpose::CASHBACK, 'FXMwu4HMK7ZT0C');
        $this->createPayoutWithWorkflowEntities(23456, '2224440041626905', Payout\Purpose::CASHBACK, 'FXMwu4HMK7ZT0D');
        $this->createPayoutWithWorkflowEntities(11111, '2224440041626905', Payout\Purpose::SALARY, 'FXMwu4HMK7ZT0F');
        $this->createPayoutWithWorkflowEntities(50000, '2224440041626905', Payout\Purpose::SALARY, 'FXMwu4HMK7ZT0G');
        $this->createPayoutWithWorkflowEntities(65432, '2224440041626905', Payout\Purpose::REFUND, 'FXMwu4HMK7ZT0H');

        $this->ba->cronAuth('live');

        $this->startTest();
    }

    public function createPayoutWithWorkflowEntities($amount, $account, $purpose, $workflowId)
    {
        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUb', // Should exist in the new WF service
                'created_at' => 1598967658
            ]);

        $p = $this->createPayoutWithWorkflow(
            [
                'account_number' => $account ?? '2224440041626905',
                'amount'         => $amount ?? 1000,
                'purpose'        => $purpose ?? 'refund',
                'status'         => 'pending'

            ],
            'rzp_live_TheLiveAuthKey');

        $we = $this->fixtures->on('live')->create('workflow_entity_map', ['entity_id' => substr($p['id'], 5), 'workflow_id' => $workflowId, 'entity_type' => 'payout',])->toArray();

        $ws = $this->fixtures->on('live')->create('workflow_state_map', ['workflow_id' => $we['workflow_id'], 'actor_type_value' => 'owner', 'status' => 'created'])->toArray();

    }

    public function testDashboardSummary()
    {
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $this->fixtures->on('live');
        $secondBankingBalance = $this->createDirectBankingBalance();

        // Creating 2 banking accounts. First for the existing bankingBalance and second for the secondBankingBalance

        $bankingAccountAttributes = [
            'id'             => 'ABCde1234ABCde',
            'account_number' => '2224440041626998',
            'balance_id'     => $this->bankingBalance->getId(),
            'account_type'   => 'nodal',
        ];

        $bankingAccount = $this->createBankingAccount($bankingAccountAttributes, 'live');

        $secondBankingAccountAttributes = [
            'id'             => 'DEcba4321DEcba',
            'account_number' => '2224440041626999',
            'balance_id'     => $secondBankingBalance->getId(),
            'account_type'   => 'current',
        ];

        $this->fixtures->on('live')->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $secondBankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626999',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $secondBankingAccount = $this->createBankingAccount($secondBankingAccountAttributes, 'live');

        // Create two queued payouts

        $firstQueuedPayoutAttributes = [
            'account_number'       => '2224440041626905',
            'amount'               => 20000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes, 'rzp_live_TheLiveAuthKey');

        $secondQueuedPayoutAttributes = [
            'account_number'       => '2224440041626906',
            'amount'               => 30000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($secondQueuedPayoutAttributes, 'rzp_live_TheLiveAuthKey');

        // Setup the payout workflow

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->createPayoutWithWorkflow(
            [
                'account_number' => '2224440041626905',
                'amount'         => 54321
            ],
            'rzp_live_TheLiveAuthKey');

        $this->createPayoutWithWorkflow(
            [
                'account_number' => '2224440041626906',
                'amount'         => 12345
            ],
            'rzp_live_TheLiveAuthKey');

        $merchantUser = $this->getDbEntity('merchant_user', ['role' => 'owner', 'product' => 'banking'], 'live')->toArray();

        $userId = $merchantUser['user_id'];

        $this->ba->proxyAuth('rzp_live_10000000000000', $userId);

        $completeSummary = $this->startTest();

        $firstBankingAccountId = $this->getDbEntity('banking_account',
                                                    ['account_number' => '2224440041626905'],
                                                    'live')->getPublicId();

        $secondBankingAccountId = $secondBankingAccount->getPublicId();

        $queuedSummaryFirstAccount   = $completeSummary[$firstBankingAccountId][Payout\Status::QUEUED];
        $pendingSummaryFirstAccount  = $completeSummary[$firstBankingAccountId][Payout\Status::PENDING];
        $queuedSummarySecondAccount  = $completeSummary[$secondBankingAccountId][Payout\Status::QUEUED];
        $pendingSummarySecondAccount = $completeSummary[$secondBankingAccountId][Payout\Status::PENDING];

        $this->assertEquals($queuedSummaryFirstAccount['low_balance']['count'], 1);
        $this->assertEquals($queuedSummaryFirstAccount['low_balance']['total_amount'], 20000099);
        $this->assertEquals($queuedSummaryFirstAccount['low_balance']['balance'], "10000000");

        $this->assertEquals($pendingSummaryFirstAccount['count'], 1);
        $this->assertEquals($pendingSummaryFirstAccount['total_amount'], 54321);

        $this->assertEquals($queuedSummarySecondAccount['low_balance']['count'], 1);
        $this->assertEquals($queuedSummarySecondAccount['low_balance']['total_amount'], 30000099);
        $this->assertEquals($queuedSummarySecondAccount['low_balance']['balance'], "10000000");

        $this->assertEquals($pendingSummarySecondAccount['count'], 1);
        $this->assertEquals($pendingSummarySecondAccount['total_amount'], 12345);
    }

    public function testOptimisedDashboardSummary()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::OPTIMISE_SUMMARY_API]);

        $this->testDashboardSummary();
    }

    /**
     * This test checks for 4 things:
     *      1. Creation of queued payouts
     *      2. Payouts remaining queued if low balance
     *      3. Payouts getting processed if enough balance
     *      4. Pagination in processing of queued payouts
     */
    public function testCreateAndProcessQueuedPayout()
    {
        $ledgerSnsPayloadArray = [];

        // since 2 queued payouts are initiated here
        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        // Setting the redis config as empty initially
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION => []]);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $currentBalance = $this->getDbLastEntity('balance');

        $response = $this->startTest();

        $newBalance = $this->getDbLastEntity('balance');

        // Since we created queued payouts, hence balance shouldn't change
        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNull($fta);

        // Create 2 more queued payouts
        $this->startTest();
        $this->startTest();

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 3 payouts in queued state.
        $this->assertEquals(3, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000003, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that there are still 3 payouts in queued state since there wasn't enough balance to process them
        $this->assertEquals(3, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000003, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance to process only one queued payout
        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $updatedSummary = $this->makePayoutSummaryRequest();

        // Assert that there is only one payout in queued state. The other one got processed.
        $this->assertEquals(2, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000002, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance to process only all queued payouts
        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 99000000]);

        // Set offset = 1 for this balance ID
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION => [
            $newBalance['id'] => 1
        ]]);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that only one payout got processed even though there was enough balance to process both.
        // This is because offset was set to 1.
        $this->assertEquals(1, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(10000001, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $offsetData = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION]);

        // Assert that offset has been set back to 0
        $this->assertEmpty($offsetData);

        $payoutsCreated = $this->getDbEntities('payout', ['status' => 'created']);

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1770', $ledgerRequestPayload['commission']);
            $this->assertEquals('270', $ledgerRequestPayload['tax']);
            $this->assertEquals('payout_initiated', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    /**
     *  The below test cases just checks that if the merchant has no credits
     *  then also if we enable this credits_new_flow feature on the merchant,
     *  the fees and tax will be calculated as expected, this is to just check
     *  that fees and tax logic works in normal scenario so later on we can
     *  just remove the flag and calculate fees and tax of payouts before
     *  itself
     */
    public function testCreateAndProcessQueuedPayoutWithNewCreditsFlow()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 2000]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 1900, 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        // Setting the redis config as empty initially
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION => []]);

        $balanceId = $this->bankingBalance->getId();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $currentBalance = $this->getDbLastEntity('balance');

        $response = $this->startTest();
        $payout   = $this->getDbLastEntity('payout');
        $this->assertEquals(0, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertNull($payout['pricing_rule_id']);

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);
        $this->assertEquals(1400, $creditTxnEntities[1]['credits_used']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);
        $this->assertEquals(-1400, $creditTxnEntities[3]['credits_used']);
        $this->assertEquals(4, count($creditTxnEntities));

        $newBalance = $this->getDbLastEntity('balance');

        // Since we created queued payouts, hence balance shouldn't change
        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNull($fta);

        $this->ba->privateAuth();

        // Create 2 more queued payouts
        $this->startTest();
        $payout = $this->getDbLastEntity('payout');
        $this->assertEquals('low_balance', $payout->getQueuedReason());
        $this->assertEquals(0, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $this->startTest();
        $payout = $this->getDbLastEntity('payout');
        $this->assertEquals('low_balance', $payout->getQueuedReason());
        $this->assertEquals(0, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 3 payouts in queued state.
        $this->assertEquals(3, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000003, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that there are still 3 payouts in queued state since there wasn't enough balance to process them
        $this->assertEquals(3, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000003, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance to process only one queued payout
        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $updatedSummary = $this->makePayoutSummaryRequest();

        // Assert that there is only one payout in queued state. The other one got processed.
        $this->assertEquals(2, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000002, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance to process only all queued payouts
        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 99000000]);

        // Set offset = 1 for this balance ID
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION => [
            $newBalance['id'] => 1
        ]]);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that only one payout got processed even though there was enough balance to process both.
        // This is because offset was set to 1.
        $this->assertEquals(1, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(10000001, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $offsetData = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION]);

        // Assert that offset has been set back to 0
        $this->assertEmpty($offsetData);

        $payoutsCreated = $this->getDbEntities('payout', ['status' => 'created']);

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('payout_initiated', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }

        $ledgerSnsPayloadArray[0]['additional_params'] = json_decode($ledgerSnsPayloadArray[0]['additional_params'], true);
        $ledgerSnsPayloadArray[1]['additional_params'] = json_decode($ledgerSnsPayloadArray[1]['additional_params'], true);

        // One payout gets processed via rewards whereas the other does not. Reward balance is 2000,
        // fees on first payout: 1500, fees on second payout: 1770
        $this->assertEquals('reward', $ledgerSnsPayloadArray[0]['additional_params']['fee_accounting']);
        $this->assertNotContains('fee_accounting', $ledgerSnsPayloadArray[1]['additional_params']);
    }

    public function testCreateQueuedPayout()
    {
        $balanceId = $this->bankingBalance->getId();

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

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);
        $this->assertEquals(true, $payoutDetails->getQueueIfLowBalanceFlag());

        $summary = $this->makePayoutSummaryRequest();

        $bankingAccountId = $bankingAccount->getPublicId();

        $this->assertEquals(2, $summary[$bankingAccountId]['queued']['low_balance']['count']);
        $this->assertEquals(20000002, $summary[$bankingAccountId]['queued']['low_balance']['total_amount']);

        $dispatchResponse = $this->dispatchQueuedPayoutsOld();

        $this->assertEquals(2, $dispatchResponse[$newBalance['id']]['total_payout_count']);
        $this->assertEquals(10000000, $dispatchResponse[$newBalance['id']]['balance_remaining']);
        $this->assertEquals(10000000, $dispatchResponse[$newBalance['id']]['original_balance']);
        $this->assertEquals(0, $dispatchResponse[$newBalance['id']]['dispatched_payout_count']);
        $this->assertEquals(0, $dispatchResponse[$newBalance['id']]['dispatched_payout_amount']);

        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayoutsOld();

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

    public function testProcessQueuedPayoutWhereMerchantBlacklisted()
    {
        $secondBankingBalance = $this->createDirectBankingBalance();

        $balanceId1 = $this->bankingBalance->getId();
        $balanceId2 = $secondBankingBalance['id'];

        // Creating 2 banking accounts. First for the existing bankingBalance and second for the secondBankingBalance

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $secondBankingAccountAttributes = [
            'id'             => 'DEcba4321DEcba',
            'account_number' => '2224440041626906',
            'balance_id'     => $balanceId2,
            'account_type'   => 'current',
        ];

        $secondBankingAccount = $this->createBankingAccount($secondBankingAccountAttributes);

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $secondBankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626906',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->fixtures->create(
            'counter',
            [
                'balance_id'   => $secondBankingBalance->getId(),
                'account_type' => $secondBankingBalance->getAccountType(),
            ]
        );

        // Create two queued payouts

        $firstQueuedPayoutAttributes = [
            'account_number'       => '2224440041626905',
            'amount'               => 20000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes);

        $secondQueuedPayoutAttributes = [
            'account_number'       => '2224440041626906',
            'amount'               => 30000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($secondQueuedPayoutAttributes);

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 1 payout each in queued state for both balances.
        $this->assertEquals(1, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000099, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);
        $this->assertEquals(1, $summary1[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000099, $summary1[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance for both balanceIds so that the payouts can go through
        $this->fixtures->edit('balance', $balanceId1, [
            'balance'    => 50000099,
            'updated_at' => Carbon::now()->getTimestamp()
        ]);

        $this->fixtures->edit('balance', $balanceId2, [
            'balance'    => 50000099,
            'updated_at' => Carbon::now()->getTimestamp()
        ]);

        $dispatchResponse = $this->dispatchQueuedPayoutsWithBlacklist($balanceId1);

        // Assert that we only attempted processing the queued payout for balance2. Since Balance 1 was blacklisted
        $this->assertEquals(1, count($dispatchResponse));
        $this->assertEquals($balanceId2, $dispatchResponse['balance_id_list'][0]);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that there are 1 payout each in queued state for both balances.
        $this->assertEquals(1, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000099, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);
        $this->assertEquals(0, $summary2[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['count']);
        $this->assertEquals(0, $summary2[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['total_amount']);
    }


    public function testProcessQueuedPayoutWhereMerchantWhitelisted()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $secondBankingBalance = $this->createDirectBankingBalance();

        $balanceId1 = $this->bankingBalance->getId();
        $balanceId2 = $secondBankingBalance['id'];

        // Creating 2 banking accounts. First for the existing bankingBalance and second for the secondBankingBalance

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $secondBankingAccountAttributes = [
            'id'             => 'DEcba4321DEcba',
            'account_number' => '2224440041626906',
            'balance_id'     => $balanceId2,
            'account_type'   => 'current',
        ];

        $secondBankingAccount = $this->createBankingAccount($secondBankingAccountAttributes);

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $secondBankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626906',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        // Create two queued payouts

        $firstQueuedPayoutAttributes = [
            'account_number'       => '2224440041626905',
            'amount'               => 20000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes);

        $secondQueuedPayoutAttributes = [
            'account_number'       => '2224440041626906',
            'amount'               => 30000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($secondQueuedPayoutAttributes);

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 1 payout each in queued state for both balances.
        $this->assertEquals(1, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000099, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);
        $this->assertEquals(1, $summary1[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000099, $summary1[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance for both balanceIds so that the payouts can go through
        $this->fixtures->edit('balance', $balanceId1, [
            'balance'    => 50000099,
            'updated_at' => Carbon::now()->getTimestamp()
        ]);

        $this->fixtures->edit('balance', $balanceId2, [
            'balance'    => 50000099,
            'updated_at' => Carbon::now()->getTimestamp()
        ]);

        $dispatchResponse = $this->dispatchQueuedPayoutsWithWhitelist($balanceId1);

        // Assert that we only attempted processing the queued payout for balance1. Since only Balance 1 is whitelisted
        $this->assertEquals(1, count($dispatchResponse));
        $this->assertEquals($balanceId1, $dispatchResponse['balance_id_list'][0]);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that there is 1 payout in queued state for second balance. The payout for first balance got processed
        $this->assertEquals(0, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['count']);
        $this->assertEquals(0, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['total_amount']);
        $this->assertEquals(1, $summary2[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000099, $summary2[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $payoutsCreated = $this->getDbEntities('payout', ['status' => 'created']);

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1770', $ledgerRequestPayload['commission']);
            $this->assertEquals('270', $ledgerRequestPayload['tax']);
            $this->assertEquals('payout_initiated', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    /**
     * Assert that queued payouts get processed even when `balance = payout amount` if the merchant
     * has enough free payouts
     */
    public function testProcessQueuedPayoutWithFreePayouts()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $secondBankingBalance = $this->createDirectBankingBalance();

        $balanceId1 = $this->bankingBalance->getId();
        $balanceId2 = $secondBankingBalance['id'];

        // Creating 2 banking accounts. First for the existing bankingBalance and second for the secondBankingBalance

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $secondBankingAccountAttributes = [
            'id'             => 'DEcba4321DEcba',
            'account_number' => '2224440041626906',
            'balance_id'     => $balanceId2,
            'account_type'   => 'current',
        ];

        $secondBankingAccount = $this->createBankingAccount($secondBankingAccountAttributes);

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $secondBankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626906',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        // Create two queued payouts

        $firstQueuedPayoutAttributes = [
            'account_number'       => '2224440041626905',
            'amount'               => 20000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes);

        $secondQueuedPayoutAttributes = [
            'account_number'       => '2224440041626906',
            'amount'               => 30000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($secondQueuedPayoutAttributes);

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 1 payout each in queued state for both balances.
        $this->assertEquals(1, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000099, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);
        $this->assertEquals(1, $summary1[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000099, $summary1[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance for both balanceIds so that the payout amounts = balance for both balances.
        $this->fixtures->edit('balance', $balanceId1, [
            'balance'    => 20000099,
            'updated_at' => Carbon::now()->getTimestamp()
        ]);

        $this->fixtures->edit('balance', $balanceId2, [
            'balance'    => 30000099,
            'updated_at' => Carbon::now()->getTimestamp()
        ]);

        // Update both counters to 300
        $counter1 = $this->getDbEntity('counter', ['balance_id' => $balanceId1]);
        $counter2 = $this->getDbEntity('counter', ['balance_id' => $balanceId2]);
        $this->fixtures->edit('counter', $counter1->getId(), ['free_payouts_consumed' => 300]);
        $this->fixtures->edit('counter', $counter2->getId(), ['free_payouts_consumed' => 300]);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        // Assert that we attempted processing the queued payout both balances.
        $this->assertEquals(2, count($dispatchResponse['balance_id_list']));
        $this->assertEquals($balanceId1, $dispatchResponse['balance_id_list'][0]);
        $this->assertEquals($balanceId2, $dispatchResponse['balance_id_list'][1]);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that the current account payout went through, because we do not calculate or deduct fees
        // at time of processing. Also assert that the shared account payout is still queued since
        // `balance = payout amount` but there are no free payouts available.
        $this->assertEquals(1, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000099, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);
        $this->assertEquals(0, $summary2[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['count']);
        $this->assertEquals(0, $summary2[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['total_amount']);

        // Update the counter to 299, so that the queued payout is considered as free payout during processing.
        $this->fixtures->edit('counter', $counter1->getId(), ['free_payouts_consumed' => 299]);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        // Assert that we attempted processing the queued payout for only first balance.
        $this->assertEquals(1, count($dispatchResponse['balance_id_list']));
        $this->assertEquals($balanceId1, $dispatchResponse['balance_id_list'][0]);

        $summary3 = $this->makePayoutSummaryRequest();

        // Assert that the payout which wasn't getting processed in the previous run gets processed now.
        $this->assertEquals(0, $summary3[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['count']);
        $this->assertEquals(0, $summary3[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['total_amount']);
        $this->assertEquals(0, $summary3[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['count']);
        $this->assertEquals(0, $summary3[$secondBankingAccount->getPublicId()][Payout\Status::QUEUED]['total_amount']);

        // Assert that we have now consumed another free payout and counter has incremented to 300.
        $updatedCounter1 = $this->getDbEntity('counter', ['balance_id' => $balanceId1]);
        $this->assertEquals(300, $updatedCounter1['free_payouts_consumed']);

        $payoutsCreated = $this->getDbEntities('payout', ['status' => 'created']);

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            // Commission and tax are zero because the payout was a free payout
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('payout_initiated', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testCancelQueuedPayoutProxyAuth()
    {
        $this->testCreateQueuedPayout();

        $queuedPayout = $this->getDbLastEntity('payout');

        $cancellationUser = $this->getDbEntityById('user', 'MerchantUser01')->toArrayPublic();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $queuedPayout->getPublicId() . '/cancel';

        $testData['response']['content']['cancellation_user_id'] = 'MerchantUser01';
        $testData['response']['content']['cancellation_user']    = $cancellationUser;

        $this->ba->proxyAuth();

        $this->startTest();

        $cancelledPayout = $this->getDbLastEntity('payout');

        // Assert that payout got cancelled
        $this->assertEquals(Status::CANCELLED, $cancelledPayout['status']);
        $this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);

        // Assert that payout has the correct cancellation user id as well.
        $this->assertEquals('MerchantUser01', $cancelledPayout['cancellation_user_id']);
    }

    public function testPayoutSummaryViaIciciCaBalance()
    {
        $balance = $this->fixtures->create('balance', [
            'merchant_id'    => '10000000000000',
            'account_type'   => 'direct',
            'type'           => 'banking',
            'channel'        => 'icici',
            'balance'        => 10000000,
            'account_number' => '9177278012',
        ]);

        $this->fixtures->create('payout', [
            'id'              => 'DuuYxmO7Yegu3x',
            'status'          => 'processed',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'balance_id'      => $balance->getId(),
        ]);

        $summary = $this->makePayoutSummaryRequest();

        $this->assertTrue(in_array('bacc_30000000000888', array_keys($summary)) === true);

        $this->assertTrue(isset($summary['bacc_30000000000888']['queued']) === true);

        $this->assertTrue(isset($summary['bacc_30000000000888']['pending']) === true);

        $this->assertTrue(isset($summary['bacc_30000000000888']['scheduled']) === true);
    }

    public function testCancelQueuedPayoutPrivateAuth()
    {
        $this->testCreateQueuedPayout();

        $queuedPayout = $this->getDbLastEntity('payout');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $queuedPayout->getPublicId() . '/cancel';

        $this->app->forgetInstance('basicauth');

        $this->ba->privateAuth();

        $this->startTest();

        $cancelledPayout = $this->getDbLastEntity('payout');

        // Assert that payout got cancelled
        $this->assertEquals(Status::CANCELLED, $cancelledPayout['status']);
        $this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);

        // Assert that payout has the correct cancellation user id as well.
        $this->assertEquals(null, $cancelledPayout['cancellation_user_id']);
    }

    public function testCancelQueuedPayoutWithComments()
    {
        $this->testCreateQueuedPayout();

        $queuedPayout = $this->getDbLastEntity('payout');

        $this->assertEquals('low_balance', $queuedPayout->getQueuedReason());

        $cancellationUser = $this->getDbEntityById('user', 'MerchantUser01')->toArrayPublic();

        $userComment = "Payout cancelled by Mehul";

        $testData                                  = &$this->testData[__FUNCTION__];
        $testData['request']['url']                = '/payouts/' . $queuedPayout->getPublicId() . '/cancel';
        $testData['request']['content']['remarks'] = $userComment;

        $testData['response']['content']['remarks']              = $userComment;
        $testData['response']['content']['cancellation_user_id'] = 'MerchantUser01';
        $testData['response']['content']['cancellation_user']    = $cancellationUser;

        $this->ba->proxyAuth();

        $this->startTest();

        $cancelledPayout = $this->getDbLastEntity('payout');

        // Assert that payout got cancelled with comments
        $this->assertEquals(Status::CANCELLED, $cancelledPayout['status']);
        $this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);
        $this->assertEquals($userComment, $cancelledPayout['remarks']);

        // Assert that payout has the correct cancellation user id as well.
        $this->assertEquals('MerchantUser01', $cancelledPayout['cancellation_user_id']);
    }

    public function testCreatePayoutToInactiveFundAccount()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'active' => 0]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000001fa',
                'source_id'    => '1000000contact',
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
                'account_type' => 'bank_account',
                'account_id'   => '100000000000ba',
                'active'       => 1,
            ]);

        $this->fixtures->create('bank_account', ['id' => '100000000000ba']);

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

    // Todo: This test case will be migrated to composite API flow once tokenised saved card flow is live
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

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->assertNotEquals($fundAccount['card']['name'], $fundAccount->contact['name']);
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
        $testData                                = $this->testData['testCreatePayoutWithOtp'];
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

    // Create Undoable payout testcase
    public function testCreateUndoablePayoutWithOtp()
    {
        $testData                                = $this->testData['testCreateUndoablePayoutWithOtp'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;
        $this->setMockRazorxTreatment(['rx_undo_payout_feature' => 'on', 'imps_mode_payout_filter' => 'control']);
        $this->fixtures->create('merchant_attribute',
                                [
                                    'merchant_id' => '10000000000000',
                                    'product'     => 'banking',
                                    'group'       => 'x_merchant_preferences',
                                    'type'        => 'undo_payouts',
                                    'value'       => 'true'
                                ]);
        $this->ba->proxyAuth();
        $this->startTest();
        $payout = $this->getLastEntity('payout_outbox', true);

        return $payout;
    }

    public function testCreateUndoablePayoutWithOtpInMobile()
    {
        $testData                                = $this->testData['testCreateUndoablePayoutWithOtpInMobile'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;
        $this->setMockRazorxTreatment(['rx_undo_payout_feature' => 'on', 'imps_mode_payout_filter' => 'control']);
        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => '10000000000000',
                'product'     => 'banking',
                'group'       => 'x_merchant_preferences',
                'type'        => 'undo_payouts',
                'value'       => 'true'
            ]);

        $this->ba->basicAuth('rzp_test_10000000000000', 'RANDOM_GRAPHQL_SECRET');
        $this->startTest();
        $payout = $this->getLastEntity('payout_outbox', true);

        return $payout;
    }

    public function testCreatePayoutWithOtpAndUndoPayoutPreferenceFalse()
    {
        $testData                                = $this->testData['testCreatePayoutWithOtpAndUndoPayoutPreferenceFalse'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;
        $this->setMockRazorxTreatment(['rx_undo_payout_feature' => 'on', 'imps_mode_payout_filter' => 'control']);
        $this->fixtures->create('merchant_attribute',
                                [
                                    'merchant_id' => '10000000000000',
                                    'product'     => 'banking',
                                    'group'       => 'x_merchant_preferences',
                                    'type'        => 'undo_payouts',
                                    'value'       => 'false'
                                ]);
        $this->ba->proxyAuth();
        $this->startTest();
        $payout = $this->getLastEntity('payout', true);
        $this->assertEquals("MerchantUser01", $payout['user_id']);
    }

    // Undo payout testcases
    public function testUndoPayout()
    {
        $payout = $this->testCreateUndoablePayoutWithOtp();

        $testData                   = $this->testData['testUndoPayout'];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/undo';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $response = $this->startTest();
        $this->assertEquals(true, $response['deleted']);
    }

    // this is a helper func. Not a standalone test
    private function testUndoPayoutWithId($id)
    {
        $testData                   = $this->testData['testUndoPayout'];
        $testData['request']['url'] = '/payouts/' . $id . '/undo';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $response = $this->startTest();
        $this->assertEquals(true, $response['deleted']);
    }

    public function testUndoOnSamePayoutMultipleTimes()
    {
        $payout = $this->testCreateUndoablePayoutWithOtp();

        // Undo the payout for the 1st time
        $this->testUndoPayoutWithId($payout['id']);

        // trying again for the same id - should throw exception
        $testData                   = $this->testData['testUndoOnSamePayoutMultipleTimes'];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/undo';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testUndoPayoutWithInvalidId()
    {
        $this->setMockRazorxTreatment(['rx_undo_payout_feature' => 'on', 'imps_mode_payout_filter' => 'control']);

        $testData                   = $this->testData['testUndoPayoutWithInvalidId'];
        $testData['request']['url'] = '/payouts/' . 123 . '/undo';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testUndoPayoutWithValidIdPostExpiryTime()
    {
        $payout = $this->testCreateUndoablePayoutWithOtp();
        sleep(PayoutOutboxConstants::DEFAULT_PAYOUT_OUTBOX_EXPIRY_IN_SECONDS + 2);
        $testData                   = $this->testData['testUndoPayoutWithValidIdPostExpiryTime'];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/undo';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $this->startTest();
    }

    // Resume payout testcases
    public function testResumePayout()
    {
        $payout = $this->testCreateUndoablePayoutWithOtp();

        $testData                   = $this->testData['testResumePayout'];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/resume';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $this->startTest();
    }

    // this is a helper func. Not a standalone test
    private function testResumePayoutWithId($id)
    {
        $testData                   = $this->testData['testResumePayout'];
        $testData['request']['url'] = '/payouts/' . $id . '/resume';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testResumeOnSamePayoutMultipleTimes()
    {
        $payout = $this->testCreateUndoablePayoutWithOtp();

        // Resume the payout for the 1st time
        $this->testResumePayoutWithId($payout['id']);

        // trying again for the same id - should throw exception
        $testData                   = $this->testData['testResumeOnSamePayoutMultipleTimes'];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/resume';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testGetOrphanPayouts() {
        $testData                                = $this->testData['testGetOrphanPayouts'];
        $testData['request']['url']              = '/payout_outbox/orphan_payouts/count';

        $this->testData[__FUNCTION__] = $testData;

        $this->createOrphanedPayout([]);
        $this->ba->cronAuth();

        $response = $this->startTest();
        $this->assertEquals(1, $response['orphaned_payout_count']);
    }


    public function testGetOrphanPayoutsOutsideTimeRange() {
        $testData                                = $this->testData['testGetOrphanPayoutsOutsideTimeRange'];
        $testData['request']['url']              = '/payout_outbox/orphan_payouts/count';

        $this->testData[__FUNCTION__] = $testData;

        // since this payout is orphaned before defined range this will not show in the response
        $this->createOrphanedPayout(['expires_at' => Carbon::now()->subMinutes(PayoutOutboxConstants::ORPHAN_PAYOUT_RANGE_IN_MINUTES+1)->getTimestamp()]);
        $this->ba->cronAuth();

        $response = $this->startTest();
        $this->assertEquals(0, $response['orphaned_payout_count']);
    }

    public function testDeleteOrphanPayouts() {
        $testData                                = $this->testData['testDeleteOrphanPayouts'];
        $testData['request']['url']              = '/payout_outbox/orphan_payouts/delete';

        $this->testData[__FUNCTION__] = $testData;

        $adminForTest = $this->prepareAdminForPayoutOutboxDelete('test');

        $this->app['config']->set('database.default', 'test');

        $this->createOrphanedPayout([]);

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id' => $adminForTest->getId(),
            'token'    => Hash::make('ThisIsATokenForTest'),
        ]);
        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        $this->startTest();
    }

    public function prepareAdminForPayoutOutboxDelete($mode)
    {
        $admin = $this->fixtures->on($mode)->create('admin', [
            'id'     => 'poutRejtAdmnId',
            'org_id' => Org::RZP_ORG,
            'name'   => 'Payout Outbox Delete Admin'
        ]);

        $role = $this->fixtures->on($mode)->create('role', [
            'id'     => 'poutRejtRoleId',
            'org_id' => '100000razorpay',
            'name'   => 'Payout Outbox Delete Admin',
        ]);

        $permission = $this->fixtures->on($mode)->create('permission', [
            'name' => 'manage_undo_payout'
        ]);

        $role->permissions()->attach($permission->getId());

        $admin->roles()->attach($role);

        return $admin;
    }

    // TODO: Move this to fixtures
    public function createOrphanedPayout(array $attributes, $mode = 'test')
    {
        $request_type = $attributes['request_type'] ?? 'payouts';

        $source = $attributes['source'] ?? 'dashboard';

        $product = $attributes['product'] ?? 'primary';

        $created_at = $attributes['created_at'] ?? Carbon::now()->getTimestamp();

        $expires_at = $attributes['expires_at'] ?? Carbon::now()->getTimestamp();

        DB::connection($mode)->table('payout_outbox')
            ->insert([
                'id' => '123',
                'merchant_id'   => '10000000000000',
                'user_id'       => 'MerchantUser01',
                'payout_data'   => '{"mode": "amazonpay", "notes": [], "amount": 100, "origin": "dashboard", "purpose": "testing 102", "currency": "INR", "narration": "Aman Fund Transfer", "balance_id": "H1tcrSbxUb7TJi", "fund_account_id": "fa_IzpdqLUJS2Kzdt", "queue_if_low_balance": 1}',
                'product'       => $product,
                'source'        => $source,
                'request_type'  => $request_type,
                'created_at'    => $created_at,
                'expires_at'    => $expires_at,
            ]);
    }

    public function testBalancesWithBearerAuth()
    {
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $client = factory(Client\Entity::class)->create(['environment' => 'prod']);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['rx_read_write', 'read_write'], 'mode' => 'live', 'client_id' => $client->getId()], 'prod');

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::PUBLIC_SETTERS_VIA_OAUTH]);

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000', 'contact_mobile' => 9999999999], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '20000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'owner'
                                                         ], 'live');

        $this->testData[__FUNCTION__]['request']['url'] = '/balances';

        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() + 12000]);

        $this->ba->oauthBearerAuth($accessToken);

        $expectedProperties = [
            'error_code' => 'SUCCESS',
            'properties' => [
                'merchant_id' => '10000000000000',
                'request'     => 'balance_fetch_multiple',
                'user_id'     => '20000000000000',
                'user_role'   => 'owner',
                'channel'     => 'slack_app',
                'filters'     => [
                    'type' => 'banking',
                ]
            ]
        ];

        $this->verifyBalanceEvent($expectedProperties);

        $this->startTest();
    }

    public function testApprovePayoutWithBearerAuth()
    {
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $client = factory(Client\Entity::class)->create(['environment' => 'prod']);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['rx_read_write', 'read_write'], 'mode' => 'live', 'client_id' => $client->getId()], 'prod');

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::PUBLIC_SETTERS_VIA_OAUTH]);

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000', 'contact_mobile' => 9999999999], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '20000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'owner'
                                                         ], 'live');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->ba->oauthBearerAuth($accessToken);

        $expectedProperties = [
            'payout'     => [
                'status'     => 'pending',
                'created_by' => 'api_user',
            ],
            'merchant'   => [
                'id'   => '10000000000000',
                'name' => 'Test Merchant',
            ],
            'error_code' => 'SUCCESS',
            'properties' => [
                'merchant_id' => '10000000000000',
                'request'     => 'payout_approve',
                'user_id'     => '20000000000000',
                'user_role'   => 'owner',
                'channel'     => 'slack_app',
            ]
        ];

        $this->verifyPayoutsEvent($expectedProperties);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $firstApprovalResponse = $this->startTest();

        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals('pending', $firstApprovalResponse['status']);
        $this->assertEquals('Approving', $firstActionChecker['user_comment']);
        $this->assertEquals(true, $firstActionChecker['approved']);
    }

    public function testRejectPayoutWithBearerAuth()
    {
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $client = factory(Client\Entity::class)->create(['environment' => 'prod']);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['rx_read_write', 'read_write'], 'mode' => 'live', 'client_id' => $client->getId()], 'prod');

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::PUBLIC_SETTERS_VIA_OAUTH]);

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000', 'contact_mobile' => 9999999999], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '20000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'owner'
                                                         ], 'live');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->ba->oauthBearerAuth($accessToken);

        $expectedProperties = [
            'payout'     => [
                'status'     => 'pending',
                'created_by' => 'api_user',
            ],
            'merchant'   => [
                'id'   => '10000000000000',
                'name' => 'Test Merchant',
            ],
            'error_code' => 'SUCCESS',
            'properties' => [
                'merchant_id' => '10000000000000',
                'request'     => 'payout_reject',
                'user_id'     => '20000000000000',
                'user_role'   => 'owner',
                'channel'     => 'slack_app',
            ]
        ];

        $this->verifyPayoutsEvent($expectedProperties);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $firstApprovalResponse = $this->startTest();

        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals('rejected', $firstApprovalResponse['status']);
        $this->assertEquals('Rejecting', $firstActionChecker['user_comment']);
        $this->assertEquals(false, $firstActionChecker['approved']);
    }

    public function testApprovePayoutInAppleWatchWithBearerAuth()
    {
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $client = factory(Client\Entity::class)->create(['environment' => 'prod']);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['apple_watch_read_write'], 'mode' => 'live', 'client_id' => $client->getId()], 'prod');

        $this->fixtures->feature->create([
                                             Feature\Entity::ENTITY_TYPE => Feature\Constants::APPLICATION,
                                             Feature\Entity::ENTITY_ID   => $client->application_id,
                                             Feature\Entity::NAME        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH
                                         ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000', 'contact_mobile' => 9999999999], 'owner', 'live');

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '70000000000000', 'contact_mobile' => 9999999999], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '20000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'owner'
                                                         ], 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '70000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'admin'
                                                         ], 'live');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->ba->oauthBearerAuth($accessToken);

        $expectedProperties = [
            'payout'     => [
                'status'     => 'pending',
                'created_by' => 'api_user',
            ],
            'merchant'   => [
                'id'   => '10000000000000',
                'name' => 'Test Merchant',
            ],
            'error_code' => 'SUCCESS',
            'properties' => [
                'merchant_id' => '10000000000000',
                'request'     => 'payout_approve',
                'user_role'   => 'owner',
                'channel'     => 'apple_watch',
            ]
        ];

        $this->verifyPayoutsEvent($expectedProperties);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $firstApprovalResponse = $this->startTest();

        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals('pending', $firstApprovalResponse['status']);
        $this->assertEquals('Approving', $firstActionChecker['user_comment']);
        $this->assertEquals(true, $firstActionChecker['approved']);
    }

    public function testRejectPayoutInAppleWatchWithBearerAuth()
    {
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $client = factory(Client\Entity::class)->create(['environment' => 'prod']);

        $this->fixtures->feature->create([
                                             Feature\Entity::ENTITY_TYPE => Feature\Constants::APPLICATION,
                                             Feature\Entity::ENTITY_ID   => $client->application_id,
                                             Feature\Entity::NAME        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH
                                         ]);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['apple_watch_read_write'], 'mode' => 'live', 'client_id' => $client->getId()], 'prod');

        $this->fixtures->feature->create([
                                             'entity_type' => 'merchant', 'entity_id' => '10000000000000', 'name' => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000', 'contact_mobile' => 9999999999], 'owner', 'live');

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '70000000000000', 'contact_mobile' => 9999999999], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '20000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'owner'
                                                         ], 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '70000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'admin'
                                                         ], 'live');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->ba->oauthBearerAuth($accessToken);

        $expectedProperties = [
            'payout'     => [
                'status'     => 'pending',
                'created_by' => 'api_user',
            ],
            'merchant'   => [
                'id'   => '10000000000000',
                'name' => 'Test Merchant',
            ],
            'error_code' => 'SUCCESS',
            'properties' => [
                'merchant_id' => '10000000000000',
                'request'     => 'payout_reject',
                'user_role'   => 'owner',
                'channel'     => 'apple_watch',
            ]
        ];

        $this->verifyPayoutsEvent($expectedProperties);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $firstApprovalResponse = $this->startTest();

        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals('rejected', $firstApprovalResponse['status']);
        $this->assertEquals('Rejecting', $firstActionChecker['user_comment']);
        $this->assertEquals(false, $firstActionChecker['approved']);
    }

    public function testGetPayoutsWithBearerAuth()
    {
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $user = $this->fixtures->on('live')->create('user');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $p = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->edit('payout', $p['id'], ['user_id' => $user['id']]);

        $client = factory(Client\Entity::class)->create(['environment' => 'prod']);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['rx_read_write', 'read_write'], 'mode' => 'live', 'client_id' => $client->getId()], 'prod');

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::PUBLIC_SETTERS_VIA_OAUTH]);

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000', 'contact_mobile' => 9999999999]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '20000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'owner'
                                                         ], 'live');

        $expectedProperties = [
            'error_code' => 'SUCCESS',
            'properties' => [
                'merchant_id' => '10000000000000',
                'request'     => 'payout_fetch_multiple',
                'user_id'     => '20000000000000',
                'user_role'   => 'owner',
                'channel'     => 'slack_app',
                'filters'     => [
                    'product' => 'banking',
                    'count'   => '10',
                    'expand'  => [
                        0 => 'fund_account.contact',
                        1 => 'user',
                    ]
                ]
            ]
        ];

        $this->verifyPayoutsEvent($expectedProperties);

        $this->ba->oauthBearerAuth($accessToken);

        $payout = $this->startTest();

        $this->assertPassport();
        $this->assertPassportKeyExists('oauth.client_id');
        $this->assertPassportKeyExists('oauth.app_id');
    }

    public function testUnauthorisedAccessToRazorpayXResourcesWithOauth()
    {
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $user = $this->fixtures->on('live')->create('user');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $p = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->edit('payout', $p['id'], ['user_id' => $user['id']]);

        $client = factory(Client\Entity::class)->create(['environment' => 'prod']);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['rx_read_write', 'read_write'], 'mode' => 'live', 'client_id' => $client->getId()], 'prod');

        $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $client->application_id,
            'entity_type' => 'application',
            'name'        => Feature\Constants::PUBLIC_SETTERS_VIA_OAUTH]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000', 'contact_mobile' => 9999999999]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => '20000000000000',
                                                             'merchant_id' => '10000000000000',
                                                             'product'     => 'banking',
                                                             'role'        => 'owner'
                                                         ], 'live');

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testCreatePayoutWithOtpBearerAuth()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['read_write', 'rx_read_write']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000', 'contact_mobile' => 9999999999]);

        $this->fixtures->create('feature', [
            'entity_id'   => '10000000000000',
            'entity_type' => 'application',
            'name'        => Feature\Constants::PUBLIC_SETTERS_VIA_OAUTH]);

        $this->fixtures->create('feature', [
            'entity_id'   => '10000000000000',
            'entity_type' => 'application',
            'name'        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH]);

        $testData                                = $this->testData[__FUNCTION__];
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;
        $this->startTest();

        $this->assertPassport();
        $this->assertPassportKeyExists('oauth.client_id');
        $this->assertPassportKeyExists('oauth.app_id');

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals("20000000000000", $payout['user_id']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
    }

    public function testCreatePayoutWithInvalidOtp()
    {
        $testData                                = $this->testData['testCreatePayout'];
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
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData                   = &$this->testData[__FUNCTION__];
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

        $payout = $this->getDbLastEntity('payout', 'live')->toArrayPublic();

        $this->assertEquals(Status::PROCESSING, $payout['status']);
    }

    public function testCreateAndProcessPayoutOnLiveModeWhenLedgerFeatureEnabled()
    {
        $ledgerSnsPayloadArray = [];

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_JOURNAL_WRITES]);

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $this->liveSetUp();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->createQueuedOrPendingPayout([], 'rzp_live_TheLiveAuthKey');

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->updateFtaAndSource($payout['id'], 'processed', '12341234', 'live');

        $payoutCreated = $this->getDbLastEntity('payout', 'live');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'payout_initiated',
            'payout_processed'
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('live', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutCreated->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }

        //
        // Assertions for fts_fund_account_id and fts_account_type
        //

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);

        // Not passed in payout initiated payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);

        // Passed in payout processed payload, and is equal to what is being sent by FTS
        $this->assertEquals('111111111', $ledgerSnsPayloadArray[1]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('current', $ledgerSnsPayloadArray[1]['identifiers']['fts_account_type']);
    }

    public function testPayoutRejectWhenWorkflowEdit()
    {
        $this->setupRedisMock();

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->disableWorkflowMocks();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $response = $this->startTest();

        $this->assertArrayHasKey(Error::STEP, $response['error']);

        $this->assertArrayHasKey(Error::METADATA, $response['error']);
    }

    public function testApprovePayoutWithoutComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData                   = &$this->testData[__FUNCTION__];
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

        $testData                   = &$this->testData[__FUNCTION__];
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

    public function testApprovePayoutWithNewWorkflowService()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $mock = $this->createMetricsMock();

        $mock->method('histogram')
             ->will($this->returnCallback(function(string $metric, float $times, array $dimensions = []) {
                 if ($metric === WorkflowService::WORKFLOW_SERVICE_REQUEST_MILLISECONDS)
                 {
                     $this->assertEquals(100, $times);
                     $this->assertEquals([], $dimensions);
                 }

                 return true;
             }));

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUb', // Should exist in the new WF service
                'created_at' => 1598967658
            ]);

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUc', // Should exist in the new WF service
                'created_at' => 1598967657
            ]);

        $payout = $this->createPayoutWithWorkflow([
                                                      'notes' => [
                                                          "random_key1" => "Hello",
                                                          "random_key2" => "Hi"
                                                      ]
                                                  ], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $this->startTest();
    }

    public function testRejectPayoutWithNewWorkflowService()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',  // Should exist in the new WF service
            ]);

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->startTest();
    }

    public function testRejectPayoutWithRejectCommentInWebhookWithWFS()
    {
        $this->liveSetUp();

        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'on', // to enable new WFS
            'on',
            'on',
            'on'
        );

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',  // Should exist in the new WF service
            ]);

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        $this->ba->appAuthLive($this->config['applications.workflows.secret']);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts_internal/' . $payout["id"] . '/reject';

        $this->mockWFS(substr($payout["id"], 5), $testData['request']['content']['user_comment']);

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutWithCommentEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        $this->startTest();
    }

    public function testRejectPayoutWithRejectCommentInWebhookWithoutCommentWithWFS()
    {
        $this->liveSetUp();

        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'on', // to enable new WFS
            'on',
            'on',
            'on'
        );

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',  // Should exist in the new WF service
            ]);

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        $this->ba->appAuthLive($this->config['applications.workflows.secret']);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts_internal/' . $payout["id"] . '/reject';

        $this->mockWFS(substr($payout["id"], 5), $testData['request']['content']['user_comment'] ?? "null");

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutWithoutCommentInWebhookEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        $this->startTest();
    }

    public function testRejectPayoutWithRejectCommentInWebhook()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment(
            'yesbank',
            'on',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'on'
        );

        // Reject with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutWithCommentEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');

        $this->assertEquals(false, $actionChecker['approved']);
        $this->assertEquals('Rejecting', $actionChecker['user_comment']);
    }

    public function testRejectPayoutWithRejectCommentInWebhookWithoutComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment(
            'yesbank',
            'on',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'on'
        );

        // Reject with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutWithoutCommentInWebhookEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');

        $this->assertEquals(false, $actionChecker['approved']);
        $this->assertEquals(null, $actionChecker['user_comment']);
    }

    public function testApprovePayoutCallbackFromNWFS()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',
            ]);

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->appAuthLive($this->config['applications.workflows.secret']);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts_internal/' . $payout["id"] . '/approve';

        $this->startTest();
    }

    public function testRejectPayoutCallbackFromNWFS()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',
            ]);

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->ba->appAuthLive($this->config['applications.workflows.secret']);

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts_internal/' . $payout["id"] . '/reject';

        $this->startTest();
    }

    public function testRejectPayoutCallbackFromNWFSTwice()
    {
        $this->testRejectPayoutCallbackFromNWFS();

        $payout = $this->getDbLastEntity('payout', 'live');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts_internal/pout_' . $payout->getId() . '/reject';

        $this->startTest();
    }

    public function testGetWorkflowFromNWFS()
    {
        $this->markTestSkipped("No route");

        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',
            ]);

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/history';

        $this->startTest();
    }

    public function testBulkRejectPayoutWithAdminWithNWFS()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',
            ]);

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout1["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout2["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        // Need to do this for test as well because in testing env,
        // admin authentication is done on test mode, even if live creds
        // have been passed.
        $adminForTest = $this->prepareAdminForPayoutWorkflow('test');
        $adminForLive = $this->prepareAdminForPayoutWorkflow('live');

        $this->app['config']->set('database.default', 'live');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id' => $adminForTest->getId(),
            'token'    => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('live', $token);

        $this->startTest();
    }

    public function testBulkRetryWorkflowOnPayout()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',
            ]);

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout1["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout2["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        // Need to do this for test as well because in testing env,
        // admin authentication is done on test mode, even if live creds
        // have been passed.
        $adminForTest = $this->prepareAdminForPayoutWorkflow('test');
        $adminForLive = $this->prepareAdminForPayoutWorkflow('live');

        $this->app['config']->set('database.default', 'live');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id' => $adminForLive->getId(),
            'token'    => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('live', $token);

        $this->startTest();
    }

    public function testBulkPayoutApprovalNWFS()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',
            ]);

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout1["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/' . $payout1['id'] . '/approve',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ];

        $approvalResponse = $this->makeRequestAndGetContent($request);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => $this->finL3RoleUser->getId()
        ];

        $testData                                          = &$this->testData[__FUNCTION__];
        $testData['request']['server']                     = $headers;
        $testData['request']['content'][0]['payout']['id'] = $payout1['id'];
        $testData['request']['content'][0]['fund']['id']   = $payout1['fund_account_id'];

        $this->startTest();
    }

    public function testBulkPayoutWithSameFundAccountNWFS()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id' => 'FVLeJYoM0GPWUb',
            ]);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testApprovePayoutWithNWFSWithQueuingDisabled()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUb', // Should exist in the new WF service
                'created_at' => 1598967658
            ]);

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUc', // Should exist in the new WF service
                'created_at' => 1598967657
            ]);

        $payout = $this->createPayoutWithWorkflow([
                                                      'notes' => [
                                                          "random_key1" => "Hello",
                                                          "random_key2" => "Hi"
                                                      ]
                                                  ], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $this->startTest();
    }

    public function testApprovePayoutWithNWFSWithQueuingEnabled()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUb', // Should exist in the new WF service
                'created_at' => 1598967658
            ]);

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUc', // Should exist in the new WF service
                'created_at' => 1598967657
            ]);

        $payout = $this->createPayoutWithWorkflow([
                                                      'notes' => [
                                                          "random_key1" => "Hello",
                                                          "random_key2" => "Hi"
                                                      ]
                                                  ], 'rzp_live_TheLiveAuthKey');

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $this->startTest();
    }

    public function testGetPayoutsForPendingOnRolesNWFS()
    {
        $this->testGetPayoutsForPendingOnRoles();
        $oldPayout = $this->getDbLastEntity('payout', 'live');
        // setting time lower thn current time fto verify sorting order
        $this->fixtures->on('live')->edit('payout', $oldPayout['id'], [
            'created_at' => (Carbon::now(Timezone::IST)->getTimestamp() - 100)
        ]);
        //Given

        //1. I have a Workflow
        // Sets up Fund Account and Merchant User mapping that may be needed to setup on live
        $this->setUpExperimentForNWFS();

        $this->fixtures->on('live')->create(
            'workflow_config',
            [
                'config_id'  => 'FVLeJYoM0GPWUb', // Should exist in the new WF service
                'created_at' => 1598967658
            ]);

        //2. I Create a Payout
        $payout           = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $expectedPayoutId = $payout["id"];

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'entity_id' => substr($payout["id"], 5), //pout_FUj82QLoJgRcM0 => FUj82QLoJgRcM0
            ]);

        $this->fixtures->on('live')->create('workflow_state_map', ['actor_type_value' => 'finance_l3']);

        //Then
        //When I filter on pending on pending on L2 Role, I shouldn't get anything

        //Assuming the role of merchant for maximum permissions
        $merchantUser = $this->getDbEntity('merchant_user', ['role' => 'owner', 'product' => 'banking'], 'live')->toArray();
        $this->ba->proxyAuth('rzp_live_10000000000000', $merchantUser['user_id']);

        $request = [
            'method'  => 'get',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'product'          => 'banking',
                'expand'           => ['user'],
                'pending_on_roles' => ['finance_l2']
            ],
            'url'     => '/payouts',
        ];

        $response = $this->sendRequest($request);
        $payout   = json_decode($response->getContent(), true);

        $this->assertEmpty($payout["items"]);

        // But when I filter by owner Role (Which is not approved), I should see the payouts

        $request = [
            'method'  => 'get',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'product'          => 'banking',
                'expand'           => ['user'],
                'pending_on_roles' => ['finance_l3']
            ],
            'url'     => '/payouts',
        ];

        $response = $this->sendRequest($request);
        $payout   = json_decode($response->getContent(), false);
        $this->assertEquals(2, count($payout->items));
        $this->assertTrue($payout->items[0]->created_at >= $payout->items[1]->created_at);
        $this->assertEquals($expectedPayoutId, $payout->items[0]->id);

        //2. I approve with the owner of workflow
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());

        $request = [
            'method'  => 'POST',
            'url'     => "/payouts/{$expectedPayoutId}/approve",
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ];
        $this->sendRequest($request);

        $stateMap = $this->getLastEntity('workflow_state_map', true, 'live');
        $this->fixtures->edit('workflow_state_map', $stateMap['id'], [
            'status' => 'processed'
        ]);

        // Now when I filter by owner Role (Which is approved), I should not see the payout
        $this->ba->proxyAuth('rzp_live_10000000000000', $merchantUser['user_id']);

        $request = [
            'method'  => 'get',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'product'          => 'banking',
                'expand'           => ['user'],
                'pending_on_roles' => ['finance_l3']
            ],
            'url'     => '/payouts',
        ];

        $response = $this->sendRequest($request);
        $payout   = json_decode($response->getContent(), false);

        $this->assertEquals(1, count($payout->items));
    }

    public function testBulkApprovePayoutWithComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData                                     = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(true, $actionChecker['approved']);
        $this->assertEquals('Bulk Approving', $actionChecker['user_comment']);
    }

    public function testBulkApprovePayoutWithNullComment()
    {
        $this->liveSetUp();

        $workflow = $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData                                     = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(true, $actionChecker['approved']);
        $this->assertNull($actionChecker['user_comment']);
    }

    public function testBulkApprovePayoutWithoutComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData                                     = &$this->testData[__FUNCTION__];
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

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment('yesbank', 'on');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutWithoutCommentInWebhookEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        // Reject with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');

        $this->assertEquals(false, $actionChecker['approved']);
        $this->assertEquals('Rejecting', $actionChecker['user_comment']);
    }

    public function testRejectPayoutWithNullComment()
    {
        $this->liveSetUp();

        $workflow = $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment('yesbank', 'on');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        // Reject with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');

        $this->assertEquals(false, $actionChecker['approved']);
        $this->assertNull($actionChecker['user_comment']);
    }

    public function testRejectPayoutWithoutComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment('yesbank', 'on');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        // Reject with Owner role user
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

        $testData                                     = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        $this->mockRazorxTreatment('yesbank', 'on');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);
        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        // Reject with Owner role user
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

        $testData                                     = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        $this->mockRazorxTreatment('yesbank', 'on');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);
        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        // Reject with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(false, $actionChecker['approved']);
        $this->assertEquals(null, $actionChecker['user_comment']);
    }

    public function testRetryPayout(): array
    {
        $payout        = $this->testCreatePayout();
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
        $this->assertEquals(Attempt\Status::INITIATED, $payoutAttempt['status']);

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

    public function testCreatePayoutFundsOnHoldForCurrentAccount()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->liveSetUpForRbl();

        $this->fixtures->edit('merchant', '10000000000000', ['activated' => 0]);

        $this->fixtures->on('live')->merchant->holdFunds();

        $this->startTest();
    }

    public function testCreatePayoutWithVaultTokenForNonRefundsApp()
    {
        $this->ba->appAuthTest($this->config['applications.settlements_service.secret']);

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

        $this->mockCardVault();

        $this->startTest();
    }

    public function testCreatePayoutWithVaultTokenAndCardNumberForRefundsApp()
    {
        $this->ba->appAuthTest($this->config['applications.scrooge.secret']);

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

        $this->mockCardVault();

        $this->startTest();
    }

    public function testCreatePayoutWithCardNumberForRefundsApp()
    {
        $this->ba->appAuthTest($this->config['applications.scrooge.secret']);

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

        $this->mockCardVault();

        $this->startTest();
    }

    public function testCreatePayoutWithVaultTokenAndDummyNameForRefundsApp()
    {
        $this->ba->appAuthTest($this->config['applications.scrooge.secret']);

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

        $this->mockCardVault();

        $cardsCountBeforeRequest = count($this->getDbEntities('card'));

        $this->startTest();

        $cardsCountAfterRequest = count($this->getDbEntities('card'));

        $this->assertEquals($cardsCountBeforeRequest + 1, $cardsCountAfterRequest);

        $card = $this->getDbLastEntity('card');

        $this->assertEquals("0137", $card["last4"]);

        $this->assertEquals("dummy card", $card["name"]);

        $this->assertNotEmpty($card['iin']);

        $this->assertNotEmpty($card['expiry_month']);

        $this->assertNotEmpty($card['expiry_month']);

        $this->assertNotEmpty($card['expiry_year']);

        $this->assertNotEmpty($card['vault_token']);

        $this->assertNotEmpty($card['global_fingerprint']);
    }

    public function testCreatePayoutWithVaultTokenAndNonDummyNameForRefundsApp()
    {
        $this->ba->appAuthTest($this->config['applications.scrooge.secret']);

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

        $this->fixtures->create('card', [
            'merchant_id'  => '10000000000000',
            'name'         => 'name1',
            'expiry_month' => 4,
            'expiry_year'  => 2024,
            'vault_token'  => 'MzQwMTY5NTcwOTkwMTM3==',
        ]);

        $this->fixtures->create('card', [
            'merchant_id'  => '10000000000000',
            'name'         => 'name2',
            'expiry_month' => 9,
            'expiry_year'  => 2030,
            'vault_token'  => 'MzQwMTY5NTcwOTkwMTM3==',
        ]);

        $this->mockCardVault();

        $cardsCountBeforeRequest = count($this->getDbEntities('card'));

        $this->startTest();

        $cardsCountAfterRequest = count($this->getDbEntities('card'));

        $this->assertEquals($cardsCountBeforeRequest + 1, $cardsCountAfterRequest);

        $card = $this->getDbLastEntity('card');

        $this->assertEquals("0137", $card["last4"]);

        $this->assertEquals("name2", $card["name"]);

        $this->assertEquals(9, $card["expiry_month"]);

        $this->assertEquals(2030, $card["expiry_year"]);

        $this->assertNotEmpty($card['iin']);

        $this->assertNotEmpty($card['vault_token']);

        $this->assertNotEmpty($card['global_fingerprint']);
    }

    public function testPayoutSetStatusQueuePushForRefundsPayout()
    {
        $this->app->instance('rzp.mode', "live");

        Queue::fake();

        $payout = $this->fixtures->create('payout', [
            'status'          => 'created',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $payout->setStatus(Status::PROCESSING);

        Queue::assertNotPushed(PayoutSourceUpdaterJob::class);

        // now adding payout source and QueuePush Should Happen
        $this->fixtures->create('payout_source',
                                [
                                    'payout_id'   => $payout->getId(),
                                    'source_id'   => 'vdpm_1',
                                    'source_type' => 'refund',
                                    'priority'    => 1
                                ]);

        $payout->setStatus(Status::PROCESSED);

        Queue::assertPushed(PayoutSourceUpdaterJob::class);
    }

    public function testCreatePayoutFundsOnHoldOnTestMode()
    {
        $contactId = $this->getDbLastEntity('contact')->getId();

        $this->fixtures->create('fund_account:vpa', [
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contactId,
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

    public function testGetPayoutsWithRemovingPayoutFeatureForMerchant()
    {
        $this->createEsIndex();

        $payout = $this->testCreatePayout();

        $payout = $this->testCreatePayout();

        $this->ba->privateAuth();

        $this->fixtures->merchant->removeFeatures(['payout']);

        $payouts = $this->startTest();

        $this->assertEquals($payouts['entity'], 'collection');

        $this->assertEquals($payouts['count'], 2);

        $this->assertNotEquals($payouts['items'], null);
    }

    public function testGetPayoutsWithRemovingPayoutFeatureForMerchantWithProxyAuth()
    {
        $this->createEsIndex();

        $payout = $this->testCreatePayout();

        $payout = $this->testCreatePayout();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->removeFeatures(['payout']);

        $payouts = $this->startTest();

        $this->assertEquals($payouts['entity'], 'collection');

        $this->assertEquals($payouts['count'], 2);

        $this->assertNotEquals($payouts['items'], null);
    }

    public function testCreatePayoutWithOtpWithIMPSLimit5L()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->proxyAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals("MerchantUser01", $payout['user_id']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
    }

    public function testGetPayoutsForReferenceId()
    {
        $this->createEsIndex();

        $payout1 = $this->testCreatePayout();

        $payout2 = $this->testCreatePayout();

        $this->fixtures->edit('payout', $payout2['id'], ['reference_id' => 'WckD']);

        $payout3 = $this->testCreatePayout();

        $this->fixtures->edit('payout', $payout3['id'], ['reference_id' => 'WckD']);

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . '&reference_id=WckD';

        $this->ba->proxyAuth();

        $payouts = $this->startTest();

        $this->assertEquals($payout3['id'], $payouts['items'][0]['id']);
        $this->assertEquals($payout2['id'], $payouts['items'][1]['id']);

        $this->assertEquals('WckD', $payouts['items'][0]['reference_id']);
        $this->assertEquals('WckD', $payouts['items'][1]['reference_id']);

        $this->assertEquals($payouts['entity'], 'collection');

        $this->assertEquals($payouts['count'], 2);

        $this->assertNotEquals($payouts['items'], null);
    }

    public function testGetPayoutsForReversalId()
    {
        $this->createEsIndex();

        $payout = $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->reversePayout($payout);

        $reversal = $this->fixtures->reversal->createPayoutReversal([
                                                                        'merchant_id' => '10000000000000',
                                                                        'entity_id'   => $payout['id'],
                                                                        'entity_type' => 'payout',
                                                                        'amount'      => $payout['amount'],
                                                                        'fee'         => 0,
                                                                        'tax'         => 0,
                                                                        'channel'     => 'rbl',
                                                                    ]);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . 'rvrsl_' . $reversal['id'];

        $payouts = $this->startTest();

        $this->assertEquals('pout_' . $reversal['entity_id'], $payouts['items'][0]['id']);

        $this->assertEquals('reversed', $payouts['items'][0]['status']);

        $this->assertEquals($payouts['entity'], 'collection');

        $this->assertEquals($payouts['count'], 1);

        $this->assertNotEquals($payouts['items'], null);
    }

    public function testGetPayoutsForPendingOnRoles()
    {
        //Given

        //1. I have a Workflow
        // Sets up Fund Account and Merchant User mapping that may be needed to setup on live
        $this->liveSetUp();
        $this->buildRolesRequiredForWorkflow();
        $this->setupWorkflowForLiveMode($this->getWorkflow1());

        //2. I Create a Payout
        $payout           = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $expectedPayoutId = $payout["id"];

        //When

        //1. I approve the level1 finL1Role of workflow
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL1RoleUser->getId());

        $request = [
            'method'  => 'POST',
            'url'     => "/payouts/{$payout['id']}/approve",
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ];
        $this->sendRequest($request);

        //2. I approve the level2 finL2Role of workflow
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL2RoleUser->getId());

        $request = [
            'method'  => 'POST',
            'url'     => "/payouts/{$payout['id']}/approve",
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ];
        $this->sendRequest($request);

        //Then
        //When I filter on pending on pending on L2 Role, I shouldn't get anything

        //Assuming the role of merchant for maximum permissions
        $merchantUser = $this->getDbEntity('merchant_user', ['role' => 'owner', 'product' => 'banking'], 'live')->toArray();
        $this->ba->proxyAuth('rzp_live_10000000000000', $merchantUser['user_id']);

        $request = [
            'method'  => 'get',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'product'          => 'banking',
                'expand'           => ['user'],
                'pending_on_roles' => ['finance_l2']
            ],
            'url'     => '/payouts',
        ];

        $response = $this->sendRequest($request);
        $payout   = json_decode($response->getContent(), true);

        $this->assertEmpty($payout["items"]);

        // But when I filter by L3 Role (Which is not approved), I should see the payouts

        $request = [
            'method'  => 'get',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'product'          => 'banking',
                'expand'           => ['user'],
                'pending_on_roles' => ['finance_l3']
            ],
            'url'     => '/payouts',
        ];

        $response = $this->sendRequest($request);
        $payout   = json_decode($response->getContent(), false);

        $this->assertEquals($expectedPayoutId, $payout->items[0]->id);
    }

    private function getWorkflow1()
    {
        return [
            'org_id'      => '100000razorpay',
            'name'        => 'some workflow',
            'permissions' => ['create_payout'],
            'name'        => 'Test workflow',
            'levels'      => [
                [
                    'level'   => 1,
                    'op_type' => 'and',
                    'steps'   => [
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::FINANCE_L1_ROLE,
                        ],
                    ],
                ],
                [
                    'level'   => 2,
                    'op_type' => 'and',
                    'steps'   => [
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::FINANCE_L2_ROLE,
                        ],
                        [
                            'reviewer_count' => 1,
                            'role_id'        => Org::FINANCE_L3_ROLE,
                        ],
                    ],
                ],
            ],
        ];
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

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/' . $payout['id'];

        $payout2 = $this->startTest();

        $this->assertArrayNotHasKey(Payout\Entity::REMARKS, $payout2);

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

    public function setPaymentPayoutUrl($payment, &$request)
    {
        $request['url'] = '/payments/' . $payment->getPublicId() . '/payouts';
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

        $request        = &$this->testData[__FUNCTION__]['request'];
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

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?status=processed&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByPayoutStatusReason()
    {
        $this->setmockRazorxTreatment(['enable_status_details_feature' => 'on'], 'control');

        $this->testCreatePayout();
        $payout = $this->getDbLastEntity('payout');

        $this->testCreatePayout();
        $payout1 = $this->getDbLastEntity('payout');
        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'bank_window_closed',
                'parameters' => [
                    'processed_by_time' => '1636472623',
                ],
            ],
        ]);

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts?status=processing&reason=bank_window_closed&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response = $this->startTest();
        $this->assertEquals(1, $response['count']);
        $this->assertEquals('pout_' . $payout['id'], $response['items'][0]['id']);

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout1, [
            'source_type'      => 'payout',
            'source_id'        => $payout1->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'bank_window_closed',
                'parameters' => [
                    'processed_by_time' => '1636472623',
                ],
            ],
        ]);

        $request['url'] = '/payouts?status=processing&reason=bank_window_closed&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response8 = $this->startTest();
        $this->assertEquals(2, $response8['count']);

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'mode'             => 'RTGS',
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'beneficiary_bank_confirmation_pending',
                'parameters' => [
                    'processed_by_time' => '1636481743',
                ],
            ],
        ]);

        $request['url'] = '/payouts?status=processing&reason=beneficiary_bank_confirmation_pending&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response1 = $this->startTest();
        $this->assertEquals(1, $response1['count']);
        $this->assertEquals('pout_' . $payout['id'], $response1['items'][0]['id']);

        $request['url'] = '/payouts?status=processing&reason=bank_window_closed&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response2 = $this->startTest();
        $this->assertEquals(1, $response2['count']);

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'payout_bank_processing',
                'parameters' => [
                    'processed_by_time' => '1636472623',
                ],
            ],
        ]);

        $request['url'] = '/payouts?status=processing&reason=payout_bank_processing&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response10 = $this->startTest();
        $this->assertEquals(1, $response10['count']);
        $this->assertEquals('pout_' . $payout['id'], $response10['items'][0]['id']);

        $request['url'] = '/payouts?status=processing&reason=bank_window_closed&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response9 = $this->startTest();
        $this->assertEquals(1, $response9['count']);
        $this->assertEquals('pout_' . $payout1['id'], $response9['items'][0]['id']);

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout1, [
            'source_type'      => 'payout',
            'source_id'        => $payout1->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'payout_bank_processing',
                'parameters' => [
                    'processed_by_time' => '1636472623',
                ],
            ],
        ]);

        $request['url'] = '/payouts?status=processing&reason=payout_bank_processing&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response3 = $this->startTest();
        $this->assertEquals(2, $response3['count']);
        $this->assertEquals('pout_' . $payout1['id'], $response3['items'][0]['id']);
        $this->assertEquals('pout_' . $payout['id'], $response3['items'][1]['id']);

        $request['url'] = '/payouts?status=processing&reason=bank_window_closed&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response4 = $this->startTest();
        $this->assertEquals(0, $response4['count']);

        $request['url'] = '/payouts?status=processing&reason=beneficiary_bank_confirmation_pending&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response5 = $this->startTest();
        $this->assertEquals(0, $response5['count']);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'processed',
            'failure_reason'   => null,
            'bank_status_code' => null
        ]);

        $request['url'] = '/payouts?reason=payout_bank_processing&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response6 = $this->startTest();
        $this->assertEquals(1, $response6['count']);
        $this->assertEquals('pout_' . $payout1['id'], $response6['items'][0]['id']);

        $request['url'] = '/payouts?status=processed&reason=payout_processed&account_number=2224440041626905';
        $this->ba->privateAuth();
        $response7 = $this->startTest();
        $this->assertEquals(1, $response7['count']);
        $this->assertEquals('pout_' . $payout['id'], $response7['items'][0]['id']);

    }

    public function testSearchPayoutByPayoutContactType()
    {
        $contact = $this->fixtures->create('contact', [
            'id'      => '1000005contact', 'email' => 'test@test5.com',
            'contact' => '8888888888', 'name' => 'test user',
            'type'    => 'customer'
        ]);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id'   => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = &$this->testData[__FUNCTION__]['request'];

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

        $request = &$this->testData[__FUNCTION__]['request'];

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
            'id'      => '1000010contact', 'email' => 'test@test5.com',
            'contact' => '8888888888', 'name' => 'test user',
            'type'    => 'customer'
        ]);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id'   => '1000010contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = &$this->testData[__FUNCTION__]['request'];

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
                'source_id'   => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = &$this->testData[__FUNCTION__]['request'];

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
                'source_id'   => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = &$this->testData[__FUNCTION__]['request'];

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
                'source_id'   => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_email=test@payout.com&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout['id'], $responsePayout['id']);
        $this->assertEquals($payout['mode'], $responsePayout['mode']);
        $this->assertEquals($payout['fees'], $responsePayout['fees']);
    }

    public function testSearchPayoutByContactEmailExactMatch($email1 = 'user1@payout.com',
                                                             $email2 = 'user2@payout.com')
    {
        $contact1 = $this->fixtures->create('contact',
                                            ['id' => '1000005contact', 'email' => $email1, 'contact' => '8888888888', 'name' => 'test user1']);

        $contact2 = $this->fixtures->create('contact',
                                            ['id' => '1000006contact', 'email' => $email2, 'contact' => '8888888889', 'name' => 'test user2']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id'   => $contact1->id,
                'source_type' => 'contact',
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

        $request = &$this->testData[__FUNCTION__]['request'];

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

    public function testSearchPayrollPayoutByContactEmailWithExperimentOn()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'on', 'imps_mode_payout_filter' => 'control']);
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@payout.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id'   => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreateXpayrollPayoutWithSourceDetails();

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_email=test@payout.com&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(0, $response['count']);
    }

    public function testSearchPayoutWithMultipleSourceByContactEmailWithExperimentOn()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'on', 'imps_mode_payout_filter' => 'control']);
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@payout.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id'   => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayoutWithMultipleSourceDetails();

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_email=test@payout.com&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(0, $response['count']);
    }

    public function testSearchPayoutByContactEmailWithExperimentOn()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'on', 'imps_mode_payout_filter' => 'control']);
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@payout.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id'   => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?contact_email=test@payout.com&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);
    }

    public function testSearchPayoutByFundAccountId()
    {
        $contact = $this->fixtures->create('contact', ['id' => '1000005contact', 'email' => 'test@payout.com', 'contact' => '8888888888', 'name' => 'test user']);

        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'source_id'   => '1000005contact',
                'source_type' => 'contact',
            ]);

        $payout = $this->testCreatePayout();

        $request = &$this->testData[__FUNCTION__]['request'];

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

    //test filters on fetch multiple api based on queued_reson paramter
    public function testFetchMultiplePayoutsWithQueuedReasonParameter()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => '10000000',
            ]);

        $this->ba->privateAuth();

        //creates 2 queued payouts initially and then processes one of them.
        //so the total queued when this returns is 1 and we are asserting for count as 1 in response
        $this->testCreateQueuedPayout();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchMultiplePayoutsWithBankingProductParameterWithViewOnlyRole()
    {
        $this->testCreatePayout();

        $viewOnlyRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'view_only');

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'contact_name'  => 'Aditya',
            'business_type' => 3
        ]);

        $userId = $viewOnlyRoleUser['id'];

        $this->ba->proxyAuth('rzp_test_10000000000000', $userId);
        $this->startTest();
    }

    public function testFetchMultiplePayoutsWithPrimaryProductParameter()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testFetchMultipleWithHasMoreOnPrivate()
    {
        $this->testCreatePayout();

        $this->ba->privateAuth();
        $response = $this->startTest();

        $this->assertArrayNotHasKey(PublicCollection::HAS_MORE, $response);
    }

    public function testFetchMultipleWithHasMoreFalseWithNoResults()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testFetchMultipleWithHasMoreWithSkipAndCount()
    {
        $this->testCreatePayout();
        $this->testCreatePayout();
        $this->testCreatePayout();
        $this->testCreatePayout();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testFetchMultipleWithHasMoreWithExactSkipAndCount()
    {
        $this->testCreatePayout();
        $this->testCreatePayout();
        $this->testCreatePayout();
        $this->testCreatePayout();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testFetchMultipleWithHasMoreWithOnlyCount()
    {
        $this->testCreatePayout();
        $this->testCreatePayout();
        $this->testCreatePayout();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testFetchMultipleWithHasMoreWithOnlyMaxCount()
    {
        $this->testCreatePayout();
        $this->testCreatePayout();
        $this->testCreatePayout();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testFetchMultipleWithHasMoreWithNoCountAndSkip()
    {
        $this->testCreatePayout();
        $this->testCreatePayout();
        $this->testCreatePayout();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testBulkPayout()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
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
            'HTTP_X_Batch_Id' => 'C0zv9I46W4wiOq',
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
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
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
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkPayoutApproval()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/' . $payout1['id'] . '/approve',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ];

        $approvalResponse = $this->makeRequestAndGetContent($request);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => $this->finL3RoleUser->getId()
        ];

        $testData                                          = &$this->testData[__FUNCTION__];
        $testData['request']['server']                     = $headers;
        $testData['request']['content'][0]['payout']['id'] = $payout1['id'];
        $testData['request']['content'][0]['fund']['id']   = $payout1['fund_account_id'];

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(true, $actionChecker['approved']);
    }

    public function testBulkPayoutRejection()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/' . $payout1['id'] . '/approve',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
                'user_comment' => 'Approving',
            ],
        ];

        $approvalResponse = $this->makeRequestAndGetContent($request);

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => $this->finL3RoleUser->getId()
        ];

        $testData                                          = &$this->testData[__FUNCTION__];
        $testData['request']['server']                     = $headers;
        $testData['request']['content'][0]['payout']['id'] = $payout1['id'];
        $testData['request']['content'][0]['fund']['id']   = $payout1['fund_account_id'];

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(false, $actionChecker['approved']);
    }

    public function testPayoutStatusUpdate()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $this->createPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($payout->getStatus(), 'created');

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'         => 'yesbank',
                'source_id'       => $payout->getId(),
                'bank_account_id' => $payout->getDestinationId(),
                'merchant_id'     => $payout->getMerchantId(),
                'purpose'         => Attempt\Purpose::REFUND,
                'status'          => Attempt\Status::CREATED,
                'source_type'     => Attempt\Type::PAYOUT,
                'is_fts'          => '1',
                'initiate_at'     => Carbon::now(Timezone::IST)->getTimestamp(),
            ]
        );

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout->getPublicId() . '/status';

        $this->ba->proxyAuth();
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($payout->getStatus(), 'processed');
        $this->assertEquals($payout->getUtr(), $payout->getId());

        $payoutCreated = $this->getDbLastEntity('payout');

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutCreated->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals('payout_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertEquals('100000000', $ledgerRequestPayload['identifiers']['fts_fund_account_id']);
            $this->assertEquals('nodal', $ledgerRequestPayload['identifiers']['fts_account_type']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
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

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout->getPublicId() . '/status';

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testPayoutStatusUpdateOnPrivateAuth()
    {
        $this->createPayout();

        $payout = $this->getDbLastEntity('payout');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout->getPublicId() . '/status';

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testPayoutStatusUpdateOnLiveMode()
    {
        $this->liveSetUp();

        $balance = $this->getDbLastEntity('balance', 'live');

        $this->fixtures->on('live')->create('payout', [
            'id'              => '12345678901234',
            'balance_id'      => $balance->getId(),
            'merchant_id'     => '10000000000000',
            'amount'          => 1,
            'status'          => 'created',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $payout = $this->getDbLastEntity('payout', 'live');

        $testData                   = &$this->testData[__FUNCTION__];
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
            'balance'     => 10000000,
            'balanceType' => 'direct',
            'channel'     => 'rbl',
        ];

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"],
            $balanceAttributes["channel"]
        );

        $virtualAccount    = $this->fixtures->create('virtual_account');
        $secondBankAccount = $this->fixtures->create(
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
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->mockRazorxTreatment('payout_to_cards_via_rbl');

        $balanceAttributes = [
            'balance'     => 10000000,
            'balanceType' => 'direct',
            'channel'     => 'rbl',
        ];

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"],
            $balanceAttributes["channel"]
        );

        $virtualAccount    = $this->fixtures->create('virtual_account');
        $secondBankAccount = $this->fixtures->create(
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

        $this->fixtures->create('counter', [
            'account_type'          => 'direct',
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => FreePayout::DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL,
        ]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithWrongFundAccountId()
    {
        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey(Error::STEP, $response['error']);

        $this->assertArrayHasKey(Error::METADATA, $response['error']);
    }

    public function testCreatePayoutIMPSMoreThanMaxAmount()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutAmazonPayMoreThanMaxAmount()
    {
        $contactId = $this->getDbLastEntity('contact')->getId();

        $this->fixtures->create('fund_account:wallet_account', [
            'id'          => '100000000003fb',
            'source_type' => 'contact',
            'source_id'   => $contactId,
        ]);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = 'fa_' . $fundAccount['id'];

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
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contactId,
        ]);

        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testSearchPayoutByMode()
    {
        $payout = $this->testCreatePayout();

        $request        = &$this->testData[__FUNCTION__]['request'];
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

        $request        = &$this->testData[__FUNCTION__]['request'];
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

    public function testAddBulkCustomPayoutPurpose()
    {
        $this->ba->appAuth();

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts/purposes/10000000000000';

        $this->startTest();
    }

    public function testGetAllCustomPayoutPurposesInternalRoute()
    {
        $this->testAddCustomPayoutPurpose();

        $this->testAddBulkCustomPayoutPurpose();

        $this->ba->appAuth();

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts/purposes/10000000000000';

        $this->startTest();
    }

    public function testAddCustomPayoutPurposeWithWrongPurposeType()
    {
        $this->startTest();
    }

    // This test is to solve a very specific problem https://razorpay.atlassian.net/browse/RX-2257
    public function testAddCustomPayoutPurposeOfNumericType()
    {
        // Test with Private Auth
        $this->ba->privateAuth();

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['purpose']      = 'Payout Purpose 1';
        $data['request']['content']['purpose_type'] = 'settlement';

        $response = $this->startTest($data);

        $this->assertEquals(in_array('Payout Purpose 1', array_column($response['items'], 'purpose'), true), true);
        $this->assertEquals(in_array('settlement', array_column($response['items'], 'purpose_type'), true), true);

        $data['request']['content']['purpose']      = 1234;
        $data['request']['content']['purpose_type'] = 'settlement';

        $response = $this->startTest($data);

        $this->assertEquals(in_array('1234', array_column($response['items'], 'purpose'), false), true);
        $this->assertEquals(in_array('settlement', array_column($response['items'], 'purpose_type'), true), true);

        $data['request']['content']['purpose']      = 'Payout Purpose 2';
        $data['request']['content']['purpose_type'] = 'settlement';

        $response = $this->startTest($data);

        $this->assertEquals(in_array('Payout Purpose 2', array_column($response['items'], 'purpose'), true), true);
        $this->assertEquals(in_array('settlement', array_column($response['items'], 'purpose_type'), true), true);
    }

    public function testAddCustomPayoutPurposeThatAlreadyExists()
    {
        $this->testAddCustomPayoutPurpose();

        $this->startTest();
    }

    public function testAddBulkCustomPayoutPurposeThatAlreadyExists()
    {
        $this->testAddBulkCustomPayoutPurpose();

        $this->ba->appAuth();

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts/purposes/10000000000000';

        $this->startTest();
    }

    public function testAdd201CustomPayoutPurposes()
    {
        for ($count = 0; $count < Payout\Validator::MAX_PURPOSES_ALLOWED; $count++)
        {
            $this->addCustomPayoutPurpose('Give Bonus To Mehul ' . $count, 'settlement');
        }

        $this->startTest();
    }

    protected function addCustomPayoutPurpose($purpose, $purposeType)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/purposes',
            'content' => [
                'purpose'      => $purpose,
                'purpose_type' => $purposeType,
            ]
        ];

        $this->ba->privateAuth();

        $this->sendRequest($request);
    }

    public function testAdd301BulkCustomPayoutPurposes()
    {
        for ($count = 0; $count < Payout\Validator::MAX_PURPOSES_ALLOWED + Payout\Validator::MAX_PURPOSES_ALLOWED_TO_XPAYROLL; $count++)
        {
            $this->addCustomPayoutBulkPurpose('Give Bonus To Mehul ' . $count, 'settlement');
        }

        $this->ba->appAuth();

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts/purposes/10000000000000';

        $this->startTest();
    }

    protected function addCustomPayoutBulkPurpose($purpose, $purposeType)
    {
        $request        = [
            'method'  => 'POST',
            'url'     => '/payouts/purposes/{merchant_id}',
            'content' => [
                [
                    'purpose'      => $purpose,
                    'purpose_type' => $purposeType,
                ]
            ]
        ];
        $request['url'] = '/payouts/purposes/10000000000000';

        $this->ba->appAuth();

        $this->sendRequest($request);
    }

    public function testFiringOfWebhookOnUpdationOfUtr()
    {
        $this->mockRazorxTreatment('yesbank', 'on');

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

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
                'utr' => null,
            ]);

        $this->expectWebhookEventWithContents('payout.updated', $eventTestDataKey);

        $this->ba->ftsAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
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
        $fta    = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('933815233814', $payout->getUtr());
        $this->assertEquals('933815233814', $fta->getUtr());
    }

    public function testNotFiringOfWebhookOnNotUpdationOfUtr()
    {
        $this->mockRazorxTreatment('yesbank', 'on');

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $utr = $payout->getUtr();

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status' => Payout\Status::PROCESSED,
            ]);

        $this->dontExpectWebhookEvent('payout.updated');

        $this->ba->ftsAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
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
            'fta_status'     => 'failed',
            'failure_reason' => '',
            'utr'            => $utr,
            'remarks'        => 'testing failed mapping',
            'channel'        => 'yesbank',
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

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
            'fta_status'     => 'failed',
            'failure_reason' => 'Beneficiary bank\'s systems are down. Please retry after some time.',
            'utr'            => $utr,
            'remarks'        => 'testing failed mapping',
            'channel'        => 'yesbank',
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

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
        $merchantUser = $this->fixtures->user->createBankingUserForMerchant('100000Razorpay');

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

    public function testCancelPayoutNotInQueuedState()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout->getPublicId() . '/cancel';

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testRejectPayoutNotInPendingState()
    {
        $this->liveSetUp();

        $payout = $this->createQueuedOrPendingPayout([], 'rzp_live_TheLiveAuthKey');

        // Creating the workflow after payout creation so that payout does not go in pending state.
        // We need the ownerRoleUser to reject the payout which gets created inside this method.
        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        // Reject with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();
    }

    public function testCancelRZPFeesPayout()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout->getId(), [
            'status'  => 'queued',
            'purpose' => 'rzp_fees',
        ]);

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/' . $payout->getPublicId() . '/cancel';

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

    public function testCreatePayoutToRzpTaxContactShouldFail()
    {
        $this->createContact();

        $this->fixtures->edit('contact', $this->contact->getId(), ['type' => 'rzp_tax_pay']);

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
                                                                 'min_amount'  => '0', 'max_amount' => '5000000']);

        $this->createPayoutWithWorkflow($workflow);
    }

    // rzp_fees payout should get processed before others. Create 3 Queued payouts, have enough balance for only
    // one to go through. Assert that rzp_fees payout went through first
    public function testRZPFeesQueuedPayoutPriority()
    {
        $balance = $this->createDirectBankingBalance()->toArray();

        $bankingAccountParams = [
            'id'             => 'xba00000000000',
            'merchant_id'    => '10000000000000',
            'account_ifsc'   => 'RATN0000088',
            'account_number' => '2224440041626906',
            'status'         => 'active',
            'channel'        => 'rbl',
            'balance_id'     => $balance['id'],
        ];

        $bankingAccount = $this->createBankingAccount($bankingAccountParams);

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $balance['id'],
            Details\Entity::ACCOUNT_NUMBER => '2224440041626906',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

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
            'id'             => 'xba00000000000',
            'merchant_id'    => '10000000000000',
            'account_ifsc'   => 'RATN0000088',
            'account_number' => '2224440041626906',
            'status'         => 'active',
            'channel'        => 'rbl',
            'balance_id'     => $balance['id'],
        ];

        $bankingAccount = $this->createBankingAccount($bankingAccountParams);

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $balance['id'],
            Details\Entity::ACCOUNT_NUMBER => '2224440041626906',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $balanceId = $balance['id'];

        $this->fixtures->edit('balance', $balanceId, ['balance' => 0]);

        $payoutData = [
            'queue_if_low_balance' => 1,
            'account_number'       => 2224440041626906,
        ];

        $this->createQueuedOrPendingPayout($payoutData);

        $payout1 = $this->getDbLastEntity('payout')->toArray();

        $payoutDataHigherAmount = [
            'queue_if_low_balance' => 1,
            'amount'               => 530000,
            'account_number'       => 2224440041626906,
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
            'balance'     => 10000000,
            'balanceType' => 'direct',
            'channel'     => 'rbl',
        ];

        $secondBankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"],
            $balanceAttributes["channel"]
        );

        // Create Second Bank Account

        $virtualAccount    = $this->fixtures->create('virtual_account');
        $secondBankAccount = $this->fixtures->create(
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

        $mode = $this->getConnection()->getName();

        $balance = $this->getDbEntity('balance', [
            'merchant_id'  => '10000000000000',
            'account_type' => 'direct'
        ],
                                      $mode);

        return $balance;
    }


    public function testFiringOfWebhookOnCreationOfPendingPayout()
    {
        $this->mockLedgerSns(0);

        $this->liveSetUp();

        $this->mockRazorxTreatment('yesbank', 'on');

        $eventTestDataKey = 'testFiringOfWebhookOnCreationOfPendingPayoutEventData';

        $this->expectWebhookEventWithContents('payout.pending', $eventTestDataKey);

        $this->dontExpectWebhookEvent('payout.initiated');

        $workflow = $this->setupWorkflowForLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $this->assertEquals('pending', $payout['status']);
    }

    public function testFiringOfWebhookPayoutStatusQueuedWithStork()
    {
        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        //statusdetails experiment is also turned on
        $this->mockRazorxTreatment('yesbank', 'on', 'on', 'off', 'off',
                                   'on', 'on', 'off', 'on',
                                   'on', 'off', 'on', 'on',
                                   'off', 'control', 'on',
                                   'on', 'off', 'control',
                                   'on');

        $payoutQueuedEventData = $this->testData['testFiringOfWebhookOnQueuedPayoutEventData'];

        $payoutInitiatedEventData          = $this->testData['testFiringOfWebhookOnInitiatedPayoutEventData'];
        $payoutTransactionCreatedEventData = $this->testData['testFiringOfWebhookOnCreatedTransactionPayoutEventData'];

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($payoutQueuedEventData, $payoutInitiatedEventData, $payoutTransactionCreatedEventData) {
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
            })->times(7);

        $this->testCreateAndProcessQueuedPayout();

        // checking whether status details entity is created and value is getting saved
        $statusDetails = $this->getDbLastEntity('payouts_status_details');
        $this->assertNotNull($statusDetails);

    }

    public function testFiringOfWebhookPayoutStatusUpdateWithStork()
    {
        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $payoutUpdatedEventData   = $this->testData['testFiringOfWebhookOnUpdateOfPayoutEventData'];
        $payoutProcessedEventData = $this->testData['testFiringOfWebhookOnProcessPayoutEventData'];

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($payoutUpdatedEventData, $payoutProcessedEventData) {
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

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $eventData = $this->testData['testFiringOfWebhookOnRejectionOfPayoutEventData'];

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($eventData) {
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

        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $testData = $this->testData['testFiringOfWebhookOnCreationOfPendingPayoutEventData'];

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($testData) {
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

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $this->startTest();
    }

    public function testPayoutToAmexCardWithNullIssuerSupportedMode()
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

        $this->mockRazorxTreatment('yesbank', 'on', 'off', 'off', 'off', 'on');

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testPayoutToAmexCardWithNullIssuerWithUPIMode()
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

        $this->mockRazorxTreatment('yesbank', 'on', 'off', 'off', 'off', 'on');

        $this->ba->privateAuth();

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

    public function testPayoutToAmexCardWithSupportedIssuerWithUPIMode()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::AMEX],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::SCBL
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

        $countOfCardsBeforeFundAccountCreateRequest = count($this->getDbEntities('card'));

        $this->ba->privateAuth();

        $this->startTest();

        $countOfCardsAfterFundAccountCreateRequest = count($this->getDbEntities('card'));

        // This is to check that card entity is created even though fund account creation fails.
        $this->assertEquals($countOfCardsBeforeFundAccountCreateRequest + 1,
                            $countOfCardsAfterFundAccountCreateRequest);
    }

    public function prepareAdminForPayoutWorkflow($mode)
    {
        $admin = $this->fixtures->on($mode)->create('admin', [
            'id'     => 'poutRejtAdmnId',
            'org_id' => Org::RZP_ORG,
            'name'   => 'Payout Rejecting Admin'
        ]);

        $role = $this->fixtures->on($mode)->create('role', [
            'id'     => 'poutRejtRoleId',
            'org_id' => '100000razorpay',
            'name'   => 'Payout Reject Admin',
        ]);

        $permission = $this->fixtures->on($mode)->create('permission', [
            'name' => 'reject_payout_bulk'
        ]);

        $permission2 = $this->fixtures->on($mode)->create('permission', [
            'name' => 'retry_payout_workflow_bulk'
        ]);

        $permission3 = $this->fixtures->on($mode)->create('permission', [
            'name' => 'wfs_config_create'
        ]);

        $permission4 = $this->fixtures->on($mode)->create('permission', [
            'name' => 'wfs_config_update'
        ]);

        $role->permissions()->attach($permission->getId());
        $role->permissions()->attach($permission2->getId());
        $role->permissions()->attach($permission3->getId());
        $role->permissions()->attach($permission4->getId());

        $admin->roles()->attach($role);

        return $admin;
    }

    public function testBulkRejectPayoutWithAdmin()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        $this->mockRazorxTreatment('yesbank', 'on');

        $eventTestDataKey = 'testFiringOfWebhookOnRejectionOfPayoutEventData';

        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);
        $this->expectWebhookEventWithContents('payout.rejected', $eventTestDataKey);

        // Need to do this for test as well because in testing env,
        // admin authentication is done on test mode, even if live creds
        // have been passed.
        $adminForTest = $this->prepareAdminForPayoutWorkflow('test');
        $adminForLive = $this->prepareAdminForPayoutWorkflow('live');

        $this->app['config']->set('database.default', 'live');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id' => $adminForTest->getId(),
            'token'    => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('live', $token);

        $this->startTest();

        $payout1       = $this->getDbEntityById('payout', $payout1['id'], 'live');
        $payout2       = $this->getDbEntityById('payout', $payout2['id'], 'live');
        $actionState   = $this->getDbLastEntity('action_state', 'live');
        $wfAction      = $this->getDbLastEntity('workflow_action', 'live');
        $actionChecker = $this->getDbLastEntity('action_checker', 'live');

        $this->assertEquals($payout1['status'], 'rejected');
        $this->assertEquals($payout2['status'], 'rejected');
        $this->assertEquals($actionState['admin_id'], 'poutRejtAdmnId');
        $this->assertEquals($actionState['name'], 'rejected');
        $this->assertNull($actionState['merchant_id']);
        $this->assertNull($actionState['user_id']);
        $this->assertEquals($wfAction['state'], 'rejected');
        $this->assertEquals($wfAction['state_changer_type'], 'admin');
        $this->assertEquals($wfAction['state_changer_id'], 'poutRejtAdmnId');
        $this->assertEquals($actionChecker['checker_type'], 'admin');
        $this->assertEquals($actionChecker['checker_id'], 'poutRejtAdmnId');
    }

    public function testBulkRejectPayoutWithAdminWithFailure()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow(['amount' => '5000001'], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        $this->mockRazorxTreatment('yesbank', 'on');

        // Need to do this for test as well because in testing env,
        // admin authentication is done on test mode, even if live creds
        // have been passed.
        $adminForTest = $this->prepareAdminForPayoutWorkflow('test');
        $adminForLive = $this->prepareAdminForPayoutWorkflow('live');

        $this->app['config']->set('database.default', 'live');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id' => $adminForTest->getId(),
            'token'    => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('live', $token);

        $this->startTest();
    }

    protected function setupRedisMock()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['get'])
                          ->getMock();

        Redis::shouldReceive('connection')
             ->andReturn($redisMock);

        $redisMock->method('get')->will($this->returnValue('true'));
    }

    public function testDashboardSummaryForNonWorkflowRoles()
    {
        $this->liveSetUp();

        $this->fixtures->on('live');
        $secondBankingBalance = $this->createDirectBankingBalance();

        // Creating 2 banking accounts. First for the existing bankingBalance and second for the secondBankingBalance

        $bankingAccountAttributes = [
            'id'             => 'ABCde1234ABCde',
            'account_number' => '2224440041626998',
            'balance_id'     => $this->bankingBalance->getId(),
            'account_type'   => 'nodal',
        ];

        $bankingAccount = $this->createBankingAccount($bankingAccountAttributes, 'live');

        $secondBankingAccountAttributes = [
            'id'             => 'DEcba4321DEcba',
            'account_number' => '2224440041626999',
            'balance_id'     => $secondBankingBalance->getId(),
            'account_type'   => 'current',
        ];

        $secondBankingAccount = $this->createBankingAccount($secondBankingAccountAttributes, 'live');

        $this->fixtures->on('live')->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $secondBankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626999',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        // Create two queued payouts

        $firstQueuedPayoutAttributes = [
            'account_number'       => '2224440041626905',
            'amount'               => 20000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes, 'rzp_live_TheLiveAuthKey');

        $secondQueuedPayoutAttributes = [
            'account_number'       => '2224440041626906',
            'amount'               => 30000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($secondQueuedPayoutAttributes, 'rzp_live_TheLiveAuthKey');

        // Setup the payout workflow with some banking users
        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->createPayoutWithWorkflow(
            [
                'account_number' => '2224440041626905',
                'amount'         => 54321
            ],
            'rzp_live_TheLiveAuthKey');

        $this->createPayoutWithWorkflow(
            [
                'account_number' => '2224440041626906',
                'amount'         => 12345
            ],
            'rzp_live_TheLiveAuthKey');

        $viewOnlyRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'view_only', 'live');

        $userId = $viewOnlyRoleUser['id'];

        $this->ba->proxyAuth('rzp_live_10000000000000', $userId);

        $completeSummary = $this->startTest();

        $firstBankingAccountId = $this->getDbEntity('banking_account',
                                                    ['account_number' => '2224440041626905'],
                                                    'live')->getPublicId();

        $secondBankingAccountId = $secondBankingAccount->getPublicId();

        $queuedSummaryFirstAccount   = $completeSummary[$firstBankingAccountId][Payout\Status::QUEUED];
        $pendingSummaryFirstAccount  = $completeSummary[$firstBankingAccountId][Payout\Status::PENDING];
        $queuedSummarySecondAccount  = $completeSummary[$secondBankingAccountId][Payout\Status::QUEUED];
        $pendingSummarySecondAccount = $completeSummary[$secondBankingAccountId][Payout\Status::PENDING];

        $this->assertEquals($queuedSummaryFirstAccount['low_balance']['count'], 1);
        $this->assertEquals($queuedSummaryFirstAccount['low_balance']['total_amount'], 20000099);
        $this->assertEquals($queuedSummaryFirstAccount['low_balance']['balance'], "10000000");

        $this->assertEquals($pendingSummaryFirstAccount['count'], 0);
        $this->assertEquals($pendingSummaryFirstAccount['total_amount'], 0);

        $this->assertEquals($queuedSummarySecondAccount['low_balance']['count'], 1);
        $this->assertEquals($queuedSummarySecondAccount['low_balance']['total_amount'], 30000099);
        $this->assertEquals($queuedSummarySecondAccount['low_balance']['balance'], "10000000");

        $this->assertEquals($pendingSummarySecondAccount['count'], 0);
        $this->assertEquals($pendingSummarySecondAccount['total_amount'], 0);
    }

    public function testFiringOfWebhooksAndEmailOnPayoutReversal()
    {
        Mail::fake();

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $transactionCreatedEventTestDataKey = $this->testData['testFiringOfWebhooksAndEmailOnPayoutReversalTransactionCreatedEventData'];

        $payoutReversedEventTestDataKey = $this->testData['testFiringOfWebhooksAndEmailOnPayoutReversalPayoutReversedEventData'];

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($transactionCreatedEventTestDataKey, $payoutReversedEventTestDataKey) {
                $this->assertContains($payload['event']['name'], ['transaction.created', 'payout.reversed']);
                switch ($payload['event']['name'])
                {
                    case Event::TRANSACTION_CREATED:
                        $this->validateStorkWebhookFireEvent('transaction.created', $transactionCreatedEventTestDataKey, $payload);
                        break;

                    case Event::PAYOUT_REVERSED:
                        $this->validateStorkWebhookFireEvent('payout.reversed', $payoutReversedEventTestDataKey, $payload);
                        break;

                }

                return new \Requests_Response();
            })->times(2);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();
        $this->startTest();

        Mail::assertQueued(PayoutMail::class, function($mail) {
            $viewData = $mail->viewData;

            $this->assertEquals($mail->originProduct, 'banking');

            $this->assertEquals('2001062', $viewData['txn']['amount']); // raw amount
            $this->assertEquals('20,010.62', amount_format_IN($viewData['txn']['amount'])); // formatted amount

            $payout = $this->getDbLastEntity('payout');

            $this->assertEquals('pout_' . $payout->getId(), $viewData['source']['id']);
            $this->assertEquals($payout->getFailureReason(), $viewData['source']['failure_reason']);

            $expectedData = [
                'txn' => [
                    'entity_id' => $payout->getId(),
                ]
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $this->assertEquals('emails.transaction.payout_reversed', $mail->view);

            return true;
        });
    }

    public function testTransactionCreatedWebhookAndPayoutReversedEmailNotFiringForCurrentAccountPayout()
    {
        Mail::fake();

        $this->createDirectAccountPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $payoutReversedEventTestDataKey = $this->testData['testTransactionCreatedWebhookAndPayoutReversedEmailNotFiringForCurrentAccountPayoutEventData'];

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($payoutReversedEventTestDataKey) {
                $this->validateStorkWebhookFireEvent('payout.reversed',
                                                     $payoutReversedEventTestDataKey,
                                                     $payload);

                return new \Requests_Response();
            })->once();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();
        $this->startTest();

        Mail::assertNotQueued(PayoutMail::class);
    }

    protected function createDirectAccountPayout()
    {
        $this->setupDirectAccount();

        $directAccountPayoutRequest = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626906',
                'amount'          => 2000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($directAccountPayoutRequest);
    }

    protected function setupDirectAccount()
    {
        $balanceAttributes = [
            'balance'     => 10000000,
            'balanceType' => 'direct',
            'channel'     => 'rbl',
        ];

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"],
            $balanceAttributes["channel"]
        );

        $virtualAccount    = $this->fixtures->create('virtual_account');
        $secondBankAccount = $this->fixtures->create(
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

        $this->fixtures->create('counter', [
            'account_type'          => 'direct',
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => FreePayout::DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL,
        ]);

        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();
    }

    public function testWorkflowActionNotesTransformationForNumericAndEmptyKeys()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->disableWorkflowMocks();

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 10000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'fund_account_id'      => 'fa_100000000000fa',
                'mode'                 => 'NEFT',
                'queue_if_low_balance' => 0,
                'notes'                => [
                    0   => 'Test',
                    1   => 'Test1',
                    ''  => 'Test2',
                    'a' => 'Test2',
                ],
            ],
        ];

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        // create a payout with custom notes
        $response = $this->sendRequest($request);

        $payout = json_decode($response->getContent(), true);

        $request = [
            'method'  => 'POST',
            'url'     => "/payouts/{$payout['id']}/reject",
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '1234',
                'user_comment' => 'Rejecting',
            ],
        ];

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $response = $this->sendRequest($request);

        // reject the payout
        json_decode($response->getContent(), true);

        $wfAction = \DB::connection('live')->table('workflow_actions')
                       ->where('entity_id', str_after($payout['id'], 'pout_'))
                       ->where('entity_name', 'payout')
                       ->first();

        // fetch diff using wf_action
        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = "/w-actions/w_action_{$wfAction->id}/diff";

        $this->ba->adminAuth('live');

        $this->addPermissionToBaAdmin(Admin\Permission\Name::VIEW_ALL_WORKFLOW);

        $this->startTest();
    }

    public function testWorkflowActionNotesTransformationForNonAssociativeArrays()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->disableWorkflowMocks();

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 10000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'fund_account_id'      => 'fa_100000000000fa',
                'mode'                 => 'NEFT',
                'queue_if_low_balance' => 0,
                'notes'                => [
                    'Test',
                    'Test1',
                    'Test2',
                    'Test3',
                ],
            ],
        ];

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        // create a payout with custom notes
        $response = $this->sendRequest($request);

        $payout = json_decode($response->getContent(), true);

        $request = [
            'method'  => 'POST',
            'url'     => "/payouts/{$payout['id']}/reject",
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '1234',
                'user_comment' => 'Rejecting',
            ],
        ];

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $response = $this->sendRequest($request);

        // reject the payout
        json_decode($response->getContent(), true);

        $wfAction = \DB::connection('live')->table('workflow_actions')
                       ->where('entity_id', str_after($payout['id'], 'pout_'))
                       ->where('entity_name', 'payout')
                       ->first();

        // fetch diff using wf_action
        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = "/w-actions/w_action_{$wfAction->id}/diff";

        $this->ba->adminAuth('live');

        $this->addPermissionToBaAdmin(Admin\Permission\Name::VIEW_ALL_WORKFLOW);

        $this->startTest();
    }

    public function testGetPayoutReversals()
    {
        $payout_data = $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]
        );

        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ]
        )->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]
        );

        $this->updateFtaAndSource($payout->getId(), Payout\Status::FAILED);

        $this->ba->privateAuth();

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/' . $payout_data['id'] . '/reversals';

        $response = $this->startTest();

        $this->assertEquals($payout_data['id'], $response['payout_id']);
    }

    public function testGetPayoutReversalsForProcessedPayout()
    {
        $payout_data = $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]
        );

        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ]
        )->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]
        );

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED);

        $this->ba->privateAuth();

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/' . $payout_data['id'] . '/reversals';

        $this->startTest();
    }

    public function testBulkPayoutWithNotes()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testCreatePayoutWithIfQueueLowBalanceFalse()
    {
        $this->mockLedgerSns(0);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey(Error::STEP, $response['error']);

        $this->assertArrayHasKey(Error::METADATA, $response['error']);
    }

    public function testCreatePayoutWithIfQueueLowBalanceFalseWithCredits()
    {
        $this->mockLedgerSns(0);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 700]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600, 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);
        $this->assertNull($payout);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNull($txn);

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals(700, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals(0, count($creditTxnEntities));
    }

    public function testPayoutFetchById()
    {
        $payout = $this->testCreatePayout();

        //$this->fixtures->edit('payout', $payout['id'], ['reference_id' => 'WckD']);

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/payouts/' . $payout['id'];

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals($payout['id'], $response['id']);
        $this->assertEquals($payout['mode'], $response['mode']);
        $this->assertEquals($payout['fees'], $response['fees']);
    }

    public function testPayoutsFetchMultiple()
    {
        $payout = $this->testCreatePayout();

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ];

        $payout2 = $this->makeRequestAndGetContent($request);

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts?mode=NEFT&account_number=2224440041626905';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $responsePayout = $response['items'][0];

        $this->assertEquals($payout2['id'], $responsePayout['id']);
        $this->assertEquals($payout2['mode'], $responsePayout['mode']);
        $this->assertEquals($payout2['fees'], $responsePayout['fees']);
    }

    public function testWithUpdatedChannelBeforeFtaReconNonRBL()
    {

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $utr = $payout->getUtr();

        $txn = $this->getLastEntity('transaction', true);

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'channel'        => 'icici',
            'failure_reason' => '',
            'utr'            => $utr,
            'remarks'        => '',
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        // Verify transaction entity
        $updatedTxn = $this->getLastEntity('transaction', true);

        $this->assertEquals($updatedPayout[Payout\Entity::CHANNEL], 'icici');

        $this->assertEquals($txn[Payout\Entity::CHANNEL], 'yesbank');

        $this->assertEquals($updatedTxn[Payout\Entity::CHANNEL], 'icici');
    }

    public function testWithUpdatedChannelBeforeFtaReconForRBL()
    {
        $this->testCreateRblPayoutSuccessfully();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $utr = $payout->getUtr();

        $this->makeRequestAndCatchException(function() use ($payout, $utr) {
            (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
                'channel'        => 'yesbank',
                'failure_reason' => '',
                'utr'            => $utr,
                'remarks'        => '',
            ]);
        },
            \RZP\Exception\LogicException::class,
            'Different channel passed by FTS for CA payouts');

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($payout[Payout\Entity::CHANNEL], 'rbl');

        $this->assertEquals($updatedPayout[Payout\Entity::CHANNEL], 'rbl');
    }

    public function testCreatePayoutWithInvalidPurpose()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithoutPurpose()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithCustomPurpose()
    {
        $custom_purpose = $this->testCreatePayoutPurpose();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['purpose'] = $custom_purpose;

        $this->testData[__FUNCTION__]['response']['content']['purpose'] = $custom_purpose;

        $this->startTest();
    }

    public function getPayoutPurposesCount()
    {
        $this->ba->privateAuth();

        $request = [
            'method'  => 'get',
            'url'     => '/payouts/purposes',
            'content' => [
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response['count'];
    }

    public function testCreatePayoutPurpose()
    {
        $existing_purposes_count = $this->getPayoutPurposesCount();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(($existing_purposes_count + 1), $response['count']);

        return $this->testData[__FUNCTION__]['request']['content']['purpose'];
    }

    public function testCreatePayoutPurposeWithInvalidPurpose()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutPurposeWithInvalidPurposeType()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateRblPayoutSuccessfully()
    {
        $balanceAttributes = [
            'balance'     => 10000000,
            'balanceType' => 'direct',
            'channel'     => 'rbl',
        ];

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"],
            $balanceAttributes["channel"]
        );

        $virtualAccount    = $this->fixtures->create('virtual_account');
        $secondBankAccount = $this->fixtures->create(
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

        $this->fixtures->create('counter', [
            'account_type'          => 'direct',
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => FreePayout::DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL,
        ]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUpdateFTAAndPayoutWithInvalidStateTransition()
    {
        // Need to keep it async here because we are testing for invalid state transition of created to reversed.
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_ASYNC_FTS_TRANSFER]);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        // Assert that fta status didn't update
        $this->assertEquals('created', $ftaForPayout->getStatus());
    }

    public function testUpdateFTAAndPayoutWithInvalidStatus()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        // Assert that fta status didn't update
        $this->assertEquals('initiated', $ftaForPayout->getStatus());
    }

    // TODO: Remove this test once we remove bulk throttling (when file based uploads are started by FTS)
    // Test creates 4 payouts (NEFT, RTGS, UPI and IMPS) [First 2 go to batch_submitted, remaining go to created]
    public function testBulkPayoutWithThrottling()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        // Assert status of all 4 payouts. [public status = processing has been asserted in response in payoutTestData]
        $this->assertEquals(Payout\Status::BATCH_SUBMITTED, $payouts[0]['status']);
        $this->assertEquals(Payout\Status::BATCH_SUBMITTED, $payouts[1]['status']);
        $this->assertEquals(Payout\Status::CREATED, $payouts[2]['status']);
        $this->assertEquals(Payout\Status::CREATED, $payouts[3]['status']);

        // Assert that origin of all 4 payouts is dashboard
        $this->assertEquals(Payout\Entity::DASHBOARD, $payouts[0][Payout\Entity::ORIGIN]);
        $this->assertEquals(Payout\Entity::DASHBOARD, $payouts[1][Payout\Entity::ORIGIN]);
        $this->assertEquals(Payout\Entity::DASHBOARD, $payouts[2][Payout\Entity::ORIGIN]);
        $this->assertEquals(Payout\Entity::DASHBOARD, $payouts[3][Payout\Entity::ORIGIN]);
    }

    public function testProcessBulkPayoutDelayedInitiation()
    {
        $this->testBulkPayoutWithThrottling();

        $this->ba->cronAuth();

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        // Assertions for first payout (NEFT)
        $this->assertEquals(Payout\Mode::NEFT, $payouts[0]['mode']);
        $this->assertEquals(Payout\Status::CREATED, $payouts[0]['status']);

        // Assertion for second payout (RTGS)
        // Payout got failed since there wasn't sufficient balance to process it.
        $this->assertEquals(Payout\Mode::RTGS, $payouts[1]['mode']);
        $this->assertEquals(Payout\Status::FAILED, $payouts[1]['status']);

        $batchProcessingPayouts = $this->getDbEntities('payout', ['status' => Payout\Status::BATCH_SUBMITTED]);

        // Assert that no payouts remain in batch_processing state
        $this->assertEquals(0, $batchProcessingPayouts->count());
    }

    /** This test asserts that credits are used in batch_submitted payouts
     *  Fees and tax are handled correctly and credit balacne is not changed
     */
    public function testProcessBulkPayoutDelayedInitiationWithNewCreditsFlow()
    {
        $this->testBulkPayoutWithThrottling();

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 700]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600, 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->ba->cronAuth();

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        // Assertions for first payout (NEFT)
        $this->assertEquals(Payout\Mode::NEFT, $payouts[0]['mode']);
        $this->assertEquals(Payout\Status::CREATED, $payouts[0]['status']);
        $this->assertEquals(500, $payouts[0]['fees']);
        $this->assertEquals(0, $payouts[0]['tax']);

        // Assertion for second payout (RTGS)
        // Payout got failed since there wasn't sufficient balance to process it.
        $this->assertEquals(Payout\Mode::RTGS, $payouts[1]['mode']);
        $this->assertEquals(Payout\Status::FAILED, $payouts[1]['status']);

        $batchProcessingPayouts = $this->getDbEntities('payout', ['status' => Payout\Status::BATCH_SUBMITTED]);

        // Assert that no payouts remain in batch_processing state
        $this->assertEquals(0, $batchProcessingPayouts->count());

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals(2, count($creditTxnEntities));
    }

    public function testUpdatePayoutStatusToProcessedManually()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta->getStatus());

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $request = [
            'url'     => '/payouts/' . $payout['id'] . '/manual/status',
            'method'  => 'PATCH',
            'content' => [
                'status'              => 'processed',
                'fts_fund_account_id' => '12345',
                'fts_account_type'    => 'NODAL',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $payout->reload();

        // Assert that payout status was updated.
        $this->assertEquals('processed', $payout->getStatus());

        $fta->reload();

        // Assert that fta status was also updated along with payout status.
        $this->assertEquals('processed', $fta->getStatus());
    }

    public function testPayoutWithoutFtaManualStatusUpdateToFailed()
    {
        $this->testCreatePayoutForRequestSubmitted();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('create_request_submitted', $payout->getStatus());

        $request = [
            'url'     => '/payouts/' . $payout['id'] . '/manual/status',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'failed',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $payout->reload();

        // Assert that payout status was updated.
        $this->assertEquals('failed', $payout->getStatus());
    }

    public function testUpdatePayoutStatusToProcessedManuallyFailed()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta->getStatus());

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $request = $this->testData[__FUNCTION__];

        $request['request']['url'] = '/payouts/' . $payout['id'] . '/manual/status';

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $payout->reload();

        // Assert that payout status was updated.
        $this->assertEquals('initiated', $payout->getStatus());

        $fta->reload();

        // Assert that fta status was also updated along with payout status.
        $this->assertEquals('initiated', $fta->getStatus());
    }

    public function testUpdatePayoutStatusToReversedManuallyFromCreated()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $request = [
            'url'     => '/payouts/' . $payout['id'] . '/manual/status',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'reversed',
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $payout->reload();

        // Assert that payout status was updated.
        $this->assertEquals('reversed', $payout->getStatus());
    }

    public function testUpdatePayoutToSomeIntermediateStatus()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta->getStatus());

        $this->testData[__FUNCTION__]['request']['url'] = '/payouts/' . $payout['id'] . '/manual/status';

        $this->ba->adminAuth();

        $this->startTest();

        $payout->reload();

        // Assert that payout status was not updated.
        $this->assertEquals('created', $payout->getStatus());

        $fta->reload();

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was also not updated.
        $this->assertEquals('initiated', $fta->getStatus());
    }

    public function testUpdatePayoutStatusManuallyToReversed()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(3, $ledgerSnsPayloadArray);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta->getStatus());

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $request = [
            'url'     => '/payouts/' . $payout['id'] . '/manual/status',
            'method'  => 'PATCH',
            'content' => [
                'status'              => 'processed',
                'fts_fund_account_id' => '12345',
                'fts_account_type'    => 'NODAL',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $payout->reload();

        // Assert that payout status was updated to processed.
        $this->assertEquals('processed', $payout->getStatus());

        $fta->reload();

        // Assert that fta status was also updated along with payout status to processed.
        $this->assertEquals('processed', $fta->getStatus());

        $request = [
            'url'     => '/payouts/' . $payout['id'] . '/manual/status',
            'method'  => 'PATCH',
            'content' => [
                'status'              => 'reversed',
                'failure_reason'      => 'payout reversed at bank',
                'fts_fund_account_id' => '     12345',
                'fts_account_type'    => 'NODAL',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $payout->reload();

        // Assert that payout status was updated.
        $this->assertEquals('reversed', $payout->getStatus());

        // Assert that payout failure reason was also updated.
        $this->assertEquals('payout reversed at bank', $payout['failure_reason']);

        $fta->reload();

        // Assert that fta status was also updated along with payout status.
        $this->assertEquals('reversed', $fta->getStatus());

        // Assert that fta failure reason was also updated along with payout failure reason.
        $this->assertEquals('payout reversed at bank', $payout['failure_reason']);

        $payoutCreated = $this->getDbLastEntity('payout');

        $reversalCreated = $this->getDbLastEntity('reversal');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'payout_initiated',
            'payout_processed',
            'payout_reversed'
        ];

        // The first 2 events pass the payout Ids, the reversal event passes the reversal Id
        $transactorIdArray = [
            $payoutCreated->getPublicId(),
            $payoutCreated->getPublicId(),
            $reversalCreated->getPublicId()
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1062', $ledgerRequestPayload['commission']);
            $this->assertEquals('162', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }

        //
        // Assertions for fts_fund_account_id and fts_account_type
        // also for banking_account_id and api_transaction_id
        //

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);
        $ledgerSnsPayloadArray[2]['identifiers'] = json_decode($ledgerSnsPayloadArray[2]['identifiers'], true);

        // fts_fund_account_id and fts_account_type are not passed in payout initiated payload
        // We send the payout's transaction ID as api_transaction_id
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertEquals($payoutCreated->transaction->getId(), $ledgerSnsPayloadArray[0]['api_transaction_id']);
        $this->assertEquals($this->bankingBalance->bankingAccount->getPublicId(), $ledgerSnsPayloadArray[0]['identifiers']['banking_account_id']);

        // fts_fund_account_id and fts_account_type are passed in payout processed payload
        // We don't send the `api_transaction_id` key at all
        $this->assertEquals('100000000', $ledgerSnsPayloadArray[1]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('nodal', $ledgerSnsPayloadArray[1]['identifiers']['fts_account_type']);
        $this->assertArrayNotHasKey('api_transaction_id', $ledgerSnsPayloadArray[1]);
        $this->assertEquals($this->bankingBalance->bankingAccount->getPublicId(), $ledgerSnsPayloadArray[1]['identifiers']['banking_account_id']);

        // fts_fund_account_id and fts_account_type are passed in payout reversed payload
        // We send the reversal's transaction ID as api_transaction_id
        $this->assertEquals('100000000', $ledgerSnsPayloadArray[2]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('nodal', $ledgerSnsPayloadArray[2]['identifiers']['fts_account_type']);
        $this->assertEquals($reversalCreated->transaction->getId(), $ledgerSnsPayloadArray[2]['api_transaction_id']);
        $this->assertEquals($this->bankingBalance->bankingAccount->getPublicId(), $ledgerSnsPayloadArray[2]['identifiers']['banking_account_id']);
    }

    // In this case we will update payout status from initiated
    // to reversed and assert that we send payout failed to
    // ledger
    public function testUpdatePayoutStatusManuallyToReversedLedger()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta->getStatus());

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $payout->reload();

        $fta->reload();

        $request = [
            'url'     => '/payouts/' . $payout['id'] . '/manual/status',
            'method'  => 'PATCH',
            'content' => [
                'status'         => 'reversed',
                'failure_reason' => 'manually marked as failed',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $payout->reload();

        // Assert that payout status was updated.
        $this->assertEquals('reversed', $payout->getStatus());

        // Assert that payout failure reason was also updated.
        $this->assertEquals('manually marked as failed', $payout['failure_reason']);

        $fta->reload();

        // Assert that fta status was also updated along with payout status.
        $this->assertEquals('reversed', $fta->getStatus());

        $payoutCreated = $this->getDbLastEntity('payout');

        $reversalCreated = $this->getDbLastEntity('reversal');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'payout_initiated',
            'payout_failed'
        ];

        // The first 1 events pass the payout Ids, the reversal event passes the reversal Id
        $transactorIdArray = [
            $payoutCreated->getPublicId(),
            $reversalCreated->getPublicId()
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1062', $ledgerRequestPayload['commission']);
            $this->assertEquals('162', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }

        //
        // Assertions for fts_fund_account_id and fts_account_type
        // also for banking_account_id and api_transaction_id
        //

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);

        // fts_fund_account_id and fts_account_type are not passed in payout initiated payload
        // We send the payout's transaction ID as api_transaction_id
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertEquals($payoutCreated->transaction->getId(), $ledgerSnsPayloadArray[0]['api_transaction_id']);
        $this->assertEquals($this->bankingBalance->bankingAccount->getPublicId(), $ledgerSnsPayloadArray[0]['identifiers']['banking_account_id']);

        // fts_fund_account_id and fts_account_type are not passed in payout failed payload
        // We send the reversal's transaction ID as api_transaction_id
        $this->assertEquals($reversalCreated->transaction->getId(), $ledgerSnsPayloadArray[1]['api_transaction_id']);
        $this->assertEquals($this->bankingBalance->bankingAccount->getPublicId(), $ledgerSnsPayloadArray[1]['identifiers']['banking_account_id']);
    }

    // check trimming in payout creation.
    public function testCreatePayoutWithUnnecessarySpacesTrimmedInPurpose()
    {
        $this->startTest();
    }

    // check trimming in payout purpose creation.
    public function testCreatePayoutWithOtpAndUnnecessarySpacesTrimmedInPurpose()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    // check trimming in payout purpose creation.
    public function testCreatePayoutPurposeWithSpacesTrimmed()
    {
        $customPurpose = ' leading trailing ';

        $purpose = &$this->testData[__FUNCTION__]['request']['content']['purpose'];

        $purpose = $customPurpose;

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(in_array(['purpose' => trim($customPurpose), 'purpose_type' => 'refund'], $response['items'], true), true);
    }

    // check trimming in payout purpose creation.
    public function testBulkPayoutWithNotesAndSpacesTrimmed()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    // Test creates 3 payouts (NEFT, UPI and IMPS) [Amount in range of workflow]
    public function testBulkPayoutCreationWithWorkflowActive()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id' => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $payouts = $this->getDbEntities('payout', [], 'live');

        // Assert status of all 3 payouts. All three should go to 'pending' state
        $this->assertEquals(Payout\Status::PENDING, $payouts[0]['status']);
        $this->assertEquals(Payout\Status::PENDING, $payouts[1]['status']);
        $this->assertEquals(Payout\Status::PENDING, $payouts[2]['status']);
    }


    //
    // Test that payout goes from pending to batch_submitted state during bulk approval of bulk payouts
    //
    public function testApproveBulkPayoutsDelayedInitiation()
    {
        $this->testBulkPayoutCreationWithWorkflowActive();

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $payouts = $this->getDbEntities('payout', [], 'live');

        $payoutIds = $payouts->getPublicIds();

        $testData                                     = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = $payoutIds;

        $firstApprovalResponse = $this->startTest();

        // Validating first approval response
        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(3, $firstApprovalResponse['total_count']);
        $this->assertEquals(true, $firstActionChecker['approved']);

        $this->app['config']->set('database.default', 'live');

        $testData                                     = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = $payoutIds;

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());
        $secondApprovalResponse = $this->startTest();

        // Validating second approval response
        $secondActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(3, $secondApprovalResponse['total_count']);
        $this->assertEquals(true, $secondActionChecker['approved']);

        $updatedPayouts = $this->getDbEntities('payout', [], 'live');

        $this->assertEquals(Status::BATCH_SUBMITTED, $updatedPayouts[0]['status']);
        $this->assertEquals(Status::BATCH_SUBMITTED, $updatedPayouts[1]['status']);
        $this->assertEquals(Status::BATCH_SUBMITTED, $updatedPayouts[2]['status']);
    }

    public function testPricingRuleAuthTypeForPrivateAuthPayout()
    {
        $this->ba->privateAuth();
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $transactionId = $payout->transaction->getId();

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7dTcURsOr77",
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PRIVATE_AUTH, $pricingRule->getAuthType());
    }

    public function testPricingRuleAuthTypeForProxyAuthPayout()
    {
        $testData                                = $this->testData['testPricingRuleAuthTypeForProxyAuthPayout'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth('rzp_test_10000000000000', 'MerchantUser01');
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals("MerchantUser01", $payout->getUserId());

        $transactionId = $payout->transaction->getId();

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7dTcURsOr79",
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PROXY_AUTH, $pricingRule->getAuthType());
    }

    public function testCreateFreePayoutForNEFTModeSharedAccountPrivateAuth()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->ba->privateAuth();
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(null, $payout->getUserId());

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout->getFees());
        $this->assertEquals(0, $payout->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $transactionId = $payout->transaction->getId();

        $transaction = $this->getDbEntityById('transaction', $transactionId)->toArray();

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA9",
            'percentage'      => null,
            'amount'          => 0,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PRIVATE_AUTH, $pricingRule->getAuthType());
    }

    /** The test case checks that if the merchant has free payout and credits and the
     * merchant is on new credits flow then free payout is used and not credits
     */
    public function testCreateFreePayoutForNEFTModeSharedAccountPrivateAuthWithNewCreditsFlow()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 700]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600, 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(null, $payout->getUserId());

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout->getFees());
        $this->assertEquals(0, $payout->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $transactionId = $payout->transaction->getId();

        $transaction = $this->getDbEntityById('transaction', $transactionId)->toArray();

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA9",
            'percentage'      => null,
            'amount'          => 0,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PRIVATE_AUTH, $pricingRule->getAuthType());

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);
    }


    /** The test case checks that if the merchant has free payout and credits and the
     * merchant is on old credits flow then free payout is used and not credits
     */
    public function testCreateFreePayoutForNEFTModeSharedAccountPrivateAuthWithOldCreditsFlow()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 700]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600, 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->ba->privateAuth();
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(null, $payout->getUserId());

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout->getFees());
        $this->assertEquals(0, $payout->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $transactionId = $payout->transaction->getId();

        $transaction = $this->getDbEntityById('transaction', $transactionId)->toArray();

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA9",
            'percentage'      => null,
            'amount'          => 0,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PRIVATE_AUTH, $pricingRule->getAuthType());

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);
    }

    public function testCreateFreePayoutForUPIModeSharedAccountPrivateAuth()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                "account_type" => "vpa",
                "contact_id"   => "cont_1000001contact",
                "vpa"          => [
                    "address" => 'yv@upi',
                ]
            ]
        ];

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id']  = $fundAccount['id'];
        $testData['response']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $balance = $this->getDbLastEntity('balance');
        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->privateAuth();
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(null, $payout->getUserId());

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout->getFees());
        $this->assertEquals(0, $payout->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $transactionId = $payout->transaction->getId();

        $transaction = $this->getDbEntityById('transaction', $transactionId)->toArray();

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7eYLkxM7sLT",
            'percentage'      => null,
            'amount'          => 0,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PRIVATE_AUTH, $pricingRule->getAuthType());
    }

    public function testCreateFreePayoutForIMPSModeSharedAccountProxyAuth()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $testData                                = $this->testData['testCreateFreePayoutForIMPSModeSharedAccountProxyAuth'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth('rzp_test_10000000000000', 'MerchantUser01');
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals("MerchantUser01", $payout->getUserId());

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout->getFees());
        $this->assertEquals(0, $payout->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $transactionId = $payout->transaction->getId();

        $transaction = $this->getDbEntityById('transaction', $transactionId)->toArray();

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7cl6t6I3XB1",
            'percentage'      => null,
            'amount'          => 0,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PROXY_AUTH, $pricingRule->getAuthType());
    }

    public function testCreateFreePayoutForNEFTModeDirectAccountProxyAuth()
    {
        $testData                                = $this->testData['testCreateFreePayoutForNEFTModeDirectAccountProxyAuth'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->setupDirectAccount();

        $balance = $this->getDbEntities('balance',
                                        [
                                            'merchant_id'  => "10000000000000",
                                            'account_type' => 'direct',
                                            'channel'      => 'rbl'
                                        ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'rbl');

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth('rzp_test_10000000000000', 'MerchantUser01');
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals("MerchantUser01", $payout->getUserId());

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout->getFees());
        $this->assertEquals(0, $payout->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'direct',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        // Assert that pricing rule id in payouts is correct
        $this->assertEquals('Bbg7cl6t6I3XB2', $payout['pricing_rule_id']);

        $pricingRule = $this->getDbEntityById('pricing', $payout['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PROXY_AUTH, $pricingRule->getAuthType());
    }

    public function testCreateFreePayoutForUPIModeDirectAccountPrivateAuth()
    {
        $this->setupDirectAccount();

        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                "account_type" => "vpa",
                "contact_id"   => "cont_1000001contact",
                "vpa"          => [
                    "address" => 'yv@upi',
                ]
            ]
        ];

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $balance = $this->getDbEntities('balance',
                                        [
                                            'merchant_id'  => "10000000000000",
                                            'account_type' => 'direct',
                                            'channel'      => 'rbl'
                                        ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'rbl');

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testCreateFreePayoutForIMPSModeDirectAccountProxyAuth()
    {
        $testData                                = $this->testData['testCreateFreePayoutForIMPSModeDirectAccountProxyAuth'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->setupDirectAccount();

        $balance = $this->getDbEntities('balance',
                                        [
                                            'merchant_id'  => "10000000000000",
                                            'account_type' => 'direct',
                                            'channel'      => 'rbl'
                                        ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'rbl');

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth('rzp_test_10000000000000', 'MerchantUser01');
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals("MerchantUser01", $payout->getUserId());

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout->getFees());
        $this->assertEquals(0, $payout->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'direct',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        // Assert that pricing rule id in payouts is correct
        $this->assertEquals('Bbg7cl6t6I3XB2', $payout['pricing_rule_id']);

        $pricingRule = $this->getDbEntityById('pricing', $payout['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PROXY_AUTH, $pricingRule->getAuthType());
    }

    public function testReverseFreePayoutForNEFTModeSharedAccount()
    {
        $this->testCreateFreePayoutForNEFTModeSharedAccountPrivateAuth();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();
        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $payout->getBalanceId(),
                                        ])->first();

        // Assert that the free payout that was consumed was reversed.
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $payout->reload();

        // Assert that null is assigned as fee_type after failing the payout
        $this->assertEquals(null, $payout->getFeeType());
    }

    public function testFailFreePayoutForNEFTModeDirectAccount()
    {
        $this->testCreateFreePayoutForNEFTModeDirectAccountProxyAuth();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();
        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'direct',
                                            'balance_id'   => $payout->getBalanceId(),
                                        ])->first();

        // Assert that the free payout that was consumed was reversed.
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $payout->reload();

        // Assert that null is assigned as fee_type after failing the payout
        $this->assertEquals(null, $payout->getFeeType());
    }

    public function testReverseFreePayoutForIMPSModeSharedAccount()
    {
        $this->testCreateFreePayoutForIMPSModeSharedAccountProxyAuth();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();
        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $payout->getBalanceId(),
                                        ])->first();

        // Assert that the free payout that was consumed was reversed.
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $payout->reload();

        // Assert that null is assigned as fee_type after reversing the payout
        $this->assertEquals(null, $payout->getFeeType());
    }

    public function testReverseFreePayoutForIMPSModeDirectAccount()
    {
        $this->testCreateFreePayoutForIMPSModeDirectAccountProxyAuth();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();
        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'direct',
                                            'balance_id'   => $payout->getBalanceId(),
                                        ])->first();

        // Assert that the free payout that was consumed was reversed.
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $payout->reload();

        // Assert that null is assigned as fee_type after reversing the payout
        $this->assertEquals(null, $payout->getFeeType());
    }

    public function testFailFreePayoutForIMPSModeDirectAccount()
    {
        $this->testCreateFreePayoutForIMPSModeDirectAccountProxyAuth();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();
        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'direct',
                                        ])->first();

        // Assert that the free payout that was consumed was reversed.
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $payout->reload();

        // Assert that null is assigned as fee_type after failing the payout
        $this->assertEquals(null, $payout->getFeeType());
    }

    public function testCreateFreePayoutAndVerifyUpdationOfFreePayoutConsumedLastResetAt()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        $someOldDate = Carbon::now(Timezone::IST)->addDays(-32)->firstOfMonth()->getTimestamp();

        $this->fixtures->edit(
            'counter',
            $counter->getId(),
            [
                'free_payouts_consumed_last_reset_at' => $someOldDate,
            ]);

        $counter->reload();

        // Assert that earlier the free_payouts_consumed_last_reset_at was null.
        $this->assertEquals($someOldDate, $counter->getFreePayoutsConsumedLastResetAt());

        $testData                                = $this->testData['testCreateFreePayoutForNEFTModeSharedAccountPrivateAuth'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth();
        $this->startTest();

        $counter->reload();

        $expectedFreePayoutsConsumedLastResetAt = Carbon::now(Timezone::IST)->firstOfMonth()->getTimestamp();

        // Assert that now the free_payouts_consumed_last_reset_at is equal to timestamp of the start of this month.
        $this->assertEquals($expectedFreePayoutsConsumedLastResetAt, $counter->getFreePayoutsConsumedLastResetAt());
    }

    public function testSharedAccountPayoutCreationFailedDueToInsufficientBalanceAndCheckCounterAttributes()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => 2000,
            ]);

        $this->ba->privateAuth();
        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());
    }

    public function testSharedAccountPayoutCreationFailedDueToInsufficientBalance()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => 2000,
            ]);

        $this->ba->privateAuth();
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');
        $this->assertNull($payout);
    }

    public function testQueuedSharedAccountPayoutCreationAndCheckCounterAttributes()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => 2000,
            ]);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(null, $payout->getUserId());

        // Assert that null is assigned as fee_type for such payouts.
        $this->assertNull($payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());
    }

    public function testFreeQueuedSharedAccountPayoutDispatchAndCheckCounterAttributes()
    {
        $this->testQueuedSharedAccountPayoutCreationAndCheckCounterAttributes();

        $balanceId = $this->bankingBalance->getId();

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => 3000000,
            ]);

        $this->dispatchQueuedPayouts();

        $payout = $this->getDbLastEntity('payout');

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());
    }

    public function testCheckHigherPriorityOfSettingsTableEntryAboveConfigKeysForFreePayoutsAttributes()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $freePayoutsAttributeUpdateRequest =
            [
                'url'     => '/balance/' . $balanceId . '/free_payout',
                'method'  => 'post',
                'content' => [
                    'free_payouts_count'           => 1,
                    'free_payouts_supported_modes' => ['IMPS']
                ],
            ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($freePayoutsAttributeUpdateRequest);

        $payoutCreateRequest = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'mode'            => 'NEFT',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ];

        //----------------- NEFT Mode Payout --------------

        // Make payout with mode as NEFT
        $this->ba->privateAuth();
        $this->makeRequestAndGetContent($payoutCreateRequest);

        $payout1 = $this->getDbLastEntity('payout');

        // Assert non zero fee and tax in payout
        $this->assertEquals(1062, $payout1->getFees());
        $this->assertEquals(162, $payout1->getTax());

        // Assert that null is assigned as fee_type for such payouts.
        $this->assertEquals(null, $payout1->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $transactionId = $payout1->transaction->getId();

        $transaction = $this->getDbEntityById('transaction', $transactionId)->toArray();

        // Assert non zero fee and tax in payout
        $this->assertEquals(1062, $transaction['fee']);
        $this->assertEquals(162, $transaction['tax']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7dTcURsOr77",
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PRIVATE_AUTH, $pricingRule->getAuthType());

        //----------------- End NEFT Mode Payout --------------

        //----------------- IMPS Mode First Payout --------------

        $payoutCreateRequest['content']['mode'] = 'IMPS';

        // Make payout with mode as IMPS
        $this->ba->privateAuth();
        $this->makeRequestAndGetContent($payoutCreateRequest);

        $payout2 = $this->getDbLastEntity('payout');

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout2->getFees());
        $this->assertEquals(0, $payout2->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout2->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $transactionId = $payout2->transaction->getId();

        $transaction = $this->getDbEntityById('transaction', $transactionId)->toArray();

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA9",
            'percentage'      => null,
            'amount'          => 0,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PRIVATE_AUTH, $pricingRule->getAuthType());

        //----------------- End IMPS Mode First Payout --------------

        //----------------- IMPS Mode Second Payout --------------

        // Make another payout with mode as IMPS
        $this->ba->privateAuth();
        $this->makeRequestAndGetContent($payoutCreateRequest);

        $payout3 = $this->getDbLastEntity('payout');

        // Assert non zero fee and tax in payout
        $this->assertEquals(1062, $payout3->getFees());
        $this->assertEquals(162, $payout3->getTax());

        // Assert that null is assigned as fee_type for such payouts.
        $this->assertEquals(null, $payout3->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $transactionId = $payout3->transaction->getId();

        $transaction = $this->getDbEntityById('transaction', $transactionId)->toArray();

        // Assert non zero fee and tax in payout
        $this->assertEquals(1062, $transaction['fee']);
        $this->assertEquals(162, $transaction['tax']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7dTcURsOr77",
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PRIVATE_AUTH, $pricingRule->getAuthType());

        //----------------- End IMPS Mode Second Payout --------------
    }

    public function testNoDecrementOfCounterIfPayoutReversalIsInAnotherMonth()
    {
        $this->testCreateFreePayoutForNEFTModeSharedAccountPrivateAuth();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $testData = $this->testData['testReverseFreePayoutForNEFTModeSharedAccount'];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $newTime = Carbon::now(Timezone::IST)->addDays(35);

        Carbon::setTestNow($newTime);

        $this->ba->ftsAuth();
        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $payout->getBalanceId(),
                                        ])->first();

        // Assert that the free payout that was consumed was NOT reversed.
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $payout->reload();

        // Assert that free_payout is assigned as fee_type after failing the payout
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());
    }

    public function testFreePayoutFromPendingToCreatedState()
    {
        $this->liveSetUp();

        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId, null, 'live');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData                   = &$this->testData[__FUNCTION__];
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

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ],
                                        'live')->first();

        // Assert that the free payout was consumed.
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $payout = $this->getDbLastEntity('payout', 'live');

        // Assert that free_payout is assigned as fee_type
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());
    }

    public function testFreeBulkPayouts()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $testData = $this->testData['testBulkPayoutWithThrottling'];

        $responseForFirstFreeCreatedBulkPayout = $testData['response']['content']['items'][2];

        $responseForFirstFreeCreatedBulkPayout['transaction']['amount']  = 100;
        $responseForFirstFreeCreatedBulkPayout['transaction']['debit']   = 100;
        $responseForFirstFreeCreatedBulkPayout['transaction']['balance'] = 9999900;
        $responseForFirstFreeCreatedBulkPayout['fees']                   = 0;
        $responseForFirstFreeCreatedBulkPayout['tax']                    = 0;

        $testData['response']['content']['items'][2] = $responseForFirstFreeCreatedBulkPayout;

        $responseForSecondFreeCreatedBulkPayout = $testData['response']['content']['items'][3];

        $responseForSecondFreeCreatedBulkPayout['transaction']['amount']  = 100;
        $responseForSecondFreeCreatedBulkPayout['transaction']['debit']   = 100;
        $responseForSecondFreeCreatedBulkPayout['transaction']['balance'] = 9999800;
        $responseForSecondFreeCreatedBulkPayout['fees']                   = 0;
        $responseForSecondFreeCreatedBulkPayout['tax']                    = 0;

        $testData['response']['content']['items'][3] = $responseForSecondFreeCreatedBulkPayout;

        $this->testData['testBulkPayoutWithThrottling'] = $testData;

        $this->testProcessBulkPayoutDelayedInitiation();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that the free payout was consumed.
        $this->assertEquals(3, $counter->getFreePayoutsConsumed());
    }

    //
    // Test that when payout goes from pending to batch_submitted state during bulk approval of bulk payouts, counter is
    // not increased.
    //
    public function testApproveBulkPayoutsDelayedInitiationWithFreePayoutsRemaining()
    {
        $this->liveSetUp();

        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId, null, 'live');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->ba->batchAuth('rzp_live_10000000000000');

        $headers = [
            'HTTP_X_Batch_Id' => 'C0zv9I46W4wiOq',
        ];

        $testData = $this->testData['testBulkPayoutCreationWithWorkflowActive'];
        // append headers
        $testData['request']['server'] = $headers;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $payouts = $this->getDbEntities('payout', [], 'live');

        // Assert status of all 3 payouts. All three should go to 'pending' state
        $this->assertEquals(Payout\Status::PENDING, $payouts[0]['status']);
        $this->assertEquals(Payout\Status::PENDING, $payouts[1]['status']);
        $this->assertEquals(Payout\Status::PENDING, $payouts[2]['status']);

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ],
                                        'live')->first();

        // Assert that zero free payout was consumed.
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $this->testData[__FUNCTION__] = $this->testData['testApproveBulkPayoutsDelayedInitiation'];

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $payouts = $this->getDbEntities('payout', [], 'live');

        $payoutIds = $payouts->getPublicIds();

        $testData                                     = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = $payoutIds;

        $firstApprovalResponse = $this->startTest();

        // Validating first approval response
        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(3, $firstApprovalResponse['total_count']);
        $this->assertEquals(true, $firstActionChecker['approved']);

        $this->app['config']->set('database.default', 'live');

        $testData                                     = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = $payoutIds;

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());
        $secondApprovalResponse = $this->startTest();

        // Validating second approval response
        $secondActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(3, $secondApprovalResponse['total_count']);
        $this->assertEquals(true, $secondActionChecker['approved']);

        $updatedPayouts = $this->getDbEntities('payout', [], 'live');

        $this->assertEquals(Status::BATCH_SUBMITTED, $updatedPayouts[0]['status']);
        $this->assertEquals(Status::BATCH_SUBMITTED, $updatedPayouts[1]['status']);
        $this->assertEquals(Status::BATCH_SUBMITTED, $updatedPayouts[2]['status']);

        $counter->reload();

        // Assert that zero free payout was consumed.
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());
    }

    public function testPayoutCreationFailedDueToInsufficientBalanceWhenNoFreePayoutsAvailableAndCheckCounterAttributes()
    {
        $balance = $this->bankingBalance;

        $balanceId = $balance->getId();

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => 2000,
            ]);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__] = $this->testData['testQueuedSharedAccountPayoutCreationAndCheckCounterAttributes'];

        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed
        $this->assertEquals($this->getDefaultFreePayoutsCount($balance), $counter->getFreePayoutsConsumed());
    }

    public function testGetFreePayoutsAttributesOnProxyAuthOwnerUser()
    {
        $this->mockRazorxTreatment();

        $this->ba->proxyAuth();

        $this->ba->addXOriginHeader();

        $testData = $this->testData[__FUNCTION__];

        $balanceId = $this->bankingBalance->getId();

        $testData['request']['url'] = '/payouts/' . $balanceId . '/free_payout';

        $testData['response']['content']['free_payouts_count'] = FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT;

        $testData['response']['content']['free_payouts_consumed'] = FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT;

        $testData['response']['content']['free_payouts_supported_modes'] = FreePayout::DEFAULT_FREE_PAYOUTS_SUPPORTED_MODES;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testGetFreePayoutsAttributesOnAdminAuth()
    {
        $this->ba->adminAuth();

        $testData = $this->testData['testGetFreePayoutsAttributesOnProxyAuthOwnerUser'];

        $balanceId = $this->bankingBalance->getId();

        $testData['request']['url'] = '/admin/payouts/' . $balanceId . '/free_payout';

        $testData['response']['content']['free_payouts_count'] = FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT;

        $testData['response']['content']['free_payouts_consumed'] = FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT;

        $testData['response']['content']['free_payouts_supported_modes'] = FreePayout::DEFAULT_FREE_PAYOUTS_SUPPORTED_MODES;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testGetFreePayoutsAttributesOnProxyAuthAdminUser()
    {
        $this->mockRazorxTreatment();

        $adminRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'admin');

        $this->ba->proxyAuth('rzp_test_10000000000000', $adminRoleUser->getId());

        $this->ba->addXOriginHeader();

        $testData = $this->testData['testGetFreePayoutsAttributesOnProxyAuthOwnerUser'];

        $balanceId = $this->bankingBalance->getId();

        $testData['request']['url'] = '/payouts/' . $balanceId . '/free_payout';

        $testData['response']['content']['free_payouts_count'] = FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT;

        $testData['response']['content']['free_payouts_consumed'] = FreePayout::DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT;

        $testData['response']['content']['free_payouts_supported_modes'] = FreePayout::DEFAULT_FREE_PAYOUTS_SUPPORTED_MODES;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    // This test fails as the route is supported only for owner and admin roles.
    public function testGetFreePayoutsAttributesOnProxyAuthViewOnlyUser()
    {
        $this->mockRazorxTreatment();

        $viewOnlyRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'view_only');

        $this->ba->proxyAuth('rzp_test_10000000000000', $viewOnlyRoleUser->getId());

        $this->ba->addXOriginHeader();

        $testData = $this->testData[__FUNCTION__];

        $balanceId = $this->bankingBalance->getId();

        $testData['request']['url'] = '/payouts/' . $balanceId . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testGetFreePayoutsAttributesOnAdminAuthWithIncorrectBalanceType()
    {
        $this->ba->adminAuth();

        $balance = $this->fixtures->create('balance',
                                           [
                                               Balance\Entity::ACCOUNT_TYPE => AccountType::SHARED,
                                               Balance\Entity::TYPE         => Balance\Type::PRIMARY,
                                           ]);

        $balanceId = $balance->getId();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/admin/payouts/' . $balanceId . '/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testGetFreePayoutsAttributesOnAdminAuthWithBalanceIdNotPresentInDb()
    {
        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/admin/payouts/FIF0eRkA4FVj8H/free_payout';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testSkipWorkflowForPayoutEnabledRequestValueTrue()
    {
        $this->createSkipWorkflowForPayoutFeature();

        $response = $this->startTest();

        $payout = $this->getDbEntityById('payout', $response['id'])->toArray();

        $this->assertEquals(WorkflowFeature::WORKFLOW_FEATURES[Feature\Constants::SKIP_WF_AT_PAYOUTS],
                            $payout[Payout\Entity::WORKFLOW_FEATURE]);
    }

    public function testSkipWorkflowForPayoutEnabledRequestValueFalse()
    {
        $this->startTest();
    }

    public function testSkipWorkflowForPayoutEnabledRequestValueTrueAndSkipWorkflowForApiEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_WORKFLOWS_FOR_API]);

        $this->createSkipWorkflowForPayoutFeature();

        $response = $this->startTest();

        $payout = $this->getDbEntityById('payout', $response['id'])->toArray();

        $this->assertEquals(WorkflowFeature::WORKFLOW_FEATURES[Feature\Constants::SKIP_WF_AT_PAYOUTS],
                            $payout[Payout\Entity::WORKFLOW_FEATURE]);
    }

    public function testSkipWorkflowForPayoutDisablesRequestWithSkipWorkflowKey()
    {
        $this->liveSetUp();
        $this->setupWorkflowForLiveMode();
        $this->disableWorkflowMocks();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testWorkflowFeatureWithoutSkip()
    {
        $response = $this->startTest();

        $payout = $this->getDbEntityById('payout', $response['id'])->toArray();

        $this->assertEquals(null, $payout[Payout\Entity::WORKFLOW_FEATURE]);
    }

    public function testSkipWorkflowKeyWithoutBoolean()
    {
        $this->startTest();
    }

    public function testCreatePayoutWithoutOriginFieldPrivateAuth()
    {
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(Payout\Entity::API, $payout->getOrigin());

        $this->assertFalse(array_key_exists(Payout\Entity::ORIGIN, $payout->toArrayPublic()));
    }

    public function testCreatePayoutWithOriginFieldPrivateAuth()
    {
        $this->startTest();
    }

    public function testCreatePayoutWithoutOriginFieldProxyAuth()
    {
        $testData                                = $this->testData['testCreatePayoutWithoutOriginFieldPrivateAuth'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $testData['response']['content']['origin'] = Payout\Entity::DASHBOARD;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreatePayoutWithCorrectOriginFieldProxyAuth()
    {
        $testData                                 = $this->testData['testCreatePayoutWithoutOriginFieldPrivateAuth'];
        $testData['request']['url']               = '/payouts_with_otp';
        $testData['request']['content']['token']  = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']    = '0007';
        $testData['request']['content']['origin'] = Payout\Entity::DASHBOARD;

        $testData['response']['content']['origin'] = Payout\Entity::DASHBOARD;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreatePayoutWithIncorrectOriginFieldProxyAuth()
    {
        $testData                                = $this->testData['testCreatePayoutWithOriginFieldPrivateAuth'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $testData['response']['content']['error']['description'] = 'The selected origin is invalid.';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testGetVendorPaymentPayoutWithOrigin()
    {
        $this->testApprovePayoutWithNewWorkflowService();

        $payout = $this->getDbLastEntity('payout', 'live');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payouts_internal/' . $payout->getPublicId();

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testCreateVendorPaymentPayoutWithOrigin()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testCreatePayoutLinkPayoutWithOrigin()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $testData = $this->testData['testCreateVendorPaymentPayoutWithOrigin'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testCreateVendorPaymentPayoutWithoutOrigin()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $testData = $this->testData['testCreateVendorPaymentPayoutWithOrigin'];

        unset($testData['request']['content']['origin']);

        $testData['response']['content']['origin'] = Payout\Entity::API;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testCreatePayoutLinkPayoutWithoutOrigin()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $testData = $this->testData['testCreateVendorPaymentPayoutWithOrigin'];

        unset($testData['request']['content']['origin']);

        $testData['response']['content']['origin'] = Payout\Entity::API;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testCompositePayoutCreationViaNewCompositeFlowV1ForPayoutsToCard()
    {
        Queue::fake();

        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_PROCESS_ASYNC]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_ASYNC_INGRESS]);

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

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::ICIC
        ]);

        $this->ba->privateAuth();

        $request = $this->testData['testCompositePayoutCreationViaNewCompositeFlowV1ForPayoutsToCard']['request'];

        $response = $this->makeRequestAndGetContent($request);

        $payoutId = substr($response['id'], 5);

        $fundAccountDetails = $response[Payout\Entity::FUND_ACCOUNT];

        $metadata = [
            Payout\Entity::PAYOUT       => [
                Payout\Entity::ID         => $payoutId,
                Payout\Entity::CREATED_AT => $response[Payout\Entity::CREATED_AT],
            ],
            Payout\Entity::CONTACT      => [
                Payout\Entity::ID         => substr($fundAccountDetails[Payout\Entity::CONTACT][Payout\Entity::ID], 5),
                Payout\Entity::CREATED_AT => $fundAccountDetails[Payout\Entity::CONTACT][Payout\Entity::CREATED_AT]
            ],
            Payout\Entity::FUND_ACCOUNT => [
                Payout\Entity::ID         => substr($fundAccountDetails[Payout\Entity::ID], 3),
                Payout\Entity::CREATED_AT => $fundAccountDetails[Payout\Entity::CREATED_AT]
            ]
        ];

        $merchantId = $this->bankingBalance->merchant->getId();

        $payoutRequest = $request['content'];

        (new PayoutPostCreateProcessLowPriority('test', $payoutId, 'false', $metadata, $merchantId, $payoutRequest))->handle();

        $payout      = $this->getDbLastEntity(Constants\Entity::PAYOUT);
        $contact     = $this->getDbLastEntity(Constants\Entity::CONTACT);
        $fundAccount = $this->getDbLastEntity(Constants\Entity::FUND_ACCOUNT);
        $card        = $this->getDbLastEntity(Constants\Entity::CARD);

        $this->assertEquals($response[PayoutEntity::ID], $payout->getPublicId());
        $this->assertEquals($response[PayoutEntity::FUND_ACCOUNT][PayoutEntity::ID], $fundAccount->getPublicId());
        $this->assertEquals($response[PayoutEntity::FUND_ACCOUNT][PayoutEntity::CONTACT][PayoutEntity::ID],
                            $contact->getPublicId());
        $this->assertEquals($fundAccount->account->getId(), $card->getId());

        Queue::assertPushed(PayoutPostCreateProcessLowPriority::class, 1);
    }

    public function testCompositePayoutCreationViaNewCompositeFlowV1()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_PROCESS_ASYNC]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_ASYNC_INGRESS]);

        $this->ba->privateAuth();

        $request = $this->testData['testCompositePayoutCreationViaNewCompositeFlow']['request'];

        $response = $this->makeRequestAndGetContent($request);

        $payout      = $this->getDbLastEntity(Constants\Entity::PAYOUT);
        $contact     = $this->getDbLastEntity(Constants\Entity::CONTACT);
        $fundAccount = $this->getDbLastEntity(Constants\Entity::FUND_ACCOUNT);

        $this->assertEquals($response[PayoutEntity::ID], $payout->getPublicId());
        $this->assertEquals($response[PayoutEntity::FUND_ACCOUNT][PayoutEntity::ID], $fundAccount->getPublicId());
        $this->assertEquals($response[PayoutEntity::FUND_ACCOUNT][PayoutEntity::CONTACT][PayoutEntity::ID],
                            $contact->getPublicId());
    }

    public function testCreatePayoutForRequestSubmitted($isLpQueue = false)
    {
        $this->ba->privateAuth();

        $this->mockRazorxTreatment('yesbank',
                                   'on',
                                   'on',
                                   'off',
                                   'off',
                                   'on',
                                   'on',
                                   'on');

        if ($isLpQueue === true)
        {
            $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_PROCESS_ASYNC_LP]);
            $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);
        }
        else
        {
            $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_PROCESS_ASYNC]);
        }

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals('create_request_submitted', $payout['internal_status']);
        $this->assertEquals('processing', $payout['status']);
        $this->assertNotNull($payout['create_request_submitted_at']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertNull($payoutAttempt);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertNull($txn);
    }

    /**
     * In this test, we shall process a create_request_submitted payout
     * ( create_request_submitted -> created )
     */
    public function testProcessingOfCreateRequestSubmittedPayout()
    {
        $this->testCreatePayoutForRequestSubmitted();

        $payout = $this->getDbLastEntity('payout');

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should get processed since merchant has enough balance
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), 'false');

        $payout->reload();

        $publicResponse = $payout->toArrayPublic();

        $this->assertEquals('created', $payout['internal_status']);
        $this->assertEquals('processing', $publicResponse['status']);
        $this->assertNotNull($payout['initiated_at']);
    }

    /**
     * In this test, we shall process a create_request_submitted payout
     * ( create_request_submitted -> created )
     */
    public function testProcessingOfCreateRequestSubmittedPayoutWithHighTpsCompositePayoutFeature()
    {
        $this->testCreatePayoutForRequestSubmitted();

        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $payout = $this->getDbLastEntity('payout');

        $balance = $this->getDbLastEntity('balance');

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should get processed since merchant has enough balance
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), 'false');

        $payout->reload();

        $publicResponse = $payout->toArrayPublic();

        $updatedBalance = $this->getDbLastEntity('balance');

        $this->assertEquals('created', $payout['internal_status']);
        $this->assertEquals('processing', $publicResponse['status']);
        $this->assertNotNull($payout['initiated_at']);

        $transaction = $this->getDbLastEntity('transaction');

        /** @var PayoutsIntermediateTransactions\Entity $intermeditateTransaction */
        $intermeditateTransaction = $this->getDbLastEntity(Constants\Entity::PAYOUTS_INTERMEDIATE_TRANSACTIONS);

        $this->assertEquals($balance->getbalance(), $updatedBalance->getbalance() + $transaction->getAmount());
        $this->assertEquals(PayoutsIntermediateTransactions\Status::COMPLETED, $intermeditateTransaction->getStatus());
        $this->assertNotNull($intermeditateTransaction->getAttribute(PayoutsIntermediateTransactions\Entity::PENDING_AT));
        $this->assertNotNull($intermeditateTransaction->getAttribute(PayoutsIntermediateTransactions\Entity::COMPLETED_AT));
        $this->assertNull($intermeditateTransaction->getAttribute(PayoutsIntermediateTransactions\Entity::REVERSED_AT));
        $this->assertEquals($balance->getBalance() + $transaction->getNetAmount(), $transaction->getBalance());

        $this->assertEquals($transaction->getBalanceId(), $payout->getBalanceId());
        $this->assertEquals($transaction->getAmount(), $payout->getAmount() + $payout->getFees());
        $this->assertEquals($transaction->getAmount(), $intermeditateTransaction->getAttribute(PayoutsIntermediateTransactions\Entity::AMOUNT));

    }

    /**
     *  We are asserting that payout fees and tax will remain as it is if the credits are not
     *  sufficient, for a merchant on new credits flow
     */
    public function testProcessingOfCreateRequestSubmittedPayoutWithNewCreditsFlowButNotEqualToFees()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 700]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600, 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->testCreatePayoutForRequestSubmitted();

        $payout = $this->getDbLastEntity('payout');

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should get processed since merchant has enough balance
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), 'false');

        $payout->reload();

        $this->assertEquals(1062, $payout['fees']);
        $this->assertEquals(162, $payout['tax']);

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals(0, count($creditTxnEntities));

        $publicResponse = $payout->toArrayPublic();

        $this->assertEquals('created', $payout['internal_status']);
        $this->assertEquals('processing', $publicResponse['status']);
        $this->assertNotNull($payout['initiated_at']);
    }

    /**
     *  We are asserting that payout fees and tax will be adjusted by the
     *  reward_fee credits for create_request_submitted payout in the
     *  credits flow
     */
    public function testProcessingOfCreateRequestSubmittedPayoutWithNewCreditsFlow()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 300, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 900]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600, 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->testCreatePayoutForRequestSubmitted();

        $payout = $this->getDbLastEntity('payout');

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should get processed since merchant has enough balance
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), 'false');

        $payout->reload();

        $this->assertEquals(900, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(300, $creditEntities[0]['used']);
        $this->assertEquals(600, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'], $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(300, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'], $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(600, $creditTxnEntities[1]['credits_used']);

        $publicResponse = $payout->toArrayPublic();

        $this->assertEquals('created', $payout['internal_status']);
        $this->assertEquals('processing', $publicResponse['status']);
        $this->assertNotNull($payout['initiated_at']);
    }

    /**
     * In this test, we shall fail a create_request_submitted payout
     * ( create_request_submitted -> failed )
     */
    public function testProcessingOfCreateRequestSubmittedPayoutInsufficientBalance()
    {
        $this->testCreatePayoutForRequestSubmitted();

        $this->fixtures->edit('balance', $this->bankingBalance['id'], ['balance' => 0]);

        $payout = $this->getDbLastEntity('payout');

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should fail since merchant does not have enough balance
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), false);

        $payout->reload();

        $publicResponse = $payout->toArrayPublic();

        $this->assertEquals('failed', $payout['internal_status']);
        $this->assertEquals('failed', $publicResponse['status']);
        $this->assertNotNull($payout['failed_at']);
    }

    /**
     * In this test, we shall queue a create_request_submitted payout
     * ( create_request_submitted -> queued )
     */
    public function testProcessingOfCreateRequestSubmittedPayoutInsufficientBalanceQueueFlagTrue()
    {
        $this->testCreatePayoutForRequestSubmitted();

        $this->fixtures->edit('balance', $this->bankingBalance['id'], ['balance' => 0]);

        $payout = $this->getDbLastEntity('payout');

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as true for this test.
        // Payout should get queued since merchant does not have enough balance
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), true);

        $payout->reload();

        $publicResponse = $payout->toArrayPublic();

        $this->assertEquals('queued', $payout['internal_status']);
        $this->assertEquals('queued', $publicResponse['status']);
        $this->assertNotNull($payout['queued_at']);
        $this->assertEquals('low_balance', $payout['queued_reason']);
    }

    public function testCreatePayoutLinkPayoutWithoutSourceDetails()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testCreatePayoutLinkPayoutWithSourceDetails()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testCreateXpayrollPayoutWithSourceDetails()
    {
        $this->ba->appAuthTest($this->config['applications.xpayroll.secret']);

        $this->startTest();
    }

    public function testCreatePayoutWithMultipleSourceDetails()
    {
        $this->ba->appAuthTest($this->config['applications.xpayroll.secret']);

        $this->startTest();
    }

    public function testCreatePayoutLinkPayoutWithExtraFieldsInSourceDetails()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testCreatePayoutLinkPayoutWithIncorrectPriorityInSourceDetails()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testCreatePayoutLinkPayoutWithIncorrectPrioritySequenceInSourceDetails()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testCreatePayoutOnPrivateAuthWithSourceDetails()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutOnProxyAuthWithSourceDetails()
    {
        $this->ba->proxyAuth();

        $testData                                = $this->testData['testCreatePayoutOnPrivateAuthWithSourceDetails'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testCreatePayoutLinkPayoutWithSourceDetailsAsNotAnArray()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testFailPayoutWithErrorCodeAsPbankValidationError()
    {
        $this->createDirectAccountPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
                                             [
                                                 'source_id'   => $payout->getId(),
                                                 'source_type' => 'payout',
                                                 'is_fts'      => true,
                                             ])->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->ba->ftsAuth();
        $this->startTest();

        $payout->reload();

        $this->assertEquals('failed', $payout->getStatus());
        $this->assertEquals('Temporary Issue at Partner bank. Reinitiate transfer after 30 min.', $payout->getFailureReason());
    }

    public function testPayoutValidatePurposeForValidPurpose()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testPayoutValidatePurposeForInvalidPurpose()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();
    }

    public function testCreatePayoutWithDelayedSourceUpdater()
    {
        Queue::fake();

        $this->testCreatePayout();

        Queue::assertPushed(PayoutSourceUpdaterJob::class, 1);
    }

    public function testCreateVendorPaymentPayoutWithDuplicateSourceDetailsButDifferentPriorities()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testFetchPayoutWithSourceIdAndSourceTypeOnInternalAuth()
    {
        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        /** @var Payout\Entity $payout1 */
        $payout1 = $this->getDbLastEntity('payout');

        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        /** @var Payout\Entity $payout2 */
        $payout2 = $this->getDbLastEntity('payout');

        $payoutSources = $payout2->getSourceDetails()->toArray();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payouts_internal?product=banking&source_id=' . $payoutSources[0]['source_id'] .
                                      '&source_type=' . $payoutSources[0]['source_type'];

        $response = $this->startTest();

        // Now only one payout will be found. because of the same I-Key
        $this->assertEquals(1, $response['count']);

        $this->assertEquals($payout1->getPublicId(), $payout2->getPublicId());

        $responsePayoutIds = [$response['items'][0]['id']];

        $payoutIds = [$payout1->getPublicId()];

        $this->assertCount(0, array_diff($responsePayoutIds, $payoutIds));

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payoutSources];

        $this->assertArraySelectiveEquals($sourceDetails, $response['items'][0]);
    }

    public function testFetchPayoutsOnProxyAuth()
    {
        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        /** @var Payout\Entity $payout1 */
        $payout1 = $this->getDbLastEntity('payout');

        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payouts?product=banking';

        $this->ba->proxyAuth();

        $response = $this->startTest();

        // Now only one payout will be found. because of the same I-Key
        $this->assertEquals(1, $response['count']);

        $responsePayoutIds = [$response['items'][0]['id']];

        $payoutIds = [$payout1->getPublicId()];

        $this->assertCount(0, array_diff($responsePayoutIds, $payoutIds));

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payout1->getSourceDetails()->toArray()];

        $this->assertArraySelectiveEquals($sourceDetails, $response['items'][0]);
    }

    public function testFetchPayoutsOnXDemo()
    {

        $merchant = $this->fixtures->create('merchant',
                                            [
                                                'id'               => 'Hrw2ujXW6LGEk7',
                                                'pricing_plan_id'  => '1hDYlICobzOCYt',
                                                'business_banking' => 1
                                            ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT,
            'entity_id'   => $merchant['id'],
            'entity_type' => 'merchant',
        ]);

        $xBalance = $this->fixtures->create('balance',
                                            [
                                                'merchant_id'    => $merchant['id'],
                                                'type'           => 'banking',
                                                'account_type'   => 'shared',
                                                'account_number' => '2224440041626905',
                                                'balance'        => 3000000,
                                            ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->fixtures->create('contact',
                                ['id' => '1000002contact', 'name' => 'Contact X', 'merchant_id' => $merchant['id']]);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => 'D6Z9Jfir2egAUT',
                                    'source_type' => 'contact',
                                    'source_id'   => '1000002contact',
                                    'merchant_id' => $merchant['id']
                                ]);

        $this->fixtures->create('payout', [
            'id'              => 'DuuYxmO7Yegu3x',
            'fund_account_id' => 'D6Z9Jfir2egAUT',
            'pricing_rule_id' => 'Bbg7cl6t6I3XA5',
            'balance_id'      => $xBalance->getId(),
            'merchant_id'     => $merchant['id'],
            'created_at'      => 0
        ]);

        $this->fixtures->create('payout', [
            'id'              => 'DuuYxmO7Yegu3y',
            'fund_account_id' => 'D6Z9Jfir2egAUT',
            'pricing_rule_id' => 'Bbg7cl6t6I3XA5',
            'balance_id'      => $xBalance->getId(),
            'merchant_id'     => $merchant['id'],
        ]);

        $this->ba->proxyAuth('rzp_test_Hrw2ujXW6LGEk7', $user->getId());

        $this->startTest();
    }

    public function testFetchPayoutsOnPrivateAuth()
    {
        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        /** @var Payout\Entity $payout1 */
        $payout1 = $this->getDbLastEntity('payout');

        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        $this->testData[__FUNCTION__] = $this->testData['testFetchPayoutsOnProxyAuth'];

        $testData = &$this->testData[__FUNCTION__];

        $accountNumber = $this->bankingBalance->getAccountNumber();

        $testData['request']['url'] = '/payouts?account_number=' . $accountNumber;

        $this->ba->privateAuth();

        $response = $this->startTest();

        // Now only one payout will be found. because of the same I-Key
        $this->assertEquals(1, $response['count']);

        $responsePayoutIds = [$response['items'][0]['id']];

        $payoutIds = [$payout1->getPublicId()];

        $this->assertCount(0, array_diff($responsePayoutIds, $payoutIds));

        $this->assertFalse(array_key_exists(Payout\Entity::SOURCE_DETAILS, $response['items'][0]));

        $this->assertFalse(array_key_exists(Payout\Entity::ORIGIN, $response['items'][0]));
    }

    public function testProcessingOfCreateRequestSubmittedPayoutIfFreePayoutsAreAvailable()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->testProcessingOfCreateRequestSubmittedPayout();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        $payout = $this->getDbLastEntity('payout');

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $transactionId = $payout->transaction->getId();

        $transaction = $this->getDbEntityById('transaction', $transactionId)->toArray();

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $transactionId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $transactionId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA9",
            'percentage'      => null,
            'amount'          => 0,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $pricingRule = $this->getDbEntityById('pricing', $expectedBreakup['pricing_rule_id']);

        $this->assertEquals(BasicAuth\Type::PRIVATE_AUTH, $pricingRule->getAuthType());
    }

    /*
     * From now on, we are not going to allow fund account creation for scbl cards having network other than amex,
     * mastercard and visa.
     */
    public function testPayoutToSCBLCardWithNetworkOtherThanAmexMasterVisa()
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

        $this->fixtures->create('iin', [
            'iin'     => 652161,
            'network' => Network::$fullName[Network::RUPAY],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::SCBL
        ]);

        $countOfCardsBeforeFundAccountCreateRequest = count($this->getDbEntities('card'));

        $this->ba->privateAuth();

        $this->startTest();

        $countOfCardsAfterFundAccountCreateRequest = count($this->getDbEntities('card'));

        // This is to check that card entity is created even though fund account creation fails.
        $this->assertEquals($countOfCardsBeforeFundAccountCreateRequest + 1,
                            $countOfCardsAfterFundAccountCreateRequest);
    }

    /*
     * This test checks for the case if fund accounts for scbl cards with network other than amex, master card and
     * visa are already created before this code went live, the payout creation should fail for those fund accounts.
     */
    public function testPayoutToSCBLCardWithNetworkOtherThanAmexMasterVisaIfFundAccountAlreadyCreated()
    {
        $this->markTestSkipped("Standalone Payouts are disabled for cards");

        $card = $this->fixtures->create(
            'card',
            [
                'merchant_id'        => '10000000000000',
                'name'               => 'Prashanth YV',
                'expiry_month'       => 10,
                'expiry_year'        => 2030,
                'iin'                => 652161,
                'last4'              => '9536',
                'network'            => Network::$fullName[Network::RUPAY],
                'type'               => 'credit',
                'issuer'             => 'SCBL',
                'emi'                => 1,
                'vault'              => 'rzpvault',
                'vault_token'        => 'MjAzMDQwMDAwMDEyMTIxMg==',
                'global_fingerprint' => '==gMxITMyEDMwADMwQDMzAjM'
            ]);

        $this->fixtures->create('fund_account', [
            'id'           => '100000000001fa',
            'source_id'    => '1000001contact',
            'source_type'  => 'contact',
            'account_type' => 'card',
            'account_id'   => $card->getId(),
            'merchant_id'  => 10000000000000,
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

        $this->startTest();
    }

    public function testPayoutToSCBLCardWithMastercardIfFundAccountAlreadyCreated()
    {
        $this->markTestSkipped("Standalone Payouts are disabled for cards");

        $card = $this->fixtures->create(
            'card',
            [
                'merchant_id'        => '10000000000000',
                'name'               => 'Prashanth YV',
                'expiry_month'       => 10,
                'expiry_year'        => 2030,
                'iin'                => 340169,
                'last4'              => '0137',
                'network'            => Network::$fullName[Network::MC],
                'type'               => 'credit',
                'issuer'             => 'SCBL',
                'emi'                => 1,
                'vault'              => 'rzpvault',
                'vault_token'        => 'MjAzMDQwMDAwMDEyMTIxMg==',
                'global_fingerprint' => '==gMxITMyEDMwADMwQDMzAjM'
            ]);

        $this->fixtures->create('fund_account', [
            'id'           => '100000000001fa',
            'source_id'    => '1000001contact',
            'source_type'  => 'contact',
            'account_type' => 'card',
            'account_id'   => $card->getId(),
            'merchant_id'  => 10000000000000,
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

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = 'fa_100000000001fa';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testPayoutToSCBLCardWithVisaIfFundAccountAlreadyCreated()
    {
        $this->markTestSkipped("Standalone Payouts are disabled for cards");

        $card = $this->fixtures->create(
            'card',
            [
                'merchant_id'        => '10000000000000',
                'name'               => 'Prashanth YV',
                'expiry_month'       => 10,
                'expiry_year'        => 2030,
                'iin'                => 402874,
                'last4'              => '2006',
                'network'            => Network::$fullName[Network::VISA],
                'type'               => 'credit',
                'issuer'             => 'SCBL',
                'emi'                => 1,
                'vault'              => 'rzpvault',
                'vault_token'        => 'MjAzMDQwMDAwMDEyMTIxMg==',
                'global_fingerprint' => '==gMxITMyEDMwADMwQDMzAjM'
            ]);

        $this->fixtures->create('fund_account', [
            'id'           => '100000000001fa',
            'source_id'    => '1000001contact',
            'source_type'  => 'contact',
            'account_type' => 'card',
            'account_id'   => $card->getId(),
            'merchant_id'  => 10000000000000,
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

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = 'fa_100000000001fa';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testSourceCreationInCaseOfPendingPayoutCreatedByVendorPayments()
    {
        $this->liveSetUp();

        $this->setupWorkflowForLiveMode();
        $this->disableWorkflowMocks();

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payout->getSourceDetails()->toArray()];

        $this->assertArraySelectiveEquals($sourceDetails, $response);
    }

    public function testTrueSkipWorkflowForXPayrollPayouts()
    {
        $this->liveSetUp();

        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::SKIP_WF_FOR_PAYROLL,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->setupWorkflowForLiveMode();
        $this->disableWorkflowMocks();

        $this->ba->appAuthLive($this->config['applications.xpayroll.secret']);

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payout->getSourceDetails()->toArray()];

        $this->assertEquals(WorkflowFeature::WORKFLOW_FEATURES[Feature\Constants::SKIP_WF_FOR_PAYROLL],
                            $payout[Payout\Entity::WORKFLOW_FEATURE]);

        $this->assertArraySelectiveEquals($sourceDetails, $response);
    }

    public function testEnableWorkflowForInternalContactPayoutCreatedByVendorPayments()
    {
        $this->liveSetUp();

        $this->setupWorkflowForLiveMode();

        $this->disableWorkflowMocks();

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('live')->edit('contact', '1000001contact', ['type' => 'rzp_tax_pay']);

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payout->getSourceDetails()->toArray()];

        $this->assertArraySelectiveEquals($sourceDetails, $response);

        $this->assertEquals(Payout\Status::PENDING, $payout->getStatus());
    }

    public function testDisablePayoutServicePayoutCreationForInternalPayoutFeatureEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_SERVICE_ENABLED]);

        $this->liveSetUp();

        $this->setupWorkflowForLiveMode();

        $this->disableWorkflowMocks();

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('live')->edit('contact', '1000001contact', ['type' => 'rzp_tax_pay']);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals($payout['workflow_feature'], 4);
        $this->assertEquals(false, $payout['is_payout_service']);
    }

    public function testDisableWorkflowForInternalContactPayoutCreatedByVendorPayments()
    {
        $this->liveSetUp();

        $this->setupWorkflowForLiveMode();

        $this->disableWorkflowMocks();

        $this->ba->appAuthLive($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('live')->edit('contact', '1000001contact', ['type' => 'rzp_tax_pay']);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals(Payout\Status::CREATED, $payout->getStatus());
    }

    public function testDefaultFlowForInternalContactPayoutCreatedByVendorPayments()
    {
        unset($this->testData['testDisableWorkflowForInternalContactPayoutCreatedByVendorPayments']
              ['request']['content']['enable_workflow_for_internal_contact']);

        $this->testDisableWorkflowForInternalContactPayoutCreatedByVendorPayments();
    }

    public function testSourceCreationInCaseOfQueuedPayoutCreatedByVendorPayments()
    {
        $balance = $this->bankingBalance;

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '20000']);

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payout->getSourceDetails()->toArray()];

        $this->assertArraySelectiveEquals($sourceDetails, $response);
    }

    public function testSourceCreationInCaseOfCompositePayoutCreatedBySettlements()
    {
        $balance = $this->bankingBalance;

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '20000']);

        $this->ba->appAuthTest($this->config['applications.settlements_service.secret']);

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payout->getSourceDetails()->toArray()];

        $this->assertArraySelectiveEquals($sourceDetails, $response);
    }

    public function testIdempotencyInCaseOfCompositePayoutCreatedBySettlementsWithDifferentRequestContents()
    {
        $balance = $this->bankingBalance;

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '20000']);

        $this->ba->settlementsAuth();

        $this->testSourceCreationInCaseOfCompositePayoutCreatedBySettlements();

        $payout = $this->getDbLastEntity('payout');

        $testData = &$this->testData[__FUNCTION__];
        // This changes the request body
        $testData['request']['content']['contact']['name'] = 'Test test';

        $this->startTest();
    }

    public function testIdempotencyInCaseOfCompositePayoutCreatedBySettlementsWithSameRequestContents()
    {
        $balance = $this->bankingBalance;

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '20000']);

        $this->ba->settlementsAuth();

        $this->testSourceCreationInCaseOfCompositePayoutCreatedBySettlements();

        $payout = $this->getDbLastEntity('payout');

        $response = $this->startTest();

        $this->assertEquals($payout->getPublicId(), $response['id']);
    }

    public function testSourceCreationInCaseOfInternalContactPayoutCreatedByXpayroll()
    {
        $balance = $this->bankingBalance;

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '2000000']);

        $this->ba->xpayrollAuth();

        $contact = $this->fixtures->create('contact',
                                           [
                                               'name' => 'test name',
                                               'type' => \RZP\Models\Contact\Type::XPAYROLL_INTERNAL
                                           ]);

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payout->getSourceDetails()->toArray()];

        $this->assertArraySelectiveEquals($sourceDetails, $response);
    }

    public function testIdempotencyInCaseOfInternalContactPayoutCreatedByXpayrollWithSameRequestContents()
    {
        $balance = $this->bankingBalance;

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '20000']);

        $this->ba->xpayrollAuth();

        $this->testSourceCreationInCaseOfInternalContactPayoutCreatedByXpayroll();

        $payout = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__] = $this->testData['testSourceCreationInCaseOfInternalContactPayoutCreatedByXpayroll'];

        $response = $this->startTest();

        $this->assertEquals($payout->getPublicId(), $response['id']);
    }

    public function testIdempotencyInCaseOfInternalContactPayoutCreatedByXpayrollWithDifferentRequestContents()
    {
        $balance = $this->bankingBalance;

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '20000']);

        $this->ba->xpayrollAuth();

        $this->testSourceCreationInCaseOfInternalContactPayoutCreatedByXpayroll();

        $this->testData[__FUNCTION__]['request'] = $this->testData['testSourceCreationInCaseOfInternalContactPayoutCreatedByXpayroll']['request'];

        // This changes the request body
        $this->testData[__FUNCTION__]['request']['content']['amount'] = 1000;

        $this->startTest();
    }

    /**
     * Keeping this test here because although we are testing for fund account dedup, it's happening via
     * composite API which requires certain setups which already exist in payoutTest
     */
    public function testDuplicateFundAccountInCaseOfCompositePayoutCreatedBySettlements()
    {
        $fundAccountCountBefore = count($this->getDbEntities('fund_account'));

        $this->testSourceCreationInCaseOfCompositePayoutCreatedBySettlements();

        $balance = $this->bankingBalance;

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '20000']);

        $this->ba->appAuthTest($this->config['applications.settlements_service.secret']);

        $response = $this->startTest();

        $this->getDbLastEntity('payout');

        $fundAccountCountAfter = count($this->getDbEntities('fund_account'));

        $this->assertEquals(1, ($fundAccountCountAfter - $fundAccountCountBefore));
    }

    public function testPayoutSetStatusQueuePushSkippedWhenSourceDetailsAbsent()
    {
        $this->app->instance('rzp.mode', "live");

        Queue::fake();

        $payout = $this->fixtures->create('payout', [
            'status'          => 'created',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $payout->setStatus(Status::PROCESSING);

        Queue::assertNotPushed(PayoutSourceUpdaterJob::class);

        // now adding payout source and QueuePush Should Happen
        $this->fixtures->create('payout_source',
                                [
                                    'payout_id'   => $payout->getId(),
                                    'source_id'   => 'vdpm_1',
                                    'source_type' => 'vendor_payments',
                                    'priority'    => 1
                                ]);

        $payout->setStatus(Status::PROCESSED);

        Queue::assertPushed(PayoutSourceUpdaterJob::class);
    }

    public function testPayoutSetStatusQueuePushForSettlementsPayout()
    {
        $this->app->instance('rzp.mode', "live");

        Queue::fake();

        $payout = $this->fixtures->create('payout', [
            'status' => 'created'
        ]);

        // now adding payout source and QueuePush Should Happen
        $this->fixtures->create('payout_source',
                                [
                                    'payout_id'   => $payout->getId(),
                                    'source_id'   => 'vdpm_1',
                                    'source_type' => 'settlements',
                                    'priority'    => 1
                                ]);

        $payout->setStatus(Status::PROCESSING);

        Queue::assertPushed(PayoutSourceUpdaterJob::class);
    }

    public function testPayoutSetStatusQueueNotPushedWhenPayoutLinkIDIsSet()
    {
        $this->app->instance('rzp.mode', "live");

        Queue::fake();

        $contact = $this->getDbLastEntity('contact');

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $payout = $this->fixtures->create('payout', [
            'status'          => 'created',
            'payout_link_id'  => $payoutLink->getId(),
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $payout->setStatus(Status::PROCESSING);

        // not pushed to queue based on payout_link_id column in payouts table
        Queue::assertNotPushed(PayoutSourceUpdaterJob::class);
    }

    public function testPayoutSetStatusQueuePushWhenPayoutLinkSourceIsPresent()
    {
        $this->app->instance('rzp.mode', "live");

        Queue::fake();

        $contact = $this->getDbLastEntity('contact');

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $payout = $this->fixtures->create('payout', [
            'status'          => 'created',
            'payout_link_id'  => $payoutLink->getId(),
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $this->fixtures->create('payout_source',
                                [
                                    'payout_id'   => $payout->getId(),
                                    'source_id'   => $payoutLink->getId(),
                                    'source_type' => 'payout_links',
                                    'priority'    => 1
                                ]);

        $payout->setStatus(Status::PROCESSING);

        Queue::assertPushed(PayoutSourceUpdaterJob::class);
    }

    public function testPayoutSetStatusQueuePushWhenPayoutCreated()
    {
        $this->app->instance('rzp.mode', "live");

        Queue::fake();

        $payout = $this->fixtures->create('payout', [
            'status'          => 'initiated',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $payout->setStatus(Status::CREATED);

        Queue::assertPushed(PayoutSourceUpdaterJob::class, 1);
    }

    public function testBackFillDataForExistingBulkUsers()
    {
        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->ba->adminAuth();

        $this->startTest();

        $payoutAmountType = (new Payout\Service)->getSettingsAccessor($merchant)->get(Batch\Constants::TYPE);

        $this->assertEquals('paise', $payoutAmountType);
    }

    public function testUpdateBulkPayoutAmountTypeByOwner()
    {
        // Using below two to set up owner role easily since the function already does that
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $merchant = $this->getDbEntityById('merchant', '10000000000000', 'live');

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $payoutAmountType = (new Payout\Service)->getSettingsAccessor($merchant)->get(Batch\Constants::TYPE);

        $this->assertEquals('rupees', $payoutAmountType);
    }

    public function testUpdateBulkPayoutAmountTypeByNonOwnerRole()
    {
        // Using below two to set up a non owner role easily since the function already does that
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $merchant = $this->getDbEntityById('merchant', '10000000000000', 'live');

        $this->mockRazorxTreatment();

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->checkerRoleUser->getId());

        $this->startTest();
    }

    public function testUsersApiForExistingNonBulkUser()
    {
        // Using below two to set up owner role easily since the function already does that
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        // Timestamp of 1 hour into the future, so that all merchants created before this become existing non-bulk
        $futureTime = Carbon::now()->getTimestamp() + 3600;

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP => $futureTime]);

        $user = $this->getDbLastEntity('user', 'live');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/users/' . $user->getId();

        $this->ba->proxyAuth('rzp_live');

        $response = $this->startTest();

        $this->assertEquals(Payout\Entity::EXISTING_NON_BULK_USER, $response['merchants'][0]['bulk_payouts_user_type']);
    }

    public function testUsersApiForNewBulkUser()
    {
        // Using below two to set up owner role easily since the function already does that
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        // Timestamp of 2010 (random). So that any merchant created after that will be considered a new merchant.
        $pastTime = Carbon::create(2010, 1, 1, null, null, null)->getTimestamp();

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP => $pastTime]);

        $user = $this->getDbLastEntity('user', 'live');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/users/' . $user->getId();

        $this->ba->proxyAuth('rzp_live');

        $response = $this->startTest();

        $this->assertEquals(Payout\Entity::NEW_USER, $response['merchants'][0]['bulk_payouts_user_type']);
    }

    public function testUsersApiForExistingBulkUserAmountTypePaise()
    {
        // Using below two to set up owner role easily since the function already does that
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        // Below request is to backfill paise as bulk amount type for existing bulk merchants
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/amount_type',
            'content' => [
                'merchant_ids' => ['10000000000000']
            ]
        ];

        $this->ba->adminAuth('live');

        $this->makeRequestAndGetContent($request);

        // Timestamp of 2010 (random). So that any merchant created after that will be considered a new merchant.
        $pastTime = Carbon::create(2010, 1, 1, null, null, null)->getTimestamp();

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP => $pastTime]);

        $user = $this->getDbLastEntity('user', 'live');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/users/' . $user->getId();

        $this->ba->proxyAuth('rzp_live');

        $response = $this->startTest();

        $this->assertEquals(Payout\Entity::EXISTING_BULK_USER_PAISE, $response['merchants'][0]['bulk_payouts_user_type']);
    }

    public function testUsersApiForExistingBulkUserAmountTypeRupees()
    {
        // Using below two to set up owner role easily since the function already does that
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        // Below request is to backfill paise as bulk amount type for existing bulk merchants
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/bulk/amount_type',
            'content' => [
                'merchant_ids' => ['10000000000000']
            ]
        ];

        $this->ba->adminAuth('live');

        $this->makeRequestAndGetContent($request);

        // Below is the request from a merchant's owner user to shift from paise to rupees
        $request = [
            'method'  => 'PATCH',
            'url'     => '/payouts/bulk/amount_type',
            'content' => [
                'merchant_ids' => ['10000000000000'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ];

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->makeRequestAndGetContent($request);

        // Timestamp of 2010 (random). So that any merchant created after that will be considered a new merchant.
        $pastTime = Carbon::create(2010, 1, 1, null, null, null)->getTimestamp();

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP => $pastTime]);

        $user = $this->getDbLastEntity('user', 'live');

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/users/' . $user->getId();

        $this->ba->proxyAuth('rzp_live');

        $response = $this->startTest();

        $this->assertEquals(Payout\Entity::EXISTING_BULK_USER_RUPEES, $response['merchants'][0]['bulk_payouts_user_type']);
    }

    public function testCsvSampleFileForBulkPayouts()
    {
        // Disabling amazon pay feature so that merchant gets file in old format
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_X_AMAZONPAY,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->proxyAuth();

        $res = $this->startTest();

        // Below function returns the file contents where every row is a string (all columns are comma separated)
        $fileContent = file($res['signed_url']);

        // Assert that there are only four rows. The first being the header and the second being the row with data.
        $this->assertEquals(4, count($fileContent));

        $expectedRowOne = "RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,Payout Purpose," .
                          "Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc," .
                          "Fund Account Number,Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email," .
                          "Contact Type,Contact Email,Contact Mobile,Contact Reference Id,notes[place],notes[code]";

        $this->assertEquals($expectedRowOne, trim($fileContent[0]));

        // NOTE : Account number is set as a sample : 7878780021057150
        $expectedRowTwo = "7878780021057150,10,INR,NEFT,refund,,bank_account,sample,SBIN0007105," .
                          "1234567890,,,sample,Sample Narration,,,vendor,sample@example.com,9988998899,,Bangalore," .
                          "This is a sample note";

        $this->assertEquals($expectedRowTwo, trim($fileContent[1]));

        // NOTE : Account number is set as a sample : 7878780021057150
        $expectedRowThree = "7878780021057150,10,INR,UPI,refund,,vpa,,,,sample@example,,sample,Sample Narration,,," .
                            "vendor,sample@example.com,9988998899,,Bangalore,This is a sample note";

        $this->assertEquals($expectedRowThree, trim($fileContent[2]));

        // NOTE : Account number is set as a sample : 7878780021057150
        $expectedRowFour = "7878780021057150,10,INR,NEFT,refund,fa_ABCDEFGGFEDCBA,,,,,,,sample,Sample Narration,,," .
                           "vendor,sample@example.com,9988998899,,Bangalore,This is a sample note";

        $this->assertEquals($expectedRowFour, trim($fileContent[3]));
    }

    public function testCsvSampleFileForBulkPayoutsAmazonPayEnabled()
    {
        $this->ba->proxyAuth();

        $res = $this->startTest();

        // Below function returns the file contents where every row is a string (all columns are comma separated)
        $fileContent = file($res['signed_url']);

        // Assert that there are only five rows. The first being the header and the second being the row with data.
        $this->assertEquals(5, count($fileContent));

        $expectedRowOne = "RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,Payout Purpose," .
                          "Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc," .
                          "Fund Account Number,Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email," .
                          "Contact Type,Contact Email,Contact Mobile,Contact Reference Id,notes[place],notes[code]";

        $this->assertEquals($expectedRowOne, trim($fileContent[0]));

        // NOTE : Account number is set as a sample : 7878780021057150
        $expectedRowTwo = "7878780021057150,10,INR,NEFT,refund,,bank_account,sample,SBIN0007105," .
                          "1234567890,,,sample,Sample Narration,,,vendor,sample@example.com,9988998899,,Bangalore," .
                          "This is a sample note";

        $this->assertEquals($expectedRowTwo, trim($fileContent[1]));

        // NOTE : Account number is set as a sample : 7878780021057150
        $expectedRowThree = "7878780021057150,10,INR,UPI,refund,,vpa,,,,sample@example,,sample,Sample Narration,,," .
                            "vendor,sample@example.com,9988998899,,Bangalore,This is a sample note";

        $this->assertEquals($expectedRowThree, trim($fileContent[2]));

        $expectedRowFour = "7878780021057150,10,INR,amazonpay,refund,,wallet,sample,," .
                           ",,+918124632237,sample,Sample Narration,,sample@example.com,vendor,sample@example.com," .
                           "9988998899,,Bangalore,This is a sample note";

        $this->assertEquals($expectedRowFour, trim($fileContent[3]));

        // NOTE : Account number is set as a sample : 7878780021057150
        $expectedRowFive = "7878780021057150,10,INR,NEFT,refund,fa_ABCDEFGGFEDCBA,,,,,,,sample,Sample Narration,,," .
                           "vendor,sample@example.com,9988998899,,Bangalore,This is a sample note";

        $this->assertEquals($expectedRowFive, trim($fileContent[4]));
    }

    public function testCsvTemplateFileForBulkPayouts()
    {
        // Disabling amazon pay feature so that merchant gets file in old format
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_X_AMAZONPAY,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->proxyAuth();

        $res = $this->startTest();

        // Below function returns the file contents where every row is a string (all columns are comma separated)
        $fileContent = file($res['signed_url']);

        // Assert that there are only four rows. The first being the header, the second and third being the rows with data.
        $this->assertEquals(4, count($fileContent));

        $expectedRowOne = "RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,Payout Purpose," .
                          "Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc," .
                          "Fund Account Number,Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email," .
                          "Contact Type,Contact Email,Contact Mobile,Contact Reference Id,notes[place],notes[code]";

        $this->assertEquals($expectedRowOne, trim($fileContent[0]));

        // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
        $expectedRowTwo = "2224440041626905,10,INR,NEFT,refund,,bank_account,sample,SBIN0007105," .
                          "1234567890,,,sample,Sample Narration,,,vendor,sample@example.com,9988998899,,Bangalore," .
                          "This is a sample note";

        $this->assertEquals($expectedRowTwo, trim($fileContent[1]));

        // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
        $expectedRowThree = "2224440041626905,10,INR,UPI,refund,,vpa,,,,sample@example,,sample,Sample Narration,,," .
                            "vendor,sample@example.com,9988998899,,Bangalore,This is a sample note";

        $this->assertEquals($expectedRowThree, trim($fileContent[2]));

        // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
        $expectedRowFour = "2224440041626905,10,INR,NEFT,refund,fa_ABCDEFGGFEDCBA,,,,,,,sample,Sample Narration,,," .
                           "vendor,sample@example.com,9988998899,,Bangalore,This is a sample note";

        $this->assertEquals($expectedRowFour, trim($fileContent[3]));
    }

    public function testCsvTemplateFileForBulkPayoutsAmazonPayEnabled()
    {
        $this->ba->proxyAuth();

        $res = $this->startTest();

        // Below function returns the file contents where every row is a string (all columns are comma separated)
        $fileContent = file($res['signed_url']);

        // Assert that there are only five rows. The first being the header, the second and third being the rows with data.
        $this->assertEquals(5, count($fileContent));

        $expectedRowOne = "RazorpayX Account Number,Payout Amount (in Rupees),Payout Currency,Payout Mode,Payout Purpose," .
                          "Fund Account Id,Fund Account Type,Fund Account Name,Fund Account Ifsc," .
                          "Fund Account Number,Fund Account Vpa,Fund Account Phone Number,Contact Name,Payout Narration,Payout Reference Id,Fund Account Email," .
                          "Contact Type,Contact Email,Contact Mobile,Contact Reference Id,notes[place],notes[code]";

        $this->assertEquals($expectedRowOne, trim($fileContent[0]));

        // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
        $expectedRowTwo = "2224440041626905,10,INR,NEFT,refund,,bank_account,sample,SBIN0007105," .
                          "1234567890,,,sample,Sample Narration,,,vendor,sample@example.com,9988998899,,Bangalore," .
                          "This is a sample note";

        $this->assertEquals($expectedRowTwo, trim($fileContent[1]));

        // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
        $expectedRowThree = "2224440041626905,10,INR,UPI,refund,,vpa,,,,sample@example,,sample,Sample Narration,,," .
                            "vendor,sample@example.com,9988998899,,Bangalore,This is a sample note";

        $this->assertEquals($expectedRowThree, trim($fileContent[2]));

        // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
        $expectedRowFour = "2224440041626905,10,INR,amazonpay,refund,,wallet,sample,," .
                           ",,+918124632237,sample,Sample Narration,,sample@example.com,vendor,sample@example.com,9988998899,,Bangalore," .
                           "This is a sample note";

        $this->assertEquals($expectedRowFour, trim($fileContent[3]));

        // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
        $expectedRowFive = "2224440041626905,10,INR,NEFT,refund,fa_ABCDEFGGFEDCBA,,,,,,,sample,Sample Narration,,," .
                           "vendor,sample@example.com,9988998899,,Bangalore,This is a sample note";

        $this->assertEquals($expectedRowFive, trim($fileContent[4]));
    }

    public function testXlsxTemplateFileForBulkPayouts()
    {
        // Disabling amazon pay feature so that merchant gets file in old format
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_X_AMAZONPAY,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->proxyAuth();

        $res = $this->startTest();

        $spreadsheet = IOFactory::load($res['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(5, $activeSheet->getHighestRow());

        $expectedData = [
            [
                // Since these are merged columns, the excel readers reads the value in the first column
                // and reads the others as null
                'Mandatory Fields',
                null,
                null,
                null,
                null,
                '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'Optional Fields',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
            [
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Fund Account Phone Number',
                'Contact Name',
                'Payout Narration',
                'Payout Reference Id',
                'Fund Account Email',
                'Contact Type',
                'Contact Email',
                'Contact Mobile',
                'Contact Reference Id',
                'notes[place]',
                'notes[code]',
            ],
            [
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                '2224440041626905 ',
                10,
                'INR',
                'NEFT',
                'refund',
                null,
                'bank_account',
                'sample',
                'SBIN0007105',
                '1234567890 ',
                null,
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',  // Todo: Mehul to validate this change
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                '2224440041626905 ',
                10,
                'INR',
                'UPI',
                'refund',
                null,
                'vpa',
                null,
                null,
                ' ',
                'sample@example',
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                '2224440041626905 ',
                10,
                'INR',
                'NEFT',
                'refund',
                'fa_ABCDEFGGFEDCBA',
                null,
                null,
                null,
                ' ',
                null,
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
        ];

        for ($row = 1; $row <= 5; $row++)
        {
            for ($col = 1; $col <= 22; $col++)
            {
                $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();

                $this->assertEquals($expectedData[$row - 1][$col - 1], $cellValue);
            }
        }
    }

    public function testXlsxTemplateFileForBulkPayoutsAmazonPayEnabled()
    {
        $this->ba->proxyAuth();

        $res = $this->startTest();

        $spreadsheet = IOFactory::load($res['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(6, $activeSheet->getHighestRow());

        $expectedData = [
            [
                // Since these are merged columns, the excel readers reads the value in the first column
                // and reads the others as null
                'Mandatory Fields',
                null,
                null,
                null,
                null,
                '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'Optional Fields',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
            [
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Fund Account Phone Number',
                'Contact Name',
                'Payout Narration',
                'Payout Reference Id',
                'Fund Account Email',
                'Contact Type',
                'Contact Email',
                'Contact Mobile',
                'Contact Reference Id',
                'notes[place]',
                'notes[code]',
            ],
            [
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                '2224440041626905 ',
                10,
                'INR',
                'NEFT',
                'refund',
                null,
                'bank_account',
                'sample',
                'SBIN0007105',
                '1234567890 ',
                null,
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                '2224440041626905 ',
                10,
                'INR',
                'UPI',
                'refund',
                null,
                'vpa',
                null,
                null,
                ' ',
                'sample@example',
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                '2224440041626905 ',
                10,
                'INR',
                'amazonpay',
                'refund',
                null,
                'wallet',
                'sample',
                null,
                ' ',
                null,
                '+918124632237',
                'sample',
                'Sample Narration',
                null,
                'sample@example.com',
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a merchant's banking balance's account number : 2224440041626905
                '2224440041626905 ',
                10,
                'INR',
                'NEFT',
                'refund',
                'fa_ABCDEFGGFEDCBA',
                null,
                null,
                null,
                ' ',
                null,
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
        ];

        for ($row = 1; $row <= 6; $row++)
        {
            for ($col = 1; $col <= 22; $col++)
            {
                $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();

                $this->assertEquals($expectedData[$row - 1][$col - 1], $cellValue);
            }
        }
    }

    public function testXlsxSampleFileForBulkPayouts()
    {
        // Disabling amazon pay feature so that merchant gets file in old format
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_X_AMAZONPAY,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->proxyAuth();

        $res = $this->startTest();

        $spreadsheet = IOFactory::load($res['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(5, $activeSheet->getHighestRow());

        $expectedData = [
            [
                // Since these are merged columns, the excel readers reads the value in the first column
                // and reads the others as null
                'Mandatory Fields',
                null,
                null,
                null,
                null,
                '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'Optional Fields',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
            [
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Fund Account Phone Number',
                'Contact Name',
                'Payout Narration',
                'Payout Reference Id',
                'Fund Account Email',
                'Contact Type',
                'Contact Email',
                'Contact Mobile',
                'Contact Reference Id',
                'notes[place]',
                'notes[code]',
            ],
            [
                // NOTE : Account number is set as a sample account number account number : 7878780021057150
                '7878780021057150 ',
                10,
                'INR',
                'NEFT',
                'refund',
                null,
                'bank_account',
                'sample',
                'SBIN0007105',
                '1234567890 ',
                null,
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a sample account number account number : 7878780021057150
                '7878780021057150 ',
                10,
                'INR',
                'UPI',
                'refund',
                null,
                'vpa',
                null,
                null,
                ' ',
                'sample@example',
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a sample account number account number : 7878780021057150
                '7878780021057150 ',
                10,
                'INR',
                'NEFT',
                'refund',
                'fa_ABCDEFGGFEDCBA',
                null,
                null,
                null,
                ' ',
                null,
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
        ];

        for ($row = 1; $row <= 5; $row++)
        {
            for ($col = 1; $col <= 22; $col++)
            {
                $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();

                $this->assertEquals($expectedData[$row - 1][$col - 1], $cellValue);
            }
        }
    }

    public function testXlsxSampleFileForBulkPayoutsAmazonPayEnabled()
    {
        $this->ba->proxyAuth();

        $res = $this->startTest();

        $spreadsheet = IOFactory::load($res['signed_url']);

        $activeSheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(6, $activeSheet->getHighestRow());

        $expectedData = [
            [
                // Since these are merged columns, the excel readers reads the value in the first column
                // and reads the others as null
                'Mandatory Fields',
                null,
                null,
                null,
                null,
                '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'Optional Fields',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
            [
                'RazorpayX Account Number',
                'Payout Amount (in Rupees)',
                'Payout Currency',
                'Payout Mode',
                'Payout Purpose',
                'Fund Account Id',
                'Fund Account Type',
                'Fund Account Name',
                'Fund Account Ifsc',
                'Fund Account Number',
                'Fund Account Vpa',
                'Fund Account Phone Number',
                'Contact Name',
                'Payout Narration',
                'Payout Reference Id',
                'Fund Account Email',
                'Contact Type',
                'Contact Email',
                'Contact Mobile',
                'Contact Reference Id',
                'notes[place]',
                'notes[code]',
            ],
            [
                // NOTE : Account number is set as a sample account number account number : 7878780021057150
                '7878780021057150 ',
                10,
                'INR',
                'NEFT',
                'refund',
                null,
                'bank_account',
                'sample',
                'SBIN0007105',
                '1234567890 ',
                null,
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a sample account number account number : 7878780021057150
                '7878780021057150 ',
                10,
                'INR',
                'UPI',
                'refund',
                null,
                'vpa',
                null,
                null,
                ' ',
                'sample@example',
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a sample account number account number : 7878780021057150
                '7878780021057150 ',
                10,
                'INR',
                'amazonpay',
                'refund',
                null,
                'wallet',
                'sample',
                null,
                ' ',
                null,
                '+918124632237',
                'sample',
                'Sample Narration',
                null,
                'sample@example.com',
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
            [
                // NOTE : Account number is set as a sample account number account number : 7878780021057150
                '7878780021057150 ',
                10,
                'INR',
                'NEFT',
                'refund',
                'fa_ABCDEFGGFEDCBA',
                null,
                null,
                null,
                ' ',
                null,
                null,
                'sample',
                'Sample Narration',
                null,
                null,
                'vendor',
                'sample@example.com',
                '9988998899 ',
                null,
                'Bangalore',
                'This is a sample note',
            ],
        ];

        for ($row = 1; $row <= 6; $row++)
        {
            for ($col = 1; $col <= 22; $col++)
            {
                $cellValue = $activeSheet->getCellByColumnAndRow($col, $row, false)->getValue();

                $this->assertEquals($expectedData[$row - 1][$col - 1], $cellValue);
            }
        }
    }

    public function testCreateBulkPayoutWithAmountTypeRupees()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testCreatePayoutViaUpi()
    {
        $contact = $this->getDbLastEntity('contact');

        $this->fixtures->create('fund_account:vpa', [
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contact->getId(),
        ]);

        $this->startTest();
    }

    public function testCreatePayoutViaAmazonPay()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $contact = $this->getDbLastEntity('contact');

        $this->fixtures->create('fund_account:wallet_account', [
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contact->getId(),
        ]);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        // Process the amazonpay payout
        $this->updateFtaAndSource($payout['id'], 'processed');

        $payoutCreated = $this->getDbLastEntity('payout');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'payout_initiated',
            'payout_processed',
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutCreated->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);

        // Not passed in payout initiated payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);

        // Passed in payout processed payload and equal to the amazonpay account
        $this->assertEquals('100000001', $ledgerSnsPayloadArray[1]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('amazonpay', $ledgerSnsPayloadArray[1]['identifiers']['fts_account_type']);
    }

    // Handles the case where merchant is disabled after creating a wallet fund account.
    // Any payout created to that fund account should fail as long as merchant is disabled
    public function testCreatePayoutViaAmazonPayMerchantDisabled()
    {
        $contact = $this->getDbLastEntity('contact');

        $this->fixtures->create('fund_account:wallet_account', [
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contact->getId(),
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_X_AMAZONPAY,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->startTest();
    }

    public function testCancelQueuedPayoutWithCommentsGreaterThanMaxRange()
    {
        $this->testCreateQueuedPayout();

        $queuedPayout = $this->getDbLastEntity('payout');

        $this->assertEquals('low_balance', $queuedPayout->getQueuedReason());

        $userComment = "Payout cancelled";

        for ($i = 0; $i < 5; $i++)
        {
            $userComment = $userComment . ' | ' . $userComment;
        }

        // Assert that comment length is greater than 255 which is the current max limit in the validations.
        $this->assertGreaterThanOrEqual(255, strlen($userComment));

        $testData                                  = &$this->testData[__FUNCTION__];
        $testData['request']['url']                = '/payouts/' . $queuedPayout->getPublicId() . '/cancel';
        $testData['request']['content']['remarks'] = $userComment;

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCancelQueuedPayoutPrivateAuthAndCheckDataAfterFetchingItAgain()
    {
        $this->testCancelQueuedPayoutPrivateAuth();

        $queuedPayout = $this->getDbLastEntity('payout');

        $this->assertEquals('low_balance', $queuedPayout->getQueuedReason());

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $queuedPayout->getPublicId();

        $testData['response']['content']['cancellation_user_id'] = null;
        $testData['response']['content']['cancellation_user']    = [];

        $this->app->forgetInstance('basicauth');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * new_banking_error feature not enabled status_code will be populated
     * but error obj will not be created
     *
     */
    public function testPublicErrorCodeMappingAndStatusCodeButErrorObjectIsNotCreated()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E1028'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                            'IMPS is not enabled on Beneficiary Account');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS_CODE], 'YB_NS_E1028');

        $this->assertArrayNotHasKey(Payout\Entity::ERROR, $updatedPayout);
    }

    public function testNewErrorObjectInPayoutResponse()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testNewErrorObjectInPayoutResponseOnLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->liveSetUp();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFiringOfWebhookPayoutResponseForReversedPayout()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        //$this->mockRazorxTreatment('yesbank', 'on', 'on');

        $this->mockRazorxTreatment(
            'yesbank', 'off', 'off', 'off', 'off',
            'on', 'on', 'off', 'on',
            'on', 'off', 'on', 'on',
            'off', 'control', 'on',
            'on', 'off', 'control',
            'on'
        );

        $payloadReversed = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadReversed) {
                $this->assertContains($payload['event']['name'], ['payout.reversed']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_REVERSED:
                        $payloadReversed = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(7);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'TXN_REJECTED_BENE_BANK'
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertEquals('beneficiary_bank_rejected', $statusDetails['reason']);
        $this->assertEquals('Payout rejected by beneficiary bank. Please contact beneficiary bank.', $statusDetails['description']);

        $payoutResponse = $payout->toArrayPublic();
        $this->assertEquals('beneficiary_bank', $payoutResponse['status_details']['source']);

        $payoutReversedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.reversed', $payoutReversedEventData, $payloadReversed);
    }

    public function testFiringOfWebhookPayoutResponseForReversedPayoutOnLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        //$this->mockRazorxTreatment('yesbank', 'on', 'on');

        $this->mockRazorxTreatment(
            'yesbank', 'off', 'off', 'off', 'off',
            'on', 'on', 'off', 'on',
            'on', 'off', 'on', 'on',
            'off', 'control', 'on',
            'on', 'off', 'control',
            'on'
        );

        $payloadReversed = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadReversed) {
                $this->assertContains($payload['event']['name'], ['payout.reversed']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_REVERSED:
                        $payloadReversed = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(7);

        $this->testCreatePayoutOnLiveMode();

        $payout = $this->getDbLastEntity('payout', 'live');

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'TXN_REJECTED_BENE_BANK'
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details', 'live');

        $this->assertEquals('beneficiary_bank_rejected', $statusDetails['reason']);
        $this->assertEquals('Payout rejected by beneficiary bank. Please contact beneficiary bank.', $statusDetails['description']);

        $payoutResponse = $payout->toArrayPublic();
        $this->assertEquals('beneficiary_bank', $payoutResponse['status_details']['source']);

        $payoutReversedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.reversed', $payoutReversedEventData, $payloadReversed, 'live');
    }

    public function testCreatePayoutWithWrongFundAccountIdNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithWrongFundAccountIdNewApiErrorOnLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->liveSetUp();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreatePayoutWithIfQueueLowBalanceFalseNewApiError()
    {
        $this->mockLedgerSns(0);

        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithIfQueueLowBalanceFalseNewApiErrorOnLiveMode()
    {
        $this->mockLedgerSns(0);

        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->liveSetUp();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testPayoutRejectWhenWorkflowEditNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->setupRedisMock();

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->disableWorkflowMocks();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testFiringOfWebhookPayoutResponseForReversedPayoutDefaultErrorObject()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $payloadReversed = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadReversed) {
                $this->assertContains($payload['event']['name'], ['payout.reversed']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_REVERSED:
                        $payloadReversed = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(7);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'NOT_REGISTERED_ERROR'
        ]);

        $payoutReversedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.reversed', $payoutReversedEventData, $payloadReversed);
    }

    public function testFiringOfWebhookPayoutResponseForReversedPayoutDefaultErrorObjectOnLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $payloadReversed = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadReversed) {
                $this->assertContains($payload['event']['name'], ['payout.reversed']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_REVERSED:
                        $payloadReversed = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(7);

        $this->testCreatePayoutOnLiveMode();

        $payout = $this->getDbLastEntity('payout', 'live');

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'NOT_REGISTERED_ERROR'
        ]);

        $payoutReversedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.reversed', $payoutReversedEventData, $payloadReversed, 'live');
    }

    public function testFiringOfWebhookPayoutResponseForProcessedPayout()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        //$this->mockRazorxTreatment('yesbank', 'on', 'on');

        $this->mockRazorxTreatment(
            'yesbank', 'off', 'off', 'off', 'off',
            'on', 'on', 'off', 'on',
            'on', 'off', 'on', 'on',
            'off', 'control', 'on',
            'on', 'off', 'control',
            'on'
        );

        $payloadProcessed = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadProcessed) {
                $this->assertContains($payload['event']['name'], ['payout.processed']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_PROCESSED:
                        $payloadProcessed = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'processed',
            'failure_reason'   => null,
            'bank_status_code' => null
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertEquals('payout_processed', $statusDetails['reason']);
        $this->assertEquals('Payout is processed and the money has been credited into the beneficiaries account.', $statusDetails['description']);

        $payoutProcessedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.processed', $payoutProcessedEventData, $payloadProcessed);
    }

    public function testFiringOfWebhookPayoutResponseForProcessedPayoutOnLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        //$this->mockRazorxTreatment('yesbank', 'on', 'on');

        $this->mockRazorxTreatment(
            'yesbank', 'off', 'off', 'off', 'off',
            'on', 'on', 'off', 'on',
            'on', 'off', 'on', 'on',
            'off', 'control', 'on',
            'on', 'off', 'control',
            'on'
        );

        $payloadProcessed = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadProcessed) {
                $this->assertContains($payload['event']['name'], ['payout.processed']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_PROCESSED:
                        $payloadProcessed = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayoutOnLiveMode();

        $payout = $this->getDbLastEntity('payout', 'live');

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'processed',
            'failure_reason'   => null,
            'bank_status_code' => null
        ]);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'processed',
            'failure_reason'   => null,
            'bank_status_code' => null
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details', 'live');

        $this->assertEquals('payout_processed', $statusDetails['reason']);
        $this->assertEquals('Payout is processed and the money has been credited into the beneficiaries account.', $statusDetails['description']);

        $payoutProcessedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.processed', $payoutProcessedEventData, $payloadProcessed, 'live');
    }

    public function testFiringOfWebhookPayoutResponseForUpdatedPayout()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $payloadUpdated = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(3);

        $this->testCreateRblPayoutSuccessfully();

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'fta_status'       => 'processed',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'remarks'          => '',
            'bank_status_code' => 'SUCESS'
        ]);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testCreatePayoutViaDashboardAndAssertMetricsSent()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $user->getId(),
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ]);

        $this->assertMetricsSentWithProduct();

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->startTest();
    }

    public function testCreatePayoutOnPrivateAuthAndMetricsSent()
    {
        $this->assertMetricsSentWithProduct();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function assertMetricsSentWithProduct()
    {
        $mock = $this->createMetricsMock();

        $mock->expects($this->atLeastOnce())
             ->method('count')
             ->will($this->returnCallback(function(string $metric, float $times, array $dimensions) {
                 if ($metric === Constants\Metric::HTTP_REQUESTS_TOTAL)
                 {
                     if (isset($dimensions[Constants\Metric::LABEL_RZP_PRODUCT]) and ($dimensions[Constants\Metric::LABEL_RZP_PRODUCT] === 'banking'))
                     {
                         return true;
                     }
                     // to make the test fail
                     throw new \Exception("product not set correctly in metrics");
                 }

                 return true;
             }));
    }

    private function mockWFS(string $payoutId = "FV57s8rpBqOD6w", string $comment = "null")
    {
        if ($comment !== "null")
        {
            $comment = '"' . $comment . '"';
        }

        $wfsMock = $this->getMockBuilder(WorkflowService::class)
                        ->setConstructorArgs([$this->app])
                        ->setMethods(['request'])
                        ->getMock();

        $this->app->instance('workflow_service', $wfsMock);

        $response = new Requests_Response();

        $response->status_code = 200;

        $content = '{
                        "id": "FV58BuqLuCP4Cw",
                        "config_id": "FV0aQGxYU4kk4c",
                        "entity_id": "' . $payoutId . '",
                        "entity_type": "payouts",
                        "title": "title",
                        "description": "[]",
                        "config_version": "1",
                        "creator_id": "10000000000000",
                        "creator_type": "merchant",
                        "diff": {
                            "old": {
                                "amount": null,
                                "merchant_id": null
                            },
                            "new": {
                                "amount": 10000,
                                "merchant_id": "10000000000000"
                            }
                        },
                        "callback_details": {
                            "state_callbacks": {
                                "created": {
                                    "method": "post",
                                    "payload": {
                                        "queue_if_low_balance": true,
                                        "type": "state_callbacks_created"
                                    },
                                    "headers": {
                                        "x-creator-id": ""
                                    },
                                    "service": "api_live",
                                    "type": "basic",
                                    "url_path": "/payouts_internal/FV57s8rpBqOD6w/approve",
                                    "response_handler": {
                                        "type": "success_status_codes",
                                        "success_status_codes": [
                                            201,
                                            200
                                        ]
                                    }
                                },
                                "processed": {
                                    "method": "post",
                                    "payload": {
                                        "queue_if_low_balance": true,
                                        "type": "state_callbacks_processed"
                                    },
                                    "headers": {
                                        "x-creator-id": ""
                                    },
                                    "service": "api_live",
                                    "type": "basic",
                                    "url_path": "/payouts_internal/FV57s8rpBqOD6w/approve",
                                    "response_handler": {
                                        "type": "success_status_codes",
                                        "success_status_codes": [
                                            201,
                                            200
                                        ]
                                    }
                                }
                            },
                            "workflow_callbacks": {
                                "processed": {
                                    "domain_status": {
                                        "approved": {
                                            "method": "post",
                                            "payload": {
                                                "queue_if_low_balance": true,
                                                "type": "workflow_callbacks_approved"
                                            },
                                            "headers": {
                                                "x-creator-id": ""
                                            },
                                            "service": "api_live",
                                            "type": "basic",
                                            "url_path": "/payouts_internal/FV57s8rpBqOD6w/approve",
                                            "response_handler": {
                                                "type": "success_status_codes",
                                                "success_status_codes": [
                                                    201,
                                                    200
                                                ]
                                            }
                                        },
                                        "rejected": {
                                            "method": "post",
                                            "payload": {
                                                "queue_if_low_balance": true,
                                                "type": "state_callbacks_rejected"
                                            },
                                            "headers": {
                                                "x-creator-id": ""
                                            },
                                            "service": "api_live",
                                            "type": "basic",
                                            "url_path": "/payouts_internal/FV57s8rpBqOD6w/reject",
                                            "response_handler": {
                                                "type": "success_status_codes",
                                                "success_status_codes": [
                                                    201,
                                                    200
                                                ]
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        "status": "initiated",
                        "domain_status": "created",
                        "owner_id": "10000000000000",
                        "owner_type": "merchant",
                        "org_id": "100000razorpay",
                        "states": {
                            "Owner_Approval": {
                                "id": "FV58Cedbz6e0a2",
                                "workflow_id": "FV58BuqLuCP4Cw",
                                "status": "created",
                                "name": "Owner_Approval",
                                "group_name": "ABC",
                                "type": "checker",
                                "rules": {
                                    "actor_property_key": "role",
                                    "actor_property_value": "owner",
                                    "count": 1
                                },
                                "pending_on_user": true,
                                "created_at": "1598377315",
                                "updated_at": "1598377315"
                            },
                            "FL1_0_Approval" :{
                                "actions" :[
                                    {
                                        "id": "FV0rayoQ8epeX6",
                                        "workflow_id": "FV0pSI6zc8v6X2",
                                        "state_id": "FV0pTiztDETNyl",
                                        "action_type": "rejected",
                                        "comment": ' . $comment . ',
                                        "actor_id": "FV0pAuYEKG1QS9",
                                        "actor_type": "user",
                                        "status": "processed",
                                        "actor_property_key": "role",
                                        "actor_property_value": "owner",
                                        "actor_meta": {
                                            "email": "raegan.swaniawski@corkery.com"
                                        },
                                        "created_at": "1598362285"
                                    }
                                ]
                            }
                        },
                        "type": "payout-approval",
                        "pending_on_user": true
                    }';

        $response->body = $content;

        $this->app->workflow_service->method('request')
                                    ->willReturn($response);
    }

    public function testCreateBulkPayoutWithErrorInPayoutData()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $payout      = $this->getDbEntity('payout', ['idempotency_key' => 'batch_abc124']);
        $contact     = $this->getDbEntity('contact', ['idempotency_key' => 'batch_abc124']);
        $fundAccount = $this->getDbEntity('fund_account', ['idempotency_key' => 'batch_abc124']);

        $this->assertEquals(null, $payout);

        // Adding below assertion to assert that although the second payout was not created, the contact got
        // created nonetheless. This is happening because we don't have any transaction at service layer.
        $this->assertEquals('abcd1234', $contact['reference_id']);
        $this->assertEquals('Mehul Kaushik', $contact['name']);
        $this->assertEquals('9988776655', $contact['contact']);
        $this->assertEquals('testemail@example.com', $contact['email']);
        $this->assertEquals('batch_abc124', $contact['idempotency_key']);

        // Adding below assertion to assert that although the second payout was not created, the fund account got
        // created nonetheless. This is happening because we don't have any transaction at service layer.
        $this->assertEquals($contact['id'], $fundAccount['source_id']);
        $this->assertEquals('vpa', $fundAccount['account_type']);
        $this->assertEquals('batch_abc124', $fundAccount['idempotency_key']);
    }

    public function testCreateBulkPayoutWithErrorInFundAccountData()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $payout      = $this->getDbEntity('payout', ['idempotency_key' => 'batch_abc124']);
        $contact     = $this->getDbEntity('contact', ['idempotency_key' => 'batch_abc124']);
        $fundAccount = $this->getDbEntity('fund_account', ['idempotency_key' => 'batch_abc124']);

        $this->assertEquals(null, $payout);
        $this->assertEquals(null, $fundAccount);

        // Adding below assertion to assert that although the second payout or fund account was not created, the
        // contact got created nonetheless. This is happening because we don't have any transaction at service layer.
        $this->assertEquals('abcd1234', $contact['reference_id']);
        $this->assertEquals('Mehul Kaushik', $contact['name']);
        $this->assertEquals('9988776655', $contact['contact']);
        $this->assertEquals('testemail@example.com', $contact['email']);
        $this->assertEquals('batch_abc124', $contact['idempotency_key']);
    }

    protected function createFundAccountOfIciciVA()
    {
        $request = [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000001contact',
                'bank_account' => [
                    'ifsc'           => 'ICIC0000104',
                    'name'           => 'Mehul Kaushik',
                    'account_number' => '3434000111000',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function createFundAccountOfYesbankVA()
    {
        $request = [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000001contact',
                'bank_account' => [
                    'ifsc'           => 'YESB0CMSNOC',
                    'name'           => 'Mehul Kaushik',
                    'account_number' => '7878780111000',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function createFundAccountOfRBLVA()
    {
        $request = [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000001contact',
                'bank_account' => [
                    'ifsc'           => 'RATN0VAAPIS',
                    'name'           => 'Mehul Kaushik',
                    'account_number' => '2223780111000',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function mockRazorxToAllowVAToVAPayouts()
    {
        $this->mockRazorxTreatment('yesbank',
                                   'off',
                                   'off',
                                   'off',
                                   'off',
                                   'on',
                                   'on',
                                   'off',
                                   'on',
                                   'on',
                                   'off',
                                   'on',
                                   'on',
                                   'off',
                                   'on');
    }

    protected function createFundAccountOfRBLCA()
    {
        $request = [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000001contact',
                'bank_account' => [
                    'ifsc'           => 'RATN0000104',
                    'name'           => 'Mehul Kaushik',
                    'account_number' => '40080111000',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    // Since this is a VA to VA payout and razorx returns control, we shall fail this payout
    public function testBlockVAtoVAPayoutsWithICICIDestination()
    {
        $fundAccount = $this->createFundAccountOfIciciVA();

        $fundAccountId = $fundAccount['id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccountId;

        $this->mockRazorxTreatment();

        $this->startTest($testData);
    }

    // Since this is a VA to VA payout and razorx returns control, we shall fail this payout
    public function testBlockVAtoVAPayoutsWithYesbankDestination()
    {
        $fundAccount = $this->createFundAccountOfYesbankVA();

        $fundAccountId = $fundAccount['id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccountId;

        $this->mockRazorxTreatment();

        $this->startTest($testData);
    }

    // Since this is a VA to VA payout and razorx returns control, we shall fail this payout
    // Below test is to block RX to Smart collect payouts
    public function testBlockVAtoVAPayoutsWithRBLDestination()
    {
        $fundAccount = $this->createFundAccountOfRBLVA();

        $fundAccountId = $fundAccount['id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccountId;

        $this->mockRazorxTreatment();

        $this->startTest($testData);
    }

    // Since this is a VA to VA payout and razorx returns control, we should fail this payout.
    // But in this case, we have whitelisted the destination MID, hence the payout should go through.
    public function testAllowVAtoVAPayoutsWhenSourceDestinationIsWhitelisted()
    {
        $fundAccountResponse = $this->createFundAccountOfYesbankVA();

        //
        // We shall setup a virtual account and a bank account that will act as a destination account
        // Using MID 100000Razorpay so that we can white-list it and the payout goes through
        //
        $destinationVirtualAccount = $this->fixtures->create('virtual_account',
                                                             [
                                                                 'merchant_id' => '100000Razorpay'
                                                             ]);

        $destinationBankAccount = $this->fixtures->create('bank_account',
                                                          [
                                                              'type'           => 'virtual_account',
                                                              'entity_id'      => $destinationVirtualAccount['id'],
                                                              'account_number' => $fundAccountResponse['bank_account']['account_number'],
                                                              'ifsc_code'      => $fundAccountResponse['bank_account']['ifsc'],
                                                              'merchant_id'    => $destinationVirtualAccount['merchant_id'],
                                                          ]);

        $this->fixtures->edit('virtual_account', $destinationVirtualAccount['id'],
                              [
                                  'bank_account_id' => $destinationBankAccount['id']
                              ]);

        $destinationVirtualAccount = $this->getDbLastEntity('virtual_account');

        $fundAccountId = $fundAccountResponse['id'];

        $this->ba->adminAuth();

        // Whitelisting the MID corresponding to the destination bank account number
        $this->makeRequestAndGetContent([
                                            'method'  => 'PUT',
                                            'url'     => '/config/keys',
                                            'content' => [
                                                Admin\ConfigKey::RX_VA_TO_VA_PAYOUTS_WHITELISTED_DESTINATION_MERCHANTS =>
                                                    [
                                                        $destinationVirtualAccount['merchant_id']
                                                    ],
                                            ],
                                        ]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccountId;

        $this->mockRazorxTreatment();

        $this->ba->privateAuth();

        $this->startTest($testData);
    }

    // Since this is a VA to VA payout but the razorx returns 'on' meaning we have allowed this merchant
    // to make VA to VA payouts, we shall allow this payout to go through
    public function testAllowVAtoVAPayoutsWithRazorXExperimentWithICICIDestination()
    {
        $fundAccount = $this->createFundAccountOfIciciVA();

        $fundAccountId = $fundAccount['id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccountId;

        $this->mockRazorxToAllowVAToVAPayouts();

        $this->startTest($testData);
    }

    // Since this is a VA to VA payout but the razorx returns 'on' meaning we have allowed this merchant
    // to make VA to VA payouts, we shall allow this payout to go through
    public function testAllowVAtoVAPayoutsWithRazorXExperimentWithYesbankDestination()
    {
        $fundAccount = $this->createFundAccountOfYesbankVA();

        $fundAccountId = $fundAccount['id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccountId;

        $this->mockRazorxToAllowVAToVAPayouts();

        $this->startTest($testData);
    }

    // CA to VA payouts should go through since this flow should be untouched by VA to VA payouts being bloacked
    public function testCAtoVAPayout()
    {
        $this->mockRazorxTreatment();

        $this->setupDirectAccount();

        $fundAccount = $this->createFundAccountOfIciciVA();

        $fundAccountId = $fundAccount['id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccountId;

        $this->ba->privateAuth();

        $this->startTest();
    }

    // This should go through
    public function testVAtoCAPayout()
    {
        $fundAccount = $this->createFundAccountOfRBLCA();

        $fundAccountId = $fundAccount['id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccountId;

        $this->mockRazorxTreatment();

        $this->startTest($testData);
    }

    public function testBlockVAtoVirtualAccountVpaPayout()
    {
        $this->ba->privateAuth();

        $this->createContact();

        $this->fundAccount = $this->createVpaFundAccount();

        $destinationVirtualAccount = $this->fixtures->create('virtual_account',
                                                             [
                                                                 'merchant_id' => '100000Razorpay'
                                                             ]);

        $destinationVpa = $this->fixtures->create('vpa',
                                                  [
                                                      'entity_type' => 'virtual_account',
                                                      'entity_id'   => $destinationVirtualAccount['id'],
                                                      'username'    => $this->fundAccount->account->getUsername(),
                                                      'handle'      => $this->fundAccount->account->getHandle(),
                                                      'merchant_id' => $destinationVirtualAccount['merchant_id'],
                                                  ]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $this->startTest();
    }

    // Since this is a VA to VA payout and razorx returns control, we shall fail this payout
    public function testBlockVAtoVACompositePayouts()
    {
        $this->mockRazorxTreatment();

        $this->ba->privateAuth();

        $this->startTest();
    }

    // Since this is a VA to VA payout but the razorx returns 'on' meaning we have allowed this merchant
    // to make VA to VA payouts, we shall allow this payout to go through
    public function testAllowVAtoVACompositePayoutsWithRazorXExperiment()
    {
        $this->mockRazorxToAllowVAToVAPayouts();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testBlockBulkVAToVAPayouts()
    {
        $this->mockRazorxTreatment();

        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id' => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testAllowBulkVAToVAPayoutsWithRazorXExperiment()
    {
        $this->mockRazorxToAllowVAToVAPayouts();

        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testFetchPayoutWithSourceIdAndSourceTypeOnProxyAuth()
    {
        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        /** @var Payout\Entity $payout1 */
        $payout1 = $this->getDbLastEntity('payout');

        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        /** @var Payout\Entity $payout2 */
        $payout2 = $this->getDbLastEntity('payout');

        $payoutSources = $payout2->getSourceDetails()->toArray();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payouts?product=banking&source_id=' . $payoutSources[0]['source_id'] .
                                      '&source_type=' . $payoutSources[0]['source_type'];

        $this->ba->proxyAuth();

        $response = $this->startTest();

        // Now only one payout will be found. because of the same I-Key
        $this->assertEquals(1, $response['count']);

        $this->assertEquals($payout1->getPublicId(), $payout2->getPublicId());

        $responsePayoutIds = [$response['items'][0]['id']];

        $payoutIds = [$payout1->getPublicId()];

        $this->assertCount(0, array_diff($responsePayoutIds, $payoutIds));

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payoutSources];

        $this->assertArraySelectiveEquals($sourceDetails, $response['items'][0]);
    }

    public function testFetchPayoutSkipXpayrollOnProxyAuth()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'on', 'imps_mode_payout_filter' => 'control']);

        // create 5 payouts [2 xpayroll + 3 payout_link]
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();

        $this->testData[__FUNCTION__] = $this->testData['testFetchPayoutsOnProxyAuth'];

        $testData = &$this->testData[__FUNCTION__];

        $accountNumber = $this->bankingBalance->getAccountNumber();

        $testData['request']['url'] = '/payouts?account_number=' . $accountNumber;

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(3, $response['count']);
    }

    public function testFetchPayoutSkipXpayrollOnPrivateAuthWithExperimentOnAndSomePayrollPayouts()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'on', 'imps_mode_payout_filter' => 'control']);

        // create 5 payouts [2 xpayroll + 3 payout_link]
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();

        $this->testData[__FUNCTION__] = $this->testData['testFetchPayoutsOnProxyAuth'];

        $testData = &$this->testData[__FUNCTION__];

        $accountNumber = $this->bankingBalance->getAccountNumber();

        $testData['request']['url'] = '/payouts?account_number=' . $accountNumber;

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(3, $response['count']);
    }

    public function testFetchPayoutSkipXpayrollOnPrivateAuthWithExperimentOffAndSomePayrollPayouts()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'off', 'imps_mode_payout_filter' => 'control']);

        // create 5 payouts [2 xpayroll + 3 payout_link]
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();

        $this->testData[__FUNCTION__] = $this->testData['testFetchPayoutsOnProxyAuth'];

        $testData = &$this->testData[__FUNCTION__];

        $accountNumber = $this->bankingBalance->getAccountNumber();

        $testData['request']['url'] = '/payouts?account_number=' . $accountNumber;

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(5, $response['count']);
    }

    public function testFetchPayoutSkipXpayrollOnPrivateAuthWithExperimentOnAndNoPayrollPayouts()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'on', 'imps_mode_payout_filter' => 'control']);

        // create 3 payouts [3 payout_link]
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();

        $this->testData[__FUNCTION__] = $this->testData['testFetchPayoutsOnProxyAuth'];

        $testData = &$this->testData[__FUNCTION__];

        $accountNumber = $this->bankingBalance->getAccountNumber();

        $testData['request']['url'] = '/payouts?account_number=' . $accountNumber;

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(3, $response['count']);
    }

    public function testFetchPayoutSkipXpayrollOnPrivateAuthWithExperimentOffAndNoPayrollPayouts()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'off', 'imps_mode_payout_filter' => 'control']);

        // create 3 payouts [3 payout_link]
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();
        $this->testCreatePayoutLinkPayoutWithSourceDetailsWithoutIKey();

        $this->testData[__FUNCTION__] = $this->testData['testFetchPayoutsOnProxyAuth'];

        $testData = &$this->testData[__FUNCTION__];

        $accountNumber = $this->bankingBalance->getAccountNumber();

        $testData['request']['url'] = '/payouts?account_number=' . $accountNumber;

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(3, $response['count']);
    }

    public function testFetchPayoutSkipXpayrollOnPrivateAuthWithExperimentOnAndAllPayrollPayouts()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'on', 'imps_mode_payout_filter' => 'control']);

        // create 3 payouts [3 xpayroll]
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreateXpayrollPayoutWithSourceDetails();

        $this->testData[__FUNCTION__] = $this->testData['testFetchPayoutsOnProxyAuth'];

        $testData = &$this->testData[__FUNCTION__];

        $accountNumber = $this->bankingBalance->getAccountNumber();

        $testData['request']['url'] = '/payouts?account_number=' . $accountNumber;

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(0, $response['count']);
    }

    public function testFetchPayoutSkipXpayrollOnPrivateAuthWithExperimentOffAndAllPayrollPayouts()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'off', 'imps_mode_payout_filter' => 'control']);

        // create 3 payouts [3 xpayroll]
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreateXpayrollPayoutWithSourceDetails();
        $this->testCreateXpayrollPayoutWithSourceDetails();

        $this->testData[__FUNCTION__] = $this->testData['testFetchPayoutsOnProxyAuth'];

        $testData = &$this->testData[__FUNCTION__];

        $accountNumber = $this->bankingBalance->getAccountNumber();

        $testData['request']['url'] = '/payouts?account_number=' . $accountNumber;

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(3, $response['count']);
    }

    public function testFetchPayoutWithSourceIdAndSourceTypeOnPrivateAuth()
    {
        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        /** @var Payout\Entity $payout1 */
        $payout1 = $this->getDbLastEntity('payout');

        $this->testCreatePayoutLinkPayoutWithSourceDetails();

        $payoutSources = $payout1->getSourceDetails()->toArray();

        $accountNumber = $this->bankingBalance->getAccountNumber();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payouts?account_number=' . $accountNumber . '&source_id=' .
                                      $payoutSources[0]['source_id'] . '&source_type=' .
                                      $payoutSources[0]['source_type'];

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetXpayrollPayoutWithExperimentOn()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'on', 'imps_mode_payout_filter' => 'control']);

        $this->testCreateXpayrollPayoutWithSourceDetails();

        $payout = $this->getLastEntity('payout', true);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__] = $this->testData['testGetXpayrollPayoutWithExperimentOn'];

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/' . $payout['id'];

        $this->startTest();

    }

    public function testGetXpayrollPayoutWithExperimentOff()
    {
        $this->setMockRazorxTreatment(['rx_skip_payroll_payouts' => 'off', 'imps_mode_payout_filter' => 'control']);

        $this->testCreateXpayrollPayoutWithSourceDetails();

        $payout = $this->getLastEntity('payout', true);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__] = $this->testData['testGetXpayrollPayoutWithExperimentOff'];

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/' . $payout['id'];

        $result = $this->startTest();

        $this->assertEquals(2000, $result['amount']);

    }

    public function testBeneficiaryNameInPayoutsResponse()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::BENE_NAME_IN_PAYOUT]);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('processed', $payout->getStatus());

        $this->assertEquals('SUSANTA BHUYAN', $payout->getRegisteredName());

        $response = $this->getPayoutStatusAPI('pout_' . $payout->getId());

        $this->assertEquals('SUSANTA BHUYAN', $response['registered_name']);
    }

    public function testBeneficiaryNameNotPresentInPayoutsRespForNonWhitelistedMerchant()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('processed', $payout->getStatus());

        $this->assertEquals('SUSANTA BHUYAN', $payout->getRegisteredName());

        $response = $this->getPayoutStatusAPI('pout_' . $payout->getId());

        $this->assertNotContains('registered_name', array_keys($response));
    }

    public function testQueueingDetailsInPayoutsResponse()
    {
        $this->testCreatePayout();

        //If feature is enabled then only we will get the queueing details for private auth
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout['id'],
            [
                'status'        => 'queued',
                'queued_reason' => 'low_balance',
            ]
        );

        $response = $this->getPayoutStatusAPI('pout_' . $payout->getId());

        $this->assertNotNull($response['queueing_details']);
        $this->assertEquals('low_balance', $response['queueing_details']['reason']);
        $this->assertEquals('Payout is queued as there is insufficient balance in your account to process the payout.',
                            $response['queueing_details']['description']);

    }

    public function testGetPrimaryBalance()
    {
        // this test should ideally be in MerchantTest. But since enabling X for merchant in tests is so hard,
        // keeping it here.

        $this->ba->proxyAuth();

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testCreateQueuedPayoutAndCheckCorrectWebhooksFired()
    {
        $this->dontExpectWebhookEvent('payout.initiated');

        $this->expectWebhookEvent('payout.queued');

        $this->testData[__FUNCTION__] = $this->testData['testCreateQueuedPayout'];

        $this->startTest();
    }

    public function testCreatePayoutForRequestSubmittedAndCheckCorrectWebhooksFired()
    {
        $this->dontExpectWebhookEvent('payout.initiated');

        $this->testCreatePayoutForRequestSubmitted();
    }

    public function testBulkPayoutWithThrottlingAndCheckCorrectWebhooksFired()
    {
        $payoutInitiatedEventData = $this->testData['testFiringOfWebhookOnInitiatedPayoutEventData'];

        // Only 2 payout.initiated webhooks should be fired for only 2 payouts that go to pending state.
        $this->expectWebhookEventWithContents('payout.initiated', $payoutInitiatedEventData);
        $this->expectWebhookEventWithContents('payout.initiated', $payoutInitiatedEventData);

        // For other 2 payouts that go to batch submitted state, payout.initiated should not be fired.
        $this->dontExpectWebhookEvent('payout.initiated');
        $this->dontExpectWebhookEvent('payout.initiated');

        $this->testBulkPayoutWithThrottling();
    }

    public function testAlternateFailureReason()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ALTERNATE_PAYOUT_FR]);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNull($payout[Payout\Entity::FAILURE_REASON]);

        $payoutId = $payout->getId();

        $utr = $payout->getUtr();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'INVALID_VPA'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertNotNull($updatedPayout[Payout\Entity::FAILURE_REASON]);
        $this->assertEquals(ErrorCodeMapping::$alternateFailureReasonMapping['INVALID_VPA'], $updatedPayout[Payout\Entity::FAILURE_REASON]);
    }

    public function testWithoutAlternateFailureReason()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNull($payout[Payout\Entity::FAILURE_REASON]);

        $payoutId = $payout->getId();

        $utr = $payout->getUtr();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'INVALID_VPA'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertNotNull($updatedPayout[Payout\Entity::FAILURE_REASON]);
        $this->assertEquals(ErrorCodeMapping::$failureReasonMapping['INVALID_VPA'], $updatedPayout[Payout\Entity::FAILURE_REASON]);
    }

    public function testSourceCreationInCaseOfCreateRequestSubmittedPayoutCreatedByVendorPayments()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->mockRazorxTreatment('yesbank',
                                   'on',
                                   'on',
                                   'off',
                                   'off',
                                   'on',
                                   'on',
                                   'on');

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_PROCESS_ASYNC]);

        $response = $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals('create_request_submitted', $payout['internal_status']);
        $this->assertEquals('processing', $payout['status']);
        $this->assertNotNull($payout['create_request_submitted_at']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertNull($payoutAttempt);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertNull($txn);

        $sourceDetails = [Payout\Entity::SOURCE_DETAILS => $payout['source_details']];

        $this->assertArraySelectiveEquals($sourceDetails, $response);
    }

    public function testUpdatePayoutStatusToProcessedManuallyInBatch()
    {
        $this->testCreatePayout();

        $payout1 = $this->getDbLastEntity('payout');

        $fta1 = $payout1->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta1->getStatus());

        $this->testCreatePayout();

        $payout2 = $this->getDbLastEntity('payout');

        $fta2 = $payout2->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta2->getStatus());

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated']);

        $this->fixtures->edit('payout', $payout2['id'], ['status' => 'initiated']);

        $request = [
            'url'     => '/payouts/manual/status_update/batch',
            'method'  => 'PATCH',
            'content' => [
                'payout_ids'          => [$payout1['id'], $payout2['id']],
                'status'              => 'processed',
                'fts_fund_account_id' => '12345',
                'fts_account_type'    => 'NODAL',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $payout1->reload();
        $payout2->reload();

        // Assert payout status was updated.
        $this->assertEquals('processed', $payout1->getStatus());
        $this->assertEquals('processed', $payout2->getStatus());

        $fta1->reload();
        $fta2->reload();

        // Assert that payout's fta's status was also updated.
        $this->assertEquals('processed', $fta1->getStatus());
        $this->assertEquals('processed', $fta2->getStatus());
    }


    public function testUpdatePayoutStatusToReversedManuallyInBatch()
    {
        $this->testCreatePayout();

        $payout1 = $this->getDbLastEntity('payout');

        $this->testCreatePayout();

        $payout2 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated']);

        $this->fixtures->edit('payout', $payout2['id'], ['status' => 'initiated']);

        $request = [
            'url'     => '/payouts/manual/status_update/batch',
            'method'  => 'PATCH',
            'content' => [
                'payout_ids'          => [$payout1['id'], $payout2['id']],
                'status'              => 'processed',
                'fts_fund_account_id' => '12345',
                'fts_account_type'    => 'NODAL',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $updatePayout1 = $this->getDbEntityById('payout', $payout1['id']);

        $updatePayout2 = $this->getDbEntityById('payout', $payout2['id']);

        $this->assertEquals('processed', $updatePayout1['status']);

        $this->assertEquals('processed', $updatePayout2['status']);

        $request = [
            'url'     => '/payouts/manual/status_update/batch',
            'method'  => 'PATCH',
            'content' => [
                'payout_ids'          => [$payout1['id'], $payout2['id']],
                'status'              => 'reversed',
                'failure_reason'      => 'payout reversed at bank',
                'fts_fund_account_id' => '12345',
                'fts_account_type'    => 'NODAL',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $updatePayout1 = $this->getDbEntityById('payout', $payout1['id']);

        $updatePayout2 = $this->getDbEntityById('payout', $payout2['id']);

        $this->assertEquals('reversed', $updatePayout1['status']);

        $this->assertEquals('reversed', $updatePayout2['status']);

        $this->assertEquals('payout reversed at bank', $updatePayout1['failure_reason']);

        $this->assertEquals('payout reversed at bank', $updatePayout2['failure_reason']);
    }

    public function testCreatePayoutToCardsNotAllowed()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ALLOW_NON_SAVED_CARDS]);

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

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    public function testCreateM2PPayoutForDebitCardWithUpperCaseCardMode()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
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

        $payout = $this->getDbLastEntity('payout');

        $payoutAttempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals($payout['mode'], 'card');
        $this->assertEquals($payout['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['mode'], 'CT');

        // Process the M2P payout
        $this->updateFtaAndSource($payout['id'], 'processed');

        $payoutCreated = $this->getDbLastEntity('payout');

        // Since there are multiple events within the flow,
        // following is a list of events in the order in which they occur in the test flow
        $transactorTypeArray = [
            'payout_initiated',
            'payout_processed',
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payoutCreated->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }

        $ledgerSnsPayloadArray[0]['identifiers'] = json_decode($ledgerSnsPayloadArray[0]['identifiers'], true);
        $ledgerSnsPayloadArray[1]['identifiers'] = json_decode($ledgerSnsPayloadArray[1]['identifiers'], true);

        // Not passed in payout initiated payload
        $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerSnsPayloadArray[0]['identifiers']);
        $this->assertArrayNotHasKey('fts_account_type', $ledgerSnsPayloadArray[0]['identifiers']);

        // Passed in payout processed payload and equal to the m2p account
        $this->assertEquals('100000002', $ledgerSnsPayloadArray[1]['identifiers']['fts_fund_account_id']);
        $this->assertEquals('m2p', $ledgerSnsPayloadArray[1]['identifiers']['fts_account_type']);
    }

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    public function testCreateM2PPayoutForDebitCardWithLowerCaseCardMode()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
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

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($payout['mode'], 'card');
        $this->assertEquals($payout['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['mode'], 'CT');
    }

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    public function testCreateM2PPayoutForDebitCardWithRandomCaseCardMode()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
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

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($payout['mode'], 'card');
        $this->assertEquals($payout['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['mode'], 'CT');

    }

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    //
    // Testing the case when the debit card is supported during FA creation
    // but support is revoked sometime between FA and PAYOUT creation
    public function testCreateM2PPayoutWithoutSupportedModes()
    {
        $this->markTestSkipped();

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
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
                    "cvv"          => "212",
                    "expiry_month" => 10,
                    "expiry_year"  => 29,
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

        $this->assertEquals(Issuer::YESB, $fundAccount['card']['issuer']);
        $this->assertEquals(Network::$fullName[Network::MC], $fundAccount['card']['network']);

        $card = $this->getDbLastEntity('card');

        // Editing the card issuer to make it an unsupported card as
        // configs can't be edited here.
        $this->fixtures->edit(
            'card',
            $card->getId(),
            [
                'issuer' => "default_issuer"
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

    }

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    public function testCreateM2PPayoutForMerchantBlacklistedByProduct()
    {
        $this->fixtures->create(
            'settings',
            [
                'module'      => 'm2p_transfer',
                'entity_type' => 'merchant',
                'entity_id'   => 10000000000000,
                'key'         => 'settlement',
                'value'       => 'true',
            ]
        );

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
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

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    public function testCreateM2PPayoutForMerchantBlacklistedByNetwork()
    {
        $this->fixtures->create(
            'settings',
            [
                'module'      => 'm2p_transfer',
                'entity_type' => 'merchant',
                'entity_id'   => 10000000000000,
                'key'         => 'MC',
                'value'       => 'true',
            ]
        );

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
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

    /**
     * In this test, we shall process a create_request_submitted payout via PayoutPostCreateProcessLowPriority
     * ( create_request_submitted -> created )
     */
    public function testProcessingOfCreateRequestSubmittedPayoutViaLowPriorityQueue()
    {
        $this->testCreatePayoutForRequestSubmitted(true);

        $payout = $this->getDbLastEntity('payout');

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should get processed since merchant has enough balance
        PayoutPostCreateProcessLowPriority::dispatch('test', $payout->getId(), 'false');

        $payout->reload();

        $publicResponse = $payout->toArrayPublic();

        $this->assertEquals('created', $payout['internal_status']);
        $this->assertEquals('processing', $publicResponse['status']);
        $this->assertNotNull($payout['initiated_at']);
    }

    /**
     * In this test, we shall check if the queue used by merchant with feature PAYOUT_LP_MERCHANT is of job
     * PayoutPostCreateProcessLowPriority
     * ( create_request_submitted -> created )
     */
    public function testQueueDispatchToBePayoutPostCreateProcessLowPriority()
    {
        Queue::fake();

        $this->testCreatePayoutForRequestSubmitted(true);

        Queue::assertPushed(PayoutPostCreateProcessLowPriority::class);
    }

    /**
     * In this test, we shall check if the queue used is of job PayoutPostCreateProcess
     * ( create_request_submitted -> created )
     */
    public function testQueueDispatchToBePayoutPostCreateProcess()
    {
        Queue::fake();

        $this->testCreatePayoutForRequestSubmitted();

        Queue::assertPushed(PayoutPostCreateProcess::class);
    }

    public function testCreatePayoutInternalWhenPayoutFeatureNotMapped()
    {
        $merchant = $this->fixtures->create('merchant');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $merchant['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->payoutLinksAppAuth();

        $this->startTest();
    }

    public function testCreatePayoutInternalWhenPayoutFeatureMapped()
    {
        $merchant = $this->fixtures->create('merchant', ['business_banking' => 1]);

        $bankAccount = $this->fixtures->create('bank_account', [
            'type'           => 'merchant',
            'merchant_id'    => $merchant['id'],
            'entity_id'      => $merchant['id'],
            'account_number' => '987654321000',
            'ifsc_code'      => 'RAZRB000000',
        ]);

        $this->fixtures->create('balance', [
            'type'           => 'banking',
            'account_type'   => 'shared',
            'account_number' => $bankAccount['account_number'],
            'merchant_id'    => $merchant['id'],
            'balance'        => 280000
        ]);

        $contact = $this->fixtures->create('contact', [
            'contact'     => '8888888888',
            'name'        => 'test user',
            'type'        => 'customer',
            'merchant_id' => $merchant['id']
        ]);

        $fa = $this->fixtures->create('fund_account:bank_account', [
            'merchant_id' => $merchant['id'],
            'source_id'   => $contact['id'],
            'source_type' => 'contact'
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT,
            'entity_id'   => $merchant['id'],
            'entity_type' => 'merchant',
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $merchant['id'];

        $testData['request']['content']['account_number'] = $bankAccount['account_number'];

        $testData['request']['content']['fund_account_id'] = 'fa_' . $fa['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->payoutLinksAppAuth();

        $this->startTest();
    }

    // Since this is a VA to VA payout and razorx returns control but feature allow Va to va is enabled,
    // we shall pass this payout
    public function testBlockVAtoVAPayoutsWithYesbankDestinationAndFeatureEnabled()
    {
        $fundAccount = $this->createFundAccountOfYesbankVA();

        $fundAccountId = $fundAccount['id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccountId;

        $this->mockRazorxTreatment();

        $this->fixtures->merchant->addFeatures([Feature\Constants::ALLOW_VA_TO_VA_PAYOUTS]);

        $this->startTest($testData);
    }

    public function testFiringOfWebhooksAndEmailOnPayoutReversalWithoutUtr()
    {
        Mail::fake();

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
            ]);

        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $transactionCreatedEventTestDataKey = $this->testData['testFiringOfWebhooksAndEmailOnPayoutReversalTransactionCreatedEventData'];

        $payoutReversedEventTestDataKey = $this->testData['testFiringOfWebhooksAndEmailOnPayoutReversalPayoutReversedEventData'];

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($transactionCreatedEventTestDataKey, $payoutReversedEventTestDataKey) {
                $this->assertContains($payload['event']['name'], ['transaction.created', 'payout.reversed']);
                switch ($payload['event']['name'])
                {
                    case Event::TRANSACTION_CREATED:
                        $this->validateStorkWebhookFireEvent('transaction.created', $transactionCreatedEventTestDataKey, $payload);
                        break;

                    case Event::PAYOUT_REVERSED:
                        $this->validateStorkWebhookFireEvent('payout.reversed', $payoutReversedEventTestDataKey, $payload);
                        break;

                }

                return new \Requests_Response();
            })->times(2);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();
        $this->startTest();

        Mail::assertQueued(PayoutMail::class, function($mail) {
            $viewData = $mail->viewData;

            $this->assertEquals($mail->originProduct, 'banking');

            $this->assertEquals('2001062', $viewData['txn']['amount']); // raw amount
            $this->assertEquals('20,010.62', amount_format_IN($viewData['txn']['amount'])); // formatted amount

            $payout = $this->getDbLastEntity('payout');

            $this->assertEquals('pout_' . $payout->getId(), $viewData['source']['id']);
            $this->assertEquals($payout->getFailureReason(), $viewData['source']['failure_reason']);

            $expectedData = [
                'txn' => [
                    'entity_id' => $payout->getId(),
                ]
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $this->assertEquals('emails.transaction.payout_reversed', $mail->view);

            return true;
        });

    }

    // This test should fail because payout amount can't be greater than MAX_PAYOUT_LIMIT
    public function testCreatePayoutGreaterThanMaxAmount()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    // This test should fail because payout amount for settlement service can't be greater than
    // MAX_SETTLEMENT_PAYOUT_LIMIT
    public function testCreatePayoutGreaterThanMaxAmountForSettlementService()
    {
        $this->ba->appAuthTest($this->config['applications.settlements_service.secret']);

        $this->startTest();
    }

    // This test should pass because payout amount for settlement service can be greater than MAX_PAYOUT_LIMIT
    public function testCreatePayoutGreaterThanGlobalMaxAmountForSettlementService()
    {
        $balance = $this->bankingBalance;

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '300000000000']);

        $this->ba->appAuthTest($this->config['applications.settlements_service.secret']);

        $this->startTest();
    }

    public function testOnHoldPayoutCreateAndAutoCancel()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);

        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout1 = $this->getDbLastEntity('payout')->toArray();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed when payout is in on_hold
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout2 = $this->getDbLastEntity('payout')->toArray();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed when payout is in on_hold
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout3 = $this->getDbLastEntity('payout')->toArray();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed when payout is in on_hold
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $this->assertEquals($payout1['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout2['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout3['status'], Payout\Status::ON_HOLD);

        $this->fixtures->edit('payout', $payout1['id'], ['on_hold_at' => strtotime(('-2000 seconds'), time())]);

        $this->fixtures->edit('payout', $payout2['id'], ['on_hold_at' => strtotime(('-2000 seconds'), time())]);

        $benebankConfig =
            [
                "BENEFICIARY" =>
                    [
                        "SBIN" => [
                            "status" => "started",
                        ],
                        "RZPB" => [
                            "status" => "started",
                        ],
                        'HDFC' => [
                            'status' => "started"
                        ],
                    ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->ba->cronAuth();

        $this->expectWebhookEvent('payout.failed');

        $this->expectWebhookEvent('payout.failed');

        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed when payout moved from on_hold to failed because of sla breach
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $payout1 = $this->getDbEntityById('payout', $payout1['id'])->toArray();
        $this->assertEquals($payout1['status'], Payout\Status::FAILED);
        $this->assertEquals($payout1['failure_reason'], 'beneficiary_bank_down');

        $payout2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();
        $this->assertEquals($payout2['status'], Payout\Status::FAILED);
        $this->assertEquals($payout1['failure_reason'], 'beneficiary_bank_down');

        $payout3 = $this->getDbEntityById('payout', $payout3['id'])->toArray();
        $this->assertEquals($payout3['status'], Payout\Status::ON_HOLD);
    }

    public function testOnHoldPayoutCreateAndProcess()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);

        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout1 = $this->getDbLastEntity('payout')->toArray();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed when payout is on_hold
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout2 = $this->getDbLastEntity('payout')->toArray();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed when payout is on_hold
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout3 = $this->getDbLastEntity('payout')->toArray();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed when payout is on_hold
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $this->assertEquals($payout1['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout2['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout3['status'], Payout\Status::ON_HOLD);

        $benebankConfig =
            [
                "BENEFICIARY" =>
                    [
                        "SBIN" => [
                            "status" => "started",
                        ],
                        'HDFC' => [
                            'status' => "started"
                        ],
                    ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->ba->cronAuth();

        $this->startTest();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that 3 free payouts have been consumed when 3 payouts moved from on_hold to created
        $this->assertEquals(3, $counter->getFreePayoutsConsumed());

        $payout1 = $this->getDbEntityById('payout', $payout1['id'])->toArray();
        $this->assertEquals($payout1['status'], Payout\Status::CREATED);

        $payout3 = $this->getDbEntityById('payout', $payout3['id'])->toArray();
        $this->assertEquals($payout3['status'], Payout\Status::CREATED);

        $payout2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();
        $this->assertEquals($payout2['status'], Payout\Status::CREATED);
    }

    public function testOnHoldPayoutForFeatureEnabledMerchantAndBeneDown()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);

        $benebankConfig =
            [
                "BENEFICIARY" => [
                    "SBIN"    => [
                        "status" => "started",
                    ],
                    "RZPB"    => [
                        "status" => "started",
                    ],
                    "default" => "started",
                ]
            ];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->expectWebhookEvent('payout.queued');

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('on_hold', $payout['status']);
        $this->assertEquals('beneficiary_bank_down', $payout['queued_reason']);
    }

    public function testOnHoldPayoutForFeatureEnabledMerchantWithNoTestTransactionsAndBeneDown()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);
        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_TEST_TXN_FOR_DMT]);

        $benebankConfig =
            [
                "BENEFICIARY" => [
                    "SBIN"    => [
                        "status" => "started",
                    ],
                    "RZPB"    => [
                        "status" => "started",
                    ],
                    "default" => "started",
                ]
            ];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->expectWebhookEvent('payout.queued');

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('on_hold', $payout['status']);
        $this->assertEquals('beneficiary_bank_down', $payout['queued_reason']);
    }

    public function testCreatePayoutWhenOnHoldFeatureEnabledAndBeneUp()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);

        $benebankConfig =
            [
                "BENEFICIARY" => [
                    "SBIN" => [
                        "status" => "started",
                    ],
                    "RZPB" => [
                        "status" => "resolved",
                    ],
                ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->dontExpectWebhookEvent('payout.queued');

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('on_hold', $payout['status']);
        $this->assertNull($payout['queued_reason']);
    }

    //test set redis config for bene bank downtime and uptime from fts webhook received
    public function testBeneBankDowntimeConfigSetup()
    {
        $this->ba->privateAuth();

        $benebankConfig =
            [
                "BENEFICIARY" => [
                    "SBIN"    => [
                        "status" => "started",
                    ],
                    'HDFC'    => [
                        'status' => "started"
                    ],
                    "default" => "started"
                ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->ba->ftsAuth("live");

        $this->mockPayoutServiceForOnHold();

        $this->startTest();

        $eventConfigFromFTS = (new Admin\Service)->getConfigKey([
                                                                    'key' => Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT
                                                                ]);

        $this->assertNotNull($eventConfigFromFTS['BENEFICIARY']['HDFC']);
        $this->assertEquals($eventConfigFromFTS['BENEFICIARY']['HDFC']['status'], 'started');
    }

    public function mockPayoutServiceForOnHold($fail = false, $request = [])
    {
        $payoutServiceOnHoldMock = Mockery::mock('RZP\Services\PayoutService\OnHoldBeneEvent',
                                                 [$this->app])->makePartial();

        $defaultRequest['headers']['X-Passport-JWT-V1'] = "";

        $request = array_merge($defaultRequest, $request);

        $payoutServiceOnHoldMock->shouldReceive('sendRequest')
                                ->withArgs(
                                    function($arg) use ($request) {
                                        try
                                        {
                                            // Using this method only here as we want to check if the keys in the
                                            // request are coming properly or not.
                                            $this->assertArrayKeySelectiveEquals($request, $arg);

                                            return true;
                                        }
                                        catch (\Throwable $e)
                                        {
                                            return false;
                                        }
                                    }
                                )
                                ->andReturn(
                                // We are returning this response only as we don't have a use case of supporting
                                // response based on $request, if needed, that can also be added here using
                                // andReturnUsing method instead of andReturn
                                    $this->getResponseForOnHoldPayoutsServiceMock($fail)
                                );

        $this->app->instance(OnHoldBeneEventService::PAYOUT_SERVICE_BENE_EVENT_UPDATE, $payoutServiceOnHoldMock);
    }

    public function getResponseForOnHoldPayoutsServiceMock($fail, $status = 'processing')
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body        = json_encode(
                [
                    "error" =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field"       => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success     = true;
        }
        else
        {
            $response->status_code = 200;
            $response->success     = true;
        }

        return $response;
    }

    // Assert that the array keys match selectively, we don't compare for values only the keys
    public function assertArrayKeySelectiveEquals(array $expected, array $actual)
    {
        foreach ($expected as $key => $value)
        {
            if (is_array($value))
            {
                $this->assertArrayHasKey($key, $actual);

                $this->assertArrayKeySelectiveEquals($expected[$key], $actual[$key]);
            }
            else
            {
                $this->assertArrayHasKey($key, $actual);
            }
        }
    }

    public function testBeneBankUptimeConfigSetup()
    {
        $this->ba->privateAuth();

        $benebankConfig =
            [
                "BENEFICIARY" =>
                    [
                        "SBIN"    => [
                            "status" => "started",
                        ],
                        'HDFC'    => [
                            'status' => "started"
                        ],
                        "default" => "resolved"
                    ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->ba->ftsAuth("live");

        $this->startTest();

        $eventConfigFromFTS = (new Admin\Service)->getConfigKey([
                                                                    'key' => Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT
                                                                ]);

        $this->assertNotContains('HDFC', $eventConfigFromFTS['BENEFICIARY'], true);
    }

    public function testCreateRequestSubmittedToOnHoldAndProcessing()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->testCreatePayoutForRequestSubmitted(true);

        $payout = $this->getDbLastEntity('payout');

        $this->assertNull($payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed when payout is in create_request_submitted state
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);

        $benebankConfig =
            [
                "BENEFICIARY" => [
                    "SBIN" => [
                        "status" => "started",
                    ],
                    "RZPB" => [
                        "status" => "started",
                    ],
                ]
            ];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        PayoutPostCreateProcessLowPriority::dispatch('test', $payout->getId(), 'false');

        $payout->reload();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that zero free payout has been consumed when payout moved from create_request_submitted to on_hold
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $publicResponse = $payout->toArrayPublic();

        $this->assertEquals('on_hold', $payout['internal_status']);
        $this->assertEquals('queued', $publicResponse['status']);
        $this->assertNotNull($payout['on_hold_at']);
    }

    public function testBatchSubmittedToOnHoldAndProcessing()
    {
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUTS_ON_HOLD,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->testBulkPayoutWithThrottling();

        $benebankConfig =
            [
                "BENEFICIARY" => [
                    "SBIN" => [
                        "status" => "started",
                    ],
                    "HDFC" => [
                        "status" => "started",
                    ],
                ]
            ];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testProcessBulkPayoutDelayedInitiation'];

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        // Assertions for first payout (NEFT)
        $this->assertEquals(Payout\Mode::NEFT, $payouts[0]['mode']);
        $this->assertEquals(Payout\Status::ON_HOLD, $payouts[0]['status']);

        // Assertion for second payout (RTGS)
        $this->assertEquals(Payout\Mode::RTGS, $payouts[1]['mode']);
        $this->assertEquals(Payout\Status::ON_HOLD, $payouts[1]['status']);

        $batchProcessingPayouts = $this->getDbEntities('payout', ['status' => Payout\Status::BATCH_SUBMITTED]);

        // Assert that no payouts remain in batch_processing state
        $this->assertEquals(0, $batchProcessingPayouts->count());
    }

    public function testPendingToOnHoldAndProcessing()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => Feature\Constants::PAYOUTS_ON_HOLD,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->liveSetUp();

        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId, null, 'live');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $benebankConfig =
            [
                "BENEFICIARY" => [
                    "RZPB" => [
                        "status" => "started",
                    ],
                    "HDFC" => [
                        "status" => "started",
                    ],
                    "YESB" => [
                        "status" => "started",
                    ]
                ]
            ];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $firstApprovalResponse = $this->startTest();

        // Validating first approval response
        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(2, $firstApprovalResponse['workflow_history']['current_level']);
        $this->assertEquals('pending', $firstApprovalResponse['status']);
        $this->assertEquals(true, $firstActionChecker['approved']);

        $this->app['config']->set('database.default', 'live');

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());
        $secondApprovalResponse = $this->startTest();

        // Validating second approval response
        $secondActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(2, $secondApprovalResponse['workflow_history']['current_level']);
        $this->assertEquals('queued', $secondApprovalResponse['status']);
        $this->assertEquals(true, $secondActionChecker['approved']);

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ],
                                        'live')->first();

        // Assert that the free payout was consumed.
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $payout = $this->getDbLastEntity('payout', 'live');

        $publicResponse = $payout->toArrayPublic();

        $this->assertEquals('on_hold', $payout['internal_status']);
        $this->assertEquals('queued', $publicResponse['status']);
        $this->assertNotNull($payout['on_hold_at']);
    }

    public function testAlternateFailureReasonForNewError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ALTERNATE_PAYOUT_FR,
                                                Feature\Constants::NEW_BANKING_ERROR]);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNull($payout[Payout\Entity::FAILURE_REASON]);

        $payoutId = $payout->getId();

        $payout->getUtr();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'INVALID_VPA'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertNotNull($updatedPayout[Payout\Entity::FAILURE_REASON]);
        $this->assertEquals(ErrorCodeMapping::$alternateFailureReasonMapping['INVALID_VPA'], $updatedPayout[Payout\Entity::FAILURE_REASON]);

        $this->assertNotNull($updatedPayout[Payout\Entity::ERROR]);
        $this->assertEquals(ErrorCodeMapping::$alternateFailureReasonMapping['INVALID_VPA'], $updatedPayout[Payout\Entity::ERROR][Payout\PayoutError::DESCRIPTION]);
    }

    public function testLedgerCallsForPayoutStatusChangeFromInitiatedToReversed()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(3);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'reversed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E1028'
        ]);
    }

    public function testPayoutWebhookNotContainingQueueingDetailsForNonWhitelistedMerchant()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]
        );

        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ]
        )->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]
        );

        $this->expectWebhookEvent(
            'payout.processed',
            function(array $event) {
                $this->assertNotContains('queueing_details', $event['payload']['payout']['entity']);
            }
        );

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED);
    }


    public function testPayoutWebhookContainingQueueingDetailsForWhitelistedMerchant()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]
        );

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]
        );

        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ]
        )->first();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $ftaForPayout->getId(),
            [
                'status' => 'initiated',
                'utr'    => 928337183,
            ]
        );

        $this->expectWebhookEvent(
            'payout.processed',
            function(array $event) {
                $this->assertEquals(null, $event['payload']['payout']['entity']['queueing_details']['reason']);
                $this->assertEquals(null, $event['payload']['payout']['entity']['queueing_details']['description']);
            }
        );

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED);
    }

    public function testPayoutPricingForXpayroll()
    {
        $this->ba->xpayrollAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('Bbg7cl6t6I3XB8', $payout['pricing_rule_id']);

        $payoutSource = $this->getDbLastEntity('payout_source');

        $this->assertEquals('100000000000sa', $payoutSource['source_id']);
    }


    public function testQueuedPayoutPricingForXpayroll()
    {
        $this->ba->xpayrollAuth();

        $balanceId = $this->bankingBalance->getId();

        $this->fixtures->edit('balance', $balanceId, ['balance' => 0]);

        $this->startTest();

        $this->fixtures->edit('balance', $balanceId, ['balance' => 1000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('Bbg7cl6t6I3XB8', $payout['pricing_rule_id']);

        $payoutSource = $this->getDbLastEntity('payout_source');

        $this->assertEquals('100000000000sa', $payoutSource['source_id']);
    }

    public function testPayoutPricingForNonXpayrollApp()
    {
        $this->ba->payoutLinksAppAuth();

        $this->startTest();
    }

    public function testPayoutPricingForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testPayoutCreateOnInternalContactByXpayroll()
    {
        $this->ba->xPayrollAuth();

        $contact = $this->fixtures->create('contact',
                                           [
                                               'name' => 'test name',
                                               'type' => \RZP\Models\Contact\Type::XPAYROLL_INTERNAL
                                           ]);

        $contactDb = $this->getDbLastEntity('contact');

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $this->startTest();

        $this->assertEquals($contactDb['type'], 'rzp_xpayroll');

        $this->assertEquals($contactDb['id'], $contact['id']);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($payout['fund_account_id'], $fundAccount['id']);
    }

    public function testPayoutCreateOnXpayrollInternalContactByOtherAppFailure()
    {
        $this->ba->xPayrollAuth();

        $contact = $this->fixtures->create('contact',
                                           [
                                               'name' => 'test name',
                                               'type' => \RZP\Models\Contact\Type::XPAYROLL_INTERNAL
                                           ]);

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $this->ba->payoutLinksAppAuth();

        $this->startTest();
    }

    public function testCompositePayoutCreationViaNewCompositeFlow()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_PROCESS_ASYNC]);

        $this->ba->privateAuth();

        return $this->startTest();
    }

    public function testProcessingOfCreateRequestSubmittedPayoutForHighTps()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $this->testCreatePayoutForRequestSubmitted();

        $payout  = $this->getDbLastEntity('payout');
        $balance = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        $request = [
            'url'     => '/create_sub_balance',
            'method'  => 'post',
            'content' => [
                'parent_balance_id' => $payout->getBalanceId(),
            ]
        ];

        $this->makeRequestAndGetContent($request);

        /** @var Balance\Entity $subBalance */
        $subBalance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $subBalance->getId(), ['balance' => $this->bankingBalance->getBalance()]);

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => 0]);

        $subBalance->reload();

        /** @var Balance\SubBalanceMap\Entity $subBalanceMap */
        $subBalanceMap = $this->getDbLastEntity('sub_balance_map');

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should get processed since merchant has enough balance
        PayoutPostCreateProcessLowPriority::dispatch('test', $payout->getId(), 'false');

        /** @var PayoutsIntermediateTransactions\Entity $intermediateTxn */
        $intermediateTxn = $this->getDbLastEntity(Constants\Entity::PAYOUTS_INTERMEDIATE_TRANSACTIONS);

        /** @var ReversalEntity $reversal */
        $reversal = $this->getDbLastEntity(Constants\Entity::REVERSAL);

        /** @var TransactionEntity $txn */
        $txn = $this->getDbLastEntity(Constants\Entity::TRANSACTION);

        /** @var Balance\Entity $subBalanceAfter */
        $subBalanceAfter = $this->getDbEntityById('balance', $subBalance->getId());

        /** @var Payout\Entity $payout */
        $payout->reload();

        // assertions on balance_id
        $this->assertEquals($subBalanceAfter->getId(), $payout->getBalanceId());
        $this->assertEquals($payout->getBalanceId(), $txn->getBalanceId());

        // assertions on closing balance
        $this->assertEquals($subBalanceAfter->getBalance(),
                            $subBalance->getBalance() - $payout->getAmount() - $payout->getFees());
        $this->assertEquals($subBalanceAfter->getBalance(), $txn->getBalance());
        $this->assertEquals($subBalanceAfter->getBalance(), $intermediateTxn->getClosingBalance());
        $this->assertEquals($intermediateTxn->getClosingBalance(), $txn->getBalance());

        // assertions on payout intermediate transactions
        $this->assertEquals(PayoutsIntermediateTransactions\Status::COMPLETED, $intermediateTxn->getStatus());
        $this->assertNotNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::PENDING_AT));
        $this->assertNotNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::COMPLETED_AT));
        $this->assertNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::REVERSED_AT));

        // assertions on id
        $this->assertEquals($txn->getId(), $payout->getTransactionId());
        $this->assertEquals($txn->getId(), $intermediateTxn->getTransactionId());
        $this->assertEquals($payout->getId(), $intermediateTxn->payout->getId());
        $this->assertEquals('payout', $txn->getType());

        // assertions on amount and fees and pricing rule id
        $this->assertEquals($payout->getAmount() + $payout->getFees(), $txn->getAmount());
        $this->assertEquals($payout->getFees(), $txn->getFee());
        $this->assertEquals($payout->getTax(), $txn->getTax());
        $this->assertEquals($payout->getAmount() + $payout->getFees(), $intermediateTxn->getAmount());
        $this->assertNotNull($payout->getPricingRuleId());

        $publicResponse = $payout->toArrayPublic();

        // assertions on payout status
        $this->assertEquals('created', $payout['internal_status']);
        $this->assertEquals('processing', $publicResponse['status']);
        $this->assertNotNull($payout['initiated_at']);
    }

    public function testProcessingOfCreateRequestSubmittedPayoutForHighTpsAsyncIngress()
    {
        Queue::fake();

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_ASYNC_INGRESS]);

        $payoutRequest = $this->testData['testCompositePayoutCreationViaNewCompositeFlow']['request']['content'];
        $response      = $this->testCompositePayoutCreationViaNewCompositeFlow();

        Queue::assertPushed(PayoutPostCreateProcessLowPriority::class, 1);

        $payoutId = substr($response['id'], 5);

        $balance = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        $request = [
            'url'     => '/create_sub_balance',
            'method'  => 'post',
            'content' => [
                'parent_balance_id' => $balance->getId(),
            ]
        ];

        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($request);

        /** @var Balance\Entity $subBalance */
        $subBalance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $subBalance->getId(), ['balance' => $this->bankingBalance->getBalance()]);

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => 0]);

        $subBalance->reload();

        $fundAccountDetails = $response[Payout\Entity::FUND_ACCOUNT];

        $metadata = [
            Payout\Entity::PAYOUT       => [
                Payout\Entity::ID         => $payoutId,
                Payout\Entity::CREATED_AT => $response[Payout\Entity::CREATED_AT],
            ],
            Payout\Entity::CONTACT      => [
                Payout\Entity::ID         => substr($fundAccountDetails[Payout\Entity::CONTACT][Payout\Entity::ID], 5),
                Payout\Entity::CREATED_AT => $fundAccountDetails[Payout\Entity::CONTACT][Payout\Entity::CREATED_AT]
            ],
            Payout\Entity::FUND_ACCOUNT => [
                Payout\Entity::ID         => substr($fundAccountDetails[Payout\Entity::ID], 3),
                Payout\Entity::CREATED_AT => $fundAccountDetails[Payout\Entity::CREATED_AT]
            ]
        ];

        $merchantId = $this->bankingBalance->merchant->getId();

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should get processed since merchant has enough balance.
        (new PayoutPostCreateProcessLowPriority('test', $payoutId, 'false', $metadata, $merchantId, $payoutRequest))->handle();

        /** @var PayoutsIntermediateTransactions\Entity $intermediateTxn */
        $intermediateTxn = $this->getDbLastEntity(Constants\Entity::PAYOUTS_INTERMEDIATE_TRANSACTIONS);

        /** @var ReversalEntity $reversal */
        $reversal = $this->getDbLastEntity(Constants\Entity::REVERSAL);

        /** @var TransactionEntity $txn */
        $txn = $this->getDbLastEntity(Constants\Entity::TRANSACTION);

        /** @var Balance\Entity $subBalanceAfter */
        $subBalanceAfter = $this->getDbEntityById('balance', $subBalance->getId());

        /** @var Payout\Entity $payout */
        $payout = $this->getDbLastEntity('payout');

        // assertions on balance_id
        $this->assertEquals($subBalanceAfter->getId(), $payout->getBalanceId());
        $this->assertEquals($payout->getBalanceId(), $txn->getBalanceId());

        // assertions on closing balance
        $this->assertEquals($subBalanceAfter->getBalance(),
                            $subBalance->getBalance() - $payout->getAmount() - $payout->getFees());
        $this->assertEquals($subBalanceAfter->getBalance(), $txn->getBalance());
        $this->assertEquals($subBalanceAfter->getBalance(), $intermediateTxn->getClosingBalance());
        $this->assertEquals($intermediateTxn->getClosingBalance(), $txn->getBalance());

        // assertions on payout intermediate transactions
        $this->assertEquals(PayoutsIntermediateTransactions\Status::COMPLETED, $intermediateTxn->getStatus());
        $this->assertNotNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::PENDING_AT));
        $this->assertNotNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::COMPLETED_AT));
        $this->assertNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::REVERSED_AT));

        // assertions on id
        $this->assertEquals($txn->getId(), $payout->getTransactionId());
        $this->assertEquals($txn->getId(), $intermediateTxn->getTransactionId());
        $this->assertEquals($payout->getId(), $intermediateTxn->payout->getId());
        $this->assertEquals('payout', $txn->getType());
        $this->assertEquals($payoutId, $payout->getId());

        // assertions on amount and fees and pricing rule id
        $this->assertEquals($payout->getAmount() + $payout->getFees(), $txn->getAmount());
        $this->assertEquals($payout->getFees(), $txn->getFee());
        $this->assertEquals($payout->getTax(), $txn->getTax());
        $this->assertEquals($payout->getAmount() + $payout->getFees(), $intermediateTxn->getAmount());
        $this->assertNotNull($payout->getPricingRuleId());

        $publicResponse = $payout->toArrayPublic();

        // assertions on payout status
        $this->assertEquals('created', $payout['internal_status']);
        $this->assertEquals('processing', $publicResponse['status']);
        $this->assertNotNull($payout['initiated_at']);
    }

    public function testGetPayoutsAndGetBalanceForHighTpsMerchantsWithSubBalances()
    {
        $this->testProcessingOfCreateRequestSubmittedPayoutForHighTps();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout->getId(), ['reference_id' => 'Kurama']);

        $this->ba->privateAuth();

        $request = [
            'url'     => '/payouts?account_number=2224440041626905&reference_id=Kurama',
            'method'  => 'get',
            'content' => []
        ];

        $payouts = $this->makeRequestAndGetContent($request);

        $this->assertEquals('pout_' . $payout->getId(), $payouts['items'][0]['id']);

        /** @var Balance\Entity $subBalance */
        $subBalance = $this->getDbEntity('balance', ['id' => $payout->getBalanceId()]);

        $balance = $this->getDbEntity('balance', ['account_number' => '2224440041626905', 'account_type' => 'shared']);

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => 1]);

        $balance->reload();

        $merhcantUser = $this->getDbEntity('merchant_user', ['merchant_id' => $payout->getMerchantId(), 'product' => 'banking'])->toArray();

        $request = [
            'url'     => '/users/' . $merhcantUser['user_id'],
            'method'  => 'get',
            'content' => []
        ];

        $request['server']['HTTP_X-Dashboard-User-id'] = $merhcantUser['user_id'];
        $this->ba->dashboardGuestAppAuth();

        $response = $this->makeRequestAndGetContent($request);

        $count = 0;

        foreach ($response['merchants'] as $merchant)
        {
            if (array_key_exists('accounts', $merchant) === true)
            {
                foreach ($merchant['accounts'] as $account)
                {
                    if ($account['banking_balance'] !== null)
                    {
                        if ($account['banking_balance']['id'] === $balance->getId())
                        {
                            $this->assertEquals($balance->getBalance() + $subBalance->getBalance(), $account['banking_balance']['balance']);

                            $count++;
                        }
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $count);
    }

    public function testProcessingOfCreateRequestSubmittedPayoutForHighTpsWithLowBalance()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $this->expectWebhookEvent('payout.failed');

        $txnsBefore = $this->getDbEntities(Constants\Entity::TRANSACTION);

        $this->fixtures->on('test')->edit('balance', $this->bankingBalance->getId(), ['balance' => 1000]);

        $this->testCreatePayoutForRequestSubmitted();

        $payout  = $this->getDbLastEntity('payout');
        $balance = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should get failed due to insufficient balance
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), false);

        /** @var PayoutsIntermediateTransactions\Entity $intermediateTxn */
        $intermediateTxn = $this->getDbLastEntity(Constants\Entity::PAYOUTS_INTERMEDIATE_TRANSACTIONS);

        $txnsAfter = $this->getDbEntities(Constants\Entity::TRANSACTION);

        /** @var Balance\Entity $balanceAfter */
        $balanceAfter = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        /** @var Payout\Entity $payout */
        $payout->reload();

        // assertions on balance_id
        $this->assertEquals($balanceAfter->getId(), $payout->getBalanceId());

        // assertions on closing balance
        $this->assertEquals($balanceAfter->getBalance(), $balance->getBalance());

        $this->assertNull($intermediateTxn);

        $this->assertEquals($txnsAfter->toArrayPublic()['count'], $txnsBefore->toArrayPublic()['count']);

        // assertions on payout status
        $this->assertEquals('failed', $payout->getStatus());
        $this->assertEquals('BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING', $payout->getStatusCode());
        $this->assertEquals('Insufficient balance to process payout', $payout->getFailureReason());
        $this->assertNotNull($payout->getFailedAt());
        $this->assertNull($payout->getTransactionId());
    }

    public function testProcessingOfCreateRequestSubmittedPayoutForHighTpsWithLowBalanceAndQueueIfLowBalanceFlagSet()
    {
        $txnsBefore = $this->getDbEntities(Constants\Entity::TRANSACTION);

        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $this->expectWebhookEvent('payout.queued');

        $this->fixtures->on('test')->edit('balance', $this->bankingBalance->getId(), ['balance' => 1000]);

        $this->testCreatePayoutForRequestSubmitted();

        $payout  = $this->getDbLastEntity('payout');
        $balance = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as true for this test.
        // Payout should get queued since balance is low but the queue flag is set to true
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), true);

        /** @var PayoutsIntermediateTransactions\Entity $intermediateTxn */
        $intermediateTxn = $this->getDbLastEntity(Constants\Entity::PAYOUTS_INTERMEDIATE_TRANSACTIONS);

        $txnsAfter = $this->getDbEntities(Constants\Entity::TRANSACTION);

        /** @var Balance\Entity $balanceAfter */
        $balanceAfter = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        /** @var Payout\Entity $payout */
        $payout->reload();

        // assertions on balance_id
        $this->assertEquals($balanceAfter->getId(), $payout->getBalanceId());

        // assertions on closing balance
        $this->assertEquals($balanceAfter->getBalance(), $balance->getBalance());

        $this->assertNull($intermediateTxn);

        $this->assertEquals($txnsAfter->toArrayPublic()['count'], $txnsBefore->toArrayPublic()['count']);

        // assertions on payout status
        $this->assertEquals('queued', $payout->getStatus());
        $this->assertEquals('low_balance', $payout->getQueuedReason());
        $this->assertNotNull($payout->getQueuedAt());
        $this->assertNull($payout->getTransactionId());
    }

    public function testProcessingOfCreateRequestSubmittedPayoutForHighTpsWithForcedError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $this->expectWebhookEvent('payout.reversed');

        $this->testCreatePayoutForRequestSubmitted();

        $payout  = $this->getDbLastEntity('payout');
        $balance = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        $this->fixtures->edit('payout', $payout['id'], [
            'fund_account_id' => null
        ]);

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as true for this test.
        // Payout should get queued since balance is low but the queue flag is set to true
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), true);

        /** @var PayoutsIntermediateTransactions\Entity $intermediateTxn */
        $intermediateTxn = $this->getDbLastEntity(Constants\Entity::PAYOUTS_INTERMEDIATE_TRANSACTIONS);

        /** @var ReversalEntity $reversal */
        $reversal = $this->getDbLastEntity(Constants\Entity::REVERSAL);

        /** @var TransactionEntity $payoutTxn */
        $payoutTxn   = $this->getDbEntity(Constants\Entity::TRANSACTION, ['type' => 'payout']);
        $reversalTxn = $this->getDbEntity(Constants\Entity::TRANSACTION, ['type' => 'reversal']);

        /** @var Balance\Entity $balanceAfter */
        $balanceAfter = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        /** @var Payout\Entity $payout */
        $payout->reload();

        // assertions on balance_id
        $this->assertEquals($balanceAfter->getId(), $payout->getBalanceId());
        $this->assertEquals($payout->getBalanceId(), $payoutTxn->getBalanceId());

        // assertions on closing balance
        $this->assertEquals($balanceAfter->getBalance(), $balance->getBalance());
        $this->assertEquals($balanceAfter->getBalance(), $reversalTxn->getBalance());
        $this->assertNotEquals($balanceAfter->getBalance(), $intermediateTxn->getClosingBalance());
        $this->assertEquals($intermediateTxn->getClosingBalance(), $payoutTxn->getBalance());

        // assertions on payout intermediate transactions
        $this->assertEquals(PayoutsIntermediateTransactions\Status::REVERSED, $intermediateTxn->getStatus());
        $this->assertNotNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::PENDING_AT));
        $this->assertNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::COMPLETED_AT));
        $this->assertNotNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::REVERSED_AT));

        // assertions on id
        $this->assertEquals($payoutTxn->getId(), $payout->getTransactionId());
        $this->assertEquals($payoutTxn->getId(), $intermediateTxn->getTransactionId());
        $this->assertEquals($payout->getId(), $intermediateTxn->payout->getId());
        $this->assertEquals('payout', $payoutTxn->getType());

        // assertions on amount and fees and pricing rule id
        $this->assertEquals($payout->getAmount() + $payout->getFees(), $payoutTxn->getAmount());
        $this->assertEquals($payout->getFees(), $payoutTxn->getFee());
        $this->assertEquals($payout->getTax(), $payoutTxn->getTax());
        $this->assertEquals($payout->getAmount() + $payout->getFees(), $intermediateTxn->getAmount());
        $this->assertNotNull($payout->getPricingRuleId());

        $publicResponse = $payout->toArrayPublic();

        // assertions on final status
        $this->assertEquals('reversed', $payout['internal_status']);
        $this->assertEquals('reversed', $publicResponse['status']);
        $this->assertNotNull($payout['reversed_at']);
    }

    // fts returns with failed status
    public function testCreateRequestSubmittedPayoutForHighTpsWithReversal()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);
        $this->testCreatePayoutForRequestSubmitted();

        $payout  = $this->getDbLastEntity('payout');
        $balance = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        // Manually pushing into the queue because this is the only way to do this.
        // Keeping the queueFlag as false for this test.
        // Payout should get processed since merchant has enough balance
        PayoutPostCreateProcess::dispatch('test', $payout->getId(), 'false');

        $this->fixtures->edit('payout', $payout->getId(), ['status' => 'initiated']);

        $this->expectWebhookEvent('payout.reversed');

        $this->updateFtaAndSource($payout->getId(), Status::FAILED);

        /** @var PayoutsIntermediateTransactions\Entity $intermediateTxn */
        $intermediateTxn = $this->getDbLastEntity(Constants\Entity::PAYOUTS_INTERMEDIATE_TRANSACTIONS);

        /** @var ReversalEntity $reversal */
        $reversal = $this->getDbLastEntity(Constants\Entity::REVERSAL);

        /** @var TransactionEntity $payoutTxn */
        $payoutTxn   = $this->getDbEntity(Constants\Entity::TRANSACTION, ['type' => 'payout']);
        $reversalTxn = $this->getDbEntity(Constants\Entity::TRANSACTION, ['type' => 'reversal']);

        /** @var Balance\Entity $balanceAfter */
        $balanceAfter = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        /** @var Payout\Entity $payout */
        $payout->reload();

        // assertions on balance_id
        $this->assertEquals($balanceAfter->getId(), $payout->getBalanceId());
        $this->assertEquals($payout->getBalanceId(), $payoutTxn->getBalanceId());

        // assertions on closing balance
        $this->assertEquals($balanceAfter->getBalance(), $balance->getBalance());
        $this->assertEquals($balanceAfter->getBalance(), $reversalTxn->getBalance());
        $this->assertNotEquals($balanceAfter->getBalance(), $intermediateTxn->getClosingBalance());
        $this->assertEquals($intermediateTxn->getClosingBalance(), $payoutTxn->getBalance());

        // assertions on payout intermediate transactions
        $this->assertEquals(PayoutsIntermediateTransactions\Status::COMPLETED, $intermediateTxn->getStatus());
        $this->assertNotNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::PENDING_AT));
        $this->assertNotNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::COMPLETED_AT));
        $this->assertNull($intermediateTxn->getAttribute(PayoutsIntermediateTransactions\Entity::REVERSED_AT));

        // assertions on id
        $this->assertEquals($payoutTxn->getId(), $payout->getTransactionId());
        $this->assertEquals($payoutTxn->getId(), $intermediateTxn->getTransactionId());
        $this->assertEquals($payout->getId(), $intermediateTxn->payout->getId());
        $this->assertEquals('payout', $payoutTxn->getType());

        // assertions on amount and fees and pricing rule id
        $this->assertEquals($payout->getAmount() + $payout->getFees(), $payoutTxn->getAmount());
        $this->assertEquals($payout->getFees(), $payoutTxn->getFee());
        $this->assertEquals($payout->getTax(), $payoutTxn->getTax());
        $this->assertEquals($payout->getAmount() + $payout->getFees(), $intermediateTxn->getAmount());
        $this->assertNotNull($payout->getPricingRuleId());

        $publicResponse = $payout->toArrayPublic();

        // assertions on final status
        $this->assertEquals('reversed', $payout['internal_status']);
        $this->assertEquals('reversed', $publicResponse['status']);
        $this->assertNotNull($payout['reversed_at']);

        $this->assertEquals($payout->getId(), $reversal->getEntityId());
        $this->assertEquals('payout', $reversal->getEntityType());
        $this->assertEquals($payout->getAmount() + $payout->getFees(), $reversal->getAmount());
    }

    public function testProcessingOfCreateRequestSubmittedPayoutForHighTpsForDirectAccounts()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_PROCESS_ASYNC]);

        $this->ba->privateAuth();

        $balance = $this->getDbEntityById('balance', $this->bankingBalance->getId());

        $this->fixtures->edit('balance', $balance->getId(), ['account_type' => 'direct']);

        $this->startTest();
    }

    public function testPayoutUpdatedWebhookWithRazorxExperimentForBeneficiaryBankConfirmationPendingRTGSMode()
    {
        $this->setmockRazorxTreatment(['enable_status_details_feature' => 'on'], 'control');

        $payloadUpdatedOne = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout->getId(), ['mode' => 'RTGS']);

        $payout = $this->getDbLastEntity('payout');

        $bankAccount = $payout->fundaccount->account;

        $this->fixtures->edit('bank_account', $bankAccount->getId(), ['ifsc' => 'ICIC0000104']);

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'mode'             => 'RTGS',
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'beneficiary_bank_confirmation_pending',
                'parameters' => [
                    'processed_by_time' => '1636481743',
                ],
            ],
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertNotNull($statusDetails);
        $this->assertEquals('beneficiary_bank_confirmation_pending', $statusDetails['reason']);
        $this->assertEquals('Confirmation of credit to the beneficiary is pending from ICICI Bank. Please check the status after 09th November 2021, 11:45 PM', $statusDetails['description']);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testPayoutUpdatedWebhookWithRazorxExperimentForBeneficiaryBankConfirmationPendingNEFTMode()
    {
        $this->setmockRazorxTreatment(['enable_status_details_feature' => 'on'], 'control');

        $payloadUpdatedOne = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout->getId(), ['mode' => 'NEFT']);

        $payout = $this->getDbLastEntity('payout');

        $bankAccount = $payout->fundaccount->account;

        $this->fixtures->edit('bank_account', $bankAccount->getId(), ['ifsc' => 'HDFC0000104']);

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'mode'             => 'NEFT',
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'beneficiary_bank_confirmation_pending',
                'parameters' => [
                    'processed_by_time' => '1636481743',
                ],
            ],
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertNotNull($statusDetails);
        $this->assertEquals('beneficiary_bank_confirmation_pending', $statusDetails['reason']);
        $this->assertEquals('Confirmation of credit to the beneficiary is pending from HDFC Bank. Please check the status after 09th November 2021, 11:45 PM', $statusDetails['description']);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testPayoutUpdatedWebhookWithRazorxExperimentForBeneficiaryBankConfirmationPendingIMPSMode()
    {
        $this->setmockRazorxTreatment(['enable_status_details_feature' => 'on'], 'control');

        $payloadUpdatedOne = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $bankAccount = $payout->fundaccount->account;

        $this->fixtures->edit('bank_account',
                              $bankAccount->getId(), ['ifsc' => 'ICIC0000104']);

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'mode'             => 'IMPS',
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'beneficiary_bank_confirmation_pending',
                'parameters' => [
                    'processed_by_time' => '1636481743',
                ],
            ],
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertNotNull($statusDetails);
        $this->assertEquals('beneficiary_bank_confirmation_pending', $statusDetails['reason']);
        $this->assertEquals('Confirmation of credit to the beneficiary is pending from ICICI Bank. Please check the status after 09th November 2021', $statusDetails['description']);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testPayoutUpdatedWebhookWithRazorxExperimentForBeneficiaryBankConfirmationPendingUPIMode()
    {
        $this->setmockRazorxTreatment(['enable_status_details_feature' => 'on'], 'control');

        $payloadUpdatedOne = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout->getId(), ['mode' => 'UPI']);

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'mode'             => 'UPI',
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'beneficiary_bank_confirmation_pending',
                'parameters' => [
                    'processed_by_time' => '1636481743',
                ],
            ],
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertNotNull($statusDetails);
        $this->assertEquals('beneficiary_bank_confirmation_pending', $statusDetails['reason']);
        $this->assertEquals('Confirmation of credit to the beneficiary is pending from beneficiary bank. Please check the status after 09th November 2021', $statusDetails['description']);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testPayoutUpdatedWebhookWithRazorxExperimentForBankWindowClosedNEFTMode()
    {
        $this->setmockRazorxTreatment(['enable_status_details_feature' => 'on'], 'control');

        $payloadUpdatedOne = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout->getId(), ['mode' => 'NEFT']);

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'bank_window_closed',
                'parameters' => [
                    'processed_by_time' => '1636472623',
                ],
            ],
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertNotNull($statusDetails);
        $this->assertEquals('bank_window_closed', $statusDetails['reason']);
        $this->assertEquals('The NEFT window for the day is closed. Please check the status after 09th November 2021, 09:13 PM', $statusDetails['description']);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testPayoutUpdatedWebhookWithRazorxExperimentForBankWindowClosedRTGSMode()
    {
        $this->setmockRazorxTreatment(['enable_status_details_feature' => 'on'], 'control');

        $payloadUpdatedOne = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout->getId(), ['mode' => 'RTGS']);

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'mode'             => 'UPI',
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'bank_window_closed',
                'parameters' => [
                    'processed_by_time' => '1636484602',
                ],
            ],
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertNotNull($statusDetails);
        $this->assertEquals('bank_window_closed', $statusDetails['reason']);
        $this->assertEquals('The RTGS window for the day is closed. Please check the status after 10th November 2021, 12:33 AM', $statusDetails['description']);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testPayoutUpdatedWebhookWithRazorxExperimentForPayoutProcessing()
    {
        $this->setmockRazorxTreatment(['enable_status_details_feature' => 'on'], 'control');

        $payloadUpdatedOne = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'mode'             => 'UPI',
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
            'status_details'   => [
                'reason'     => 'payout_bank_processing',
                'parameters' => [
                    'processed_by_time' => '',
                ],
            ],
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertNotNull($statusDetails);
        $this->assertEquals('payout_bank_processing', $statusDetails['reason']);
        $this->assertEquals('Payout is being processed by our partner bank. Please check the final status after some time', $statusDetails['description']);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testPayoutUpdatedWebhookWithRazorxExperimentForNullCase()
    {
        $this->setmockRazorxTreatment(['enable_status_details_feature' => 'on'], 'control');

        $payloadUpdatedOne = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'source_type'      => 'payout',
            'source_id'        => $payout->getId(),
            'fta_status'       => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'mode'             => 'UPI',
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS',
        ]);

        $statusDetails = $this->getDbLastEntity('payouts_status_details');

        $this->assertNull($statusDetails);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testPayoutUpdatedWebhookWithoutRazorxExperimentNotContainingStatusDetails()

    {
        $payloadUpdatedOne = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadUpdated) {
                $this->assertContains($payload['event']['name'], ['payout.updated']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_UPDATED:
                        $payloadUpdated = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(5);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        (new Payout\Core)->updateWithDetailsBeforeFtaRecon($payout, [
            'status'           => 'initiated',
            'channel'          => 'rbl',
            'failure_reason'   => '',
            'utr'              => 928337183,
            'remarks'          => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $payoutUpdatedEventData = $this->testData[__FUNCTION__];

        $this->validateStorkWebhookFireEvent('payout.updated', $payoutUpdatedEventData, $payloadUpdated);
    }

    public function testPendingPayoutNotificationToSlackApp()
    {
        $this->setupSlackAppMock();

        $expected = [
            'merchant_id' => '10000000000000',
            'count'       => 5
        ];

        $this->slackAppMock->shouldReceive('sendPendingPayoutNotificationRequestToSlack')
                           ->withArgs(function($payload) use ($expected) {
                               return (($payload['merchant_id'] === $expected['merchant_id']) &&
                                       $payload['count'] === $expected['count']);
                           })->andReturn([]);

        $response = new \Requests_Response;

        $response->body = json_encode([
                                          "status" => 1,
                                          "msg"    => "success",
                                          "data"   => [
                                              [
                                                  "id"            => 1,
                                                  "merchant_id"   => "acc_10000000000000",
                                                  "slack_team_id" => "T025HQJDGKH",
                                                  "slack_user_id" => "U02E6JHBV7S"
                                              ]
                                          ]
                                      ]);

        $this->slackAppMock->shouldReceive('sendRequestToSlack')
                           ->andReturn($response);

        $this->testGetPayoutsForPendingOnRoles();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWithWorkflowEntities(12345, '2224440041626905', Payout\Purpose::CASHBACK, 'FXMwu4HMK7ZT0C');
        $this->createPayoutWithWorkflowEntities(23456, '2224440041626905', Payout\Purpose::CASHBACK, 'FXMwu4HMK7ZT0D');
        $this->createPayoutWithWorkflowEntities(11111, '2224440041626905', Payout\Purpose::SALARY, 'FXMwu4HMK7ZT0F');
        $this->createPayoutWithWorkflowEntities(50000, '2224440041626905', Payout\Purpose::SALARY, 'FXMwu4HMK7ZT0G');
        $this->createPayoutWithWorkflowEntities(65432, '2224440041626905', Payout\Purpose::REFUND, 'FXMwu4HMK7ZT0H');

        $this->payoutService->sendPendingPayoutsNotificationToSlack();
    }

    private function setupSlackAppMock()
    {
        $this->app['rzp.mode'] = 'live';

        $this->payoutService = new Payout\Service();

        $this->slackAppMock = Mockery::mock('RZP\Services\RazorpayLabs\SlackApp', [$this->app])->makePartial();

        $this->slackAppMock->shouldAllowMockingProtectedMethods();

        $this->unitTestCase->setPrivateProperty($this->payoutService, 'slackAppService', $this->slackAppMock);
    }

    public function testBeneNotificationOnPayoutProcessed()
    {
        Mail::fake();

        $this->fixtures->edit('contact', '1000001contact', ['email' => 'naruto@gmail.com', 'contact' => '919999188882']);

        $contact = $this->getDbEntityById('contact', '1000001contact');

        $this->fixtures->merchant->addFeatures([Feature\Constants::BENE_EMAIL_NOTIFICATION,
                                                Feature\Constants::BENE_SMS_NOTIFICATION]);

        $attributes = [
            'bas_business_id' => '10000000000000',
            'merchant_id'     => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $attributes);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta->getStatus());

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $storkMock = Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $storkMock = $this->expectStorkSendSmsRequest($storkMock,
                                         PayoutProcessedNotification::SMS_TEMPLATE,
                                         $contact->getContact());

        $this->app->instance('stork_service', $storkMock);

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED, '933815383814');

        Mail::assertQueued(PayoutProcessedContactCommunication::class, function($mail) {
            $mail->build();
            $this->assertEquals($mail->subject, '[Notification] Test Merchant has successfully transferred to you.');

            $this->assertArrayHasKey('payout_amount', $mail->viewData);
            $this->assertArrayHasKey('merchant_name', $mail->viewData);
            $this->assertArrayHasKey('merchant_billing_label', $mail->viewData);
            $this->assertArrayHasKey('merchant_brand_logo', $mail->viewData);
            $this->assertArrayHasKey('merchant_brand_color', $mail->viewData);
            $this->assertArrayHasKey('merchant_contrast_color', $mail->viewData);
            $this->assertArrayHasKey('payout_status', $mail->viewData);
            $this->assertArrayHasKey('payout_utr', $mail->viewData);
            $this->assertArrayHasKey('payout_reference_id', $mail->viewData);
            $this->assertArrayHasKey('payout_mode', $mail->viewData);
            $this->assertArrayHasKey('payout_id', $mail->viewData);
            $this->assertArrayHasKey('payout_processed_at', $mail->viewData);
            $this->assertArrayHasKey('merchant_website', $mail->viewData);
            $this->assertArrayHasKey('learn_more_url', $mail->viewData);

            $mail->hasTo('naruto@gmail.com');

            return true;
        });

        Mail::assertQueued(PayoutMail::class);
    }

    public function testBeneNotificationOnPayoutProcessedNotSentWhenFeatureIsNotEnabled()
    {
        Mail::fake();

        $this->fixtures->edit('contact', '1000001contact', ['email' => 'naruto@gmail.com', 'contact' => '919999188882']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta->getStatus());

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED, '933815383814');

        Mail::assertNotQueued(PayoutProcessedContactCommunication::class);

    }

    public function testBeneNotificationOnPayoutProcessedNotSentWhenContactEmailIsNotSet()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures([Feature\Constants::BENE_EMAIL_NOTIFICATION,
                                                Feature\Constants::BENE_SMS_NOTIFICATION]);

        $this->fixtures->edit('contact', '1000001contact', ['email' => null, 'contact' => '919999188882']);

        $contact = $this->getDbEntityById('contact', '1000001contact');

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta->getStatus());

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $storkMock = Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $storkMock = $this->expectStorkSendSmsRequest($storkMock,
                                                      PayoutProcessedNotification::SMS_TEMPLATE,
                                                      $contact->getContact());

        $this->app->instance('stork_service', $storkMock);

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED, '933815383814');

        Mail::assertNotQueued(PayoutProcessedContactCommunication::class);

    }

    private function mockDiag()
    {
        $diagMock = $this->getMockBuilder(DiagClient::class)
                         ->setConstructorArgs([$this->app])
                         ->setMethods(['trackEvent'])
                         ->getMock();

        $this->app->instance('diag', $diagMock);
    }

    private function verifyPayoutsEvent($expectedProperties)
    {
        $this->mockDiag();

        $this->app->diag->method('trackEvent')
                        ->will($this->returnCallback(
                            function(string $eventType,
                                     string $eventVersion,
                                     array $event,
                                     array $properties) use ($expectedProperties) {
                                if (($event['group'] === 'payouts') and
                                    ($event['name'] === 'payouts.fetch.request'))
                                {
                                    $this->assertArraySelectiveEquals($expectedProperties, $properties);
                                }

                                return;
                            }));
    }

    private function verifyBalanceEvent($expectedProperties)
    {
        $this->mockDiag();

        $this->app->diag->method('trackEvent')
                        ->will($this->returnCallback(
                            function(string $eventType,
                                     string $eventVersion,
                                     array $event,
                                     array $properties) use ($expectedProperties) {
                                if (($event['group'] === 'balance') and
                                    ($event['name'] === 'balance.fetch.request'))
                                {
                                    $this->assertArraySelectiveEquals($expectedProperties, $properties);
                                }

                                return;
                            }));
    }

    public function testCreatePayoutInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testCreatePayout();
    }

    public function testCreatePayoutOnLiveModeInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testCreatePayoutOnLiveMode();
    }

    public function testPayoutReversalInLedgerReverseShadowMode()
    {
        Queue::fake();
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->makeRequestAndGetContent($this->testData['testCreatePayout']['request']);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E10282323'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                            'Payout failed. Contact support for help.');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $reversal = $this->getLastEntity('reversal', true);
        $this->assertEquals(2001062, $reversal['amount']);

        // pushed twice
        // once for payout creation transaction
        // once for reversal transaction
        Queue::assertPushed(Transactions::class, 2);
    }

    public function testPayoutProcessedInLedgerReverseShadowMode()
    {
        Queue::fake();
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->makeRequestAndGetContent($this->testData['testCreatePayout']['request']);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'processed',
            'failure_reason'   => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::PROCESSED);
        $this->assertNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        // pushed once for payout creation
        Queue::assertPushed(Transactions::class, 1);
    }

    public function testPayoutChannelChangeWhenTxnNotCreatedInLedgerReverseShadow()
    {
        Queue::fake();

        $this->app['config']->set('applications.ledger.enabled', false);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->makeRequestAndGetContent($this->testData['testCreatePayout']['request']);

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('icici', $payout->getChannel());

        $this->assertNull($payout->transaction);

        $payoutId = $payout->getId();

        $this->ba->ftsAuth();

        $ftsWebhook = [
            'bank_processed_time' => '',
            'bank_account_type'   => 'SHARED',
            'bank_status_code'    => 'SUCCESS',
            'channel'             => 'ICICI',
            'extra_info'          => [
                'beneficiary_name' => 'Pullak',
                'cms_ref_no'       => '7a452792bee811ec949d0a0047340000',
                'internal_error'   => false,
                'ponum'            => '',
            ],
            'failure_reason'      => '',
            'fund_transfer_id'    => 327798418,
            'gateway_error_code'  => '',
            'gateway_ref_no'      => 'JKjdVokXZ2KMcP',
            'mode'                => 'IMPS',
            'narration'           => '256557209A0A',
            'remarks'             => '',
            'return_utr'          => '',
            'source_account_id'   => 1,
            'source_id'           => $payout->getId(),
            'source_type'         => 'payout',
            'status'              => 'PROCESSED',
            'utr'                 => '231456121234458',
            'status_details'      => null,
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => $ftsWebhook,
        ];

        $this->makeRequestAndGetContent($request);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::PROCESSED);
        $this->assertEquals('icici', $updatedPayout[Payout\Entity::CHANNEL]);
        $this->assertNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        // pushed once for payout creation
        Queue::assertPushed(Transactions::class, 1);
    }

    public function testPayoutChannelChangeViaReversalWhenTxnNotCreatedInLedgerReverseShadow()
    {
        Queue::fake();

        $this->app['config']->set('applications.ledger.enabled', false);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->makeRequestAndGetContent($this->testData['testCreatePayout']['request']);

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('icici', $payout->getChannel());

        $this->assertNull($payout->transaction);

        $payoutId = $payout->getId();

        $this->ba->ftsAuth();

        $ftsWebhook = [
            'bank_processed_time' => '',
            'bank_account_type'   => 'SHARED',
            'bank_status_code'    => 'FAILED',
            'channel'             => 'ICICI',
            'extra_info'          => [
                'beneficiary_name' => '',
                'cms_ref_no'       => '',
                'internal_error'   => false,
                'ponum'            => '',
            ],
            'failure_reason'      => 'Gateway rejection',
            'fund_transfer_id'    => 327798418,
            'gateway_error_code'  => '',
            'gateway_ref_no'      => 'JKjdVokXZ2KMcP',
            'mode'                => 'IMPS',
            'narration'           => '256557209A0A',
            'remarks'             => '',
            'return_utr'          => '',
            'source_account_id'   => 1,
            'source_id'           => $payout->getId(),
            'source_type'         => 'payout',
            'status'              => 'FAILED',
            'utr'                 => '231456121234458',
            'status_details'      => null,
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => $ftsWebhook,
        ];

        $this->makeRequestAndGetContent($request);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertNull($reversal->transaction);

        $reversal = $reversal->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertEquals('icici', $updatedPayout[Payout\Entity::CHANNEL]);

        $this->assertEquals('icici', $reversal[Payout\Entity::CHANNEL]);

        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        // pushed twice, once for payout creation and once for reversal
        Queue::assertPushed(Transactions::class, 2);
    }

    public function testPayoutChannelChangeWhenTxnAlreadyCreatedInLedgerReverseShadow()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->makeRequestAndGetContent($this->testData['testCreatePayout']['request']);

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('icici', $payout->getChannel());

        $this->assertEquals($payout->getChannel(), $payout->transaction->getChannel());

        $payoutId = $payout->getId();

        $this->ba->ftsAuth();

        $ftsWebhook = [
            'bank_processed_time' => '',
            'bank_account_type'   => 'SHARED',
            'bank_status_code'    => 'SUCCESS',
            'channel'             => 'ICICI',
            'extra_info'          => [
                'beneficiary_name' => 'Pullak',
                'cms_ref_no'       => '7a452792bee811ec949d0a0047340000',
                'internal_error'   => false,
                'ponum'            => '',
            ],
            'failure_reason'      => '',
            'fund_transfer_id'    => 327798418,
            'gateway_error_code'  => '',
            'gateway_ref_no'      => 'JKjdVokXZ2KMcP',
            'mode'                => 'IMPS',
            'narration'           => '256557209A0A',
            'remarks'             => '',
            'return_utr'          => '',
            'source_account_id'   => 1,
            'source_id'           => $payout->getId(),
            'source_type'         => 'payout',
            'status'              => 'PROCESSED',
            'utr'                 => '231456121234458',
            'status_details'      => null,
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => $ftsWebhook,
        ];

        $this->makeRequestAndGetContent($request);

        $updatedPayout    = $this->getDbEntityById('payout', $payoutId)->toArray();
        $updatedPayoutTxn = $this->getDbEntity('transaction', ['entity_id' => $payoutId])->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::PROCESSED);
        $this->assertEquals('icici', $updatedPayout[Payout\Entity::CHANNEL]);
        $this->assertEquals('icici', $updatedPayoutTxn[TransactionEntity::CHANNEL]);
        $this->assertNull($updatedPayout[Payout\Entity::REVERSED_AT]);
    }

    public function testPayoutChannelChangeViaReversalWhenTxnAlreadyCreatedInLedgerReverseShadow()
    {
        $this->app['config']->set('applications.ledger.enabled', false);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->makeRequestAndGetContent($this->testData['testCreatePayout']['request']);

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('icici', $payout->getChannel());

        $this->assertEquals($payout->getChannel(), $payout->transaction->getChannel());

        $payoutId = $payout->getId();

        $this->ba->ftsAuth();

        $ftsWebhook = [
            'bank_processed_time' => '',
            'bank_account_type'   => 'SHARED',
            'bank_status_code'    => 'FAILED',
            'channel'             => 'ICICI',
            'extra_info'          => [
                'beneficiary_name' => '',
                'cms_ref_no'       => '',
                'internal_error'   => false,
                'ponum'            => '',
            ],
            'failure_reason'      => 'Gateway rejection',
            'fund_transfer_id'    => 327798418,
            'gateway_error_code'  => '',
            'gateway_ref_no'      => 'JKjdVokXZ2KMcP',
            'mode'                => 'IMPS',
            'narration'           => '256557209A0A',
            'remarks'             => '',
            'return_utr'          => '',
            'source_account_id'   => 1,
            'source_id'           => $payout->getId(),
            'source_type'         => 'payout',
            'status'              => 'FAILED',
            'utr'                 => '231456121234458',
            'status_details'      => null,
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => $ftsWebhook,
        ];

        $this->makeRequestAndGetContent($request);

        $updatedPayout      = $this->getDbEntityById('payout', $payoutId)->toArray();
        $reversal           = $this->getDbLastEntity('reversal')->toArray();
        $updatedReversalTxn = $this->getDbEntity('transaction', ['entity_id' => $reversal['id']])->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertEquals('icici', $updatedPayout[Payout\Entity::CHANNEL]);

        $this->assertEquals('icici', $reversal[Payout\Entity::CHANNEL]);
        $this->assertEquals('icici', $updatedReversalTxn[TransactionEntity::CHANNEL]);

        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);
    }

    public function testDirectAccountPayoutProcessedInLedgerShadowMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_JOURNAL_WRITES]);

        $this->createDirectAccountPayout();

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $this->fixtures->edit('payout', $payoutId, [
            'utr' => 'sampleutr876545'
        ]);

        $payout = $this->getDbLastEntity('payout');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId234',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternal',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreated = $this->getDbLastEntity('transaction');

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternal',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreated['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreated = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'debit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreated['id'],
                                    'entity_id'           => $externalCreated['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr()
                                ]);

        // create a bas details for this payout so it gets used to pick basd id for ledger
        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => $payout->getMerchantId(),
            Details\Entity::BALANCE_ID     => $payout->getBalanceId(),
            Details\Entity::ACCOUNT_NUMBER => $payout->balance->getAccountNumber(),
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'processed',
            'failure_reason'   => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $updatedPayout      = $this->getDbEntityById('payout', $payoutId)->toArray();
        $updatedTransaction = $this->getDbEntityById('transaction', 'sampleTxnId234')->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::PROCESSED);
        $this->assertNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        $transactorTypeArray = [
            'da_ext_payout_processed',
            'da_payout_processed_recon',
        ];

        $this->assertEquals('sampleTxnId234', $updatedPayout['transaction_id']);
        $this->assertEquals('payout', $updatedTransaction['type']);

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payout->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    public function testDirectAccountPayoutProcessedInLedgerReverseShadowMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_REVERSE_SHADOW]);

        $this->createDirectAccountPayout();

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $this->fixtures->edit('payout', $payoutId, [
            'utr' => 'sampleutr876545'
        ]);

        $payout = $this->getDbLastEntity('payout');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId234',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternal',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreated = $this->getDbLastEntity('transaction');

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternal',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreated['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreated = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'debit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreated['id'],
                                    'entity_id'           => $externalCreated['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr()
                                ]);

        // create a bas details for this payout so it gets used to pick basd id for ledger
        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => $payout->getMerchantId(),
            Details\Entity::BALANCE_ID     => $payout->getBalanceId(),
            Details\Entity::ACCOUNT_NUMBER => $payout->balance->getAccountNumber(),
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'processed',
            'failure_reason'   => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();
        $transaction   = $this->getDbEntityById('transaction', 'sampleTxnId234')->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::PROCESSED);
        $this->assertNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        $transactorTypeArray = [
            'da_ext_payout_processed',
            'da_payout_processed_recon',
        ];

        $this->assertNull($updatedPayout['transaction_id']);
        $this->assertEquals('external', $transaction['type']);

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($payout->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    public function testDirectAccountPayoutReversedInLedgerShadowMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_JOURNAL_WRITES]);

        $this->createDirectAccountPayout();

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        // create a txn for payout
        $attributes = [
            'id'          => 'sampleTxnId000',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'payout',
            //            'entity_id'   => $payoutId,
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnForPayout = $this->getDbLastEntity('transaction');

        // update payout with utr and txn, so that we can mover ahead to test reversal
        $this->fixtures->edit('payout', $payoutId, [
            'utr' => 'sampleutr876545',
            //            'transaction_id'    => $txnForPayout['id'],
        ]);

        $txnForPayout->sourceAssociate($payout);
        $payout->transaction()->associate($txnForPayout);

        $txnForPayout->saveOrFail();
        $payout->saveOrFail();

        $txnForPayout = $this->getDbLastEntity('transaction');
        $payout       = $this->getDbLastEntity('payout');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId235',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternal',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreated = $this->getDbLastEntity('transaction');

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternal',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreated['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreated = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'credit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreated['id'],
                                    'entity_id'           => $externalCreated['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr(),
                                    'created_at'          => Carbon::now()->getTimestamp() + 3600
                                ]);

        // create a bas details for this payout so it gets used to pick basd id for ledger
        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => $payout->getMerchantId(),
            Details\Entity::BALANCE_ID     => $payout->getBalanceId(),
            Details\Entity::ACCOUNT_NUMBER => $payout->balance->getAccountNumber(),
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'reversed',
            'failure_reason'   => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $updatedPayout      = $this->getDbEntityById('payout', $payoutId)->toArray();
        $updatedTransaction = $this->getDbEntityById('transaction', 'sampleTxnId235')->toArray();

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        $transactorTypeArray = [
            'da_ext_payout_reversed',
            'da_payout_reversed_recon',
        ];

        $this->assertEquals('sampleTxnId235', $reversal['transaction_id']);
        $this->assertEquals('reversal', $updatedTransaction['type']);

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($reversal->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    public function testDirectAccountPayoutReversedInLedgerReverseShadowMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_REVERSE_SHADOW]);

        $this->createDirectAccountPayout();

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        // create a txn for payout
        $attributes = [
            'id'          => 'sampleTxnId000',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'payout',
            //            'entity_id'   => $payoutId,
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnForPayout = $this->getDbLastEntity('transaction');

        // update payout with utr and txn, so that we can mover ahead to test reversal
        $this->fixtures->edit('payout', $payoutId, [
            'utr' => 'sampleutr876545',
            //            'transaction_id'    => $txnForPayout['id'],
        ]);

        $txnForPayout->sourceAssociate($payout);
        $payout->transaction()->associate($txnForPayout);

        $txnForPayout->saveOrFail();
        $payout->saveOrFail();

        $txnForPayout = $this->getDbLastEntity('transaction');
        $payout       = $this->getDbLastEntity('payout');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId235',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternal',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreated = $this->getDbLastEntity('transaction');

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternal',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreated['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreated = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'credit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreated['id'],
                                    'entity_id'           => $externalCreated['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr(),
                                    'created_at'          => Carbon::now()->getTimestamp() + 3600
                                ]);

        // create a bas details for this payout so it gets used to pick basd id for ledger
        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => $payout->getMerchantId(),
            Details\Entity::BALANCE_ID     => $payout->getBalanceId(),
            Details\Entity::ACCOUNT_NUMBER => $payout->balance->getAccountNumber(),
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'reversed',
            'failure_reason'   => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();
        $transaction   = $this->getDbEntityById('transaction', 'sampleTxnId235')->toArray();

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        $transactorTypeArray = [
            'da_ext_payout_reversed',
            'da_payout_reversed_recon',
        ];

        $this->assertNull($reversal['transaction_id']);
        $this->assertEquals('external', $transaction['type']);

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($reversal->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    public function testDirectAccountPayoutProcessedAndReversedInLedgerShadowMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_JOURNAL_WRITES]);

        $this->createDirectAccountPayout();

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(4, $ledgerSnsPayloadArray);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $this->fixtures->edit('payout', $payoutId, [
            'utr' => 'sampleutr876545',
        ]);

        $payout = $this->getDbLastEntity('payout');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId235',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternal',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreatedForExternalPayout = $this->getDbLastEntity('transaction');

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternal',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreatedForExternalPayout['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreatedForPayout = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'debit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreatedForExternalPayout['id'],
                                    'entity_id'           => $externalCreatedForPayout['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr(),
                                    'created_at'          => Carbon::now()->getTimestamp() + 3600
                                ]);

        $banking_account_statement = $this->getDbLastEntity('banking_account_statement');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId230',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternl1',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreatedForExternalReversal = $this->getDbEntity('transaction', ['id' => 'sampleTxnId230']);

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternl1',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreatedForExternalReversal['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreatedForReversal = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'credit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreatedForExternalReversal['id'],
                                    'entity_id'           => $externalCreatedForReversal['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr(),
                                    'created_at'          => Carbon::now()->getTimestamp() + 3600
                                ]);

        $banking_account_statement_rev = $this->getDbLastEntity('banking_account_statement');

        // create a bas details for this payout so it gets used to pick basd id for ledger
        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => $payout->getMerchantId(),
            Details\Entity::BALANCE_ID     => $payout->getBalanceId(),
            Details\Entity::ACCOUNT_NUMBER => $payout->balance->getAccountNumber(),
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'reversed',
            'failure_reason'   => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        $transactorTypeArray = [
            'da_ext_payout_processed',
            'da_payout_processed_recon',
            'da_ext_payout_reversed',
            'da_payout_reversed_recon',
        ];

        $transactorIdArray = [
            $payout->getPublicId(),
            $payout->getPublicId(),
            $reversal->getPublicId(),
            $reversal->getPublicId(),
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    public function testDirectAccountFeePayoutProcessedAndReversedInLedgerShadowMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_JOURNAL_WRITES]);

        $this->createDirectAccountPayout();

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $this->fixtures->edit('payout', $payoutId, [
            'utr'     => 'sampleutr876545',
            'purpose' => 'rzp_fees',
        ]);

        $payout = $this->getDbLastEntity('payout');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId235',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternal',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreatedForExternalPayout = $this->getDbLastEntity('transaction');

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternal',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreatedForExternalPayout['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreatedForPayout = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'debit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreatedForExternalPayout['id'],
                                    'entity_id'           => $externalCreatedForPayout['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr(),
                                    'created_at'          => Carbon::now()->getTimestamp() + 3600
                                ]);

        $banking_account_statement = $this->getDbLastEntity('banking_account_statement');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId230',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternl1',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreatedForExternalReversal = $this->getDbEntity('transaction', ['id' => 'sampleTxnId230']);

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternl1',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreatedForExternalReversal['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreatedForReversal = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'credit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreatedForExternalReversal['id'],
                                    'entity_id'           => $externalCreatedForReversal['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr(),
                                    'created_at'          => Carbon::now()->getTimestamp() + 3600
                                ]);

        $banking_account_statement_rev = $this->getDbLastEntity('banking_account_statement');

        // create a bas details for this payout so it gets used to pick basd id for ledger
        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => $payout->getMerchantId(),
            Details\Entity::BALANCE_ID     => $payout->getBalanceId(),
            Details\Entity::ACCOUNT_NUMBER => $payout->balance->getAccountNumber(),
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'reversed',
            'failure_reason'   => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        $transactorTypeArray = [
            'da_ext_fee_payout_processed',
            'da_ext_fee_payout_reversed',
        ];

        $transactorIdArray = [
            $payout->getPublicId(),
            $reversal->getPublicId(),
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('2000', $ledgerRequestPayload['amount']);
            $this->assertEquals('590', $ledgerRequestPayload['commission']);
            $this->assertEquals('90', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    public function testDirectAccountPayoutProcessedAndReversedWithoutLedgerShadowMode()
    {
        $this->createDirectAccountPayout();

        $this->mockLedgerSns(0);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        $this->fixtures->edit('payout', $payoutId, [
            'utr' => 'sampleutr876545',
        ]);

        $payout = $this->getDbLastEntity('payout');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId235',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternal',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreatedForExternalPayout = $this->getDbLastEntity('transaction');

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternal',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreatedForExternalPayout['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreatedForPayout = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'debit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreatedForExternalPayout['id'],
                                    'entity_id'           => $externalCreatedForPayout['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr(),
                                    'created_at'          => Carbon::now()->getTimestamp() + 3600
                                ]);

        $banking_account_statement = $this->getDbLastEntity('banking_account_statement');

        // create a txn linked to bas and external
        $attributes = [
            'id'          => 'sampleTxnId230',
            'merchant_id' => $payout->getMerchantId(),
            'amount'      => $payout->getAmount(),
            'balance_id'  => $payout->getBalanceId(),
            'type'        => 'external',
            'entity_id'   => 'randomexternl1',
        ];
        $this->fixtures->create('transaction', $attributes);
        $txnCreatedForExternalReversal = $this->getDbEntity('transaction', ['id' => 'sampleTxnId230']);

        // create a external for this payout so it gets picked
        $this->fixtures->create('external',
                                [
                                    'id'             => 'randomexternl1',
                                    'merchant_id'    => $payout->getMerchantId(),
                                    'amount'         => $payout->getAmount(),
                                    'channel'        => $payout->getChannel(),
                                    'transaction_id' => $txnCreatedForExternalReversal['id'],
                                    'utr'            => $payout->getUtr(),
                                    'balance_id'     => $payout->getBalanceId(),
                                ]);

        $externalCreatedForReversal = $this->getDbLastEntity('external');

        // create a bas for this payout so it gets picked
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                => 'credit',
                                    'amount'              => $payout->getAmount(),
                                    'channel'             => $payout->getChannel(),
                                    'account_number'      => $payout->balance->getAccountNumber(),
                                    'transaction_id'      => $txnCreatedForExternalReversal['id'],
                                    'entity_id'           => $externalCreatedForReversal['id'],
                                    'entity_type'         => 'external',
                                    'bank_transaction_id' => 'SDHDH',
                                    'balance'             => 30019891,
                                    'transaction_date'    => 1584987183,
                                    'utr'                 => $payout->getUtr(),
                                    'created_at'          => Carbon::now()->getTimestamp() + 3600
                                ]);

        $banking_account_statement_rev = $this->getDbLastEntity('banking_account_statement');

        // create a bas details for this payout so it gets used to pick basd id for ledger
        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => $payout->getMerchantId(),
            Details\Entity::BALANCE_ID     => $payout->getBalanceId(),
            Details\Entity::ACCOUNT_NUMBER => $payout->balance->getAccountNumber(),
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'reversed',
            'failure_reason'   => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);
    }

    public function testCreateAndProcessQueuedPayoutInLedgerReverseShadowMode()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateAndProcessQueuedPayout'];

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
                   ->andThrow(new RuntimeException(
                                  'Unexpected response code received from Ledger service.',
                                  [
                                      'status_code'   => 400,
                                      'response_body' => [
                                          'code' => 'invalid_argument',
                                          'msg'  => 'validation_failure: validation_failure: BAD_REQUEST_INSUFFICIENT_BALANCE',
                                      ],
                                  ]
                              ));

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        // Setting the redis config as empty initially
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION => []]);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $currentBalance = $this->getDbLastEntity('balance');

        $response = $this->startTest();

        $newBalance = $this->getDbLastEntity('balance');

        // Since we created queued payouts, hence balance shouldn't change
        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNull($fta);

        // Create 2 more queued payouts
        $this->startTest();
        $this->startTest();

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 3 payouts in queued state.
        $this->assertEquals(3, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000003, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that there are still 3 payouts in queued state since there wasn't enough balance to process them
        $this->assertEquals(3, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000003, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance to process only one queued payout
        $this->app['config']->set('applications.ledger.enabled', true);
        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $updatedSummary = $this->makePayoutSummaryRequest();

        // Assert that there is only one payout in queued state. The other one got processed.
        $this->assertEquals(2, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000002, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance to process all queued payouts
        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 99000000]);

        // Set offset = 1 for this balance ID
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION => [
            $newBalance['id'] => 1
        ]]);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that only one payout got processed even though there was enough balance to process both.
        // This is because offset was set to 1.
        $this->assertEquals(1, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(10000001, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $offsetData = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION]);

        // Assert that offset has been set back to 0
        $this->assertEmpty($offsetData);
    }

    public function testFreePayoutsOnLedgerReverseShadowInLiveMode()
    {
        $this->testData[__FUNCTION__]                                = $this->testData['testCreatePayoutOnLiveMode'];
        $this->testData[__FUNCTION__]['response']['content']['fees'] = 0;
        $this->testData[__FUNCTION__]['response']['content']['tax']  = 0;

        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->liveSetUp();

        $balance = $this->bankingBalance;

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId, null, 'live');

        $counter1 = $this->getDbEntities('counter',
                                         [
                                             'account_type' => 'shared',
                                             'balance_id'   => $balanceId,
                                         ],
                                         'live')->first();

        //$freePayoutsCountBefore = $counter1->getFreePayoutsConsumed();

        // keeping 1 free payout available to be consumed
        $this->fixtures->on('live')->edit('counter', $counter1->getId(), ['free_payouts_consumed' => 299]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $payout = $this->getLastEntity('payout', true, 'live');

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true, 'live');

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'icici');

        // Verify transaction entity
        $txn   = $this->getLastEntity('transaction', true, 'live');
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true, 'live');

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA9",
            'percentage'      => null,
            'amount'          => 0,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $counter1->reload();

        $this->assertEquals(300, $counter1->getFreePayoutsConsumed());
    }

    public function testProcessingOfCreateRequestSubmittedPayoutInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testProcessingOfCreateRequestSubmittedPayout();
    }

    public function testProcessingOfCreateRequestSubmittedPayoutInsufficientBalanceInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
                   ->andThrow(new RuntimeException(
                                  'Unexpected response code received from Ledger service.',
                                  [
                                      'status_code'   => 400,
                                      'response_body' => [
                                          'code' => 'invalid_argument',
                                          'msg'  => 'validation_failure: validation_failure: BAD_REQUEST_INSUFFICIENT_BALANCE',
                                      ],
                                  ]
                              ));

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testProcessingOfCreateRequestSubmittedPayoutInsufficientBalance();
    }

    public function testProcessingOfCreateRequestSubmittedPayoutInsufficientBalanceQueueFlagTrueInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
                   ->andThrow(new RuntimeException(
                                  'Unexpected response code received from Ledger service.',
                                  [
                                      'status_code'   => 400,
                                      'response_body' => [
                                          'code' => 'invalid_argument',
                                          'msg'  => 'validation_failure: validation_failure: BAD_REQUEST_INSUFFICIENT_BALANCE',
                                      ],
                                  ]
                              ));

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testProcessingOfCreateRequestSubmittedPayoutInsufficientBalanceQueueFlagTrue();
    }

    // This tests processing of batch submitted payouts
    public function testProcessBatchSubmittedPayoutsInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->ba->batchAuth();

        $request =
            [
                'url'     => '/payouts/bulk',
                'method'  => 'POST',
                'content' => [
                    [
                        'razorpayx_account_number' => '2224440041626905',
                        'payout'                   => [
                            'amount'       => '100',
                            'currency'     => 'INR',
                            'mode'         => 'NEFT',
                            'purpose'      => 'refund',
                            'narration'    => '123',
                            'reference_id' => ''
                        ],
                        'fund'                     => [
                            'account_type'   => 'bank_account',
                            'account_name'   => 'Vivek Karna',
                            'account_IFSC'   => 'HDFC0003780',
                            'account_number' => '50100244702362',
                            'account_vpa'    => ''
                        ],
                        'contact'                  => [
                            'type'         => 'customer',
                            'name'         => 'Vivek Karna',
                            'email'        => 'sampleone@example.com',
                            'mobile'       => '9988998899',
                            'reference_id' => ''
                        ],
                        'notes'                    => [
                            'abc' => 'xyz',
                        ],
                        'idempotency_key'          => 'batch_abc123',

                    ]
                ]
            ];

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $request['server'] = $headers;

        $this->makeRequestAndGetContent($request);

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($this->testData['testProcessBulkPayoutDelayedInitiation']['request']);

        $payouts = $this->getDbEntities('payout');

        // Assertions for first payout (NEFT)
        $this->assertEquals(Payout\Mode::NEFT, $payouts[0]['mode']);
        $this->assertEquals(Payout\Status::CREATED, $payouts[0]['status']);

        $batchProcessingPayouts = $this->getDbEntities('payout', ['status' => Payout\Status::BATCH_SUBMITTED]);

        // Assert that no payouts remain in batch_processing state
        $this->assertEquals(0, $batchProcessingPayouts->count());
    }

    public function testProcessBatchSubmittedPayoutsWithInsufficientBalanceInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->ba->batchAuth();

        $request =
            [
                'url'     => '/payouts/bulk',
                'method'  => 'POST',
                'content' => [
                    [
                        'razorpayx_account_number' => '2224440041626905',
                        'payout'                   => [
                            'amount'       => '100',
                            'currency'     => 'INR',
                            'mode'         => 'NEFT',
                            'purpose'      => 'refund',
                            'narration'    => '123',
                            'reference_id' => ''
                        ],
                        'fund'                     => [
                            'account_type'   => 'bank_account',
                            'account_name'   => 'Vivek Karna',
                            'account_IFSC'   => 'HDFC0003780',
                            'account_number' => '50100244702362',
                            'account_vpa'    => ''
                        ],
                        'contact'                  => [
                            'type'         => 'customer',
                            'name'         => 'Vivek Karna',
                            'email'        => 'sampleone@example.com',
                            'mobile'       => '9988998899',
                            'reference_id' => ''
                        ],
                        'notes'                    => [
                            'abc' => 'xyz',
                        ],
                        'idempotency_key'          => 'batch_abc123',

                    ]
                ]
            ];

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $request['server'] = $headers;

        $this->makeRequestAndGetContent($request);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
                   ->andThrow(new RuntimeException(
                                  'Unexpected response code received from Ledger service.',
                                  [
                                      'status_code'   => 400,
                                      'response_body' => [
                                          'code' => 'invalid_argument',
                                          'msg'  => 'validation_failure: validation_failure: BAD_REQUEST_INSUFFICIENT_BALANCE',
                                      ],
                                  ]
                              ));

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($this->testData['testProcessBulkPayoutDelayedInitiation']['request']);

        $payouts = $this->getDbEntities('payout');

        // Assertions for first payout (NEFT)
        $this->assertEquals(Payout\Mode::NEFT, $payouts[0]['mode']);
        $this->assertEquals(Payout\Status::FAILED, $payouts[0]['status']);

        $batchProcessingPayouts = $this->getDbEntities('payout', ['status' => Payout\Status::BATCH_SUBMITTED]);

        // Assert that no payouts remain in batch_processing state
        $this->assertEquals(0, $batchProcessingPayouts->count());
    }

    public function testOnHoldPayoutCreateAndProcessInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout1 = $this->getDbLastEntity('payout')->toArray();

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout2 = $this->getDbLastEntity('payout')->toArray();

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout3 = $this->getDbLastEntity('payout')->toArray();

        $this->assertEquals($payout1['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout2['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout3['status'], Payout\Status::ON_HOLD);

        $benebankConfig =
            [
                "BENEFICIARY" =>
                    [
                        "SBIN" => [
                            "status" => "started",
                        ],
                        'HDFC' => [
                            'status' => "started"
                        ],
                    ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testOnHoldPayoutCreateAndProcess'];

        $this->startTest();

        $payout1 = $this->getDbEntityById('payout', $payout1['id'])->toArray();
        $this->assertEquals($payout1['status'], Payout\Status::CREATED);

        $payout3 = $this->getDbEntityById('payout', $payout3['id'])->toArray();
        $this->assertEquals($payout3['status'], Payout\Status::CREATED);

        $payout2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();
        $this->assertEquals($payout2['status'], Payout\Status::CREATED);
    }

    public function testOnHoldPayoutCreateAndProcessWithInsufficientBalanceInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout1 = $this->getDbLastEntity('payout')->toArray();

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout2 = $this->getDbLastEntity('payout')->toArray();

        $this->createOnHoldPayoutWhenBeneBankIsDown();

        $payout3 = $this->getDbLastEntity('payout')->toArray();

        $this->assertEquals($payout1['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout2['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout3['status'], Payout\Status::ON_HOLD);

        $benebankConfig =
            [
                "BENEFICIARY" =>
                    [
                        "SBIN" => [
                            "status" => "started",
                        ],
                        'HDFC' => [
                            'status' => "started"
                        ],
                    ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
                   ->andThrow(new RuntimeException(
                                  'Unexpected response code received from Ledger service.',
                                  [
                                      'status_code'   => 400,
                                      'response_body' => [
                                          'code' => 'invalid_argument',
                                          'msg'  => 'validation_failure: validation_failure: BAD_REQUEST_INSUFFICIENT_BALANCE',
                                      ],
                                  ]
                              ));

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testOnHoldPayoutCreateAndProcess'];

        $this->startTest();

        $payout1 = $this->getDbEntityById('payout', $payout1['id'])->toArray();
        $this->assertEquals($payout1['status'], Payout\Status::FAILED);

        $payout3 = $this->getDbEntityById('payout', $payout3['id'])->toArray();
        $this->assertEquals($payout3['status'], Payout\Status::FAILED);

        $payout2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();
        $this->assertEquals($payout2['status'], Payout\Status::FAILED);
    }

    public function testOnHoldPayoutCreateAndProcessWithInsufficientBalanceWithQueueIfLowBalanceFlagTrueInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUTS_ON_HOLD]);

        $this->createOnHoldPayoutWhenBeneBankIsDownWithQueueIfLowBalanceFlagTrue();

        $payout1 = $this->getDbLastEntity('payout')->toArray();

        $this->createOnHoldPayoutWhenBeneBankIsDownWithQueueIfLowBalanceFlagTrue();

        $payout2 = $this->getDbLastEntity('payout')->toArray();

        $this->createOnHoldPayoutWhenBeneBankIsDownWithQueueIfLowBalanceFlagTrue();

        $payout3 = $this->getDbLastEntity('payout')->toArray();

        $this->assertEquals($payout1['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout2['status'], Payout\Status::ON_HOLD);
        $this->assertEquals($payout3['status'], Payout\Status::ON_HOLD);

        $benebankConfig =
            [
                "BENEFICIARY" =>
                    [
                        "SBIN" => [
                            "status" => "started",
                        ],
                        'HDFC' => [
                            'status' => "started"
                        ],
                    ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
                   ->andThrow(new RuntimeException(
                                  'Unexpected response code received from Ledger service.',
                                  [
                                      'status_code'   => 400,
                                      'response_body' => [
                                          'code' => 'invalid_argument',
                                          'msg'  => 'validation_failure: validation_failure: BAD_REQUEST_INSUFFICIENT_BALANCE',
                                      ],
                                  ]
                              ));

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testOnHoldPayoutCreateAndProcess'];

        $this->startTest();

        $payout1 = $this->getDbEntityById('payout', $payout1['id'])->toArray();
        $this->assertEquals($payout1['status'], Payout\Status::QUEUED);

        $payout3 = $this->getDbEntityById('payout', $payout3['id'])->toArray();
        $this->assertEquals($payout3['status'], Payout\Status::QUEUED);

        $payout2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();
        $this->assertEquals($payout2['status'], Payout\Status::QUEUED);
    }

    public function testProcessPendingPayoutinLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->app['config']->set('heimdall.workflows.mock', false);

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createQueuedOrPendingPayout([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->testData[__FUNCTION__]                   = $this->testData['testApprovePayoutWithComment'];
        $this->testData[__FUNCTION__]['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $firstApprovalResponse = $this->startTest();

        $this->app['config']->set('database.default', 'live');

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());
        $secondApprovalResponse = $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals(Status::CREATED, $payout->getStatus());
    }

    public function testCreateAndProcessQueuedPayoutWithLowMerchantBalanceInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
                   ->andThrow(new RuntimeException(
                                  'Unexpected response code received from Ledger service.',
                                  [
                                      'status_code'   => 400,
                                      'response_body' => [
                                          'code' => 'invalid_argument',
                                          'msg'  => 'validation_failure: validation_failure: BAD_REQUEST_INSUFFICIENT_BALANCE',
                                      ],
                                  ]
                              ));

        $mockLedger->shouldReceive('fetchMerchantAccounts')
                   ->andReturn([
                                   "merchant_id"      => "10000000000000",
                                   "merchant_balance" => [
                                       "balance"     => "0.000000",
                                       "min_balance" => "0.000000"
                                   ],
                                   "reward_balance"   => [
                                       "balance"     => "20.000000",
                                       "min_balance" => "-20.000000"
                                   ],
                               ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        // Setting the redis config as empty initially
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_QUEUED_PAYOUTS_PAGINATION => []]);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $currentBalance = $this->getDbLastEntity('balance');

        $response = $this->startTest();

        $newBalance = $this->getDbLastEntity('balance');

        // Since we created queued payouts, hence balance shouldn't change
        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 4)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 4)]);

        $this->assertNull($fta);

        // Create 2 more queued payouts
        $this->startTest();
        $this->startTest();

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 3 payouts in queued state.
        $this->assertEquals(3, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000003, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that there are still 3 payouts in queued state since there wasn't enough balance to process them
        $this->assertEquals(3, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(30000003, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);
    }

    public function testSkipEmailNotificationForFeatureEnabledMerchantOnPayoutProcessed()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_PAYOUT_EMAIL]);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED, '933815383814');

        $payout->reload();

        $this->assertEquals('933815383814', $payout->getUtr());

        $this->assertEquals(Payout\Status::PROCESSED, $payout->getStatus());

        Mail::assertNotQueued(PayoutMail::class);

        $this->fixtures->merchant->removeFeatures([Feature\Constants::SKIP_PAYOUT_EMAIL]);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED, '933815383814');

        $payout->reload();

        $this->assertEquals('933815383814', $payout->getUtr());

        $this->assertEquals(Payout\Status::PROCESSED, $payout->getStatus());

        Mail::assertQueued(PayoutMail::class);

    }

    public function testSkipEmailNotificationForFeatureEnabledMerchantOnPayoutReversed()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_PAYOUT_EMAIL]);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $fta = $payout->fundTransferAttempts()->first();

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => 'initiated']);

        $this->updateFtaAndSource($payout->getId(), Payout\Status::REVERSED, '933815383814');

        $payout->reload();

        $this->assertEquals('933815383814', $payout->getUtr());

        $this->assertEquals(Payout\Status::REVERSED, $payout->getStatus());

        Mail::assertNotQueued(PayoutMail::class);
    }

    public function testProcessingOfCreateRequestSubmittedPayoutIfFreePayoutsAreAvailableInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testProcessingOfCreateRequestSubmittedPayoutIfFreePayoutsAreAvailable();
    }

    public function testPayoutCreationFailedDueToInsufficientBalanceWhenFreePayoutsAreAvailableAndWithQueuedFlagInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
                   ->times(1)
                   ->andThrow(new RuntimeException(
                                  'Unexpected response code received from Ledger service.',
                                  [
                                      'status_code'   => 400,
                                      'response_body' => [
                                          'code' => 'invalid_argument',
                                          'msg'  => 'validation_failure: validation_failure: BAD_REQUEST_INSUFFICIENT_BALANCE',
                                      ],
                                  ]
                              ));

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $balance = $this->bankingBalance;

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        $freePayoutsCountBefore = $counter->getFreePayoutsConsumed();

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => 2000,
            ]);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__] = $this->testData['testQueuedSharedAccountPayoutCreationAndCheckCounterAttributes'];

        $this->startTest();

        $counter->reload();

        $freePayoutsCountAfter = $counter->getFreePayoutsConsumed();

        // Assert that zero free payout has been consumed
        $this->assertEquals($freePayoutsCountBefore, $freePayoutsCountAfter);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('queued', $payout->getStatus());
    }

    public function testPayoutCreationFailedDueToInsufficientBalanceWhenFreePayoutsAreAvailableAndWithoutQueuedFlagInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->expectException(BadRequestException::class);

        $mockLedger->shouldReceive('createJournal')
                   ->times(1)
                   ->andThrow(new RuntimeException(
                                  'Unexpected response code received from Ledger service.',
                                  [
                                      'status_code'   => 400,
                                      'response_body' => [
                                          'code' => 'invalid_argument',
                                          'msg'  => 'validation_failure: validation_failure: BAD_REQUEST_INSUFFICIENT_BALANCE',
                                      ],
                                  ]
                              ));

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $balance = $this->bankingBalance;

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        $freePayoutsCountBefore = $counter->getFreePayoutsConsumed();

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => 2000,
            ]);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]                                               = $this->testData['testQueuedSharedAccountPayoutCreationAndCheckCounterAttributes'];
        $this->testData[__FUNCTION__]['request']['content']['queue_if_low_balance'] = 0;

        $this->startTest();

        $counter->reload();

        $freePayoutsCountAfter = $counter->getFreePayoutsConsumed();

        // Assert that zero free payout has been consumed
        $this->assertEquals($freePayoutsCountBefore, $freePayoutsCountAfter);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('failed', $payout->getStatus());
    }

    public function testProcessingOfFreePayoutsInLedgerReverseShadowModeInLiveMode()
    {
        $this->testFreePayoutsOnLedgerReverseShadowInLiveMode();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $this->bankingBalance->getId(),
                                        ],
                                        'live')->first();

        $freePayoutsCountBefore = $counter->getFreePayoutsConsumed();

        $payout = $this->getDbLastEntity('payout', 'live');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'processed',
            'failure_reason'   => '',
            'bank_status_code' => 'SUCCESS'
        ]);

        $counter->reload();

        $freePayoutsCountAfter = $counter->getFreePayoutsConsumed();

        // Assert that zero free payout has been consumed
        $this->assertEquals($freePayoutsCountBefore, $freePayoutsCountAfter);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::PROCESSED);
        $this->assertNull($updatedPayout[Payout\Entity::REVERSED_AT]);
    }

    public function testReversalOfFreePayoutsInLedgerReverseShadowModeInLiveMode()
    {
        $this->testFreePayoutsOnLedgerReverseShadowInLiveMode();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $this->bankingBalance->getId(),
                                        ],
                                        'live')->first();

        $freePayoutsCountBefore = $counter->getFreePayoutsConsumed();

        $payout = $this->getDbLastEntity('payout', 'live');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E10282323'
        ]);

        $counter->reload();

        $freePayoutsCountAfter = $counter->getFreePayoutsConsumed();

        // Assert that zero free payout has been consumed
        $this->assertEquals($freePayoutsCountBefore - 1, $freePayoutsCountAfter);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);
    }

    public function testPayoutReversalWithRewardsInLedgerReverseShadowMode()
    {
        $this->testData[__FUNCTION__] = $this->testData['testPayoutReversalWithRewards'];
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 1500, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);
        $this->assertNull($payout['user_id']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals(900, $payout['fees']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(900, $txn['fee']);
        $this->assertEquals('reward_fee', $txn['credit_type']);
        $this->assertEquals(900, $txn['fee_credits']);

        $balance = $this->getLastEntity('balance', true);
        $this->assertEquals('shared', $balance['account_type']);

        $this->assertEquals($balanceBefore - 2000000, $balance['balance']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(900, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true);
        $this->assertEquals('payout', $creditTxnEntity['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntity['entity_id']);
        $this->assertEquals(900, $creditTxnEntity['credits_used']);

        $payout = $this->getDbLastEntity('payout');

        $payoutId = $payout->getId();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'       => 'failed',
            'failure_reason'   => '',
            'bank_status_code' => 'YB_NS_E10282323'
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutId)->toArray();

        $this->assertEquals($updatedPayout[Payout\Entity::FAILURE_REASON],
                            'Payout failed. Contact support for help.');
        $this->assertEquals($updatedPayout[Payout\Entity::STATUS], Payout\Status::REVERSED);
        $this->assertNotNull($updatedPayout[Payout\Entity::REVERSED_AT]);

        //get reversal and check posted_at in reversal txn
        $payoutReversal = $this->getDbLastEntity('reversal');

        $txn = $this->getLastEntity('transaction', true);
        $this->assertNotNull($txn['posted_at']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(0, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true);
        $this->assertEquals('reversal', $creditTxnEntity['entity_type']);
        $this->assertEquals(-900, $creditTxnEntity['credits_used']);

        $reversal = $this->getLastEntity('reversal', true);
        $this->assertEquals(2000000, $reversal['amount']);

        $this->assertEquals(substr($txn['id'], strlen('txn_')), $reversal['transaction_id']);
    }

    public function testPayoutInitiatedInLedgerCron()
    {
        $this->testData[__FUNCTION__] = $this->testData['testPayoutInitiatedInLedgerCron'];
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->fixtures->create('payout', [
            'status'          => 'created',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'created_at'      => Carbon::now()->subMinutes(20)->getTimestamp(),
        ]);

        $this->ba->cronAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);
        $txn    = $this->getLastEntity('transaction', true);
        $this->assertEquals($payout['transaction_id'], $txn['id']);
    }

    public function testPayoutCreateOnInternalContactByCapitalCollections()
    {
        $this->ba->capitalCollectionsAuth();

        $contact = $this->fixtures->create('contact',
                                           [
                                               'name' => 'test name',
                                               'type' => \RZP\Models\Contact\Type::CAPITAL_COLLECTIONS_INTERNAL_CONTACT
                                           ]);

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($payout['fund_account_id'], $fundAccount['id']);
    }

    public function testPayoutCreateOnCollectionsInternalContactByOtherAppFailure()
    {
        $this->ba->capitalCollectionsAuth();

        $contact = $this->fixtures->create('contact',
                                           [
                                               'name' => 'test name',
                                               'type' => \RZP\Models\Contact\Type::CAPITAL_COLLECTIONS_INTERNAL_CONTACT
                                           ]);

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $this->ba->xPayrollAuth();

        $this->startTest();
    }

    private function removePermission($permission)
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = (new Admin\Permission\Repository())->retrieveIdsByNames([$permission]);

        $role->permissions()->delete($perm->firstOrFail()->getId());
    }

    public function testUpdateMerchantSlaForOnHoldPayoutsInsufficientPermission()
    {
        $this->ba->adminAuth();

        $this->removePermission(Admin\Permission\Name::SET_MERCHANT_SLA_FOR_ON_HOLD_PAYOUTS);

        $this->fixtures->merchant->create(['id' => '90000merchant1']);
        $this->fixtures->merchant->create(['id' => '90000merchant2']);
        $this->fixtures->merchant->create(['id' => '90000merchant3']);
        $this->fixtures->merchant->create(['id' => '90000merchant4']);

        $this->startTest();
    }

    public function testUpdateMerchantSlaForOnHoldPayoutsSuccess()
    {
        $this->ba->adminAuth();

        $this->fixtures->merchant->create(['id' => '90000merchant1']);
        $this->fixtures->merchant->create(['id' => '90000merchant2']);
        $this->fixtures->merchant->create(['id' => '90000merchant3']);
        $this->fixtures->merchant->create(['id' => '90000merchant4']);

        $this->startTest();

        $merchantSlas = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::RX_ON_HOLD_PAYOUTS_MERCHANT_SLA]);

        $expectedSlas        = array(
            '90000merchant1' => 10,
            '90000merchant2' => 20,
            '90000merchant3' => 10,
            '90000merchant4' => 20,
        );
        $expectedMerchantIds = array_keys($expectedSlas);

        $this->assertArrayKeysExist($merchantSlas, $expectedMerchantIds);

        $this->assertEquals($expectedSlas['90000merchant1'], $merchantSlas['90000merchant1']);
        $this->assertEquals($expectedSlas['90000merchant2'], $merchantSlas['90000merchant2']);
        $this->assertEquals($expectedSlas['90000merchant3'], $merchantSlas['90000merchant3']);
        $this->assertEquals($expectedSlas['90000merchant4'], $merchantSlas['90000merchant4']);

        $settingsEntities        = $this->getDbEntities('settings');
        $merchantIdsFromSettings = [];

        foreach ($settingsEntities as $settingsEntity)
        {
            $settingsData = $settingsEntity->toArray();
            $merchantId   = $settingsData['entity_id'];
            array_push($merchantIdsFromSettings, $merchantId);
            $this->assertEquals($expectedSlas[$merchantId], $settingsData['value']);
            $this->assertEquals(Settings\Module::PAYOUTS, $settingsData['module']);
            $this->assertEquals('merchant', $settingsData['entity_type']);
        }
        $this->assertEqualsCanonicalizing($expectedMerchantIds, $merchantIdsFromSettings);
    }

    public function testUpdateMerchantSlaForOnHoldPayoutsMissingPayload()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateMerchantSlaForOnHoldPayoutsInvalidSla()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateMerchantSlaForOnHoldPayoutsEmptyMerchantIdList()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateMerchantSlaForOnHoldPayoutsInvalidMerchantId()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateMerchantSlaForOnHoldPayoutsNonUniqueMerchantIds()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateMerchantSlaForOnHoldPayoutsUnknownMerchantIds()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testPayoutProcessedEmailNotification()
    {
        Mail::fake();

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->updateFtaAndSource($payout->getId(), Payout\Status::PROCESSED, '933815383814');

        $payout->reload();

        $this->assertEquals('933815383814', $payout->getUtr());

        $this->assertEquals(Payout\Status::PROCESSED, $payout->getStatus());

        Mail::assertQueued(PayoutMail::class, function($mail) {
            $viewData = $mail->viewData;

            $this->assertEquals($mail->originProduct, 'banking');

            $this->assertEquals('2001062', $viewData['txn']['amount']); // raw amount
            $this->assertEquals('20,010.62', amount_format_IN($viewData['txn']['amount'])); // formatted amount

            $payout = $this->getDbLastEntity('payout');

            $this->assertEquals('pout_' . $payout->getId(), $viewData['source']['id']);
            $this->assertEquals($payout->getFailureReason(), $viewData['source']['failure_reason']);

            $expectedData = [
                'txn' => [
                    'entity_id' => $payout->getId(),
                ]
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $this->assertArrayHasKey('created_at_formatted', $viewData['txn']);

            $this->assertEquals('emails.transaction.payout_processed', $mail->view);

            return true;
        });
    }

    public function testCohesiveCreatePayoutWithTdsFailsForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCohesiveCreatePayoutWithTdsPayoutToBeQueuedFailsForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCohesiveCreatePayoutWithoutTdsPayoutToBeQueuedSuccessForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG));

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO));
    }

    public function testCohesiveCreatePayoutWithAttachmentsFailsForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCohesiveCreatePayoutWithoutTdsSuccessForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCohesiveCreatePayoutWithoutAttachmentSuccessForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCohesiveCreateCompositePayoutWithTdsForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCohesiveCreateCompositePayoutWithAttachmentForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCohesiveCreateCompositePayoutWithoutTdsForPrivateAuth()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ALLOW_VA_TO_VA_PAYOUTS]);

        $this->ba->privateAuth();

        $this->startTest();

        $contact = $this->getDbLastEntity('contact');

        $this->assertNotNull($contact);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->assertNotNull($fundAccount);

        $this->assertEquals('contact', $fundAccount->getAttribute('source_type'));

        $this->assertEquals($contact->getAttribute('id'), $fundAccount->getAttribute('source_id'));

        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $this->assertNotNull($payout);

        $this->assertEquals($fundAccount->getAttribute('id'), $payout->getAttribute('fund_account_id'));
    }

    public function testCohesiveCreateCompositePayoutWithTdsPayoutToBeQueuedForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCohesiveCreateCompositePayoutWithoutTdsPayoutToBeQueuedForPrivateAuth()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ALLOW_VA_TO_VA_PAYOUTS]);

        $this->ba->privateAuth();

        $this->startTest();

        $contact = $this->getDbLastEntity('contact');

        $this->assertNotNull($contact);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->assertNotNull($fundAccount);

        $this->assertEquals('contact', $fundAccount->getAttribute('source_type'));

        $this->assertEquals($contact->getAttribute('id'), $fundAccount->getAttribute('source_id'));

        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $this->assertNotNull($payout);

        $this->assertEquals($fundAccount->getAttribute('id'), $payout->getAttribute('fund_account_id'));

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG));

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO));
    }

    protected function prepareTdsCategoriesCache()
    {
        $tdsCategories = [
            [
                'id'   => 1,
                'slab' => 3.75,
            ],
            [
                'id'   => 2,
                'slab' => 4,
            ],
            [
                'id'   => 3,
                'slab' => 4.25,
            ],
        ];

        $this->app['cache']->put('tds_category_id_list', $tdsCategories, 60);
    }

    protected function clearTdsCategoriesCache()
    {
        $this->app['cache']->delete('tds_category_id_list');
    }

    public function testCohesiveCreatePayoutWithTdsSuccessForProxyAuthTdsCategoriesInCache()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->prepareTdsCategoriesCache();

        $this->ba->proxyAuth();

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $this->assertEquals(0, $payoutDetails->getAttribute(PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG));

        $expectedAdditionalInfo = [
            'tds_amount'                           => 1000,
            PayoutsDetails\Entity::SUBTOTAL_AMOUNT => 10000,
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        $this->clearTdsCategoriesCache();
    }

    protected function mockTaxPaymentsGetTdsCategories()
    {
        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tdsCategories = [
            [
                'id'              => 1,
                'name'            => 'Test Category - 1',
                'extern_goi_code' => '6CK',
                'slab'            => 3.75,
            ],
            [
                'id'              => 2,
                'name'            => 'Test Category - 2',
                'extern_goi_code' => '206CA',
                'slab'            => 4,
            ],
            [
                'id'              => 3,
                'name'            => 'Test Category - 3',
                'extern_goi_code' => '94F',
                'slab'            => 4.25,
            ],
        ];

        $tpMock->shouldReceive('getTdsCategories')->andReturn($tdsCategories);

        $this->app['tax-payments'] = $tpMock;
    }

    public function testCohesiveCreatePayoutWithTdsSuccessForProxyAuthTdsCategoriesNotInCache()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCohesiveCreatePayoutWithTdsSuccessForProxyAuthTdsCategoriesInCache'];

        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->mockTaxPaymentsGetTdsCategories();

        $this->ba->proxyAuth();

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $this->assertEquals(0, $payoutDetails->getAttribute(PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG));

        $expectedAdditionalInfo = [
            'tds_amount'                           => 1000,
            PayoutsDetails\Entity::SUBTOTAL_AMOUNT => 10000,
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        $this->clearTdsCategoriesCache();
    }

    public function testCohesiveCreatePayoutWithAttachmentSuccessForProxyAuth()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->proxyAuth();

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $expectedAdditionalInfo = [
            PayoutsDetails\Entity::ATTACHMENTS_KEY => [
                [
                    PayoutsDetails\Entity::ATTACHMENTS_FILE_ID   => 'file_testing',
                    PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME => 'not-your-attachment.pdf'
                ],
            ],
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));
    }

    public function testCohesiveCreatePayoutWithoutTdsSuccessForProxyAuth()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->proxyAuth();

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNull($payoutDetails);
    }

    public function testCohesiveCreatePayoutWithTdsMissingTdsAmountForProxyAuth()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCohesiveCreatePayoutWithTdsMissingTdsCategoryIdForProxyAuth()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCohesiveCreatePayoutWithTdsTdsAmountMoreThanPayoutAmountForProxyAuth()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->mockTaxPaymentsGetTdsCategories();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCohesiveCreatePayoutWithTdsIncorrectTdsCategoryIdForProxyAuth()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->mockTaxPaymentsGetTdsCategories();

        $this->ba->proxyAuth();

        $this->startTest();

        $this->clearTdsCategoriesCache();
    }

    public function testCohesiveCreatePayoutWithTdsPayoutToBeQueuedForProxyAuth()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->mockTaxPaymentsGetTdsCategories();

        $this->ba->proxyAuth();

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG));

        $expectedAdditionalInfo = [
            'tds_amount'                           => 1000,
            PayoutsDetails\Entity::SUBTOTAL_AMOUNT => 10000,
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        $this->clearTdsCategoriesCache();
    }

    public function testCohesiveCreatePayoutWithoutTdsPayoutToBeQueuedForProxyAuth()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '200000000']);

        $this->ba->proxyAuth();

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG));

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO));
    }

    public function testCohesiveCreatePayoutWithTdsIncorrectTdsCategoryIdForInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->mockTaxPaymentsGetTdsCategories();

        $this->startTest();

        $this->clearTdsCategoriesCache();
    }

    public function testCohesiveCreatePayoutWithTdsMissingTdsCategoryIdForInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->mockTaxPaymentsGetTdsCategories();

        $this->startTest();

        $this->clearTdsCategoriesCache();
    }

    public function testCohesiveCreatePayoutWithTdsMissingTdsAmountForInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->mockTaxPaymentsGetTdsCategories();

        $this->startTest();

        $this->clearTdsCategoriesCache();
    }

    public function testCohesiveCreatePayoutWithTdsTdsAmountMoreThanPayoutAmountForInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->mockTaxPaymentsGetTdsCategories();

        $this->startTest();

        $this->clearTdsCategoriesCache();
    }

    public function testCohesiveCreatePayoutWithTdsSuccessForInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->prepareTdsCategoriesCache();

        $this->startTest();

        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals($payout->getId(), $payoutDetails->getPayoutId());

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $this->assertEquals(0, $payoutDetails->getAttribute(PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG));

        $expectedAdditionalInfo = [
            'tds_amount'                           => 1000,
            PayoutsDetails\Entity::SUBTOTAL_AMOUNT => 10000,
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        /** @var PayoutSourceEntity $payoutSource */
        $payoutSource = $this->getDbLastEntity(Constants\Entity::PAYOUT_SOURCE);

        $this->assertEquals('100000000000sa', $payoutSource->getSourceId());

        $this->assertEquals(PayoutSourceEntity::PAYOUT_LINK, $payoutSource->getSourceType());

        $this->assertEquals(1, $payoutSource->getPriority());

        $this->assertEquals($payout->getId(), $payoutSource->getPayoutId());

        $this->clearTdsCategoriesCache();
    }

    public function testCohesiveCreatePayoutWithAttachmentSuccessForInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();

        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $expectedAdditionalInfo = [
            PayoutsDetails\Entity::ATTACHMENTS_KEY => [
                [
                    PayoutsDetails\Entity::ATTACHMENTS_FILE_ID   => 'file_testing',
                    PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME => 'not-your-attachment.pdf'
                ],
            ],
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        /** @var PayoutSourceEntity $payoutSource */
        $payoutSource = $this->getDbLastEntity(Constants\Entity::PAYOUT_SOURCE);

        $this->assertEquals('100000000000sa', $payoutSource->getSourceId());

        $this->assertEquals(PayoutSourceEntity::PAYOUT_LINK, $payoutSource->getSourceType());

        $this->assertEquals(1, $payoutSource->getPriority());

        $this->assertEquals($payout->getId(), $payoutSource->getPayoutId());
    }

    public function testCohesiveCreatePayoutWithoutTdsSuccessForInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();

        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNull($payoutDetails);

        /** @var PayoutSourceEntity $payoutSource */
        $payoutSource = $this->getDbLastEntity(Constants\Entity::PAYOUT_SOURCE);

        $this->assertEquals('100000000000sa', $payoutSource->getSourceId());

        $this->assertEquals(PayoutSourceEntity::PAYOUT_LINK, $payoutSource->getSourceType());

        $this->assertEquals(1, $payoutSource->getPriority());

        $this->assertEquals($payout->getId(), $payoutSource->getPayoutId());
    }

    public function testCohesiveCreatePayoutWithTdsPayoutToBeQueuedForInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->prepareTdsCategoriesCache();

        $this->startTest();

        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals($payout->getId(), $payoutDetails->getAttribute(PayoutsDetails\Entity::PAYOUT_ID));

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG));

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $expectedAdditionalInfo = [
            PayoutsDetails\Entity::TDS_AMOUNT_KEY  => 1000,
            PayoutsDetails\Entity::SUBTOTAL_AMOUNT => 10000,
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        /** @var PayoutSourceEntity $payoutSource */
        $payoutSource = $this->getDbLastEntity(Constants\Entity::PAYOUT_SOURCE);

        $this->assertEquals('100000000000sa', $payoutSource->getSourceId());

        $this->assertEquals(PayoutSourceEntity::PAYOUT_LINK, $payoutSource->getSourceType());

        $this->assertEquals(1, $payoutSource->getPriority());

        $this->assertEquals($payout->getId(), $payoutSource->getPayoutId());

        $this->clearTdsCategoriesCache();
    }

    public function testCohesiveCreatePayoutWithoutTdsPayoutToBeQueuedForInternalAuth()
    {
        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->startTest();

        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals($payout->getId(), $payoutDetails->getAttribute(PayoutsDetails\Entity::PAYOUT_ID));

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG));

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO));

        /** @var PayoutSourceEntity $payoutSource */
        $payoutSource = $this->getDbLastEntity(Constants\Entity::PAYOUT_SOURCE);

        $this->assertEquals('100000000000sa', $payoutSource->getSourceId());

        $this->assertEquals(PayoutSourceEntity::PAYOUT_LINK, $payoutSource->getSourceType());

        $this->assertEquals(1, $payoutSource->getPriority());

        $this->assertEquals($payout->getId(), $payoutSource->getPayoutId());
    }

    protected function createPayoutWithTds()
    {
        $this->testCohesiveCreatePayoutWithTdsSuccessForProxyAuthTdsCategoriesInCache();
    }

    protected function createPayoutWithAttachments()
    {
        $this->testCohesiveCreatePayoutWithAttachmentSuccessForProxyAuth();
    }

    protected function createPayoutWithoutTds()
    {
        $this->testCohesiveCreatePayoutWithoutTdsSuccessForProxyAuth();
    }

    public function testCohesiveFetchPayoutByIdForPayoutWithTdsForProxyAuth()
    {
        $this->createPayoutWithTds();

        // validating that the payout got created with TDS
        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertEquals($payout->getId(), $payoutDetails->getPayoutId());

        $this->assertNotNull($payoutDetails);

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $expectedAdditionalInfo = [
            PayoutsDetails\Entity::TDS_AMOUNT_KEY  => 1000,
            PayoutsDetails\Entity::SUBTOTAL_AMOUNT => 10000,
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        // fetching by ID
        $request = array(
            'url'    => '/payouts/pout_' . $payout['id'],
            'method' => 'GET');

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        // validating that the response for fetch-by-id has TDS details
        $this->assertArrayHasKey(Payout\Entity::META, $content);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $content[Payout\Entity::META]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::CATEGORY_ID, $content[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertEquals(1, $content[Payout\Entity::META][PayoutsDetails\Entity::TDS][PayoutsDetails\Entity::CATEGORY_ID]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS_AMOUNT, $content[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertEquals(1000, $content[Payout\Entity::META][PayoutsDetails\Entity::TDS][PayoutsDetails\Entity::TDS_AMOUNT]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $content[Payout\Entity::META]);

        $this->assertEquals(10000, $content[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);
    }

    public function testCohesiveFetchPayoutByIdForPayoutWithAttachmentsForProxyAuth()
    {
        $this->createPayoutWithAttachments();

        // validating that the payout got created with attachments
        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals($payout->getId(), $payoutDetails->getPayoutId());

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $expectedAdditionalInfo = [
            PayoutsDetails\Entity::ATTACHMENTS_KEY => [
                [
                    PayoutsDetails\Entity::ATTACHMENTS_FILE_ID   => 'file_testing',
                    PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME => 'not-your-attachment.pdf'
                ],
            ],
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        // fetching by ID
        $request = array(
            'url'    => '/payouts/pout_' . $payout['id'],
            'method' => 'GET');

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        // validating that the response for fetch-by-id has Attachments
        $this->assertArrayHasKey(Payout\Entity::META, $content);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $content[Payout\Entity::META]);

        $this->assertNull($content[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TAX_PAYMENT_ID, $content[Payout\Entity::META]);

        $this->assertNull($content[Payout\Entity::META][PayoutsDetails\Entity::TAX_PAYMENT_ID]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $content[Payout\Entity::META]);

        $this->assertNull($content[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_KEY, $content[Payout\Entity::META]);

        $this->assertEquals($expectedAdditionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY], $content[Payout\Entity::META][PayoutsDetails\Entity::ATTACHMENTS_KEY]);
    }

    public function testCohesiveFetchPayoutByIdForPayoutWithoutTdsForProxyAuth()
    {
        $this->createPayoutWithoutTds();

        // validating that the payout got created without TDS
        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNull($payoutDetails);

        // fetching by ID
        $request = array(
            'url'    => '/payouts/pout_' . $payout['id'],
            'method' => 'GET');

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        // validating that the response for fetch-by-id has null TDS details
        $this->assertArrayHasKey(Payout\Entity::META, $content);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $content[Payout\Entity::META]);

        $this->assertNull($content[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $content[Payout\Entity::META]);

        $this->assertNull($content[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);
    }

    public function testCohesiveFetchPayoutByIdForPayoutWithoutAttachmentsForProxyAuth()
    {
        $this->createPayoutWithoutTds();

        // validating that the payout got created without TDS
        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNull($payoutDetails);

        // fetching by ID
        $request = array(
            'url'    => '/payouts/pout_' . $payout['id'],
            'method' => 'GET');

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        // validating that the response for fetch-by-id has null TDS details
        $this->assertArrayHasKey(Payout\Entity::META, $content);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $content[Payout\Entity::META]);

        $this->assertNull($content[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $content[Payout\Entity::META]);

        $this->assertNull($content[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);

        // validating that the response for fetch-by-id has empty attachments
        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_KEY, $content[Payout\Entity::META]);

        $this->assertEmpty($content[Payout\Entity::META][PayoutsDetails\Entity::ATTACHMENTS_KEY]);
    }

    public function testCohesiveFetchPayoutByIdForPayoutWithTdsForPrivateAuth()
    {
        $this->createPayoutWithTds();

        // validating that the payout got created with TDS
        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertEquals($payout->getId(), $payoutDetails->getPayoutId());

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $expectedAdditionalInfo = [
            'tds_amount'                           => 1000,
            PayoutsDetails\Entity::SUBTOTAL_AMOUNT => 10000,
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        // fetching by ID
        $request = array(
            'url'    => '/payouts/pout_' . $payout['id'],
            'method' => 'GET');

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        // validating that the response for fetch-by-id does not have meta attribute
        $this->assertArrayNotHasKey(Payout\Entity::META, $content);
    }

    public function testCohesiveFetchPayoutByIdForPayoutWithAttachmentsForPrivateAuth()
    {
        $this->createPayoutWithAttachments();

        // validating that the payout got created with TDS
        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertEquals($payout->getId(), $payoutDetails->getPayoutId());

        $this->assertNull($payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $expectedAdditionalInfo = [
            PayoutsDetails\Entity::ATTACHMENTS_KEY => [
                [
                    PayoutsDetails\Entity::ATTACHMENTS_FILE_ID   => 'file_testing',
                    PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME => 'not-your-attachment.pdf'
                ],
            ],
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        // fetching by ID
        $request = array(
            'url'    => '/payouts/pout_' . $payout['id'],
            'method' => 'GET');

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        // validating that the response for fetch-by-id does not have meta attribute
        $this->assertArrayNotHasKey(Payout\Entity::META, $content);
    }

    public function testCohesiveFetchPayoutByIdForPayoutWithoutTdsForPrivateAuth()
    {
        $this->createPayoutWithoutTds();

        /** @var PayoutEntity $payout */
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNull($payoutDetails);

        // fetching by ID
        $request = array(
            'url'    => '/payouts/pout_' . $payout['id'],
            'method' => 'GET');

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        // validating that the response for fetch-by-id does not have meta attribute
        $this->assertArrayNotHasKey(Payout\Entity::META, $content);
    }

    public function testCohesiveFetchPayoutByIdForPayoutWithTdsForInternalAppAuth()
    {
        $this->createPayoutWithTds();

        // validating that the payout got created with TDS
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertEquals($payout->getId(), $payoutDetails->getPayoutId());

        $this->assertEquals(1, $payoutDetails->getAttribute(PayoutsDetails\Entity::TDS_CATEGORY_ID));

        $expectedAdditionalInfo = [
            PayoutsDetails\Entity::TDS_AMOUNT_KEY  => 1000,
            PayoutsDetails\Entity::SUBTOTAL_AMOUNT => 10000,
        ];

        $this->assertEquals($expectedAdditionalInfo, json_decode($payoutDetails->getAttribute(PayoutsDetails\Entity::ADDITIONAL_INFO), true));

        // fetching by ID
        $request = array(
            'url'    => '/payouts_internal/pout_' . $payout['id'],
            'method' => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]);

        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $content = $this->makeRequestAndGetContent($request);

        // validating that the response for fetch-by-id has TDS details
        $this->assertArrayHasKey(Payout\Entity::META, $content);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $content[Payout\Entity::META]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::CATEGORY_ID, $content[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertEquals(1, $content[Payout\Entity::META][PayoutsDetails\Entity::TDS][PayoutsDetails\Entity::CATEGORY_ID]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS_AMOUNT, $content[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertEquals(1000, $content[Payout\Entity::META][PayoutsDetails\Entity::TDS][PayoutsDetails\Entity::TDS_AMOUNT]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $content[Payout\Entity::META]);

        $this->assertEquals(10000, $content[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);
    }

    public function testCohesiveFetchPayoutByIdForPayoutWithoutTdsForInternalAppAuth()
    {
        $this->createPayoutWithoutTds();

        // validating that the payout got created with TDS
        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNull($payoutDetails);

        // fetching by ID
        $request = array(
            'url'    => '/payouts_internal/pout_' . $payout['id'],
            'method' => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]);

        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $content = $this->makeRequestAndGetContent($request);

        // validating that the response for fetch-by-id has TDS details
        $this->assertArrayHasKey(Payout\Entity::META, $content);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $content[Payout\Entity::META]);

        $this->assertNull($content[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $content[Payout\Entity::META]);

        $this->assertNull($content[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);
    }

    public function testCohesiveFetchMultiplePayoutsForPayoutWithTdsForProxyAuth()
    {
        $this->createPayoutWithTds();

        $payoutWithTds = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        // fetching multiple payouts
        $request = array(
            'url'    => '/payouts?fund_account_id=fa_100000000000fa&account_number=2224440041626905',
            'method' => 'GET');

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $content['count']);

        $fetchedPayout = $content['items'][0];

        $this->assertEquals($payoutWithTds->getPublicId(), $fetchedPayout['id']);

        $this->assertArrayHasKey(Payout\Entity::META, $fetchedPayout);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $fetchedPayout[Payout\Entity::META]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::CATEGORY_ID, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertEquals(1, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS][PayoutsDetails\Entity::CATEGORY_ID]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS_AMOUNT, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertEquals(1000, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS][PayoutsDetails\Entity::TDS_AMOUNT]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $fetchedPayout[Payout\Entity::META]);

        $this->assertEquals(10000, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);
    }

    public function testCohesiveFetchMultiplePayoutsForPayoutWithoutTdsForProxyAuth()
    {
        $this->createPayoutWithoutTds();

        $payoutWithTds = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        // fetching multiple payouts
        $request = array(
            'url'    => '/payouts?fund_account_id=fa_100000000000fa&account_number=2224440041626905',
            'method' => 'GET');

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $content['count']);

        $fetchedPayout = $content['items'][0];

        $this->assertEquals($payoutWithTds->getPublicId(), $fetchedPayout['id']);

        $this->assertArrayHasKey(Payout\Entity::META, $fetchedPayout);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $fetchedPayout[Payout\Entity::META]);

        $this->assertNull($fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $fetchedPayout[Payout\Entity::META]);

        $this->assertNull($fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);
    }

    public function testCohesiveFetchMultiplePayoutsForPayoutWithTdsForPrivateAuth()
    {
        $this->createPayoutWithTds();

        $payoutWithTds = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        // fetching multiple payouts
        $request = array(
            'url'    => '/payouts?fund_account_id=fa_100000000000fa&account_number=2224440041626905',
            'method' => 'GET');

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $content['count']);

        $fetchedPayout = $content['items'][0];

        $this->assertEquals($payoutWithTds->getPublicId(), $fetchedPayout['id']);

        $this->assertArrayNotHasKey(Payout\Entity::META, $fetchedPayout);
    }

    public function testCohesiveFetchMultiplePayoutsForPayoutWithoutTdsForPrivateAuth()
    {
        $this->createPayoutWithoutTds();

        $payoutWithTds = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        // fetching multiple payouts
        $request = array(
            'url'    => '/payouts?fund_account_id=fa_100000000000fa&account_number=2224440041626905',
            'method' => 'GET');

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $content['count']);

        $fetchedPayout = $content['items'][0];

        $this->assertEquals($payoutWithTds->getPublicId(), $fetchedPayout['id']);

        $this->assertArrayNotHasKey(Payout\Entity::META, $fetchedPayout);
    }

    public function testCohesiveFetchMultiplePayoutsForPayoutWithTdsForInternalAuth()
    {
        $this->createPayoutWithTds();

        $payoutWithTds = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        // fetching multiple payouts
        $request = array(
            'url'    => '/payouts_internal?fund_account_id=fa_100000000000fa&account_number=2224440041626905',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]);

        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $content['count']);

        $fetchedPayout = $content['items'][0];

        $this->assertEquals($payoutWithTds->getPublicId(), $fetchedPayout['id']);

        $this->assertArrayHasKey(Payout\Entity::META, $fetchedPayout);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $fetchedPayout[Payout\Entity::META]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::CATEGORY_ID, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertEquals(1, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS][PayoutsDetails\Entity::CATEGORY_ID]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS_AMOUNT, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertEquals(1000, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS][PayoutsDetails\Entity::TDS_AMOUNT]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $fetchedPayout[Payout\Entity::META]);

        $this->assertEquals(10000, $fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);
    }

    public function testCohesiveFetchMultiplePayoutsForPayoutWithoutTdsForInternalAuth()
    {
        $this->createPayoutWithoutTds();

        $payoutWithTds = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        // fetching multiple payouts
        $request = array(
            'url'    => '/payouts_internal?fund_account_id=fa_100000000000fa&account_number=2224440041626905',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]);

        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $content['count']);

        $fetchedPayout = $content['items'][0];

        $this->assertEquals($payoutWithTds->getPublicId(), $fetchedPayout['id']);

        $this->assertArrayHasKey(Payout\Entity::META, $fetchedPayout);

        $this->assertArrayHasKey(PayoutsDetails\Entity::TDS, $fetchedPayout[Payout\Entity::META]);

        $this->assertNull($fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::TDS]);

        $this->assertArrayHasKey(PayoutsDetails\Entity::SUBTOTAL_AMOUNT, $fetchedPayout[Payout\Entity::META]);

        $this->assertNull($fetchedPayout[Payout\Entity::META][PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);
    }

    public function testCohesiveFetchMultiplePayoutsWithTdsCategoryIdFilterForProxyAuth()
    {
        $this->createPayoutWithTds();

        $payoutWithTds = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        // fetching multiple payouts
        $request = array(
            'url'    => '/payouts?tds_category_id=1&account_number=2224440041626905',
            'method' => 'GET');

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $content['count']);

        $fetchedPayout = $content['items'][0];

        $this->assertEquals($payoutWithTds->getPublicId(), $fetchedPayout['id']);
    }

    public function testCohesiveFetchMultiplePayoutsWithTdsCategoryIdFilterForPrivateAuth()
    {
        $this->createPayoutWithTds();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCohesiveFetchMultiplePayoutsWithTaxPaymentIdFilterForProxyAuth()
    {
        $this->createPayoutWithTds();

        $payoutWithTds = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetailsCreated = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertEquals($payoutWithTds->getId(), $payoutDetailsCreated[PayoutsDetails\Entity::PAYOUT_ID]);

        $this->fixtures->edit(Constants\Entity::PAYOUTS_DETAILS, $payoutDetailsCreated[PayoutsDetails\Entity::PAYOUT_ID], [PayoutsDetails\Entity::TAX_PAYMENT_ID => '1234']);

        // fetching multiple payouts
        $request = array(
            'url'    => '/payouts?tax_payment_id=txpy_1234&account_number=2224440041626905',
            'method' => 'GET');

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $content['count']);

        $fetchedPayout = $content['items'][0];

        $this->assertEquals($payoutWithTds->getPublicId(), $fetchedPayout['id']);
    }

    public function testCohesiveFetchMultiplePayoutsWithIncorrectTaxPaymentPublicIdFilterForProxyAuth()
    {
        $this->createPayoutWithTds();

        $payoutWithTds = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetailsCreated = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertEquals($payoutWithTds->getId(), $payoutDetailsCreated[PayoutsDetails\Entity::PAYOUT_ID]);

        $this->fixtures->edit(Constants\Entity::PAYOUTS_DETAILS, $payoutDetailsCreated[PayoutsDetails\Entity::PAYOUT_ID], [PayoutsDetails\Entity::TAX_PAYMENT_ID => '1234']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPayoutProcessFailureInFtsStatusUpdate()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout['id'];

        $this->ba->ftsAuth();

        $this->startTest();
    }

    public function testUpdateAttachmentWithProxyAuth()
    {
        $this->testCohesiveCreatePayoutWithoutTdsSuccessForProxyAuth();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNull($payoutDetails);

        $payouts = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $this->testData[__FUNCTION__]['request']['url'] = sprintf('/payouts/%s/attachments', $payouts->getPublicId());

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertNull($payoutDetails[PayoutsDetails\Entity::TDS_CATEGORY_ID]);

        $this->assertNull($payoutDetails[PayoutsDetails\Entity::TAX_PAYMENT_ID]);

        $this->assertEquals(0, $payoutDetails[PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO]);

        $additionalInfo = json_decode($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO], true);

        $this->assertNotNull($additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY]);

        $this->assertEquals('file_JLYYnaOtQ0Xgzt', $additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_ID]);

        $this->assertEquals('new file.pdf', $additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }

    public function testUpdateAttachmentWithTds()
    {
        $this->testCohesiveCreatePayoutWithTdsPayoutToBeQueuedForProxyAuth();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertTrue($payoutDetails[PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::TDS_CATEGORY_ID]);

        $this->assertNull($payoutDetails[PayoutsDetails\Entity::TAX_PAYMENT_ID]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO]);

        $additionalInfo = json_decode($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO], true);

        $this->assertEquals(1000, $additionalInfo[PayoutsDetails\Entity::TDS_AMOUNT_KEY]);

        $this->assertEquals(10000, $additionalInfo[PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);

        $payouts = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $this->testData[__FUNCTION__]['request']['url'] = sprintf('/payouts/%s/attachments', $payouts->getPublicId());

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::TDS_CATEGORY_ID]);

        $this->assertNull($payoutDetails[PayoutsDetails\Entity::TAX_PAYMENT_ID]);

        $this->assertTrue($payoutDetails[PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO]);

        $additionalInfo = json_decode($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO], true);

        $this->assertNotNull($additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY]);

        $this->assertEquals('file_JLYYnaOtQ0Xgzt', $additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_ID]);

        $this->assertEquals('new file.pdf', $additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }

    public function testUpdateAttachmentForPayoutLink()
    {
        $this->testCohesiveCreatePayoutWithTdsSuccessForInternalAuth();

        $payout = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertFalse($payoutDetails[PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::TDS_CATEGORY_ID]);

        $this->assertNull($payoutDetails[PayoutsDetails\Entity::TAX_PAYMENT_ID]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO]);

        $additionalInfo = json_decode($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO], true);

        $this->assertNotNull($additionalInfo[PayoutsDetails\Entity::TDS_AMOUNT_KEY]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::SUBTOTAL_AMOUNT]);

        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->testData[__FUNCTION__]['request']['content'] = [
            'payout_ids'     => [
                $payout->getId()
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $this->startTest();

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertFalse($payoutDetails[PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::TDS_CATEGORY_ID]);

        $this->assertNull($payoutDetails[PayoutsDetails\Entity::TAX_PAYMENT_ID]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO]);

        $additionalInfo = json_decode($payoutDetails[PayoutsDetails\Entity::ADDITIONAL_INFO], true);

        $this->assertNotNull($additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY]);

        $this->assertEquals('file_123456', $additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_ID]);

        $this->assertEquals('file_name.pdf', $additionalInfo[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }

    public function testUpdateAttachmentPayoutLinkWithoutPayoutDetails()
    {
        $this->testCohesiveCreatePayoutWithoutTdsSuccessForInternalAuth();

        $payout2 = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNull($payoutDetails);

        $this->testCohesiveCreatePayoutWithTdsSuccessForInternalAuth();

        $payout1 = $this->getDbLastEntity(Constants\Entity::PAYOUT);

        $payoutDetails = $this->getDbLastEntity(Constants\Entity::PAYOUTS_DETAILS);

        $this->assertNotNull($payoutDetails);

        $this->assertFalse($payoutDetails[PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG]);

        $this->assertNotNull($payoutDetails[PayoutsDetails\Entity::TDS_CATEGORY_ID]);

        $this->assertNull($payoutDetails[PayoutsDetails\Entity::TAX_PAYMENT_ID]);

        $this->ba->appAuthTest($this->config['applications.payout_links.secret']);

        $this->testData[__FUNCTION__]['request']['content'] = [
            'payout_ids'     => [
                $payout1->getId(),
                $payout2->getId(),
            ],
            'update_request' => [
                'attachments' => [
                    [
                        'file_id'   => 'file_123456',
                        'file_name' => 'file_name.pdf'
                    ]
                ]
            ]
        ];

        $this->startTest();

        $payoutDetails1 = $this->getDbEntity(Constants\Entity::PAYOUTS_DETAILS, [PayoutsDetails\Entity::PAYOUT_ID => $payout1->getId()]);

        $payoutDetails2 = $this->getDbEntity(Constants\Entity::PAYOUTS_DETAILS, [PayoutsDetails\Entity::PAYOUT_ID => $payout2->getId()]);

        $this->assertNotNull($payoutDetails1);
        $this->assertNotNull($payoutDetails2);

        $this->assertEquals(0, $payoutDetails1[PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG]);
        $this->assertEquals(0, $payoutDetails2[PayoutsDetails\Entity::QUEUE_IF_LOW_BALANCE_FLAG]);

        $this->assertNotNull($payoutDetails1[PayoutsDetails\Entity::TDS_CATEGORY_ID]);
        $this->assertNull($payoutDetails2[PayoutsDetails\Entity::TDS_CATEGORY_ID]);

        $this->assertNull($payoutDetails1[PayoutsDetails\Entity::TAX_PAYMENT_ID]);
        $this->assertNull($payoutDetails2[PayoutsDetails\Entity::TAX_PAYMENT_ID]);

        $this->assertNotNull($payoutDetails1[PayoutsDetails\Entity::ADDITIONAL_INFO]);
        $this->assertNotNull($payoutDetails2[PayoutsDetails\Entity::ADDITIONAL_INFO]);

        $additionalInfo1 = json_decode($payoutDetails1[PayoutsDetails\Entity::ADDITIONAL_INFO], true);
        $additionalInfo2 = json_decode($payoutDetails2[PayoutsDetails\Entity::ADDITIONAL_INFO], true);

        $this->assertNotNull($additionalInfo1[PayoutsDetails\Entity::ATTACHMENTS_KEY]);
        $this->assertNotNull($additionalInfo2[PayoutsDetails\Entity::ATTACHMENTS_KEY]);

        $this->assertEquals('file_123456', $additionalInfo1[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_ID]);
        $this->assertEquals('file_123456', $additionalInfo2[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_ID]);

        $this->assertEquals('file_name.pdf', $additionalInfo1[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
        $this->assertEquals('file_name.pdf', $additionalInfo2[PayoutsDetails\Entity::ATTACHMENTS_KEY][0][PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }

    public function testUploadAttachmentOnPayout()
    {
        $this->ba->proxyAuth();

        $fileName = 'k.png';

        $localFilePath = __DIR__ . '/../Storage/k.png';

        $request = $this->createUploadFileRequest($fileName, $localFilePath);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_ID, $response);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME, $response);

        $this->assertEquals($fileName, $response[PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }

    public function testUploadAttachmentOnPayoutWithSpecialCharacters()
    {
        $this->ba->proxyAuth();

        $fileName = '%&%^#&eghj89.png';

        $localFilePath = $this->createNewFile($fileName);

        $request = $this->createUploadFileRequest($fileName, $localFilePath);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_ID, $response);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME, $response);

        $this->assertEquals($fileName, $response[PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }

    public function testUploadAttachmentOnPayoutWithSpecialCharactersAndSpaces()
    {
        $this->ba->proxyAuth();

        $fileName = ' %&%^&eghj89 .png';

        $localFilePath = $this->createNewFile($fileName);

        $request = $this->createUploadFileRequest($fileName, $localFilePath);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_ID, $response);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME, $response);

        $this->assertEquals($fileName, $response[PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }

    public function testUploadAttachmentOnPayoutWithEmojis()
    {
        $this->ba->proxyAuth();

        $fileName = '😓😓😓😓.png';

        $localFilePath = $this->createNewFile($fileName);

        $request = $this->createUploadFileRequest($fileName, $localFilePath);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_ID, $response);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME, $response);

        $this->assertEquals($fileName, $response[PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }

    public function testUploadAttachmentOnPayoutWithMultipleWords()
    {
        $this->ba->proxyAuth();

        $fileName = 'invoice file.png';

        $localFilePath = $this->createNewFile($fileName);

        $request = $this->createUploadFileRequest($fileName, $localFilePath);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_ID, $response);

        $this->assertArrayHasKey(PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME, $response);

        $this->assertEquals($fileName, $response[PayoutsDetails\Entity::ATTACHMENTS_FILE_NAME]);
    }
}
