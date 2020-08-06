<?php

namespace RZP\Tests\Functional\SettlementOndemand;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Base\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Services\Mock\RazorpayXClient;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class SettlementOndemandTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $reportUrl;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/SettlementOndemandTestData.php';

        parent::setUp();

        $this->merchantDetail = $this->fixtures->create('merchant_detail:sane', [
            'merchant_id'   => '10000000000000',
            'contact_name'  => 'dummy_name',
            'contact_email' => 'test@gmail.com',
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

    //Test OndemandCreation on banking hours with no mock webhook
    public function testBankingHourOndemandCreation()
    {
        $this->markTestSkipped();

        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

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
                       'amount'                => 19557292,
                       'fee'                   => 472708,
                       'tax'                   => 72108,
                       'debit'                 => 20030000,
                       'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                               ], $txn);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
 //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 20030000,
            'total_amount_settled'  => 0,
            'total_fees'            => 472708,
            'total_tax'             => 72108,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 19557292,
            'max_balance'           => 0,
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
            'mode'           => 'NEFT',
//          'initiated_at'   => 1582000200,
            'processed_at'   => NULL,
            'reversed_at'    => NULL,
            'fees'           => 472708,
            'tax'            => 72108,
            'utr'            => NULL,
            'status'         => 'initiated',
            'amount'         => 20030000,
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

    //Test OndemandCreation on banking hours with mock webhook update status
    public function testBankingHourOndemandCreationWithMockWebhook()
    {

        $this->markTestSkipped();

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

        $txn = $this->getLastEntity('transaction',true);

        $this->assertArraySelectiveEquals([
        //             'id'                    => 'txn_F1aL3bkJd2t5fK',
        //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                       'type'                  => 'settlement.ondemand',
                       'merchant_id'           => '10000000000000',
                       'amount'                => 19557292,
                       'fee'                   => 472708,
                       'tax'                   => 72108,
                       'debit'                 => 20030000,
                       'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                               ], $txn);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
 //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 20030000,
            'total_amount_settled'  => 19557292,
            'total_fees'            => 472708,
            'total_tax'             => 72108,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 0,
            'max_balance'           => 0,
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
            'mode'           => 'NEFT',
//          'initiated_at'   => 1582000200,
//           'processed_at'   => 1582000200,
            'reversed_at'    => NULL,
            'fees'           => 472708,
            'tax'            => 72108,
            // 'utr'            => 'qwer12uijaaasssd',
            'status'         => 'processed',
            'amount'         => 20030000,
            'failure_reason' => NULL,
//          'created_at'     => 1582000200,
                    ], $settlementOndemandPayout);
    }

    //Test OndemandCreation on banking hours with mock webhook update status
    public function testBankingHourOndemandCreationWithReversal()
    {
        $this->markTestSkipped();

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
                        'amount'                => 110000,
                        'fee'                   => 0,
                        'tax'                   => 0,
                        'credit'                 => 110000,
                        'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                                ], $reversalTxn);

        $this->assertArraySelectiveEquals([
        //             'id'                    => 'txn_F1aL3bkJd2t5fK',
        //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                        'type'                  => 'settlement.ondemand',
                        'merchant_id'           => '10000000000000',
                        'amount'                => 107404,
                        'fee'                   => 2596,
                        'tax'                   => 396,
                        'debit'                 => 110000,
                        'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                                ], $settlementOndemandTxn);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertArraySelectiveEquals([
            //      'id'                    => 'rvrsl_F2enlXFQyGJqje',
                    'merchant_id'           => '10000000000000',
                    'amount'                => 110000,
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
            'amount'                => 110000,
            'total_amount_settled'  => 0,
            'total_fees'            => 0,
            'total_tax'             => 0,
            'total_amount_reversed' => 110000,
            'total_amount_pending'  => 0,
            'max_balance'           => 0,
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
            'mode'           => 'NEFT',
//          'initiated_at'   => 1582000200,
//          'processed_at'   => 1582000200,
//          'reversed_at'    => 1582000200,
            'fees'           => 2596,
            'tax'            => 396,
            'utr'            => NULL,
            'status'         => 'reversed',
            'amount'         => 110000,
            'failure_reason' => 'dummy_reason',
//          'created_at'     => 1582000200,
                    ], $settlementOndemandPayout);
    }

    public function testOndemandCreationBankingHour()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->on(Mode::TEST)->create('settlement.ondemand_fund_account');

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

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
            'amount'                => 20030000,
            'total_amount_settled'  => 0,
            'total_fees'            => 472708,
            'total_tax'             => 72108,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 19557292,
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
                        'fees'           => 472000,
                        'tax'            => 72000,
                        'utr'            => NULL,
                        'status'         => 'initiated',
                        'amount'         => 20000000,
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
            'amount'                => 20030000,
            'total_amount_settled'  => 19557292,
            'total_fees'            => 472708,
            'total_tax'             => 72108,
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
                        'fees'           => 472000,
                        'tax'            => 72000,
                        // 'utr'            => 'qwer12uijaaasssd',
                        'status'         => 'processed',
                        'amount'         => 20000000,
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
                            'amount'                => 110000,
                            'fee'                   => 0,
                            'tax'                   => 0,
                            'credit'                 => 110000,
                            'currency'              => 'INR',
            //             'settled_at'            => '1582000200',
            //             'created_at'          => 1582000200,
                                    ], $reversalTxn);

        $this->assertArraySelectiveEquals([
        //             'id'                    => 'txn_F1aL3bkJd2t5fK',
        //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                        'type'                  => 'settlement.ondemand',
                        'merchant_id'           => '10000000000000',
                        'amount'                => 19635404,
                        'fee'                   => 474596,
                        'tax'                   => 72396,
                        'debit'                 => 20110000,
                        'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                                ], $settlementOndemandTxn);

        $this->assertArraySelectiveEquals([
            //      'id'                    => 'rvrsl_F2enlXFQyGJqje',
                    'merchant_id'           => '10000000000000',
                    'amount'                => 110000,
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
            'amount'                => 20110000,
            'total_amount_settled'  => 19528000,
            'total_fees'            => 472000,
            'total_tax'             => 72000,
            'total_amount_reversed' => 110000,
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
                        'fees'           => 2596,
                        'tax'            => 396,
                        'utr'            => null,
                        'status'         => 'reversed',
                        'amount'         => 110000,
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
                        'fees'           => 472000,
                        'tax'            => 72000,
                        // 'utr'            => 'qwer12uijaaasssd',
                        'status'         => 'processed',
                        'amount'         => 20000000,
                        'failure_reason' => null,
            //          'created_at'     => 1582000200,
                                ], $settlementOndemandPayouts['items'][1]);
    }

    //merchant request with max_balance as 1
    public function testCreateOndemandForMaxBalance()
    {
        $this->markTestSkipped();

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

        $txn = $this->getLastEntity('transaction',true);

        $this->assertArraySelectiveEquals([
        //             'id'                    => 'txn_F1aL3bkJd2t5fK',
        //             'entity_id'             => 'sod_F1aL3U2O8oHd5e',
                        'type'                  => 'settlement.ondemand',
                        'merchant_id'           => '10000000000000',
                        'amount'                => 19557292,
                        'fee'                   => 472708,
                        'tax'                   => 72108,
                        'debit'                 => 20030000,
                        'currency'              => 'INR',
        //             'settled_at'            => '1582000200',
        //             'created_at'          => 1582000200,
                                ], $txn);

        $settlementOndemand = $this->getLastEntity('settlement.ondemand',true);

        $this->assertArraySelectiveEquals([
    //           'id'                    => 'sod_F0qG7YFH2KMpzY',
            'merchant_id'           => '10000000000000',
            'user_id'               => '20000000000000',
            'amount'                => 20030000,
            'total_amount_settled'  => 0,
            'total_fees'            => 472708,
            'total_tax'             => 72108,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 19557292,
            'max_balance'           => true,
            'currency'              => 'INR',
            'status'                => 'initiated',
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
            'mode'           => 'NEFT',
//          'initiated_at'   => 1582000200,
            'processed_at'   => NULL,
            'reversed_at'    => NULL,
            'fees'           => 472708,
            'tax'            => 72108,
            'utr'            => NULL,
            'status'         => 'initiated',
            'amount'         => 20030000,
            'failure_reason' => NULL,
//          'created_at'     => 1582000200,
                    ], $settlementOndemandPayout);
    }

    //merchant request amount > balance
    public function testCreateOndemandOnLowBalance()
    {
        $this->markTestSkipped();

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
            'amount'                => 20030000,
            'total_amount_settled'  => 0,
            'total_fees'            => 1180,
            'total_tax'             => 180,
            'total_amount_reversed' => 0,
            'total_amount_pending'  => 20028820,
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
                        'amount'         => 20000000,
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

        $this->startTest();
    }

    public function testOndemandFeesForFixedRate()
    {
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 20030000]);

        $this->fixtures->pricing->createOndemandFixedRatePricingPlan();

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
        $this->ba->proxyAuth('rzp_test_' . $this->merchantDetail['merchant_id'], $this->user->getId());

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_on_demand']);

        $this->startTest();
    }

    public function testNoMinLimitFornEsAutomaticMerchants()
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
