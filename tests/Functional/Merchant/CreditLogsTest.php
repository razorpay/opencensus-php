<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Batch\Header;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Credits;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Traits\MocksSplitz;

use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Mail\Merchant\RazorpayX\Credits\ConfirmationForKycUsers;

class CreditLogsTest extends TestCase
{
    use BatchTestTrait;
    use TestsBusinessBanking;
    use MocksSplitz;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CreditsData.php';

        parent::setUp();

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();
    }

    // create fee credits and fetch using get route
    public function testGetCreditsLog()
    {
        $creditsLog = $this->fixtures->create('credits',
            [
                Credits\Entity::TYPE => Credits\Type::FEE
            ]);

        $this->testData[__FUNCTION__]['request']['url'] .= $creditsLog->getPublicId();
        $this->testData[__FUNCTION__]['response']['content']['id'] = $creditsLog->getPublicId();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreateCreditsBulk()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);
        $this->startTest();

        $ledgerOutboxEntry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntry);

        $payload = base64_decode($ledgerOutboxEntry['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "amount_credit_loading",
            "money_params" => [
                'razorpay_reward' => '250000',
                'amount_credits' => '250000'
            ],
            "additional_params" => null,
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertEquals("credits_".$ledgerOutboxEntry["entity_id"]."-amount_credit_loading", $ledgerOutboxEntry["payload_name"]);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['identifiers'], $actualLedgerOutboxEntry['identifiers']);
    }

    public function testCreateCreditsBulkWithAccountsSplitInCLS()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createAccountsOnEvent')
            ->times(1)
            ->andReturn([]);

        $splitzInput = [
            'experiment_id' => 'OfAzGZfcmRLgrT',
            'id'            => '10000000000000',
        ];

        $splitzOutput = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $this->startTest();

        $ledgerOutboxEntry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntry);

        $payload = base64_decode($ledgerOutboxEntry['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $creditId = $ledgerOutboxEntry["entity_id"];

        $expectedLedgerOutboxEntry = [
            "merchant_id" =>  "10000000000000",
            "currency" => "INR",
            "transactor_event" =>  "amount_credit_loading",
            "money_params" => [
                'razorpay_reward' => '250000',
                'amount_credits' => '250000'
            ],
            "identifiers" => [
                'credit_id' => $creditId,
            ],
            "additional_params" => null,
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertEquals("credits_".$creditId."-amount_credit_loading", $ledgerOutboxEntry["payload_name"]);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($expectedLedgerOutboxEntry['additional_params'], $actualLedgerOutboxEntry['additional_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['money_params'], $actualLedgerOutboxEntry['money_params']);
        $this->assertEquals($expectedLedgerOutboxEntry['identifiers'], $actualLedgerOutboxEntry['identifiers']);
    }

    public function testCreateCreditsBulkInternal()
    {
        $this->ba->capitalCardsClientAppAuth();

        $this->startTest();
    }

    public function testPositiveUpdateCredits()
    {
        $creditsLog = $this->addFeeCredits(['value' => 150, 'campaign' => 'silent-ads']);

        $id = $creditsLog['id'];

        $this->testData[__FUNCTION__]['request']['url'] .= $id;

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', $id, true);
        $this->assertEquals($creditsLog['value'], 190);

        $balance = $this->fetchBalance();
        $this->assertEquals($balance['fee_credits'], 190);
    }

    public function testNegativeUpdateCredits()
    {
        $creditsLog = $this->addFeeCredits(['value' => 150, 'campaign' => 'silent-ads']);
        $id = $creditsLog['id'];

        $this->testData[__FUNCTION__]['request']['url'] .= $id;

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', $id, true);
        $this->assertEquals($creditsLog['value'], 100);

        $balance = $this->fetchBalance();
        $this->assertEquals($balance['fee_credits'], 100);
    }

    public function testFailNegativeUpdateCredits()
    {
        $creditsLog = $this->fixtures->create('credits', ['value' => 150, 'type' => Credits\Type::FEE]);
        $merchant = $creditsLog->merchant;

        $balance = (new Merchant\Balance\Repository)->editMerchantAmountCredits($merchant, 10);

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/10000000000000/credits/' . $creditsLog->getId();

        $this->startTest();
    }

    public function testFailDeductCreditsCampaign()
    {
        $creditslog = $this->fixtures->create('credits', ['value' => 90, 'type' => Credits\Type::FEE]);

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/10000000000000/credits/' . $creditslog->getId();

        $this->startTest();
    }

    public function testNegativeFeeCredits()
    {
        $this->fixtures->merchant->editFeeCredits('30000000', Account::TEST_ACCOUNT);
        $this->fixtures->merchant->editCreditsforNodalAccount('30000000', 'fee');

        $this->startTest();

        $balance = $this->getEntityById('balance', Account::TEST_ACCOUNT, true);

        $merchantCredits = $balance['fee_credits'];

        $this->assertEquals($merchantCredits, 27660840);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], -2339160);
    }

    public function testFeeCreditsGrantedInCampaign()
    {
        $this->fixtures->create(
            'credits',
            [
                'value' => 90, 'campaign' => 'noisy-ads',
                'type' => Credits\Type::FEE
            ]);
        $this->fixtures->create(
            'credits',
            ['value' => 90, 'type' => Credits\Type::FEE]);
        $this->fixtures->create(
            'credits',
            ['value' => 90, 'type' => Credits\Type::AMOUNT]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreditsGrantedToMerchant()
    {
        $this->testData[__FUNCTION__]['request']['url'] .= '?type=fee';

        $this->fixtures->create('merchant');
        $this->fixtures->create(
            'credits',
            [
                'value' => 90,
                'type' => Credits\Type::AMOUNT
            ]);
        $this->fixtures->create(
            'credits',
            ['value' => 90, 'type' => Credits\Type::FEE]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreditsTypeCollision()
    {
        $creditsLog = $this->addFeeCredits(['value' => 150, 'campaign' => 'silent-ads']);

        $this->startTest();
    }

    public function testAddRefundCredits()
    {
        $this->startTest();
    }

    public function testPositiveUpdateRefundCredits()
    {
        $creditsLog = $this->addCredits(
            [
                'value'    => 150,
                'campaign' => 'silent-ads',
                'type'     => 'refund'
            ]);

        $id = $creditsLog['id'];

        $this->testData[__FUNCTION__] = $this->testData['testPositiveUpdateCredits'];

        $this->testData[__FUNCTION__]['request']['url'] .= $id;

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', $id, true);

        $this->assertEquals($creditsLog['value'], 190);

        $balance = $this->fetchBalance();

        $this->assertEquals($balance['refund_credits'], 190);
    }

    public function testAddRefundCreditsWithoutUpperLimit()
    {
        $this->startTest();
    }

    public function testUploadBulkCreditFileWithPermission()
    {
        $entries = [
            [
                Header::CREDITS_MERCHANT_ID         => '10000000000000',
                Header::CAMPAIGN                    => 'Churn rewards',
                Header::REMARKS                     => '',
                Header::CREDIT_POINTS               => 100,
                Header::PRODUCT                     => 'banking',
                Header::TYPE                        => 'reward_fee',
            ],
            [
                Header::CREDITS_MERCHANT_ID         => '10000000000000',
                Header::CAMPAIGN                    => 'Churn rewards',
                Header::REMARKS                     => '38R00001',
                Header::CREDIT_POINTS               => -50,
                Header::PRODUCT                     => 'banking',
                Header::TYPE                        => 'reward_fee',
            ]
        ];

        $url = $this->writeToCsvFile($entries, 'file', null, 'files/batch');

        $uploadedFile = $this->createUploadedFileCsv($url);

        $content = [
            'type' => 'credit',
        ];

        $this->mockRazorX();

        $this->ba->adminAuth('live');

        $response = $this->makeBatchRequest($content, $uploadedFile);

        $this->assertEquals('CREATED', $response['status']);
    }

    public function testBulkCreditRoute()
    {
        // No call to ledger since we haven't enabled the LedgerJournalWrite Feature yet
        $this->mockLedgerSns(0);

        Mail::fake();

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'          => '10000SampleBal',
            'type'        => 'credit',
            'merchant_id' => '10000000000000',
        ]);

        $this->ba->batchAppAuth('rzp_live');

        $headers = [
            'HTTP_X_Batch_Id'          => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Id'        => 'RzrpySprAdmnId',
            'HTTP_X_Creator_Type'      => 'admin',
        ];
        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        Mail::assertQueued(ConfirmationForKycUsers::class, function ($mail)
        {
            $data = $mail->subject;

            $this->assertEquals('Your ₹1.00 worth Free Credits are waiting for you!', $data);

            return true;
        });

    }

    public function testBulkCreditRouteWithLedgerWrite()
    {
        $ledgerSnsPayloadArray = [];

        // We'll make 3 ledger calls. There are 6 items in the credit create payload,
        // but 2 fail and 1 has the same idempotency key. Hence only 3 successful ledger calls.
        $this->mockLedgerSns(3, $ledgerSnsPayloadArray);

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'          => '10000BankingB1',
            'type'        => 'banking',
            'merchant_id' => '10000000000000',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_JOURNAL_WRITES]);

        Mail::fake();

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'          => '10000SampleBal',
            'type'        => 'credit',
            'merchant_id' => '10000000000000',
        ]);

        $this->ba->batchAppAuth('rzp_live');

        $headers = [
            'HTTP_X_Batch_Id'          => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Id'        => 'RzrpySprAdmnId',
            'HTTP_X_Creator_Type'      => 'admin',
        ];

        $this->testData[__FUNCTION__] = $this->testData['testBulkCreditRoute'];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        Mail::assertQueued(ConfirmationForKycUsers::class, function ($mail)
        {
            $data = $mail->subject;

            $this->assertEquals('Your ₹1.00 worth Free Credits are waiting for you!', $data);

            return true;
        });

        $creditsCreated = $this->getDbEntities('credits', [],'live');

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);
            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('live', $ledgerRequestPayload['mode']);
            $this->assertEquals($creditsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('fund_loading_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertEquals('reward', $ledgerRequestPayload['additional_params']['fee_accounting']);
            $this->assertEmpty($ledgerRequestPayload['identifiers']);
        }
    }

    public function testBulkCreditRouteWithDALedgerWrite()
    {
        $ledgerSnsPayloadArray = [];

        // We'll make 3 ledger calls. There are 6 items in the credit create payload,
        // but 2 fail and 1 has the same idempotency key. Hence only 3 successful ledger calls.
        $this->mockLedgerSns(3, $ledgerSnsPayloadArray);

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'          => '10000BankingB1',
            'type'        => 'banking',
            'merchant_id' => '10000000000000',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_JOURNAL_WRITES]);

        Mail::fake();

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'          => '10000SampleBal',
            'type'        => 'credit',
            'merchant_id' => '10000000000000',
        ]);

        $this->ba->batchAppAuth('rzp_live');

        $headers = [
            'HTTP_X_Batch_Id'          => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Id'        => 'RzrpySprAdmnId',
            'HTTP_X_Creator_Type'      => 'admin',
        ];

        $this->testData[__FUNCTION__] = $this->testData['testBulkCreditRoute'];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        Mail::assertQueued(ConfirmationForKycUsers::class, function ($mail)
        {
            $data = $mail->subject;

            $this->assertEquals('Your ₹1.00 worth Free Credits are waiting for you!', $data);

            return true;
        });

        $creditsCreated = $this->getDbEntities('credits', [],'live');

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);
            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('live', $ledgerRequestPayload['mode']);
            $this->assertEquals($creditsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('fund_loading_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertEquals('reward', $ledgerRequestPayload['additional_params']['fee_accounting']);
            $this->assertEmpty($ledgerRequestPayload['identifiers']);
        }
    }

    public function testBulkCreditRouteWithLedgerReverseShadow()
    {
        // No call to ledger in shadow mode
        $this->mockLedgerSns(0);

        $this->app['config']->set('applications.ledger.enabled', false);

        // enable feature to call ledger in reverse shadow mode
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'          => '10000BankingB1',
            'type'        => 'banking',
            'merchant_id' => '10000000000000',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        Mail::fake();

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'          => '10000SampleBal',
            'type'        => 'credit',
            'merchant_id' => '10000000000000',
        ]);

        $this->ba->batchAppAuth('rzp_live');

        $headers = [
            'HTTP_X_Batch_Id'          => 'C0zv9I46W4wiAa',
            'HTTP_X_Creator_Id'        => 'RzrpySprAdmnId',
            'HTTP_X_Creator_Type'      => 'admin',
        ];

        $this->testData[__FUNCTION__] = $this->testData['testBulkCreditLedgerReverseShadowRoute'];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        Mail::assertQueued(ConfirmationForKycUsers::class, function ($mail)
        {
            $data = $mail->subject;

            $this->assertEquals('Your ₹1.00 worth Free Credits are waiting for you!', $data);

            return true;
        });

        $creditsCreated = $this->getDbLastEntity('credits', 'live');
        $this->assertEquals(100, $creditsCreated['value']);
        $this->assertEquals('bkwydgsZPxiesSRCRAa', $creditsCreated['idempotency_key']);
    }

    public function testBulkCreditRouteInTestMode()
    {
        $this->ba->batchAppAuth();

        $headers = [
            'HTTP_X_Batch_Id'          => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Id'        => 'RzrpySprAdmnId',
            'HTTP_X_Creator_Type'      => 'admin',
        ];
        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkCreditRouteEntities()
    {
        Mail::fake();

        $this->ba->batchAppAuth('rzp_live');

        $headers = [
            'HTTP_X_Batch_Id'          => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Id'        => 'RzrpySprAdmnId',
            'HTTP_X_Creator_Type'      => 'admin',
        ];
        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
        $credit = $this->getDbLastEntity('credits', 'live');
        $balance = $this->getDbLastEntity('credit_balance', 'live');

        $this->assertEquals($balance['type'], $credit['type']);
        $this->assertEquals(100, $balance['balance']);
        $this->assertEquals($balance['id'], $credit['balance_id']);

        Mail::assertQueued(ConfirmationForKycUsers::class, function ($mail)
        {
            $data = $mail->subject;

            $this->assertEquals('Your ₹1.00 worth Free Credits are waiting for you!', $data);

            return true;
        });
    }

    public function testCreditRowsWithNegativeBalance()
    {
        $this->fixtures->on('live')->create('credits', ['merchant_id' => '10000000000000', 'value' => 2000000 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->on('live')->create('credits', ['merchant_id' => '10000000000000', 'value' => -2000000 , 'campaign' => 'test rewards 1', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->on('live')->create('credits', ['merchant_id' => '10000000000000', 'value' => 2000000 , 'used' => 2000000, 'campaign' => 'test rewards 2', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->on('live')->create('credits', ['merchant_id' => '10000000000000', 'value' => 2000000 , 'used' => 1000000, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->on('live')->create('credits', ['merchant_id' => '10000000000000', 'value' => 1000000 , 'used' => 2000000, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->on('live')->create('credits', ['merchant_id' => '10000000000000', 'value' => 5000 , 'used' => 0, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail',['merchant_id' => '10000000000000']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id'], [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $credit = $this->getDbLastEntity('credits', 'live');

        // test merchant dashboard API call to fetch credit balances of merchant
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    protected function makeBatchRequest($content, $file)
    {
        $request = [
            'url' => '/admin/batches',
            'method' => 'POST',
            'content' => $content,
            'files' => [
                'file' => $file,
            ]
        ];

        $this->mockRazorX();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function mockRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(function ($mid, $feature, $mode)
            {
                if ($feature === 'batch_service_credit_migration')
                {
                    return 'on';
                }

                return 'off';
            }));
    }
    public function testFeeCreditWithdrawalSuccessCaseWithSingleCreditCompletelyUsed(): void
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 100000
            ]);

        $withdrawAmount = 100000;

        $this->fixtures->merchant->editRefundCredits('100000', '10000000000000');
        $merchantId = '10000000000000';
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $expectedJournalPayload = $this->getCreditWithdrawalExpectedJournalPayload($withdrawAmount);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload['money_params'], $journalPayload['money_params']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn($this->getCreditWithdrawalJournalResponse($withdrawAmount));


        $request = [
            'url' => '/merchants/pre_fund/withdraw',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
                'amount' => '100000'
            ]
        ];
        $response = $this->makeRequestAndGetContent($request);
        $credits = $this->getDbEntities('credits', ['merchant_id' => 10000000000000]);

        //assert for refund credit added
        $this->assertEquals('refund', $credits[0]['type']);
        $this->assertEquals(100000, $credits[0]['value']);
        $this->assertEquals(100000, $credits[0]['used']);

        //assert for refund credit withdrawal
        $this->assertEquals('refund_withdraw', $credits[1]['type']);
        $this->assertEquals(100000, $credits[1]['value']);
        $this->assertEquals(0, $credits[1]['used']);
    }

    public function testFeeCreditWithdrawalSuccessCaseWithSingleCreditPartiallyUsed(): void
    {

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 10000
            ]);

        $withdrawAmount = 8000;

        $this->fixtures->merchant->editRefundCredits('10000', '10000000000000');
        $merchantId = '10000000000000';
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);
        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $expectedJournalPayload = $this->getCreditWithdrawalExpectedJournalPayload($withdrawAmount);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload['money_params'], $journalPayload['money_params']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn($this->getCreditWithdrawalJournalResponse($withdrawAmount));


        $request = [
            'url' => '/merchants/pre_fund/withdraw',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
                'amount' => '8000'
            ]
        ];
        $response = $this->makeRequestAndGetContent($request);
        $credits = $this->getDbEntities('credits', ['merchant_id' => 10000000000000]);

        //assert for refund credit added
        $this->assertEquals('refund', $credits[0]['type']);
        $this->assertEquals(10000, $credits[0]['value']);
        $this->assertEquals(8000, $credits[0]['used']);

        //assert for refund credit withdrawal
        $this->assertEquals('refund_withdraw', $credits[1]['type']);
        $this->assertEquals(8000, $credits[1]['value']);
        $this->assertEquals(0, $credits[1]['used']);
    }

    public function testFeeCreditWithdrawalSuccessCaseWithMultipleCredit(): void
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 10000
            ]);

        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 5000
            ]);

        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 2000
            ]);

        $this->fixtures->merchant->editRefundCredits('17000', '10000000000000');
        $merchantId = '10000000000000';
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);
        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $withdrawAmount = 16000;
        $expectedJournalPayload = $this->getCreditWithdrawalExpectedJournalPayload($withdrawAmount);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload['money_params'], $journalPayload['money_params']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn($this->getCreditWithdrawalJournalResponse($withdrawAmount));

        $request = [
            'url' => '/merchants/pre_fund/withdraw',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
                'amount' => '16000',
            ]
        ];
        $response = $this->makeRequestAndGetContent($request);
        $credits = $this->getDbEntities('credits', ['merchant_id' => 10000000000000]);

        //assert for refund credits
        $this->assertEquals('refund', $credits[0]['type']);
        $this->assertEquals(10000, $credits[0]['value']);
        $this->assertEquals(10000, $credits[0]['used']);

        $this->assertEquals('refund', $credits[1]['type']);
        $this->assertEquals(5000, $credits[1]['value']);
        $this->assertEquals(5000, $credits[1]['used']);

        $this->assertEquals('refund', $credits[2]['type']);
        $this->assertEquals(2000, $credits[2]['value']);
        $this->assertEquals(1000, $credits[2]['used']);

        //assert for refund credit withdrawal
        $this->assertEquals('refund_withdraw', $credits[3]['type']);
        $this->assertEquals(16000, $credits[3]['value']);
        $this->assertEquals(0, $credits[3]['used']);
    }
    public function testFeeCreditWithdrawalWithSingleCreditMultipleWithdrawal() {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 10000
            ]);

        $withdrawAmount = 2000;

        $this->fixtures->merchant->editRefundCredits('10000', '10000000000000');
        $merchantId = '10000000000000';
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);
        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $expectedJournalPayload = $this->getCreditWithdrawalExpectedJournalPayload($withdrawAmount);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload['money_params'], $journalPayload['money_params']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn($this->getCreditWithdrawalJournalResponse($withdrawAmount));

        $request = [
            'url' => '/merchants/pre_fund/withdraw',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
                'amount' => '2000'
            ]
        ];
        $response = $this->makeRequestAndGetContent($request);
        $credits = $this->getDbEntities('credits', ['merchant_id' => 10000000000000]);

        //assert for refund credit added
        $this->assertEquals('refund', $credits[0]['type']);
        $this->assertEquals(10000, $credits[0]['value']);
        $this->assertEquals(2000, $credits[0]['used']);

        //assert for refund credit withdrawal
        $this->assertEquals('refund_withdraw', $credits[1]['type']);
        $this->assertEquals(2000, $credits[1]['value']);
        $this->assertEquals(0, $credits[1]['used']);



        //Second withdrawal
        $withdrawAmount = 4000;
        $expectedJournalPayload = $this->getCreditWithdrawalExpectedJournalPayload($withdrawAmount);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload['money_params'], $journalPayload['money_params']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn($this->getCreditWithdrawalJournalResponse($withdrawAmount, 2));

        $request = [
            'url' => '/merchants/pre_fund/withdraw',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
                'amount' => '4000'
            ]
        ];
        $response = $this->makeRequestAndGetContent($request);
        $credits = $this->getDbEntities('credits', ['merchant_id' => 10000000000000]);

        //assert for refund credit added
        $this->assertEquals('refund', $credits[0]['type']);
        $this->assertEquals(10000, $credits[0]['value']);
        $this->assertEquals(6000, $credits[0]['used']);

        //assert for refund credit withdrawal
        $this->assertEquals('refund_withdraw', $credits[1]['type']);
        $this->assertEquals(2000, $credits[1]['value']);
        $this->assertEquals(0, $credits[1]['used']);
        $this->assertEquals('refund_withdraw', $credits[2]['type']);
        $this->assertEquals(4000, $credits[2]['value']);
        $this->assertEquals(0, $credits[2]['used']);
    }

    public function testFeeCreditWithdrawalWithMultipleCreditMultipleWithdrawal() {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // Create multiple credit entries
        $this->fixtures->create('credits', [
            'type' => 'refund',
            'value' => 10000
        ]);
        $this->fixtures->create('credits', [
            'type' => 'refund',
            'value' => 5000
        ]);
        $this->fixtures->create('credits', [
            'type' => 'refund',
            'value' => 2000
        ]);

        // Setup merchant details
        $this->fixtures->merchant->editRefundCredits('17000', '10000000000000');
        $merchantId = '10000000000000';
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);
        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        // First withdrawal
        $withdrawAmount1 = 16000;
        $expectedJournalPayload1 = $this->getCreditWithdrawalExpectedJournalPayload($withdrawAmount1);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload1) {
                $this->assertArrayHasKey('transaction_date', $journalPayload);
                $this->assertArrayHasKey('transactor_id', $journalPayload);
                $this->assertEquals($expectedJournalPayload1['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload1['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload1['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload1['money_params'], $journalPayload['money_params']);
                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);
                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn($this->getCreditWithdrawalJournalResponse($withdrawAmount1));

        $request1 = [
            'url' => '/merchants/pre_fund/withdraw',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
                'amount' => '16000',
            ]
        ];
        $response1 = $this->makeRequestAndGetContent($request1);
        $creditsAfterFirstWithdrawal = $this->getDbEntities('credits', ['merchant_id' => $merchantId]);

        // Assert after the first withdrawal
        $this->assertEquals('refund', $creditsAfterFirstWithdrawal[0]['type']);
        $this->assertEquals(10000, $creditsAfterFirstWithdrawal[0]['value']);
        $this->assertEquals(10000, $creditsAfterFirstWithdrawal[0]['used']);
        $this->assertEquals('refund', $creditsAfterFirstWithdrawal[1]['type']);
        $this->assertEquals(5000, $creditsAfterFirstWithdrawal[1]['value']);
        $this->assertEquals(5000, $creditsAfterFirstWithdrawal[1]['used']);
        $this->assertEquals('refund', $creditsAfterFirstWithdrawal[2]['type']);
        $this->assertEquals(2000, $creditsAfterFirstWithdrawal[2]['value']);
        $this->assertEquals(1000, $creditsAfterFirstWithdrawal[2]['used']);
        $this->assertEquals('refund_withdraw', $creditsAfterFirstWithdrawal[3]['type']);
        $this->assertEquals(16000, $creditsAfterFirstWithdrawal[3]['value']);
        $this->assertEquals(0, $creditsAfterFirstWithdrawal[3]['used']);

        // Second withdrawal
        $withdrawAmount2 = 1000;
        $expectedJournalPayload2 = $this->getCreditWithdrawalExpectedJournalPayload($withdrawAmount2);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload2) {
                $this->assertArrayHasKey('transaction_date', $journalPayload);
                $this->assertArrayHasKey('transactor_id', $journalPayload);
                $this->assertEquals($expectedJournalPayload2['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload2['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload2['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload2['money_params'], $journalPayload['money_params']);
                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);
                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn($this->getCreditWithdrawalJournalResponse($withdrawAmount2, 1));

        $request2 = [
            'url' => '/merchants/pre_fund/withdraw',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
                'amount' => '1000',
            ]
        ];
        $response2 = $this->makeRequestAndGetContent($request2);
        $creditsAfterSecondWithdrawal = $this->getDbEntities('credits', ['merchant_id' => $merchantId]);
        // Assert after the second withdrawal
        $this->assertEquals('refund', $creditsAfterSecondWithdrawal[0]['type']);
        $this->assertEquals(10000, $creditsAfterSecondWithdrawal[0]['value']);
        $this->assertEquals(10000, $creditsAfterSecondWithdrawal[0]['used']);
        $this->assertEquals('refund', $creditsAfterSecondWithdrawal[1]['type']);
        $this->assertEquals(5000, $creditsAfterSecondWithdrawal[1]['value']);
        $this->assertEquals(5000, $creditsAfterSecondWithdrawal[1]['used']);
        $this->assertEquals('refund', $creditsAfterSecondWithdrawal[2]['type']);
        $this->assertEquals(2000, $creditsAfterSecondWithdrawal[2]['value']);
        $this->assertEquals(2000, $creditsAfterSecondWithdrawal[2]['used']);
        $this->assertEquals('refund_withdraw', $creditsAfterSecondWithdrawal[3]['type']);
        $this->assertEquals(16000, $creditsAfterSecondWithdrawal[3]['value']);
        $this->assertEquals(0, $creditsAfterSecondWithdrawal[3]['used']);
        $this->assertEquals('refund_withdraw', $creditsAfterSecondWithdrawal[4]['type']);
        $this->assertEquals(1000, $creditsAfterSecondWithdrawal[4]['value']);
        $this->assertEquals(0, $creditsAfterSecondWithdrawal[4]['used']);
    }

    public function getCreditWithdrawalExpectedJournalPayload(int $withdrawAmount): array
    {
        return [
            "merchant_id"           => "10000000000000",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // any
            "transactor_event"      => "merchant_refund_credit_withdrawal",
            "money_params"          => [
                "amount"                    => strval($withdrawAmount),
                "base_amount"               => strval($withdrawAmount),
                "refund_credits_amount"     => strval($withdrawAmount),
                "credit_control_amount"     => strval($withdrawAmount)
            ],
            "notes"                 => [
                "credit_id"         => "" //any
            ]
        ];
    }

    public function getCreditWithdrawalJournalResponse(int $withdrawAmount, int $count = 0): array
    {
        $amount = strval($withdrawAmount);

        $ledgerResp = [
            'code' => 200,
            'body' => [
                "id"=> "LN5BWCGvLdPu7T",
                "created_at"=> "1677853302",
                "updated_at"=> "1677853302",
                "amount"=> $amount,
                "base_amount"=> $amount,
                "currency"=> "INR",
                "tenant"=> "PG",
                "transactor_id"=> "credits_PCNYIZL7lE48oR",
                "transactor_event"=> "merchant_refund_credit_withdrawal",
                "transaction_date"=> "1677853302",
                "ledger_entry"=> [
                    [
                        "id"=> "PCNYIhc2rAjgqM",
                        "created_at"=> "1677853302",
                        "updated_at"=> "1677853302",
                        "merchant_id"=> "10000000000000",
                        "journal_id"=> "PCNYIhLYo7UTBg",
                        "account_id"=> "JjpZUD9PmJeNPk",
                        "amount"=> $amount,
                        "base_amount"=> $amount,
                        "type"=> "credit",
                        "currency"=> "INR",
                        "balance"=> "531425.000000",
                        "balance_updated"=> true,
                        "account_entities"=> [
                            "account_type"=> [
                                "payable"
                            ],
                            "fund_account_type"=> [
                                "pre_fund_withdrawal_control"
                            ]
                        ]
                    ],
                    [
                        "id"=> "PCNYIhc48b8LIQ",
                        "created_at"=> "1677853302",
                        "updated_at"=> "1677853302",
                        "merchant_id"=> "10000000000000",
                        "journal_id"=> "PCNYIhLYo7UTBg",
                        "account_id"=> "LN22CRUSOTIIBG",
                        "amount"=> $amount,
                        "base_amount"=> $amount,
                        "type"=> "debit",
                        "currency"=> "INR",
                        "balance"=> "997350.000000",
                        "balance_updated"=> true,
                        "account_entities"=> [
                            "account_type"=> [
                                "payable"
                            ],
                            "fund_account_type"=> [
                                "merchant_refund_credits"
                            ]
                        ]
                    ]
                ]
            ],
        ];

        if ($count == 1) {
            $ledgerResp['body']['id'] = 'LN5BWCGvLdPu7U';
        }
        if ($count == 2) {
            $ledgerResp['body']['id'] = 'LN5BWCGvLdPu7Q';
        }
        return $ledgerResp;
    }


}
