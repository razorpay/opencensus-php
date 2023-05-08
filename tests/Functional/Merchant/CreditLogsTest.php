<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Batch\Header;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Credits;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Mail\Merchant\RazorpayX\Credits\ConfirmationForKycUsers;

class CreditLogsTest extends TestCase
{
    use BatchTestTrait;
    use TestsBusinessBanking;

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
        $this->startTest();
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
}
