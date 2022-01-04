<?php

namespace RZP\Tests\Functional\SettlementOndemand;

use Hash;
use Queue;
use Config;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\HashAlgo;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Services\Mock\RazorpayXClient;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Jobs\SettlementOndemand\RequestOndemandPayout;
use RZP\Models\Admin\Permission\Name as AdminPermission;
use RZP\Jobs\SettlementOndemand\PartialScheduledSettlementJob;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandPayoutJobs;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandBulkTransfer;

class SettlementOndemandTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $reportUrl;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/SettlementOndemandTestData.php';

        parent::setUp();

        $this->merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'contact_name'  => 'dummy_name',
            'contact_email' => 'test@gmail.com',
            'business_type' => 3
        ]);

        $this->user = $this->fixtures->user->createUserForMerchant($this->merchantDetail['merchant_id'], [
            'id'               => '20000000000000',
            'name'              => 'john doe',
            'contact_mobile'    => '9876543210',
        ]);

        $this->bankAccount    = $this->fixtures->create('bank_account', [
            'type'           => 'merchant',
            'merchant_id'    => '10000000000000',
            'entity_id'      => '10000000000000',
            'account_number' => '11122275867',
            'ifsc_code'      => 'RAZRB000000',
        ]);

        $razorpayXClientMock = $this->getMockBuilder(RazorpayXClient::class)
                        ->setConstructorArgs([$this->app])
                        ->setMethods(['createContact', 'createFundAccount'])
                        ->getMock();

        $razorpayXClientMock->method('createContact')
            ->will($this->returnCallback(
                function ($data)
                {
                    $this->assertNotEmpty($data['name']);

                    return [
                        'id' => 'cont_EuNd0bPmYkIOfL',
                        'entity' => 'contact',
                        'name' => 'Razorpay Fee Account',
                        'contact' => $data['contact'],
                        'email' => $data['email'],
                        'type' => $data['type'],
                        'reference_id' => NULL,
                        'batch_id' => NULL,
                        'active' => TRUE,
                        'notes' => array (0),
                        'created_at' => 1590363870,
                    ];
                }));

        $razorpayXClientMock->method('createFundAccount')
            ->will($this->returnCallback(
                function ($contactId, $data)
                {
                    $this->assertNotEmpty($data['name']);

                    $this->assertNotEmpty($data['ifsc']);

                    $this->assertNotEmpty($data['account_number']);

                    return [
                        'id' => 'fa_EuNd48DKKaIlcV',
                        'entity' => 'fund_account',
                        'contact_id' => $contactId,
                        'account_type' => 'bank_account',
                        'bank_account' => [
                            'ifsc' => $data['ifsc'],
                            'bank_name' => 'Random Bank',
                            'name' => $data['name'],
                            'notes' => [],
                            'account_number' => $data['account_number'],
                        ],
                        'batch_id' => NULL,
                        'active' => TRUE,
                        'created_at' => now(),
                    ];
                }));

        $this->app->instance('razorpayXClient', $razorpayXClientMock);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();
    }

    public function getAmountWithStatus($status)
    {
        switch($status)
        {
            case 'processed':
                return 229616;
            case 'processing':
                return 250000;
            case 'reversed':
                return 659232;
            default:
                return 1;
        }
    }

    public function createSampleBulkSettlement($status)
    {
        $settlementOndemandBulk1 = $this->fixtures->on('live')->create('settlement.ondemand.bulk',[
            'amount'                    => 200000,
            'created_at'                => Carbon::now(Timezone::IST)->getTimestamp(),
            'updated_at'                => Carbon::now(Timezone::IST)->getTimestamp(),
        ]);

        $settlementOndemandBulk1->save();

        $amount = $this->getAmountWithStatus($status);

        $settlementOndemandBulk2 = $this->fixtures->on('live')->create('settlement.ondemand.bulk',[
                    'amount'                    => $amount,
                    'created_at'                => Carbon::now(Timezone::IST)->getTimestamp(),
                    'updated_at'                => Carbon::now(Timezone::IST)->getTimestamp(),
           ]);

        $settlementOndemandBulk2->save();
    }

    public function createSampleSettlementTransfer($status, $secondStatus = null)
    {
        $amount = $this->getAmountWithStatus($status) + 200000;

        $secondAmount = $secondStatus !== null ? $this->getAmountWithStatus($secondStatus) + 200000 : $amount ;

        $settlementOndemandTransfer1 = $this->fixtures
                                            ->on('live')
                                            ->create('settlement.ondemand.transfer',[
                                                'amount'                  => $amount,
                                                'attempts'                => 0,
                                                'status'                  => 'created',
                                                'mode'                    => 'IMPS'
                                                ]);

        $settlementOndemandTransfer1->save();

        $settlementOndemandAttempt1 = $this->fixtures
                                            ->on('live')
                                            ->create('settlement.ondemand.attempt',[
                                                'settlement_ondemand_transfer_id' => $settlementOndemandTransfer1['id'],
                                                'status'                          => 'created',
                                            ]);

        $settlementOndemandAttempt1->save();

        $settlementOndemandTransfer2 = $this->fixtures
                                            ->on('live')
                                            ->create('settlement.ondemand.transfer',[
                                                'amount'                  => $secondAmount,
                                                'attempts'                => 0,
                                                'status'                  => 'created',
                                                'mode'                    => 'IMPS'
                                            ]);

        $settlementOndemandTransfer2->save();

        $settlementOndemandAttempt2 = $this->fixtures
                                           ->on('live')
                                           ->create('settlement.ondemand.attempt',[
                                                'settlement_ondemand_transfer_id' => $settlementOndemandTransfer2['id'],
                                                'status'                          => 'created',
                                           ]);

        $settlementOndemandAttempt2->save();
    }

    public function processLastCycleSettlements()
    {
        $this->ba->cronAuth('live');

        $request = [
            'url'     => '/settlements/ondemand/process',
            'method'  => 'POST',
            'content' => [],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function mockWebHook($settlementOndemandAttempt, $settlementOndemandTransfer)
    {
        $input = [
            'entity' => 'event',
            'event' => 'payout.processed',
            'contains' => ['payout'],
            'payload' => [
                'payout' => [
                    'entity' => [
                        'id' => $settlementOndemandAttempt['payout_id'],
                        'entity' => 'payout',
                        'fund_account_id' => Config::get('applications.razorpayx_client.live.ondemand_contact.fund_account_id'),
                        'amount' => $settlementOndemandTransfer['amount'],
                        'currency' => 'INR',
                        'notes' => [],
                        'fees' => 0,
                        'tax' => 0,
                        'status' => 'processed',
                        'purpose' => 'payout',
                        'utr' => random_integer(10),
                        'mode' => $settlementOndemandTransfer['mode'],
                        'reference_id' => $settlementOndemandAttempt['id'],
                        'narration' => 'Acme Fund Transfer',
                        'batch_id' => null,
                        'failure_reason' => null,
                        'created_at' => Carbon::now(Timezone::IST)->getTimestamp(),
                    ]
                ]
            ],
            'created_at' => Carbon::now(Timezone::IST)->getTimestamp()
        ];

        if (($settlementOndemandTransfer['amount'] === 450000) or
            ($settlementOndemandTransfer['amount'] === 880000))
        {
            $input['event'] = 'payout.reversed';

            $input['payload']['payout']['entity']['utr'] = null;

            $input['payload']['payout']['entity']['failure_reason'] = 'dummy_reason';
        }

        $rawContent = 'dummy_raw_content';

        $key = Config::get('applications.razorpayx_client.live.ondemand_x_merchant.webhook_key');

        $signature = hash_hmac(HashAlgo::SHA256,  $rawContent, $key);

        $headers =['x-razorpay-signature' => [$signature]];

        (new OndemandPayout\Service)->statusUpdate($input, $headers, $rawContent);
    }

    public function testEnqueueJob()
    {
        Queue::fake();

        $request = [
            'method'    => 'POST',
            'url'       => 'settlements/ondemand/enqueue/12345678910234',
        ];

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand',[
            'id'     => '12345678910234',
            'status' => 'created',
        ]);

        $this->ba->adminAuth();

        $this->startTest();

        Queue::assertPushed(CreateSettlementOndemandPayoutJobs::class, 1);
    }

    public function testProcessXSettlementBulkTransferNull()
    {
        $response = $this->processLastCycleSettlements();

        $settlementOndemandTransfer = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,
            true,
            'live');

        $settlementOndemandAttempt = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            true,
            'live');

        self::assertNull($settlementOndemandAttempt);

        self::assertNull($settlementOndemandTransfer);

        self::assertNull($response);
    }

    public function testProcessXSettlementBulkTransferProcessing()
    {
        $this->createSampleBulkSettlement('processing');

        $this->processLastCycleSettlements();

        $settlementOndemandTransfer = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'amount'       => 450000,
            'status'       => 'processing',
        ], $settlementOndemandTransfer);

        $settlementOndemandBulks = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_BULK,
            ['count' => 2],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id' => $settlementOndemandTransfer['id'],
        ], $settlementOndemandBulks['items'][0]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id' => $settlementOndemandTransfer['id'],
        ], $settlementOndemandBulks['items'][1]);

        $settlementOndemandAttempt = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfer['id'],
            'status'                                => 'processing',
        ], $settlementOndemandAttempt);
    }

    public function testProcessXSettlementBulkTransferProcessed()
    {
        $this->createSampleBulkSettlement('processed');

        $this->processLastCycleSettlements();

        $settlementOndemandTransfer = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'amount'       => 429616,
            'status'       => 'processed',
        ], $settlementOndemandTransfer);

        $settlementOndemandBulks = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_BULK,
            ['count' => 2],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id' => $settlementOndemandTransfer['id'],
        ], $settlementOndemandBulks['items'][0]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id' => $settlementOndemandTransfer['id'],
        ], $settlementOndemandBulks['items'][1]);

        $settlementOndemandAttempt = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfer['id'],
            'status'                                => 'processed',
        ], $settlementOndemandAttempt);
    }

    public function testProcessXSettlementBulkTransferReversed()
    {
        $this->createSampleBulkSettlement('reversed');

        $this->processLastCycleSettlements();

        $settlementOndemandTransfer = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'amount'       => 859232,
            'status'       => 'reversed',
        ], $settlementOndemandTransfer);

        $settlementOndemandBulks = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_BULK,
            ['count' => 2],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id' => $settlementOndemandTransfer['id'],
        ], $settlementOndemandBulks['items'][0]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id' => $settlementOndemandTransfer['id'],
        ], $settlementOndemandBulks['items'][1]);

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 10],
            true,
            'live');

        for ($i =0; $i <10;$i++)
        {
            $this->assertArraySelectiveEquals([
                'settlement_ondemand_transfer_id'       => $settlementOndemandTransfer['id'],
                'status'                                => 'reversed',
            ], $settlementOndemandAttempts['items'][$i]);
        }

    }

    public function testProcessXSettlementBulkTransferWithProcessedWebhook()
    {
        $this->createSampleBulkSettlement(null);

        $this->processLastCycleSettlements();

        $settlementOndemandTransfer = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,
            true,
            'live');

        $settlementOndemandAttempt = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            true,
            'live');

        $this->mockWebHook($settlementOndemandAttempt, $settlementOndemandTransfer);

        $settlementOndemandTransfer = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,
            true,
            'live');

        $settlementOndemandAttempt = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfer['id'],
            'status'                                => 'processed',
        ], $settlementOndemandAttempt);

        $this->assertArraySelectiveEquals([
            'amount'       => 200001,
            'status'       => 'processed',
        ], $settlementOndemandTransfer);

    }

    public function testProcessXSettlementBulkTransferWithReversedWebhook()
    {
        $this->createSampleBulkSettlement('processing');

        $this->processLastCycleSettlements();

        $settlementOndemandTransfer = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,
            true,
            'live');

        $settlementOndemandAttempt = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            true,
            'live');

        $this->mockWebHook($settlementOndemandAttempt, $settlementOndemandTransfer);

        $settlementOndemandTransfer = $this->getLastEntity(
            EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,
            true,
            'live');

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 2],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfer['id'],
            'status'                                => 'processing',
        ], $settlementOndemandAttempts['items'][0]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfer['id'],
            'status'                                => 'reversed',
        ], $settlementOndemandAttempts['items'][1]);

        $this->assertArraySelectiveEquals([
            'amount'       => 450000,
            'status'       => 'processing',
        ], $settlementOndemandTransfer);
    }

    public function testOndemandCreationForMerchantWithXSettlementAccount()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->on(Mode::TEST)->create('bank_account',[
            'merchant_id'       => $this->merchantDetail['merchant_id'],
            'type'              => 'merchant',
            'ifsc_code'         => 'ICIC0000104',
            'account_number'    => '10010101011',
        ]);

        $this->fixtures->on(Mode::TEST)->create('balance', [
            'balance' => 10000000000,
        ]);

        $this->fixtures->on(Mode::TEST)->create('bank_account',[
            'id'                =>'bankaccountid1',
            'merchant_id'       => $this->merchantDetail['merchant_id'],
            'type'              => 'virtual_account',
            'ifsc_code'         => 'ICIC0000104',
            'account_number'    => '10010101011',
        ]);

        $this->fixtures->on(Mode::TEST)->create('virtual_account', [
            'bank_account_id' => 'bankaccountid1',
            'balance_id'      => '10000000000000',
        ]);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures
             ->base
             ->editEntity(
                 'balance',
                 '10000000000000',
                 [   'balance'        => 10000000000,
                     'account_type'   => 'shared',
                     'type'           => 'banking',
                     'account_number' => '10010101011']);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

        $txns = $this->getEntities(
            'transaction',
            ['count' => 2],
            true);

        $this->assertArraySelectiveEquals([
            'type'                  => 'adjustment',
            'merchant_id'           => '10000000000000',
            'amount'                => 19557292,
            'fee'                   => 0,
            'tax'                   => 0,
            'debit'                 => 0,
            'credit'                => 19557292,
            'currency'              => 'INR',
        ], $txns['items'][0]);

        $this->assertArraySelectiveEquals([
            'type'                  => 'settlement.ondemand',
            'merchant_id'           => '10000000000000',
            'amount'                => 19557292,
            'fee'                   => 472708,
            'tax'                   => 72108,
            'debit'                 => 20030000,
            'credit'                => 0,
            'currency'              => 'INR',
        ], $txns['items'][1]);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 20030000,
            'total_amount_settled'  => 19557292,
            'total_fees'            => 472708,
            'total_tax'             => 72108,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 0,
            'currency'              => 'INR',
            'status'                => 'processed',
            'narration'             => 'Demo Narration - optional',
            'notes'                 => [
                'key1' => 'note3',
                'key2' => 'note5',
            ],
            'transaction_type'      => 'transaction',
        ], $settlementOndemand);

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals([
            'merchant_id' => '10000000000000',
            'amount'      => 19557292,
            'description' => 'ondemand settlement - '.$settlementOndemand['id'],
            'currency'    => 'INR',
        ], $adjustment);

        $settlementOndemandPayout = $this->getLastEntity('settlement.ondemand_payout',true);

        $this->assertArraySelectiveEquals([
            'merchant_id'    => '10000000000000',
            'user_id'        => '20000000000000',
            'entity_type'    => 'adjustment',
            'payout_id'      =>  $adjustment['id'],
            'mode'           => NULL,
            'reversed_at'    => NULL,
            'fees'           => 472708,
            'tax'            => 72108,
            'status'         => 'processed',
            'amount'         => 20030000,
            'failure_reason' => NULL,
        ], $settlementOndemandPayout);

        $settlementOndemandBulk = $this->getLastEntity(EntityConstants::SETTLEMENT_ONDEMAND_BULK,true);

        $this->assertArraySelectiveEquals([
            'amount'                          => 19557292,
            'settlement_ondemand_transfer_id' => NULL,
        ], $settlementOndemandBulk);
    }

    public function testOndemandTransferMarkAsProcessed()
    {
        $this->ba->adminAuth(MODE::TEST);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.transfer', [
            'id'       => '12345678910111',
            'status'   => 'reversed',
            'mode'     => 'IMPS',
            'attempts' => 1,
        ]);

        $this->startTest();

        $settlementOndemandTransfer = $this->getLastEntity(EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,true);

        $settlementOndemandAttempt = $this->getLastEntity(EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,true);

        $this->assertArraySelectiveEquals([
            'id'                              => '12345678910111',
            'status'                          => 'processed',
            'payout_id'                       => null,
            'attempts'                        => 2,
        ], $settlementOndemandTransfer);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id' => '12345678910111',
            'status'                          => 'processed',
            'payout_id'                       => null,
        ], $settlementOndemandAttempt);
    }

    public function testOndemandTransferTrigger()
    {
        Queue::fake();

        $this->ba->adminAuth(MODE::TEST);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.transfer', [
            'id'       => '12345678910111',
            'status'   => 'reversed',
            'mode'     => 'NEFT',
            'attempts' => 10,
        ]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.transfer', [
            'id'       => '12345678910112',
            'status'   => 'reversed',
            'mode'     => 'NEFT',
            'attempts' => 10,
        ]);

        $this->startTest();

        Queue::assertPushed(CreateSettlementOndemandBulkTransfer::class, 2);
    }

    public function testOndemandPartialEsScheduledTrigger()
    {
        Queue::fake();

        $this->ba->adminAuth(MODE::TEST);

        $this->startTest();

        Queue::assertPushed(PartialScheduledSettlementJob::class, 1);
    }

    private function mockDataForOndemandPartialEs()
    {
        $merchantId = $this->merchantDetail['merchant_id'];

        $this->ba->adminAuth(MODE::TEST);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->on(Mode::TEST)->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => $merchantId, 'name' => 'es_on_demand']);

        $this->fixtures->on(Mode::TEST)->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => $merchantId, 'name' => 'es_on_demand_restricted']);

        $this->fixtures->on(Mode::TEST)->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => $merchantId, 'name' => 'es_automatic_restricted']);

        $this->fixtures->create('pricing',[
            'id'                  => '1zE3CYqf1zbyaa',
            'plan_id'             => '1BFFkd38fFGbnh',
            'plan_name'           => 'testDefaultPlan',
            'feature'             => Feature::ESAUTOMATIC_RESTRICTED,
            'payment_method'      => 'fund_transfer',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 25,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
        ]);

        $this->fixtures->on(Mode::TEST)->pricing->createOndemandPercentRatePricingPlan();

        $this->fixtures->on(Mode::TEST)->merchant->edit($merchantId, ['pricing_plan_id' => '1BFFkd38fFGbnh', 'international' => 0]);
    }

    public function testOndemandPartialScheduledSettlement()
    {
        $this->ba->adminAuth(MODE::TEST);

        $this->mockDataForOndemandPartialEs();

        $this->fixtures->on(Mode::TEST)->base->editEntity('balance', '10000000000000', ['balance' => 3000000]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 750000,
            'pricing_percent'             => 50,
            'es_pricing_percent'          => 25,
        ]);

        $this->startTest();

        $settlementOndemand = $this->getLastEntity('settlement.ondemand', true);

        $this->assertArraySelectiveEquals([
            'merchant_id'           => '10000000000000',
            'amount'                => 750000,
            'total_amount_settled'  => 747787,
            'total_fees'            => 2213,
            'total_tax'             => 338,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 0,
            'max_balance'           => true,
            'currency'              => 'INR',
            'status'                => 'processed',
            'scheduled'             => true
        ], $settlementOndemand);

    }



    public function testOndemandPartialScheduledSettlementWithBalanceLessThanSettleableBalance()
    {

        $this->ba->adminAuth(MODE::TEST);

        $this->mockDataForOndemandPartialEs();

        $this->fixtures->on(Mode::TEST)->base->editEntity('balance', '10000000000000', ['balance' => 60000]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 750000,
            'pricing_percent'             => 50,
            'es_pricing_percent'          => 25,
        ]);

        $this->startTest();

        $settlementOndemand = $this->getLastEntity('settlement.ondemand', true);

        $this->assertArraySelectiveEquals([
            'merchant_id'           => '10000000000000',
            'amount'                => 30000,
            'total_amount_settled'  => 29911,
            'total_fees'            => 89,
            'total_tax'             => 14,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 0,
            'max_balance'           => true,
            'currency'              => 'INR',
            'status'                => 'processed',
            'scheduled'             => true
        ], $settlementOndemand);

    }

    public function testOndemandPartialScheduledSettlementWithMaxAmountLimitCrossed()
    {

        $merchantId = $this->merchantDetail['merchant_id'];

        $this->ba->adminAuth(MODE::TEST);

        $this->mockDataForOndemandPartialEs();

        $this->fixtures->on(Mode::TEST)->base->editEntity('balance', $merchantId, ['balance' => 3000000]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand',[
            'merchant_id'                 => $merchantId,
            'amount'                      => 750000,
        ]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $merchantId,
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 750000,
            'pricing_percent'             => 50,
            'es_pricing_percent'          => 25,
        ]);

        $this->startTest();

        $settlementOndemand = $this->getDbEntity('settlement.ondemand', ['merchant_id' => $merchantId, 'scheduled' => true]);

        $this->assertNull($settlementOndemand);

    }

    public function testOndemandPartialScheduledSettlementErrorWithBalanceBelowThreshold()
    {

        $merchantId = $this->merchantDetail['merchant_id'];

        $this->ba->adminAuth(MODE::TEST);

        $this->fixtures->on(Mode::TEST)->base->editEntity('balance', $merchantId, ['balance' => 9900]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $merchantId,
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 750000,
            'pricing_percent'             => 50,
            'es_pricing_percent'          => 25,
        ]);

        $this->startTest();

        $settlementOndemand = $this->getDbEntity('settlement.ondemand', ['merchant_id' => $merchantId, 'scheduled' => true]);

        $this->assertNull($settlementOndemand);

    }

    public function testUpdateOndemandTransferPayoutId()
    {
        $this->ba->adminAuth(MODE::LIVE);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.transfer', [
            'id'       => '12345678910111',
            'status'   => 'reversed',
            'mode'     => 'NEFT',
            'attempts' =>  10,
            'payout_id'=> 'pout_Hjswrr4zGv1jpY',
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.transfer', [
            'id'       => '12345678910112',
            'status'   => 'reversed',
            'mode'     => 'NEFT',
            'attempts' =>  10,
            'payout_id'=> 'pout_HjtCcoBj338MzV',
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.attempt', [
            'id'                              => 'Hjw8I44gThg3z7',
            'status'                          => 'processed',
            'settlement_ondemand_transfer_id' => '12345678910111',
            'payout_id'                       => 'pout_Hjswrr4zGv1jpY'
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.attempt', [
            'id'                              => 'Hjw8I3sWSwlhlD',
            'status'                          => 'processed',
            'settlement_ondemand_transfer_id' => '12345678910112',
            'payout_id'                       => 'pout_HjtCcoBj338MzV'
        ]);

        $this->startTest();

        $settlementOndemandTransfers = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_TRANSFER,
            ['count' => 2],
            true,
            'live');

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 2],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'id'        => '12345678910112',
            'payout_id' => 'pout_GjtCcoBj338MzV',
        ], $settlementOndemandTransfers['items'][0]);

        $this->assertArraySelectiveEquals([
            'id'        => '12345678910111',
            'payout_id' => 'pout_Gjswrr4zGv1jpY',
        ], $settlementOndemandTransfers['items'][1]);

        $this->assertArraySelectiveEquals([
            'id'        => 'Hjw8I3sWSwlhlD',
            'payout_id' => 'pout_GjtCcoBj338MzV',
        ], $settlementOndemandAttempts['items'][1]);

        $this->assertArraySelectiveEquals([
            'id'        => 'Hjw8I44gThg3z7',
            'payout_id' => 'pout_Gjswrr4zGv1jpY',
        ], $settlementOndemandAttempts['items'][0]);

    }

    public function testOndemandCreationForMerchantWithXSettlementAccountNonBankingHoursGreaterThanIMPSLimit()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->on(Mode::TEST)->create('bank_account',[
            'merchant_id'       => $this->merchantDetail['merchant_id'],
            'type'              => 'merchant',
            'ifsc_code'         => 'ICIC0000104',
            'account_number'    => '10010101011',
        ]);

        $this->fixtures->on(Mode::TEST)->create('balance', [
            'balance' => 10000000000,
        ]);

        $this->fixtures->on(Mode::TEST)->create('bank_account',[
            'id'                =>'bankaccountid1',
            'merchant_id'       => $this->merchantDetail['merchant_id'],
            'type'              => 'virtual_account',
            'ifsc_code'         => 'ICIC0000104',
            'account_number'    => '10010101011',
        ]);

        $this->fixtures->on(Mode::TEST)->create('virtual_account', [
            'bank_account_id' => 'bankaccountid1',
            'balance_id'      => '10000000000000',
        ]);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures
            ->base
            ->editEntity(
                'balance',
                '10000000000000',
                [   'balance'        => 10000000000,
                    'account_type'   => 'shared',
                    'type'           => 'banking',
                    'account_number' => '10010101011']);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $nonBankingHour = Carbon::create(2020, 12, 13, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($nonBankingHour);

        $this->startTest();

        $txns = $this->getEntities(
            'transaction',
            ['count' => 2],
            true);

        $this->assertArraySelectiveEquals([
            'type'                  => 'adjustment',
            'merchant_id'           => '10000000000000',
            'amount'                => 58584000,
            'fee'                   => 0,
            'tax'                   => 0,
            'debit'                 => 0,
            'credit'                => 58584000,
            'currency'              => 'INR',
        ], $txns['items'][0]);

        $this->assertArraySelectiveEquals([
            'type'                  => 'settlement.ondemand',
            'merchant_id'           => '10000000000000',
            'amount'                => 58584000,
            'fee'                   => 1416000,
            'tax'                   => 216000,
            'debit'                 => 60000000,
            'credit'                => 0,
            'currency'              => 'INR',
        ], $txns['items'][1]);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 60000000,
            'total_amount_settled'  => 58584000,
            'total_fees'            => 1416000,
            'total_tax'             => 216000,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 0,
            'currency'              => 'INR',
            'status'                => 'processed',
            'narration'             => 'Demo Narration - optional',
            'notes'                 => [
                'key1' => 'note3',
                'key2' => 'note5',
            ],
            'transaction_type'      => 'transaction',
        ], $settlementOndemand);

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals([
            'merchant_id' => '10000000000000',
            'amount'      => 58584000,
            'description' => 'ondemand settlement - '.$settlementOndemand['id'],
            'currency'    => 'INR',
        ], $adjustment);

        $settlementOndemandPayout = $this->getLastEntity('settlement.ondemand_payout', true);

        $this->assertArraySelectiveEquals([
            'merchant_id'    => '10000000000000',
            'user_id'        => '20000000000000',
            'entity_type'    => 'adjustment',
            'payout_id'      =>  $adjustment['id'],
            'mode'           => NULL,
            'reversed_at'    => NULL,
            'fees'           => 1416000,
            'tax'            => 216000,
            'status'         => 'processed',
            'amount'         => 60000000,
            'failure_reason' => NULL,
        ], $settlementOndemandPayout);

        $settlementOndemandTransfers = $this->getEntities(
            'settlement.ondemand.transfer',
            ['count' => 2],
            true);

        $this->assertArraySelectiveEquals([
            'amount'                          => 8584000,
            'status'                          => 'processing',
       ], $settlementOndemandTransfers['items'][0]);

        $this->assertArraySelectiveEquals([
            'amount'                          => 50000000,
            'status'                          => 'processing',
        ], $settlementOndemandTransfers['items'][1]);

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 2],
            true);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][0]['id'],
            'status'                                => 'processing',
        ], $settlementOndemandAttempts['items'][0]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][1]['id'],
            'status'                                => 'processing',
        ], $settlementOndemandAttempts['items'][1]);

        $settlementOndemandBulks = $this->getEntities(
            'settlement.ondemand.bulk',
            ['count' => 2],
            true);

        $this->assertArraySelectiveEquals([
            'amount'                          => 8584000,
            'settlement_ondemand_transfer_id' => $settlementOndemandTransfers['items'][0]['id'],
        ], $settlementOndemandBulks['items'][0]);

        $this->assertArraySelectiveEquals([
            'amount'                          => 50000000,
            'settlement_ondemand_transfer_id' => $settlementOndemandTransfers['items'][1]['id'],
        ], $settlementOndemandBulks['items'][1]);
    }

    public function testOndemandCreationForMerchantWithXSettlementAccountNonBankingHoursLessThanIMPSLimit()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->on(Mode::TEST)->create('bank_account',[
            'merchant_id'       => $this->merchantDetail['merchant_id'],
            'type'              => 'merchant',
            'ifsc_code'         => 'ICIC0000104',
            'account_number'    => '10010101011',
        ]);

        $this->fixtures->on(Mode::TEST)->create('balance', [
            'balance' => 10000000000,
        ]);

        $this->fixtures->on(Mode::TEST)->create('bank_account',[
            'id'                =>'bankaccountid1',
            'merchant_id'       => $this->merchantDetail['merchant_id'],
            'type'              => 'virtual_account',
            'ifsc_code'         => 'ICIC0000104',
            'account_number'    => '10010101011',
        ]);

        $this->fixtures->on(Mode::TEST)->create('virtual_account', [
            'bank_account_id' => 'bankaccountid1',
            'balance_id'      => '10000000000000',
        ]);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures
            ->base
            ->editEntity(
                'balance',
                '10000000000000',
                [   'balance'        => 10000000000,
                    'account_type'   => 'shared',
                    'type'           => 'banking',
                    'account_number' => '10010101011']);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $nonBankingHour = Carbon::create(2020, 12, 13, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($nonBankingHour);

        $this->startTest();

        $txns = $this->getEntities(
            'transaction',
            ['count' => 2],
            true);

        $this->assertArraySelectiveEquals([
            'type'                  => 'adjustment',
            'merchant_id'           => '10000000000000',
            'amount'                => 5858,
            'fee'                   => 0,
            'tax'                   => 0,
            'debit'                 => 0,
            'credit'                => 5858,
            'currency'              => 'INR',
        ], $txns['items'][0]);

        $this->assertArraySelectiveEquals([
            'type'                  => 'settlement.ondemand',
            'merchant_id'           => '10000000000000',
            'amount'                => 5858,
            'fee'                   => 142,
            'tax'                   => 22,
            'debit'                 => 6000,
            'credit'                => 0,
            'currency'              => 'INR',
        ], $txns['items'][1]);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 6000,
            'total_amount_settled'  => 5858,
            'total_fees'            => 142,
            'total_tax'             => 22,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 0,
            'currency'              => 'INR',
            'status'                => 'processed',
            'narration'             => 'Demo Narration - optional',
            'notes'                 => [
                'key1' => 'note3',
                'key2' => 'note5',
            ],
            'transaction_type'      => 'transaction',
        ], $settlementOndemand);

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals([
            'merchant_id' => '10000000000000',
            'amount'      => 5858,
            'description' => 'ondemand settlement - '.$settlementOndemand['id'],
            'currency'    => 'INR',
        ], $adjustment);

        $settlementOndemandPayout = $this->getLastEntity('settlement.ondemand_payout', true);

        $this->assertArraySelectiveEquals([
            'merchant_id'    => '10000000000000',
            'user_id'        => '20000000000000',
            'entity_type'    => 'adjustment',
            'payout_id'      =>  $adjustment['id'],
            'mode'           => NULL,
            'reversed_at'    => NULL,
            'fees'           => 142,
            'tax'            => 22,
            'status'         => 'processed',
            'amount'         => 6000,
            'failure_reason' => NULL,
        ], $settlementOndemandPayout);

        $settlementOndemandTransfer = $this->getLastEntity('settlement.ondemand.transfer', true);

        $this->assertArraySelectiveEquals([
            'amount'                          => 5858,
            'status'                          => 'processing',
        ], $settlementOndemandTransfer);

        $settlementOndemandAttempt = $this->getLastEntity(EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT, true);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfer['id'],
            'status'                                => 'processing',
        ], $settlementOndemandAttempt);

        $settlementOndemandBulk = $this->getLastEntity('settlement.ondemand.bulk', true);

        $this->assertArraySelectiveEquals([
            'amount'                          => 5858,
            'settlement_ondemand_transfer_id' => $settlementOndemandTransfer['id'],
        ], $settlementOndemandBulk);

    }

    public function testOndemandCreationForMerchantWithXSettlementAccountNonBankingHoursProcessedWebhook()
    {
       $this->createSampleSettlementTransfer('processed');

        $settlementOndemandTransfers = $this->getEntities(
            'settlement.ondemand.transfer',
            ['count' => 2],
            true,
            'live');

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 2],
            true,
            'live');

       for($i = 0; $i < sizeof($settlementOndemandTransfers['items']); $i++)
       {
           $this->mockWebHook($settlementOndemandAttempts['items'][$i], $settlementOndemandTransfers['items'][$i]);
       }

        $settlementOndemandTransfers = $this->getEntities(
            'settlement.ondemand.transfer',
            ['count' => 2],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'amount'                          => 429616,
            'status'                          => 'processed',
        ], $settlementOndemandTransfers['items'][0]);

        $this->assertArraySelectiveEquals([
            'amount'                          => 429616,
            'status'                          => 'processed',
        ], $settlementOndemandTransfers['items'][1]);

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 2],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][0]['id'],
            'status'                                => 'processed',
        ], $settlementOndemandAttempts['items'][0]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][1]['id'],
            'status'                                => 'processed',
        ], $settlementOndemandAttempts['items'][1]);
    }

    public function testOndemandCreationForMerchantWithXSettlementAccountNonBankingHoursReversedWebhook()
    {
       $this->createSampleSettlementTransfer('processing');

        $settlementOndemandTransfers = $this->getEntities(
            'settlement.ondemand.transfer',
            ['count' => 2],
            true,
            'live');

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 2],
            true,
            'live');

        for($i = 0; $i < sizeof($settlementOndemandTransfers['items']); $i++)
        {
            $this->mockWebHook($settlementOndemandAttempts['items'][$i], $settlementOndemandTransfers['items'][$i]);
        }

        $settlementOndemandTransfers = $this->getEntities(
            'settlement.ondemand.transfer',
            ['count' => 2],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'amount'                          => 450000,
            'status'                          => 'processing',
        ], $settlementOndemandTransfers['items'][0]);

        $this->assertArraySelectiveEquals([
            'amount'                          => 450000,
            'status'                          => 'processing',
        ], $settlementOndemandTransfers['items'][1]);

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 4],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][1]['id'],
            'status'                                => 'processing',
        ], $settlementOndemandAttempts['items'][0]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][0]['id'],
            'status'                                => 'processing',
        ], $settlementOndemandAttempts['items'][1]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][0]['id'],
            'status'                                => 'reversed',
        ], $settlementOndemandAttempts['items'][2]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][1]['id'],
            'status'                                => 'reversed',
        ], $settlementOndemandAttempts['items'][3]);
    }

    public function testOndemandCreationForMerchantWithXSettlementAccountNonBankingHoursProcessedAndReversedWebhook()
    {
        $this->createSampleSettlementTransfer('processed', 'processing');

        $settlementOndemandTransfers = $this->getEntities(
            'settlement.ondemand.transfer',
            ['count' => 2],
            true,
            'live');

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 2],
            true,
            'live');

        for($i =0; $i < sizeof($settlementOndemandTransfers['items']); $i++)
        {
            $this->mockWebHook($settlementOndemandAttempts['items'][$i], $settlementOndemandTransfers['items'][$i]);
        }

        $settlementOndemandTransfers = $this->getEntities(
            'settlement.ondemand.transfer',
            ['count' => 2],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'amount'                          => 450000,
            'status'                          => 'processing',
        ], $settlementOndemandTransfers['items'][0]);

        $this->assertArraySelectiveEquals([
            'amount'                          => 429616,
            'status'                          => 'processed',
        ], $settlementOndemandTransfers['items'][1]);

        $settlementOndemandAttempts = $this->getEntities(
            EntityConstants::SETTLEMENT_ONDEMAND_ATTEMPT,
            ['count' => 4],
            true,
            'live');

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][0]['id'],
            'status'                                => 'processing',
        ], $settlementOndemandAttempts['items'][0]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][0]['id'],
            'status'                                => 'reversed',
        ], $settlementOndemandAttempts['items'][1]);

        $this->assertArraySelectiveEquals([
            'settlement_ondemand_transfer_id'       => $settlementOndemandTransfers['items'][1]['id'],
            'status'                                => 'processed',
        ], $settlementOndemandAttempts['items'][2]);

    }

    public function testFundAccountCreationOnEsOndemandAssigning()
    {
        $this->ba->adminAuth(MODE::TEST);

        $this->startTest();

        $fundAccount = $this->getLastEntity(EntityConstants::SETTLEMENT_ONDEMAND_FUND_ACCOUNT, true);

        $this->assertArraySelectiveEquals([
            //                'id'                => 'sodfa_F03SCl1YK4UC6B',
                        'merchant_id'      => '10000000000000',
                        'contact_id'       => 'cont_EuNd0bPmYkIOfL',
                        'fund_account_id'  => 'fa_EuNd48DKKaIlcV',
            //                'created_at'        => 1591602865
                    ], $fundAccount);
    }

    public function testFundAccountUpdationOnBankAccountEdit()
    {
        $this->ba->adminAuth(MODE::TEST);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $bankAccountId = $this->bankAccount->getId();

        $this->testData[__FUNCTION__]['request']['url'] = strtr($this->testData[__FUNCTION__]['request']['url'], ['{id}' => $bankAccountId,]);

        $this->startTest();

        $fundAccount = $this->getLastEntity(EntityConstants::SETTLEMENT_ONDEMAND_FUND_ACCOUNT, true);

        $this->assertNotEmpty($fundAccount['fund_account_id']);
        $this->assertNotEmpty($fundAccount['contact_id']);

        $this->assertArraySelectiveEquals([
            //                'id'                => 'sodfa_F03SCl1YK4UC6B',
                        'merchant_id'      => '10000000000000',
                        // 'contact_id'       => 'cont_EwjVv4aprYdlR5',
                        // 'fund_account_id'  => 'fa_EuNd48DKKaIlcV',
            //                'created_at'        => 1591602865
                    ], $fundAccount);
    }

    public function testOndemandFeatureWithoutRequiredPermission()
    {
        $org = $this->fixtures->create('org', [
            'email' => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type' => 'password',
        ]);

        $admin = $this->fixtures->create('admin', [
            'org_id' => $org->getId(),
            'username' => 'auth admin',
            'password' => 'Heimdall!234',
        ]);

        $role = $this->fixtures->create('role', [
            'org_id' => $org->getId(),
            'name' => 'Test Role',
        ]);

        $adminBatchPerm = $this->fixtures->create('permission', [
            'name' => AdminPermission::ADMIN_BATCH_CREATE,
        ]);

        $role->permissions()->attach($adminBatchPerm->getId());
        $admin->roles()->attach($role);

        $authToken = $this->getAuthTokenForAdmin($admin);
        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest();
    }

    public function testOndemandFeatureWithPermission()
    {
        $org = $this->fixtures->create('org', [
            'email' => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type' => 'password',
        ]);

        $admin = $this->fixtures->create('admin', [
            'org_id' => $org->getId(),
            'username' => 'auth admin',
            'password' => 'Heimdall!234',
        ]);

        $role = $this->fixtures->create('role', [
            'org_id' => $org->getId(),
            'name' => 'Test Role',
        ]);

        $adminBatchPerm = $this->fixtures->create('permission', [
            'name' => AdminPermission::ADMIN_BATCH_CREATE,
        ]);
        $role->permissions()->attach($adminBatchPerm->getId());

        $plBatchPerm = $this->fixtures->create('permission',[
            'name'   => AdminPermission::SETTLEMENT_ONDEMAND_FEATURE_ENABLE,
        ]);
        $role->permissions()->attach($plBatchPerm->getId());

        $admin->roles()->attach($role);

        $authToken = $this->getAuthTokenForAdmin($admin);
        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest();
    }

    protected function getAuthTokenForAdmin($admin): string
    {
        $now = Carbon::now();

        $bearerToken = 'ThisIsATokenFORAdmin';

        $adminToken = $this->fixtures->create('admin_token', [
            'admin_id' => $admin->getId(),
            'token' => Hash::make($bearerToken),
            'created_at' => $now->timestamp,
            'expires_at' => $now->addDays(2)->timestamp,
        ]);

        return $bearerToken . $adminToken->getId();
    }

    public function testOndemandFeatureValidationSuccess()
    {
        $this->ba->proxyAuth('rzp_live_' . $this->merchantDetail['merchant_id']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 50,
            ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000]);

        $this->startTest();
    }

    public function testOndemandFeatureValidationNoAttemptLeftFailure()
    {
        $this->ba->proxyAuth('rzp_live_' . $this->merchantDetail['merchant_id']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 50,
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 250,
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 250,
        ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000]);

        $this->startTest();
    }

    public function testOndemandFeatureValidationDailyAmountExceededFailure()
    {
        $this->ba->proxyAuth('rzp_live_' . $this->merchantDetail['merchant_id']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 3,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 50,
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 5000,
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 2500,
        ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000]);

        $this->startTest();
    }

    public function testEnableEsOnDemandFullAccessFromBatchRoute()
    {
        $this->ba->batchAppAuth();

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->create('merchant', [
            'id'   => '10000000000001'
        ]);

        $this->fixtures->merchant->edit('10000000000000',
                                            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->fixtures->merchant->edit('10000000000001',
                                            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->startTest();

        $featureConfigs = $this->getEntities(
            'settlement.ondemand.feature_config',
            ['count' => 2],
            true,
            'test');

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000001',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][0]);

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000000',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][1]);

        $pricingRule = $this->getDbEntity('pricing',
                                                [   'product' => 'primary',
                                                    'feature' =>'settlement_ondemand',
                                                    'plan_id' => '1BFFkd38fFGbnh'
                                                ],
                                         'test');

        $this->assertEquals($pricingRule['percent_rate'], 50);

        $firstMerchantFeature = $this->getDbEntity('feature',
                                                          [   'name'        => 'es_on_demand',
                                                              'entity_id'   => '10000000000000',
                                                              'entity_type' => 'merchant'
                                                          ],
                                                    'test');

        $secondMerchantFeature = $this->getDbEntity('feature',
                                                           [   'name'        => 'es_on_demand',
                                                               'entity_id'   => '10000000000001',
                                                               'entity_type' => 'merchant'
                                                           ],
                                                    'test');

        $this->assertNotNull($firstMerchantFeature);

        $this->assertNotNull($secondMerchantFeature);

    }

    public function testEnableEsOnDemandFullAccessWithEsAutomaticRestrictedEnabledFromBatchRoute()
    {
        $this->ba->batchAppAuth();

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->create('merchant', [
            'id'   => '10000000000001'
        ]);

        $this->fixtures->create('schedule', [
            'id'        => 'IaVvw68vQ2lgp2',
            'period'    => 'hourly',
            'interval'  => 1,
            'hour'      => 0,
            'delay'     => 0,
            'type'      => 'settlement'
        ]);


        $this->fixtures->feature->create(
            [
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'name'        => 'es_automatic_restricted'
            ]
        );

        $this->fixtures->create('methods', [
            'merchant_id'    => '10000000000001',
            'card'            => '1',
            'disabled_banks' => [],
            'banks'          => '[]'
        ]);

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->fixtures->merchant->edit('10000000000001',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->startTest();

        $featureConfigs = $this->getEntities(
            'settlement.ondemand.feature_config',
            ['count' => 2],
            true,
            'test');

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000001',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
            'es_pricing_percent'            => 20,
        ], $featureConfigs['items'][0]);

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000000',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
            'es_pricing_percent'            => 12,
        ], $featureConfigs['items'][1]);

        $pricingRule = $this->getDbEntity('pricing',
            [
                'product' => 'primary',
                'feature' =>'settlement_ondemand',
                'plan_id' => '1BFFkd38fFGbnh'
            ],
            'test');

        $this->assertEquals($pricingRule['percent_rate'], 50);

        $firstMerchantFeature = $this->getDbEntity('feature',
            [
                'name'        => 'es_on_demand',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');

        $secondMerchantFeature = $this->getDbEntity('feature',
            [
                'name'        => 'es_on_demand',
                'entity_id'   => '10000000000001',
                'entity_type' => 'merchant'
            ],
            'test');

        $firstMerchantEsAutomaticFeature = $this->getDbEntity('feature',
            [
                'name' => 'es_automatic',
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');


        $secondMerchantEsAutomaticFeature = $this->getDbEntity('feature',
            [
                'name' => 'es_automatic',
                'entity_id' => '10000000000001',
                'entity_type' => 'merchant'
            ],
            'test');

        $scheduledTasks = $this->getLastEntity('schedule_task',true);

        $this->assertArraySelectiveEquals([
            'schedule_id' => 'IaVvw68vQ2lgp2',
        ], $scheduledTasks);

        $this->assertNotNull($firstMerchantFeature);

        $this->assertNotNull($secondMerchantFeature);

        $this->assertNotNull($firstMerchantEsAutomaticFeature);

        $this->assertNull($secondMerchantEsAutomaticFeature);

    }

    public function testDisableOnDemandFullAccessWithEsAutomaticEnabledFromBatchRoute()
    {
        $this->ba->batchAppAuth();

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->create('merchant', [
            'id'   => '10000000000001'
        ]);

        $this->fixtures->create('schedule', [
            'id'        => '10000000000000',
            'period'    => 'hourly',
            'interval'  => 1,
            'hour'      => 0,
            'delay'     => 0,
            'type'      => 'settlement'
        ]);

        $this->fixtures->feature->create(
            [
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'name'        => 'es_on_demand'
            ]
        );

        $this->fixtures->feature->create(
            [
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000001',
                'name'        => 'es_on_demand'
            ]
        );

        $this->fixtures->feature->create(
            [
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'name'        => 'es_automatic'
            ]
        );

        $this->fixtures->create('methods', [
            'merchant_id'    => '10000000000001',
            'card'            => '1',
            'disabled_banks' => [],
            'banks'          => '[]'
        ]);

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->fixtures->merchant->edit('10000000000001',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->startTest();

        $featureConfigs = $this->getEntities(
            'settlement.ondemand.feature_config',
            ['count' => 2],
            true,
            'test');

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000001',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][0]);

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000000',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][1]);

        $pricingRule = $this->getDbEntity('pricing',
            [   'product' => 'primary',
                'feature' =>'settlement_ondemand',
                'plan_id' => '1BFFkd38fFGbnh'
            ],
            'test');

        $this->assertEquals($pricingRule['percent_rate'], 50);

        $firstMerchantFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');

        $secondMerchantFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '10000000000001',
                'entity_type' => 'merchant'
            ],
            'test');

        $this->assertNotNull($firstMerchantFeature);

        $this->assertNotNull($secondMerchantFeature);
    }

    public function testEnableEsOnDemandRestrictedAccessFromBatchRoute()
    {
        $this->ba->batchAppAuth();

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->create('merchant', [
            'id'   => '10000000000001'
        ]);

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->fixtures->merchant->edit('10000000000001',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->startTest();

        $featureConfigs = $this->getEntities(
            'settlement.ondemand.feature_config',
            ['count' => 2],
            true,
            'test');

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000001',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][0]);

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000000',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][1]);

        $pricingRule = $this->getDbEntity('pricing',
            [   'product' => 'primary',
                'feature' =>'settlement_ondemand',
                'plan_id' => '1BFFkd38fFGbnh'
            ],
            'test');

        $this->assertEquals($pricingRule['percent_rate'], 50);

        $firstMerchantOndemandFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');

        $firstMerchantOndemandRestrictedFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');

        $secondMerchantOndemandFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand',
                'entity_id'   => '10000000000001',
                'entity_type' => 'merchant'
            ],
            'test');

        $secondMerchantOndemandRestrictedFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '10000000000001',
                'entity_type' => 'merchant'
            ],
            'test');

        $this->assertNotNull($firstMerchantOndemandFeature);

        $this->assertNotNull($firstMerchantOndemandRestrictedFeature);

        $this->assertNotNull($secondMerchantOndemandRestrictedFeature);

        $this->assertNotNull($secondMerchantOndemandFeature);
    }

    public function testEnableEsOnDemandRestrictedAccessForCrossOrgMerchantFromBatchRoute()
    {
        $orgId = '6dLbNSpv5XbCOG';

        $this->ba->batchAppAuth();

        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->pricing->createPricingPlanForDifferentOrg($orgId);

        $this->fixtures->create('merchant', [
            'id'     => '10000000000001',
            'org_id' => $orgId
        ]);

        $this->fixtures->merchant->edit('10000000000001',
            ['pricing_plan_id' => '1hDYlICxbxOCYx',  'international' => 0, 'org_id' => $orgId]);

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->startTest();

        $featureConfigs = $this->getEntities(
            'settlement.ondemand.feature_config',
            ['count' => 2],
            true,
            'test');

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000001',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][0]);

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000000',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][1]);

        $pricingRule1 = $this->getDbEntity('pricing',
            [   'product' => 'primary',
                'feature' =>'settlement_ondemand',
                'plan_id' => '1BFFkd38fFGbnh'
            ],
            'test');

        $this->assertEquals($pricingRule1['percent_rate'], 50);

        $pricingRule2 = $this->getDbEntity('pricing',
            [   'product' => 'primary',
                'feature' =>'settlement_ondemand',
                'plan_id' => '1hDYlICxbxOCYx'
            ],
            'test');

        $this->assertEquals($pricingRule2['percent_rate'], 50);

        $firstMerchantOndemandFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');

        $firstMerchantOndemandRestrictedFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');

        $secondMerchantOndemandFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand',
                'entity_id'   => '10000000000001',
                'entity_type' => 'merchant'
            ],
            'test');

        $secondMerchantOndemandRestrictedFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '10000000000001',
                'entity_type' => 'merchant'
            ],
            'test');

        $this->assertNotNull($firstMerchantOndemandFeature);

        $this->assertNotNull($firstMerchantOndemandRestrictedFeature);

        $this->assertNotNull($secondMerchantOndemandRestrictedFeature);

        $this->assertNotNull($secondMerchantOndemandFeature);
    }

    public function testUpdateFeatureConfigFromBatchRoute()
    {
        $this->ba->batchAppAuth();

        $this->fixtures->pricing->createStandardPlan();

        $this->fixtures->create('merchant', [
            'id' => '100DemoAccount'
        ]);

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1A0Fkd38fGZPVC',  'international' => 0]);

        $this->fixtures->merchant->edit('100DemoAccount',
            ['pricing_plan_id' => '1A0Fkd38fGZPVC',  'international' => 0]);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '100DemoAccount', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '100DemoAccount', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => '10000000000000',
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 50,
        ]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => '100DemoAccount',
            'percentage_of_balance_limit' => 25,
            'settlements_count_limit'     => 1,
            'max_amount_limit'            => 1000,
            'pricing_percent'             => 50,
        ]);

        $this->startTest();

        $featureConfigs = $this->getEntities(
            'settlement.ondemand.feature_config',
            ['count' => 2],
            true,
            'test');

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '100DemoAccount',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][0]);

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000000',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][1]);

        $pricingRule = $this->getDbEntity('pricing',
            [   //'product' => 'primary',
                'feature' =>'settlement_ondemand',
                'plan_id' => '1A0Fkd38fGZPVC'
            ],
            'test');

        $this->assertEquals($pricingRule['percent_rate'], 50);

        $firstMerchantOndemandRestrictedFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');

        $secondMerchantOndemandRestrictedFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '100DemoAccount',
                'entity_type' => 'merchant'
            ],
            'test');

        $this->assertNull($firstMerchantOndemandRestrictedFeature);

        $this->assertNull($secondMerchantOndemandRestrictedFeature);

    }

    public function testUpdateFeatureConfigForCrossOrgMerchantFromBatchRoute()
    {
        $orgId = '6dLbNSpv5XbCOG';

        $this->ba->batchAppAuth();

        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->pricing->createStandardPlan();

        $this->fixtures->pricing->createPricingPlanForDifferentOrg($orgId);

        $this->fixtures->create('pricing',[
            'id'                  => '1zE3CYqf1zbyaa',
            'plan_id'             => '1hDYlICxbxOCYx',
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'settlement_ondemand',
            'payment_method'      => 'fund_transfer',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 25,
            'fixed_rate'          => 0,
            'org_id'              => $orgId,
        ]);

        $this->fixtures->create('merchant', [
            'id'     => '100DemoAccount',
            'org_id' => $orgId
        ]);

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1A0Fkd38fGZPVC',  'international' => 0]);

        $this->fixtures->merchant->edit('100DemoAccount',
            ['pricing_plan_id' => '1hDYlICxbxOCYx',  'international' => 0, 'org_id' => $orgId]);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '100DemoAccount', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '100DemoAccount', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => '10000000000000',
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 18,
        ]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => '100DemoAccount',
            'percentage_of_balance_limit' => 25,
            'settlements_count_limit'     => 1,
            'max_amount_limit'            => 1000,
            'pricing_percent'             => 25,
        ]);

        $this->startTest();

        $featureConfigs = $this->getEntities(
            'settlement.ondemand.feature_config',
            ['count' => 2],
            true,
            'test');

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '100DemoAccount',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][0]);

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000000',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][1]);

        $pricingRule = $this->getDbEntity('pricing',
            [   //'product' => 'primary',
                'feature' =>'settlement_ondemand',
                'plan_id' => '1A0Fkd38fGZPVC'
            ],
            'test');

        $this->assertEquals($pricingRule['percent_rate'], 50);

        $firstMerchantOndemandRestrictedFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');

        $secondMerchantOndemandRestrictedFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '100DemoAccount',
                'entity_type' => 'merchant'
            ],
            'test');

        $this->assertNull($firstMerchantOndemandRestrictedFeature);

        $this->assertNull($secondMerchantOndemandRestrictedFeature);

    }

    public function testEnableEsOnDemandRestrictedAccessFromBatchRouteFailure()
    {
        $this->ba->batchAppAuth();

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->create('merchant', [
            'id'   => '10000000000001'
        ]);

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->startTest();

        $featureConfigs = $this->getEntities(
            'settlement.ondemand.feature_config',
            ['count' => 1],
            true,
            'test');

        $this->assertArraySelectiveEquals([
            'merchant_id'                   => '10000000000000',
            'percentage_of_balance_limit'   => 50,
            'settlements_count_limit'       => 2,
            'max_amount_limit'              => 2000000,
            'pricing_percent'               => 50,
        ], $featureConfigs['items'][0]);

        $pricingRule = $this->getDbEntity('pricing',
            [   'product' => 'primary',
                'feature' =>'settlement_ondemand',
                'plan_id' => '1BFFkd38fFGbnh'
            ],
            'test');

        $this->assertEquals($pricingRule['percent_rate'], 50);

        $firstMerchantOndemandFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');

        $firstMerchantOndemandRestrictedFeature = $this->getDbEntity('feature',
            [   'name'        => 'es_on_demand_restricted',
                'entity_id'   => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'test');


        $this->assertNotNull($firstMerchantOndemandFeature);

        $this->assertNotNull($firstMerchantOndemandRestrictedFeature);
    }

    public function testOndemandCreationWithLimitExceededError()
    {
        $this->ba->proxyAuth('rzp_live_' . $this->merchantDetail['merchant_id']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => '10000000000000',
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 50,
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 250,
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 250,
        ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000]);

        $this->startTest();
    }

    public function testOndemandCreationWithAmountExceededError()
    {
        $this->ba->proxyAuth('rzp_live_' . $this->merchantDetail['merchant_id']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => '10000000000000',
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 3,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 50,
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 5000,
        ]);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 2300,
        ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000]);

        $this->startTest();
    }

    public function testOndemandCreationWithNoError()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 5000,
        ]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'amount'                      => 2500,
        ]);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000]);

        $this->startTest();
    }

    //Test OndemandCreation on banking hours with no mock webhook
    public function testBankingHourOndemandCreation()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

        $txn = $this->getLastEntity('transaction',true);

        $this->assertArraySelectiveEquals([
        //             'id'                    => 'txn_F1aL3bkJd2t5fK',
        //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                       'type'                  => 'settlement.ondemand',
                       'merchant_id'           => '10000000000000',
                       'amount'                => 48849292,
                       'fee'                   => 1180708,
                       'tax'                   => 180108,
                       'debit'                 => 50030000,
                       'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                               ], $txn);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
 //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 50030000,
            'total_amount_settled'  => 0,
            'total_fees'            => 1180708,
            'total_tax'             => 180108,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 48849292,
            'currency'              => 'INR',
            'status'                => 'initiated',
            'narration'             => 'Demo Narration - optional',
            'notes'                 => [
                                            'key1' => 'note3',
                                            'key2' => 'note5',
                                        ],
//            'transaction_id'        => 'F0qG7d1MGiNbmc',
            'transaction_type'      => 'transaction',
//            'created_at'            => 1582000200,
                    ], $settlementOndemand);

        $settlementOndemandPayout = $this->getLastEntity('settlement.ondemand_payout',true);

        $this->assertArraySelectiveEquals([
//          'id'             => 'sodp_F0qiDKkbH2QRRR',
            'merchant_id'    => '10000000000000',
            'user_id'        =>'20000000000000',
//          'ondemand_id'    => 'F0qiDHrgiKpZJi',
//          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
            'mode'           => 'IMPS',
//          'initiated_at'   => 1582000200,
            'processed_at'   => NULL,
            'reversed_at'    => NULL,
            'fees'           => 708,
            'tax'            => 108,
            'utr'            => NULL,
            'status'         => 'initiated',
            'amount'         => 30000,
            'failure_reason' => NULL,
//          'created_at'     => 1582000200,
                    ], $settlementOndemandPayout);
    }

    public function testFetchApi()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 100000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        $this->makeRequestAndGetContent($this->testData['testNonBankingHourOndemandCreationWithMockWebhook']['request']);

        $this->makeRequestAndGetContent($this->testData['testNonBankingHourOndemandCreationWithPartialReversal']['request']);

        $key = $this->fixtures->create('key', ['merchant_id' => $this->merchantDetail['merchant_id']]);

        $key = $key->getKey();

        $this->ba->privateAuth('rzp_test_' . $key);

        $this->startTest();
    }

    public function testFetchApiWithStatus()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());
        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create(['entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 100000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        $this->makeRequestAndGetContent($this->testData['testNonBankingHourOndemandCreationWithMockWebhook']['request']);

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', true);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', true);
        $this->makeRequestAndGetContent($this->testData['testBankingHourOndemandCreationWithMockWebhook']['request']);

        $key = $this->fixtures->create('key', ['merchant_id' => $this->merchantDetail['merchant_id']]);

        $key = $key->getKey();

        $this->ba->privateAuth('rzp_test_' . $key);

        $this->startTest();
    }

    //Test OndemandCreation on banking hours with mock webhook update status
    public function testBankingHourOndemandCreationWithMockWebhook()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', true);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', true);

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

        $txn = $this->getLastEntity('transaction',true);

        $this->assertArraySelectiveEquals([
        //             'id'                    => 'txn_F1aL3bkJd2t5fK',
        //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                       'type'                  => 'settlement.ondemand',
                       'merchant_id'           => '10000000000000',
                       'amount'                => 48849292,
                       'fee'                   => 1180708,
                       'tax'                   => 180108,
                       'debit'                 => 50030000,
                       'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                               ], $txn);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
 //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 50030000,
            'total_amount_settled'  => 48849292,
            'total_fees'            => 1180708,
            'total_tax'             => 180108,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 0,
            'currency'              => 'INR',
            'status'                => 'processed',
            'narration'             => 'Demo Narration - optional',
//            'transaction_id'        => 'F0qG7d1MGiNbmc',
            'transaction_type'      => 'transaction',
//            'created_at'            => 1582000200,
                    ], $settlementOndemand);

        $settlementOndemandPayout = $this->getLastEntity('settlement.ondemand_payout',true);

        $this->assertNotEmpty($settlementOndemandPayout['payout_id']);
        $this->assertNotEmpty($settlementOndemandPayout['processed_at']);
        $this->assertNotEmpty($settlementOndemandPayout['utr']);

        $this->assertArraySelectiveEquals([
//          'id'             => 'sodp_F0qiDKkbH2QRRR',
            'merchant_id'    => '10000000000000',
            'user_id'        =>'20000000000000',
//          'ondemand_id'    => 'F0qiDHrgiKpZJi',
//          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
            'mode'           => 'IMPS',
//          'initiated_at'   => 1582000200,
//           'processed_at'   => 1582000200,
            'reversed_at'    => NULL,
            'fees'           => 708,
            'tax'            => 108,
            // 'utr'            => 'qwer12uijaaasssd',
            'status'         => 'processed',
            'amount'         => 30000,
            'failure_reason' => NULL,
//          'created_at'     => 1582000200,
                    ], $settlementOndemandPayout);
    }

    //Test OndemandCreation on banking hours with mock webhook update status
    public function testBankingHourOndemandCreationWithReversal()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', true);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', true);

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

        $transactions = $this->getEntities('transaction', ['count' => 2], true);

        if ($transactions['items'][0]['type'] === 'reversal')
        {
            $reversalTxn = $transactions['items'][0];
            $settlementOndemandTxn = $transactions['items'][1];
        }
        else
        {
            $reversalTxn = $transactions['items'][1];
            $settlementOndemandTxn = $transactions['items'][0];
        }

        $this->assertArraySelectiveEquals([
        //             'id'                    => 'txn_F1aL3bkJd2t5fK',
        //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                        'type'                  => 'reversal',
                        'merchant_id'           => '10000000000000',
                        'amount'                => 220000,
                        'fee'                   => 0,
                        'tax'                   => 0,
                        'credit'                 => 220000,
                        'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                                ], $reversalTxn);

        $this->assertArraySelectiveEquals([
        //             'id'                    => 'txn_F1aL3bkJd2t5fK',
        //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                        'type'                  => 'settlement.ondemand',
                        'merchant_id'           => '10000000000000',
                        'amount'                => 214808,
                        'fee'                   => 5192,
                        'tax'                   => 792,
                        'debit'                 => 220000,
                        'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                                ], $settlementOndemandTxn);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertArraySelectiveEquals([
            //      'id'                    => 'rvrsl_F2enlXFQyGJqje',
                    'merchant_id'           => '10000000000000',
                    'amount'                => 220000,
                    'entity_type'           => 'settlement.ondemand',
                    'fee'                   => 0,
                    'tax'                   => 0,
                    'currency'              => 'INR',
        //          'created_at'            => 1582000200,
                            ], $reversal);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
    //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 220000,
            'total_amount_settled'  => 0,
            'total_fees'            => 0,
            'total_tax'             => 0,
            'total_amount_reversed' => 220000,
            'total_amount_pending'  => 0,
            'max_balance'           => false,
            'currency'              => 'INR',
            'status'                => 'reversed',
            'narration'             => 'Demo Narration - optional',
//            'transaction_id'        => 'F0qG7d1MGiNbmc',
            'transaction_type'      => 'transaction',
//            'created_at'            => 1582000200,
                    ], $settlementOndemand);

        $settlementOndemandPayout = $this->getLastEntity('settlement.ondemand_payout',true);

        $this->assertArraySelectiveEquals([
//          'id'             => 'sodp_F0qiDKkbH2QRRR',
            'merchant_id'    => '10000000000000',
            'user_id'        =>'20000000000000',
//          'ondemand_id'    => 'F0qiDHrgiKpZJi',
//          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
            'mode'           => 'IMPS',
//          'initiated_at'   => 1582000200,
//          'processed_at'   => 1582000200,
//          'reversed_at'    => 1582000200,
            'fees'           => 5192,
            'tax'            => 792,
            'utr'            => NULL,
            'status'         => 'reversed',
            'amount'         => 220000,
            'failure_reason' => 'dummy_reason',
//          'created_at'     => 1582000200,
                    ], $settlementOndemandPayout);
    }

    public function testBankingHourOndemandCreationWithProcessedPayout()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', true);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', true);

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

        $ondemandpayout = $this->getLastEntity('settlement.ondemand_payout',true);

        dispatch_now(new RequestOndemandPayout(Mode::TEST, substr($ondemandpayout['id'], -14, 14),
            $this->merchantDetail['merchant_id'], 'inr'));

        $reversal = $this->getDbEntity('reversal');

        $this->assertNull($reversal);
    }

    public function testBankingHourOndemandCreationWithReversedPayoutResponse()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

        $ondemandpayout = $this->getLastEntity('settlement.ondemand_payout',true);

        dispatch_now(new RequestOndemandPayout(Mode::TEST, substr($ondemandpayout['id'], -14, 14),
            $this->merchantDetail['merchant_id'], 'inr'));

        $reversals = $this->getDbEntities('reversal');

        $this->assertEquals(1, $reversals->count());
    }

    public function testOndemandCreationBankingHour()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

        $settlementOndemand = $this->getLastEntity('settlement.ondemand', true);

        $this->assertArraySelectiveEquals([
 //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 50030000,
            'total_amount_settled'  => 0,
            'total_fees'            => 1180708,
            'total_tax'             => 180108,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 48849292,
            'max_balance'           => false,
            'currency'              => 'INR',
            'status'                => 'initiated',
            'narration'             => 'Demo Narration - optional',
//            'transaction_id'        => 'F0qG7d1MGiNbmc',
            'transaction_type'      => 'transaction',
//            'created_at'            => 1582000200,
                    ], $settlementOndemand);

        $settlementOndemandPayouts = $this->getEntities('settlement.ondemand_payout', ['count' => 2], true);

        $this->assertArraySelectiveEquals([
            //          'id'             => 'sodp_F0qiDKkbH2QRRR',
                        'merchant_id'    => '10000000000000',
                        'user_id'        =>'20000000000000',
            //          'ondemand_id'    => 'F0qiDHrgiKpZJi',
            //          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
                        'mode'           => 'IMPS',
            //          'initiated_at'   => 1582000200,
                        'processed_at'   => NULL,
                        'reversed_at'    => NULL,
                        'fees'           => 708,
                        'tax'            => 108,
                        'utr'            => NULL,
                        'status'         => 'initiated',
                        'amount'         => 30000,
                        'failure_reason' => NULL,
            //          'created_at'     => 1582000200,
                                ], $settlementOndemandPayouts['items'][0]);

        $this->assertArraySelectiveEquals([
            //          'id'             => 'sodp_F0qiDKkbH2QRRR',
                        'merchant_id'    => '10000000000000',
                        'user_id'        =>'20000000000000',
            //          'ondemand_id'    => 'F0qiDHrgiKpZJi',
            //          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
                        'mode'           => 'IMPS',
            //          'initiated_at'   => 1582000200,
                        'processed_at'   => NULL,
                        'reversed_at'    => NULL,
                        'fees'           => 1180000,
                        'tax'            => 180000,
                        'utr'            => NULL,
                        'status'         => 'initiated',
                        'amount'         => 50000000,
                        'failure_reason' => NULL,
            //          'created_at'     => 1582000200,
                                ], $settlementOndemandPayouts['items'][1]);
    }

    public function testNonBankingHourOndemandCreationWithMockWebhook()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', true);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', true);

        // Force setting non banking hour for non banking hour test.
        $nonBankingHour = Carbon::create(2020, 2, 18, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($nonBankingHour);

        $this->startTest();

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
 //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 50030000,
            'total_amount_settled'  => 48849292,
            'total_fees'            => 1180708,
            'total_tax'             => 180108,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 0,
            'max_balance'           => false,
            'currency'              => 'INR',
            'status'                => 'processed',
            'narration'             => 'Demo Narration - optional',
//            'transaction_id'        => 'F0qG7d1MGiNbmc',
            'transaction_type'      => 'transaction',
//            'created_at'            => 1582000200,
                    ], $settlementOndemand);

        $settlementOndemandPayouts = $this->getEntities('settlement.ondemand_payout', ['count' => 2], true);

        $this->assertNotEmpty($settlementOndemandPayouts['items'][0]['utr']);

        $this->assertArraySelectiveEquals([
            //          'id'             => 'sodp_F0qiDKkbH2QRRR',
                        'merchant_id'    => '10000000000000',
                        'user_id'        =>'20000000000000',
            //          'ondemand_id'    => 'F0qiDHrgiKpZJi',
            //          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
                        'mode'           => 'IMPS',
            //          'initiated_at'   => 1582000200,
            //           'processed_at'   => 1582036200,
                        'reversed_at'    => NULL,
                        'fees'           => 708,
                        'tax'            => 108,
                        // 'utr'            => 'qwer12uijaaasssd',
                        'status'         => 'processed',
                        'amount'         => 30000,
                        'failure_reason' => NULL,
            //          'created_at'     => 1582000200,
                                ], $settlementOndemandPayouts['items'][0]);

        $this->assertNotEmpty($settlementOndemandPayouts['items'][1]['utr']);

        $this->assertArraySelectiveEquals([
            //          'id'             => 'sodp_F0qiDKkbH2QRRR',
                        'merchant_id'    => '10000000000000',
                        'user_id'        =>'20000000000000',
            //          'ondemand_id'    => 'F0qiDHrgiKpZJi',
            //          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
                        'mode'           => 'IMPS',
            //          'initiated_at'   => 1582000200,
            //          'processed_at'   => 1582000200,
                        'reversed_at'    => NULL,
                        'fees'           => 1180000,
                        'tax'            => 180000,
                        // 'utr'            => 'qwer12uijaaasssd',
                        'status'         => 'processed',
                        'amount'         => 50000000,
                        'failure_reason' => NULL,
            //          'created_at'     => 1582000200,
                                ], $settlementOndemandPayouts['items'][1]);
    }

    public function testNonBankingHourOndemandCreationWithPartialReversal()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', true);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', true);

        // Force setting non banking hour for non banking hour test.
        $nonBankingHour = Carbon::create(2020, 2, 18, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($nonBankingHour);

        $this->startTest();

        $transactions = $this->getEntities('transaction', ['count' => 3], true);

        $reversal = $this->getLastEntity('reversal',true);

        if ($transactions['items'][0]['type'] === 'reversal')
        {
            $reversalTxn = $transactions['items'][0];
            $settlementOndemandTxn = $transactions['items'][1];
        }
        else if ($transactions['items'][1]['type'] === 'reversal')
        {
            $reversalTxn = $transactions['items'][1];
            $settlementOndemandTxn = $transactions['items'][0];
        }
        else
        {
            $reversalTxn = $transactions['items'][2];
            $settlementOndemandTxn = $transactions['items'][0];
        }

        $this->assertArraySelectiveEquals([
            //             'id'                    => 'txn_F1aL3bkJd2t5fK',
            //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                            'type'                  => 'reversal',
                            'merchant_id'           => '10000000000000',
                            'amount'                => 220000,
                            'fee'                   => 0,
                            'tax'                   => 0,
                            'credit'                 => 220000,
                            'currency'              => 'INR',
            //             'settled_at'            => '1582000200',
            //             'created_at'          => 1582000200,
                                    ], $reversalTxn);

        $this->assertArraySelectiveEquals([
        //             'id'                    => 'txn_F1aL3bkJd2t5fK',
        //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                        'type'                  => 'settlement.ondemand',
                        'merchant_id'           => '10000000000000',
                        'amount'                => 49034808,
                        'fee'                   => 1185192,
                        'tax'                   => 180792,
                        'debit'                 => 50220000,
                        'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                                ], $settlementOndemandTxn);

        $this->assertArraySelectiveEquals([
            //      'id'                    => 'rvrsl_F2enlXFQyGJqje',
                    'merchant_id'           => '10000000000000',
                    'amount'                => 220000,
                    'entity_type'           => 'settlement.ondemand',
                    'fee'                  => 0,
                    'tax'                   => 0,
                    'currency'              => 'INR',
        //          'created_at'            => 1582000200,
                            ], $reversal);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
 //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 50220000,
            'total_amount_settled'  => 48820000,
            'total_fees'            => 1180000,
            'total_tax'             => 180000,
            'total_amount_reversed' => 220000,
            'total_amount_pending'  => 0,
            'max_balance'           => false,
            'currency'              => 'INR',
            'status'                => 'partially_processed',
            'narration'             => 'Demo Narration - optional',
//            'transaction_id'        => 'F0qG7d1MGiNbmc',
            'transaction_type'      => 'transaction',
//            'created_at'            => 1582000200,
                    ], $settlementOndemand);

        $settlementOndemandPayouts = $this->getEntities('settlement.ondemand_payout', ['count' => 2], true);

        $this->assertArraySelectiveEquals([
            //          'id'             => 'sodp_F0qiDKkbH2QRRR',
                        'merchant_id'    => '10000000000000',
                        'user_id'        =>'20000000000000',
            //          'ondemand_id'    => 'F0qiDHrgiKpZJi',
            //          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
                        'mode'           => 'IMPS',
            //          'initiated_at'   => 1582000200,
            //          'processed_at'   => 1582036200,
            //          'reversed_at'    => 1582036200,
                        'fees'           => 5192,
                        'tax'            => 792,
                        'utr'            => null,
                        'status'         => 'reversed',
                        'amount'         => 220000,
                        'failure_reason' => 'dummy_reason',
            //          'created_at'     => 1582000200,
                                ], $settlementOndemandPayouts['items'][0]);

        $this->assertNotEmpty($settlementOndemandPayouts['items'][1]['utr']);

        $this->assertArraySelectiveEquals([
            //          'id'             => 'sodp_F0qiDKkbH2QRRR',
                        'merchant_id'    => '10000000000000',
                        'user_id'        =>'20000000000000',
            //          'ondemand_id'    => 'F0qiDHrgiKpZJi',
            //          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
                        'mode'           => 'IMPS',
            //          'initiated_at'   => 1582000200,
            //          'processed_at'   => 1582000200,
                        'reversed_at'    => NULL,
                        'fees'           => 1180000,
                        'tax'            => 180000,
                        // 'utr'            => 'qwer12uijaaasssd',
                        'status'         => 'processed',
                        'amount'         => 50000000,
                        'failure_reason' => null,
            //          'created_at'     => 1582000200,
                                ], $settlementOndemandPayouts['items'][1]);
    }

    //merchant request with max_balance as 1
    public function testCreateOndemandForMaxBalance()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 20030000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();
    }

    //merchant request with max_balance as 1
    public function testCreateOndemandForMaxBalanceWhenDisabledByCollectionsForLoc()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'disable_ondemand_for_loc']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 20030000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();
    }

    //merchant request with max_balance as 1
    public function testCreateOndemandForMaxBalanceWhenDisabledByCollectionsForLoan()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'disable_ondemand_for_loan']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 20030000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();
    }

    //merchant request with max_balance as 1
    public function testCreateOndemandForMaxBalanceWhenDisabledByCollectionsForCard()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'disable_ondemand_for_card']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 20030000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        //set time as banking hour for testing
        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();
    }

    //merchant request amount > balance
    public function testCreateOndemandOnLowBalance()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 100]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->startTest();
    }

    //merchant request amount > 2cr
    public function testCreateOndemandGreaterThanMaxLimit()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 3000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->startTest();
    }

    //Creating Ondemand for merchant whose funds are on hold
    public function testCreateOndemandForFundsOnHoldMerchant()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 23,
        ]);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_automatic']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 3000000]);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['hold_funds' => true]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->startTest();
    }

    //test ondemand creation for fixed_rate pricing plan
    public function testOndemandCreationForFixedRatePricing()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandFixedRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        // Force setting non banking hour for non banking hour test.
        $nonBankingHour = Carbon::create(2020, 2, 18, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($nonBankingHour);

        $this->startTest();

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
 //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 50030000,
            'total_amount_settled'  => 0,
            'total_fees'            => 1180,
            'total_tax'             => 180,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 50028820,
            'max_balance'           => false,
            'currency'              => 'INR',
            'status'                => 'initiated',
            'narration'             => 'Demo Narration - optional',
//            'transaction_id'        => 'F0qG7d1MGiNbmc',
            'transaction_type'      => 'transaction',
//            'created_at'            => 1582000200,
                    ], $settlementOndemand);

        $settlementOndemandPayouts = $this->getEntities('settlement.ondemand_payout', ['count' => 2], true);

        $this->assertArraySelectiveEquals([
            //          'id'             => 'sodp_F0qiDKkbH2QRRR',
                        'merchant_id'    => '10000000000000',
                        'user_id'        =>'20000000000000',
            //          'ondemand_id'    => 'F0qiDHrgiKpZJi',
            //          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
                        'mode'           => 'IMPS',
            //          'initiated_at'   => 1582000200,
                        'processed_at'   => NULL,
                        'reversed_at'    => NULL,
                        'fees'           => 590,
                        'tax'            => 90,
                        'utr'            => NULL,
                        'status'         => 'initiated',
                        'amount'         => 30000,
                        'failure_reason' => NULL,
            //          'created_at'     => 1582000200,
                                ], $settlementOndemandPayouts['items'][0]);

        $this->assertArraySelectiveEquals([
            //          'id'             => 'sodp_F0qiDKkbH2QRRR',
                        'merchant_id'    => '10000000000000',
                        'user_id'        =>'20000000000000',
            //          'ondemand_id'    => 'F0qiDHrgiKpZJi',
            //          'payout_id'      => 'pout_F0qiDcJxmKpuOJ',
                        'mode'           => 'IMPS',
            //          'initiated_at'   => 1582000200,
                        'processed_at'   => NULL,
                        'reversed_at'    => NULL,
                        'fees'           => 590,
                        'tax'            => 90,
                        'utr'            => NULL,
                        'status'         => 'initiated',
                        'amount'         => 50000000,
                        'failure_reason' => NULL,
            //          'created_at'     => 1582000200,
                                ], $settlementOndemandPayouts['items'][1]);
    }

    public function testOndemandFees()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 20030000]);

       $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1hDYlICobzOCYt',  'international' => 0]);

        $this->startTest();
    }

    public function testOndemandDay1FeesWithPricingInConfig()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 20030000]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 23,
        ]);

        $this->startTest();

        $pricingRule = $this->getDbEntity('pricing',
            [   'product' => 'primary',
                'feature' => 'settlement_ondemand',
                'plan_id' => '1BFFkd38fFGbnh'
            ],
            'test');

        $this->assertEquals($pricingRule['percent_rate'], 23);

    }

    public function testOndemandDay1FeesWithNoPricingInConfig()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand_restricted']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 20030000]);

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 7500,
        ]);

        $this->startTest();

        $pricingRule = $this->getDbEntity('pricing',
            [   'product' => 'primary',
                'feature' => 'settlement_ondemand',
                'plan_id' => '1BFFkd38fFGbnh'
            ],
            'test');

        $this->assertEquals($pricingRule['percent_rate'], 30);

    }

    public function testOndemandFeesWithNoPricing()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international' => 0]);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 20030000]);

        $this->startTest();

        $pricingRule = $this->getDbEntity('pricing',
            [   'product' => 'primary',
                'feature' => 'settlement_ondemand',
                'plan_id' => '1BFFkd38fFGbnh'
            ],
            'test');

        $this->assertEquals($pricingRule['percent_rate'], 25);

    }

    public function testOndemandFeesForFixedRate()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'updated_imps_ondemand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 50030000]);

        $this->fixtures->pricing->createOndemandFixedRatePricingPlan();

        $this->fixtures->merchant->edit('10000000000000',
            ['pricing_plan_id' => '1hDYlICobzOCYt',  'international' => 0]);

        // Force setting non banking hour for non banking hour test.
        $nonBankingHour = Carbon::create(2020, 2, 18, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($nonBankingHour);

        $this->startTest();
    }

    public function testAdjustmentAditionToOndemandXMerchant()
    {
        $this->ba->proxyAuth('rzp_live_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->on(Mode::LIVE)->create('settlement.ondemand.feature_config',[
            'merchant_id'                 => $this->merchantDetail['merchant_id'],
            'percentage_of_balance_limit' => 50,
            'settlements_count_limit'     => 2,
            'max_amount_limit'            => 7500,
            'pricing_percent'             => 23,
        ]);

        $this->fixtures->on('live')->edit('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->merchant->editPricingPlanId(Pricing::DEFAULT_PRICING_PLAN_ID);

        $this->fixtures->on('live')->create('balance', [
            'id'             => '10000SampleBal',
            'account_number' => '2323230041626905',
            'type'           => 'banking',
            'merchant_id'    => '10000000000000',
            'currency'       => 'INR',
            'balance'        => 0,
        ]);

        $this->fixtures->on('live')->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.live.mock', true);

        $this->app['config']->set('applications.razorpayx_client.live.ondemand_x_merchant.id', '10000000000000');

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

        $adjustment = $this->getLastEntity('adjustment', false, 'live');

        $this->assertArraySelectiveEquals([
//            'id'             => 'adj_FBzOs9JIQYrwT2',
            'entity'         => 'adjustment',
            'amount'         => 19557292,
            'currency'       => 'INR',
//            'description'    => 'adding funds to Ondemand-X merchant for OndemandID - FC13NuI1Niv5FB',
//            'transaction_id' => 'FBzOsAMF80GFWt',
        ], $adjustment);
    }

    public function testMinLimitForNonEsAutomaticMerchants()
    {
        $this->markTestSkipped('2000 limit removed');

        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->startTest();
    }

    public function testAddOndemandPricingIfAbsent()
    {
        $this->ba->adminAuth();

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1BFFkd38fFGbnh', 'international'    => 0]);

        $merchant2 = $this->fixtures->create('merchant');

        $merchant2Id = $merchant2['id'];

        $this->fixtures->merchant->edit($merchant2Id, ['pricing_plan_id' => '1BFFkd38fFGbnh', 'international' => 0]);

        $this->startTest();

        $newPricingPlanId =  $this->getDbEntities('merchant', ['id' => $this->merchantDetail['merchant_id']])->toArray()[0]['pricing_plan_id'];

        $settlementOndemandPricingRule = $this->getDbEntities('pricing', ['feature'        => 'settlement_ondemand',
                                                                          'payment_method' => 'fund_transfer',
                                                                          'plan_id'        => '1BFFkd38fFGbnh'])->toArray();

        $ondemandPayoutPricingRule = $this->getDbEntities('pricing', ['feature'        => 'payout',
                                                                      'payment_method' => 'fund_transfer',
                                                                      'plan_id'        => '1BFFkd38fFGbnh'])->toArray();

        $this->assertEmpty($settlementOndemandPricingRule);

        $this->assertEmpty($ondemandPayoutPricingRule);

        $newSettlementOndemandPricingRule = $this->getDbEntities('pricing', ['product'        =>  'primary',
                                                                             'feature'        => 'settlement_ondemand',
                                                                             'payment_method' => 'fund_transfer',
                                                                             'percent_rate'   => 25,])->toArray();

        $newOndemandPayoutPricingRule = $this->getDbEntities('pricing', ['product'        =>  'primary',
                                                                         'feature'        => 'payout',
                                                                         'payment_method' => 'fund_transfer',
                                                                         'percent_rate'   => 25,])->toArray();

        $this->assertNotEmpty($newSettlementOndemandPricingRule);

        $this->assertNotEmpty($newOndemandPayoutPricingRule);
    }

    public function testNoMinLimitFormEsAutomaticMerchants()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_automatic']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 10000000000]);

        $this->fixtures->pricing->createOndemandPercentRatePricingPlan();

        $this->app['config']->set('applications.razorpayx_client.test.mock_webhook', false);

        $this->app['config']->set('applications.razorpayx_client.live.mock_webhook', false);

        $bankingHour = Carbon::create(2020, 2, 18, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($bankingHour);

        $this->startTest();

    }
}
