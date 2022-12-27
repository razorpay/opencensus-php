<?php

namespace RZP\Tests\Functional\FundTransfer;

use Queue;
use Redis;
use Mockery;
use Carbon\Carbon;

use RZP\Jobs\BeamJob;
use RZP\Models\Payout;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Account;
use RZP\Models\FundTransfer\Mode;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class AttemptTest extends TestCase
{
    use AttemptTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/AttemptTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function testSettlementFileCreationIcici()
    {
        Queue::fake();

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::ICICI, 1, Attempt\Type::SETTLEMENT);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);
    }

    public function testSettlementFileCreationKotak()
    {
        $this->markTestSkipped('Kotak is not used anymore');

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::KOTAK, 1, Attempt\Type::SETTLEMENT);
    }

    public function testSettlementFileCreationAxis()
    {
        Queue::fake();

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::AXIS, 1, Attempt\Type::SETTLEMENT);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);
    }

    public function testInitiateAtCheckDuringFileCreation()
    {
        $channel = Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createPaymentEntities(2);

        $this->initiateSettlements($channel);

        $purpose = Attempt\Purpose::SETTLEMENT;

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $initiate_at = $fta[Attempt\Entity::INITIATE_AT];

        // Set current time to a value before initiate_at
        $beforeInitiateTime = Carbon::createFromTimestamp($initiate_at, Timezone::IST)->subDay()->hour(10);

        Carbon::setTestNow($beforeInitiateTime);

        $content = $this->initiateTransfer($channel, $purpose, Attempt\Type::SETTLEMENT);

        // Check that no attempts were picked up
        $this->assertEquals(0, $content[$channel]['count']);
        $this->assertEquals('No Attempts to process', $content[$channel]['message']);

        // Set initiate_at to a value post the value in the column
        $postInitiateAt = Carbon::createFromTimestamp($initiate_at, Timezone::IST)->addSecond()->hour(10);

        Carbon::setTestNow($postInitiateAt);

        // Verify that attempts were picked up
        $content = $this->initiateTransfer($channel, $purpose, Attempt\Type::SETTLEMENT);

        $this->assertEquals(1, $content[$channel]['count']);
    }

    public function testPayoutFileCreationAxisSuccess()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::AXIS, 1, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationAxisFail()
    {
        $this->markTestSkipped('test mode overrides transfer time check');

        $channel = Channel::AXIS;

        $purpose = Attempt\Purpose::SETTLEMENT;

        $now = Carbon::create(2018, 8, 14, 6, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $payout = $this->fixtures->create(
            'payout',
            [
                'channel' => $channel,
                'amount' => 1000,
            ]);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $payout->getId(),
                'bank_account_id'           => $payout->getDestinationId(),
                'merchant_id'               => $payout->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::PAYOUT,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]
        );

        $content = $this->initiateTransfer($channel, $purpose, Attempt\Type::PAYOUT,false);

        $this->assertEquals($channel, $content['channel']);
        $this->assertEquals(0, $content['count']);
        $this->assertEquals('Invalid time to initiate transfer', $content['message']);
    }

    public function testPayoutFileCreationIciciSuccess()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::ICICI, 1, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationIciciFail()
    {
        $this->markTestSkipped('test mode overrides transfer time check');

        $channel = Channel::ICICI;

        $purpose = Attempt\Purpose::SETTLEMENT;

        $now = Carbon::create(2018, 8, 15, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $payout = $this->fixtures->create(
            'payout',
            [
                'channel' => $channel,
                'amount' => 1000,
            ]);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $payout->getId(),
                'bank_account_id'           => $payout->getDestinationId(),
                'merchant_id'               => $payout->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::PAYOUT,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]
        );

        $content = $this->initiateTransfer($channel, $purpose, Attempt\Type::PAYOUT, false);

        $this->assertEquals($channel, $content['channel']);
        $this->assertEquals(0, $content['count']);
        $this->assertEquals('Invalid time to initiate transfer', $content['message']);
    }

    public function testPayoutFileCreationYesbankImps()
    {
        // Yesbank is now using api based channels.
        // Hence, removing testcase
        $this->markTestSkipped();

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::YESBANK, 2, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationYesbankRtgsSuccess()
    {
        // Yesbank is now using api based channels.
        // Hence, removing testcase
        $this->markTestSkipped();

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::YESBANK, 2, Attempt\Type::PAYOUT);
    }

    public function testPayoutFileCreationYesbankRtgsFailed()
    {
        // Yesbank is now using api based channels.
        // Hence, removing testcase

        $this->markTestSkipped();

        $channel = Channel::YESBANK;

        $purpose = Attempt\Purpose::SETTLEMENT;

        $now = Carbon::create(2018, 8, 14, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->fixtures->edit('balance', '10000000000000', ['balance' => 40000000]);

        $payout = $this->fixtures->create(
            'payout',
            [
                'channel' => $channel,
                'amount' => 30000000,
            ]);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $payout->getId(),
                'bank_account_id'           => $payout->getDestinationId(),
                'merchant_id'               => $payout->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::PAYOUT,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]);

        $content = $this->initiateTransfer($channel, $purpose, Attempt\Type::PAYOUT, false);

        $this->assertEquals(1, $content[$channel]['count']);
        $this->assertEquals(0, $content[$channel]['success']);
        $this->assertEquals(1, $content[$channel]['failed']);
    }

    public function testYesbankRefundToCreditCard()
    {
        $channel = Channel::YESBANK;

        $purpose = Attempt\Purpose::REFUND;

        $now = Carbon::create(2018, 8, 14, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->fixtures->edit('balance', '10000000000000', ['balance' => 40000000]);

        $payment = $this->fixtures->create('payment');

        $refund = $this->fixtures->create(
            'refund',
            [
                'payment_id'  => $payment->getId(),
                'merchant_id' => Account::TEST_ACCOUNT,
                'amount'      => $payment->getAmount(),
                'base_amount' => $payment->getAmount(),
                'gateway'     => 'upi_axis',
            ]);

        $card = $this->fixtures->create('card', ['type' => 'credit']);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $refund->getId(),
                'card_id'                   => $card->getId(),
                'merchant_id'               => $refund->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::REFUND,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]);

        $content = $this->initiateTransfer($channel, $purpose, Attempt\Type::REFUND, false);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(1, $content[$channel]['count']);
        $this->assertEquals(1, $content[$channel]['success']);
        $this->assertEquals(0, $content[$channel]['failed']);
        $this->assertEquals(Attempt\Status::PROCESSED, $fta['status']);
    }

    public function testYesbankRefundToInvalidCard()
    {
        $channel = Channel::YESBANK;

        $purpose = Attempt\Purpose::REFUND;

        $now = Carbon::create(2018, 8, 14, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->fixtures->edit('iin',411111, ['type' => 'debit']);

        $card = $this->fixtures->create('card');

        $this->fixtures->edit('balance', '10000000000000', ['balance' => 40000000]);

        $payment = $this->fixtures->create('payment');

        $refund = $this->fixtures->create(
            'refund',
            [
                'payment_id'  => $payment->getId(),
                'merchant_id' => Account::TEST_ACCOUNT,
                'amount'      => $payment->getAmount(),
                'base_amount' => $payment->getAmount(),
                'gateway'     => 'upi_axis',
            ]);

        $this->fixtures->create(
            'fund_transfer_attempt',
            [
                'channel'                   => $channel,
                'source_id'                 => $refund->getId(),
                'card_id'                   => $card->getId(),
                'merchant_id'               => $refund->getMerchantId(),
                'purpose'                   => $purpose,
                'status'                    => Attempt\Status::CREATED,
                'source_type'               => Attempt\Type::REFUND,
                'initiate_at'               => Carbon::now(Timezone::IST)->getTimestamp(),
            ]);

        $content = $this->initiateTransfer($channel, $purpose, Attempt\Type::REFUND, false);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(1, $content[$channel]['count']);
        $this->assertEquals(0, $content[$channel]['success']);
        $this->assertEquals(1, $content[$channel]['failed']);
        $this->assertEquals(Attempt\Status::CREATED, $fta['status']);
    }

    public function testRblPayoutFailed()
    {
        $now = Carbon::create(2019, 10, 16, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $channel = Channel::RBL;

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(
            false,
            9000000,
            AccountType::DIRECT,
            $channel);

        $this->createContact();

        $this->createFundAccount();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => 200000,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'mode'            => 'NEFT',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'notes'           => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'       => '/payouts',
            'method'    => 'POST',
            'content'   => $content
        ];

        $this->makeRequestAndGetContent($request);

        $payout = $this->getLastEntity('payout', true);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($channel, $payout['channel']);
        $this->assertEquals($channel, $attempt['channel']);
        $this->assertEquals(1, $attempt['is_fts']);
        $this->assertEquals(Payout\Status::PROCESSING, $payout['status']);
        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->fixtures->stripSign($attempt['id']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::FAILED);

        $payout = $this->getLastEntity('payout', true);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);

        $this->assertNull($payout['transaction_id']);
    }

    public function testRblPayoutReversed()
    {
        $now = Carbon::create(2019, 10, 16, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $channel = Channel::RBL;

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(
            false,
            9000000,
            AccountType::DIRECT,
            $channel);

        $this->createContact();

        $this->createFundAccount();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => 200000,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'mode'            => 'NEFT',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'notes'           => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'       => '/payouts',
            'method'    => 'POST',
            'content'   => $content
        ];

        $this->makeRequestAndGetContent($request);

        $payout = $this->getLastEntity('payout', true);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->assertEquals($channel, $payout['channel']);
        $this->assertEquals($channel, $attempt['channel']);
        $this->assertEquals(1, $attempt['is_fts']);
        $this->assertEquals(Payout\Status::PROCESSING, $payout['status']);
        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->fixtures->stripSign($attempt['id']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        //state transition has been added for fta update where initiated to reversed state transition is not allowed.
        //first it will be changed to processed and then into reversed state.
        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $payout = $this->getLastEntity('payout', true);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Payout\Status::REVERSED, $payout['status']);
        $this->assertEquals(Attempt\Status::REVERSED, $attempt['status']);

        $this->assertNull($payout['transaction_id']);
    }

    public function testRblPayoutSuccess()
    {
        $now = Carbon::create(2019, 10, 16, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $channel = Channel::RBL;

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(
            false,
            9000000,
            AccountType::DIRECT,
            $channel);

        $this->createContact();

        $this->createFundAccount();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => 20000,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'mode'            => 'IMPS',
            'notes'           => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'       => '/payouts',
            'method'    => 'POST',
            'content'   => $content
        ];

        $this->makeRequestAndGetContent($request);

        $payout = $this->getLastEntity('payout', true);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->assertEquals($channel, $payout['channel']);
        $this->assertEquals($channel, $attempt['channel']);
        $this->assertEquals(1, $attempt['is_fts']);
        $this->assertEquals(Payout\Status::PROCESSING, $payout['status']);
        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->fixtures->stripSign($attempt['id']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getLastEntity('payout', true);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);
    }

    public function testSettlementFileCreationAxis2()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        Queue::fake();

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createDataAndAssertInitiateTransferSuccess(
            Channel::AXIS2, 1, Attempt\Type::SETTLEMENT);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);

        $fileStore = $this->getEntities("file_store", [
            'type' => 'fund_transfer_default'
        ],true);

        $this->assertEquals("1", $fileStore['count']);
        $this->assertEquals("text/plain", $fileStore['items'][0]['mime']);
        $this->assertEquals("rzp-api-settlement", $fileStore['items'][0]['bucket']);
    }

    public function testFundTransferAttemptForAsyncWhenStateIsTerminal()
    {
        $this->setUpMerchantForBusinessBankingLive(
            false,
            900000000,
            AccountType::SHARED);

        $this->app['rzp.mode'] = EnvMode::LIVE;

        $this->fixtures->on('live')->create('fund_account:bank_account',
            [
                'id'          => 'D6Z9Jfir2egAUT',
                'source_type' => 'contact',
                'source_id'   => 'Dsp92d4N1Mmm6Q',
                'merchant_id' => '10000000000000'
            ]);

        $this->fixtures->on('live')->create('payout',
            [
                'id'              => 'DuuYxmO7Yegu3x',
                'fund_account_id' => 'D6Z9Jfir2egAUT',
                'pricing_rule_id' => '1nvp2XPMmaRLxb',
                'type'            => 'default',
                'amount'          => 100,
                'balance_id'      => $this->bankingBalance->getId(),
                'status'          => 'created'
            ]);


        $this->fixtures->on('live')->create('fund_transfer_attempt',
            [
                'id'                => '90whtP7hFXopAW',
                'source_id'         => 'DuuYxmO7Yegu3x',
                'source_type'       => 'payout',
                'merchant_id'       => '10000000000000',
                'purpose'           => 'payout',
                'channel'           => 'yesbank',
                'mode'              => 'IMPS',
                'initiate_at'       => 1643288943,
                'bank_account_id'   => '1000000lcustba',
            ]);

        $mock = Mockery::mock(\RZP\Services\FTS\FundTransfer::class, [$this->app])->makePartial();
        $mock = $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('createAndSendRequest')->once()->andReturn(
           [
               'body' => [
                    'status'           => 'processed',
                    'fund_transfer_id' => 123,
                    'fund_account_id'  => 'D6Z9Jfir2egAUT'
               ],
               'code' => 201,
           ]
        );

        $this->app->instance('fts_fund_transfer', $mock);

        $ftsCreateTransfer = new FtsFundTransfer(EnvMode::LIVE, '90whtP7hFXopAW');

        $ftsCreateTransfer->handle();

        $payout = $this->getDbLastEntityToArray('payout', 'live');

        $this->assertEquals('initiated', $payout['status']);
    }

    public function testGetCardSupportedModesForScrooge()
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/fund_transfer_attempts/modes',
            'server'  => [
                'HTTP_X-Request-ID' => $this->app['request']->getTaskId(),
            ],
            'content' => [],
        ];

        $this->ba->appAuthTest($this->config['applications.scrooge.secret']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($response['card']);
    }
}
