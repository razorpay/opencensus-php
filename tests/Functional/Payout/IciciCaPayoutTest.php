<?php

namespace Functional\Payout;

use Queue;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Payout;
use Rzp\Models\FundTransfer;
use RZP\Services\Mock\Mozart;
use RZP\Models\BankingAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Mode as EnvMode;
Use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\Admin\Service as AdminService;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Jobs\IciciBankingAccountGatewayBalanceUpdate;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Jobs\IciciBankingAccountStatement as IciciBankingAccountStatementJob;

class IciciCaPayoutTest extends TestCase
{
    use PayoutTrait;
    use AttemptTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IciciCaPayoutTestData.php';

        parent::setUp();

        $this->fixtures->create('org:razorpay_org_live');

        $this->fixtures->on('test')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('test')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000, 'direct', 'icici');

        $bankingAccountParams = [
            'id'             => 'xba00000000002',
            'merchant_id'    => '10000000000000',
            'account_ifsc'   => 'ICIC0000047',
            'account_number' => '2224440041626905',
            'status'         => 'activated',
            'channel'        => 'icici',
            'balance_id'     => $this->bankingBalance->getId(),
        ];

        $this->createBankingAccount($bankingAccountParams);

        $this->fixtures->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::ICICI,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testIciciAccountStatementFetchV2()
    {
        $this->ba->cronAuth();

        $request = [
            'url'       => '/banking_account_statement/process/icici',
            'method'    => 'POST'
        ];

        $this->flushCache();

        (new AdminService)->setConfigKeys([ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 1]);

        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        Queue::fake();

        $this->makeRequestAndGetContent($request);

        Queue::assertPushed(IciciBankingAccountStatementJob::class, 1);
    }

    protected function liveSetUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IciciCaPayoutTestData.php';

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

        $this->setUpMerchantForBusinessBankingLive(true, 10000000, 'direct', 'icici');

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping([
                                                                         'merchant_id' => '10000000000000',
                                                                         'user_id'     => User::MERCHANT_USER_ID,
                                                                         'product'     => 'primary',
                                                                         'role'        => 'owner',
                                                                     ], 'live');
    }

    protected function mockMozartResponseForFetchingBalanceFromIciciGateway($amount, $exception = null): void
    {
        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->willReturn([
                                           Icici\Fields::DATA => [

                                               Icici\Fields::BALANCE => $amount
                                           ]
                                       ]);

        if ($exception !== null)
        {
            $mozartServiceMock->method('sendMozartRequest')
                              ->willThrowException($exception);
        }

        $this->app->instance('mozart', $mozartServiceMock);
    }

    protected function setupIciciDispatchGatewayBalanceUpdateForMerchants()
    {
        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/icici/balance',
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testDispatchGatewayBalanceUpdateJob()
    {
        Queue::fake();

        $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        Queue::assertPushed(IciciBankingAccountGatewayBalanceUpdate::class, 1);
    }

    public function testProcessGatewayBalanceUpdate()
    {
        /** @var BankingAccount\Entity $baBeforeTest */
        $baBeforeTest = $this->getDbEntityById('banking_account', 'xba00000000002');

        $this->assertNull($baBeforeTest->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var BankingAccount\Entity $baAfterCronRuns */
        $baAfterCronRuns = $this->getDbEntityById('banking_account', 'xba00000000002');

        /** @var Details\Entity $baAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(50000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotNull($baAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testGatewayBalanceFetchWithGatewayFailure()
    {
        $exception = new GatewayErrorException("GATEWAY_ERROR_UNKNOWN_ERROR",
                                               "Failure",
                                               "(No error description was mapped for this error code)");
        $mozartError = [
            "description"               => "",
            "gateway_error_code"        => "Failure",
            "gateway_error_description" => "(No error description was mapped for this error code)",
            "gateway_status_code"       => 200,
            "internal_error_code"       => "GATEWAY_ERROR_UNKNOWN_ERROR",
        ];

        /** @var BankingAccount\Entity $baBeforeTest */
        $baBeforeTest = $this->getDbEntityById('banking_account', 'xba00000000002');

        $this->assertNull($baBeforeTest->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(null, $exception);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var BankingAccount\Entity $baAfterCronRuns */

        $baAfterCronRuns = $this->getDbEntityById('banking_account', 'xba00000000002');

        $this->assertNull($baAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testCreatePayout()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals('icici', $payout['channel']);
        $this->assertEquals('processing', $payout['status']);

        $transaction = $this->getLastEntity('transaction', true);
        $this->assertNull($transaction);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::INITIATED);

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);
    }

    public function testCreatePayoutProcessed()
    {
        $this->testCreatePayout();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);
        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
    }

    public function testPayoutFailed()
    {
        $this->testCreatePayout();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);
    }

    public function testPayoutReversed()
    {
        $this->testCreatePayout();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::REVERSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::REVERSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $reversal = $this->getDbLastEntity('reversal');
        $this->assertEquals($payout['id'], $reversal['entity_id']);
    }

    public function testIciciPayoutUsingCredits()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);
    }

    public function testIciciPayoutUsingCreditsFailed()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'amount' => '104']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959']);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 30019995]);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);

    }

    public function testIciciPayoutUsingCreditsReversed()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'amount' => '104']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959']);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 30019995]);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');

        $reversal = $this->getDbLastEntity('reversal');
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::REVERSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::REVERSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);
    }
}
