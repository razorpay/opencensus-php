<?php

namespace RZP\Tests\Functional\BankTransfer;

use Carbon\Carbon;
use DB;
use Mail;
use Queue;
use Cache;
use Mockery;
use RZP\Constants\Timezone;
use RZP\Models\Admin;
use RZP\Models\Feature;
use RZP\Models\Pricing\Fee;
use RZP\Models\Batch\Header;
use RZP\Models\Terminal\Type;
use RZP\Models\Admin\Service;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Services\RazorXClient;
use RZP\Models\VirtualAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Mail\Transaction\BankTransfer;
use RZP\Models\VirtualAccount\Provider;
use RZP\Jobs\BankTransferCreateProcess;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\BankTransfer\Status as S;
use RZP\Models\BankTransferRequest\Entity;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Mail\Merchant\RazorpayX\FundLoadingFailed;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Models\Payout\SourceUpdater\XPayrollUpdater;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use RZP\Tests\Traits\MocksSplitz;

class BankTransferRxTest extends TestCase
{
    use AttemptTrait;
    use CreatesInvoice;
    use FileHandlerTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use TestsBusinessBanking;
    use AttemptReconcileTrait;
    use ReconTrait;
    use MocksSplitz;

    protected $virtualAccountId;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/BankTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->createAccount('BankAccountMer');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->merchant->edit('BankAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['virtual_accounts'], 'BankAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('BankAccountMer', 'bank_transfer');
        $this->fixtures->on('live')->merchant->edit('BankAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->createTerminals();

        $this->bankAccount = $this->createVirtualAccount();
    }

    protected function createTerminals()
    {
        // Creating Fallback Terminals,
        // Fallback Terminals are those terminals which are created with just Root
        // and are assigned to the Shared Merchant to get unexpected payments.
        $terminalAttributes = [ 'id' => 'GENERICBANKACC', 'gateway_merchant_id2' => '', 'enabled' => false ];
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $terminalAttributes = ['id' => 'GENERICABNKACC', 'gateway_merchant_id2' => '', 'enabled' => false];
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal_alpha_num', $terminalAttributes);

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal_alpha_num');

        $terminalAttributes = [ 'id' => 'GENERICBANKACC', 'gateway_merchant_id2' => '', 'enabled' => false, 'gateway' => Gateway::BT_YESBANK ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $terminalAttributes = [ 'gateway' => Gateway::BT_YESBANK ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:vpa_shared_terminal_icici');

        $this->fixtures->on('test');
    }

    public function testVirtualAccountClosedAfterValidationCallbackForAxis()
    {
        Mail::fake();

        $testData = $this->testData['testValidateBankTransferAxisForX'];

        $testData['request']['content']['UTR'] = 'RazP00010742429600013';

        $this->setUpCommonMerchantForBusinessBankingLive(true, 1000000);

        $terminalAttributes = [ 'id' =>'GENERICBNKAXIS', 'gateway' => Gateway::BT_AXIS, 'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type' => 'shared',
        ]);

        $initialAmount = $balance1->getBalance();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);


        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id'           => $balance1->getId(),
                'status'               => 'approved',
                'payer_ifsc'           => 'HDFC0000522',
                'payer_account_number' => '910910910910910'
            ]);

        $testData['request']['content']['Bene_acc_no'] = 984520125355346;
        $testData['request']['content']['Req_type'] = 'notification';

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);

        $bankTransfer =  $this->getDbLastEntity('bank_transfer',  'live');

        $payout =  $this->getDbLastEntity('payout',  'live');

        $this->assertEquals(300, $payout['amount']);
        $this->assertEquals('refund', $payout['purpose']);

        $this->assertEquals(300, $bankTransfer['amount']);
        $this->assertEquals("HDFC0000522", $bankTransfer['payer_ifsc']);

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true, 'live');

        $this->assertEquals(strtoupper($testData['request']['content']['UTR']), $bankTransfer['utr']);

        $this->assertEquals(984520125355346, $bankTransfer['payee_account']);
        $this->assertEquals(VirtualAccount\Provider::AXIS_COMMON_IFSC, $bankTransfer['payee_ifsc']);
    }

    public function testBankTransferProcessXDemoCron()
    {
        $merchant_id = \RZP\Models\Merchant\Account::X_DEMO_PROD_ACCOUNT;

        $x_demo_bank_account = \RZP\Constants\BankingDemo::BANK_ACCOUNT;

        $this->fixtures->merchant->createAccount($merchant_id);

        $this->fixtures->on('test')->merchant->edit($merchant_id, ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('test')->merchant->addFeatures(['virtual_accounts'], $merchant_id);
        $this->fixtures->on('test')->merchant->enableMethod($merchant_id, 'bank_transfer');

        $bankAccount = $this->createVirtualAccount('test',$merchant_id);
        $this->fixtures->on('test')->edit('bank_account',$bankAccount['id'],[
           'account_number' => $x_demo_bank_account
        ]);

        $this->ba->cronAuth();

        $this->startTest();
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
    }

    public function testBankTransferYesbankMIS()
    {
        $ifsc = Provider::IFSC[Provider::YESBANK];

        $balance1 = $this->getDbEntity('balance',
                                       [
                                           'merchant_id' => '10000000000000',
                                       ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => 4564562235678281,
        ]);

        $ba = $this->fixtures->on('live')->create('bank_account',
                                                  [
                                                      'merchant_id'    => '10000000000000',
                                                      'entity_id'      => '100000000000va',
                                                      'type'           => 'virtual_account',
                                                      'ifsc_code'      => $ifsc,
                                                      'account_number' => 4564562235678281,
                                                  ]);

        $this->fixtures->on('live')->create('virtual_account',
                                            [
                                                'id'              => '100000000000va',
                                                'merchant_id'     => '10000000000000',
                                                'status'          => 'active',
                                                'bank_account_id' => $ba->getId(),
                                                'balance_id'      => $balance1->getId(),
                                            ]);

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $balance1->getId(),
                                                'status'     => 'approved',
                                                'payer_ifsc' => 'IOBA0002897',
                                                'payer_account_number' => '9876543210123456789'
                                            ]);

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = 4564562235678281;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->batchAppAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['valid']);
        $this->assertEquals($request['content']['transaction_id'], $response['transaction_id']);

        $bankTransfer =  $this->getDbLastEntity('bank_transfer',  'live');

        $this->assertEquals(5000000, $bankTransfer['amount']);
        $this->assertEquals("9020", $bankTransfer['payer_ifsc']);

        $this->assertEquals("IOBA0002897", $bankTransfer->payerBankAccount['ifsc_code']);

        $bankTransferRequest = $this->getDbLastEntity('bank_transfer_request', 'live');

        $this->assertNotNull($bankTransferRequest);
        $this->assertTrue($bankTransferRequest['is_created']);
        $this->assertEquals(
            ['source' => 'file', 'request_from' => 'bank'],
            json_decode($bankTransferRequest['request_source'], true)
        );
    }

    public function testBankTransferYesbankMISForClosedVirtualAccount()
    {
        $ifsc = Provider::IFSC[Provider::YESBANK];

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->setUpCommonMerchantForBusinessBankingLive(true, 1000000);

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => 4564562235678281,
        ]);

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => '100000000000va',
                'type'           => 'virtual_account',
                'ifsc_code'      => $ifsc,
                'account_number' => 4564562235678281,
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id'              => '100000000000va',
                'merchant_id'     => '10000000000000',
                'status'          => 'closed',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'IOBA0002897',
                'payer_account_number' => '9876543210123456789'
            ]);

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = 4564562235678281;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->batchAppAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['valid']);
        $this->assertEquals($request['content']['transaction_id'], $response['transaction_id']);

        $bankTransfer =  $this->getDbLastEntity('bank_transfer',  'live');

        $payout =  $this->getDbLastEntity('payout',  'live');

        $this->assertEquals(5000000, $payout['amount']);
        $this->assertEquals('refund', $payout['purpose']);

        $this->assertEquals(5000000, $bankTransfer['amount']);
        $this->assertEquals("9020", $bankTransfer['payer_ifsc']);

        $this->assertEquals("IOBA0002897", $bankTransfer->payerBankAccount['ifsc_code']);
    }

    protected function createVirtualAccount($mode = 'test', $merchantId = '10000000000000', $additionalFields = [])
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $request = array_merge($this->testData[__FUNCTION__], $additionalFields);

        $response = $this->makeRequestAndGetContent($request);

        $this->virtualAccountId = $response['id'];

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }

    protected function createVirtualAccountForBanking($mode = 'test', $merchantId = '10000000000000', $additionalFields = [])
    {
        $this->fixtures->on($mode)->merchant->addFeatures(['virtual_accounts_banking'], $merchantId);

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_TheLiveAuthKey');
        }
        else
        {
            $this->ba->privateAuth();
        }

        $request = array_merge($this->testData[__FUNCTION__], $additionalFields);

        $response = $this->makeRequestAndGetContent($request);

        $this->virtualAccountId = $response['id'];

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }

    /*
     * Here we are not disabling tpv flow and still all fund loads should happen successfully as these are test mode
     * fund loads.
     */
    public function testBankTransferProcessWithFieldsOnTestMode()
    {
        Mail::fake();

        $ledgerSnsPayloadArray = [];

        // During fund loading, there has been push to SNS topic for creating this transaction in Ledger service.
        // Mocking ledger sns because call to ledger is currently async via SNS. Once it is in sync, this will be removed.
        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $balance = $this->getDbEntity('balance',
                                      [
                                          'merchant_id'  => '10000000000000',
                                      ], 'test');

        $this->fixtures->edit('balance', $balance->getId(), [
            'type' => 'banking',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  $balance['account_number'],
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'contact_name'  => 'Aditya',
            'business_type' => 3
        ]);

        $this->setupRedisConfigKeysForTerminalSelection();

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->ba->proxyAuth();
        $this->startTest();

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);

        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $this->assertEquals($transaction['entity_id'], $bankTransfer['id']);
        $this->assertEquals($transaction['id'], 'txn_'.$bankTransfer['transaction_id']);

        Mail::assertNotQueued(BankTransfer::class);

        // Since this was a test mode fund loading, it shall always pass and thus we will not send Fund loading failed
        // mail.
        Mail::assertNotQueued(FundLoadingFailed::class);

        $bankTransfersCreated = $this->getDbEntities('bank_transfer');

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($bankTransfersCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('fund_loading_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertEquals('term_SHRDBANKACC3DS', $ledgerRequestPayload['identifiers']['terminal_id']);
            $this->assertEquals('nodal', $ledgerRequestPayload['identifiers']['terminal_account_type']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testValidateBankTransferAxisForX()
    {
        Queue::fake();
        $testData = $this->testData[__FUNCTION__];

        $terminalAttributes = [ 'id' =>'GENERICBNKAXIS', 'gateway' => Gateway::BT_AXIS, 'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type'   => 'shared',
        ]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $bankAccount = $this->createVirtualAccountForBanking('live');

        $this->assertEquals(VirtualAccount\Provider::AXIS_COMMON_IFSC, $bankAccount['ifsc']);
        $this->assertEquals(\RZP\Models\BankAccount\Entity::ACCOUNT_NUMBER_LENGTH_FOR_AXIS_BANKING_TERMINAL,
            strlen($bankAccount['account_number']));

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id'           => $balance1->getId(),
                'status'               => 'approved',
                'payer_ifsc'           => 'HDFC0000522',
                'payer_account_number' => '910910910910910'
            ]);

        $testData['request']['content']['Bene_acc_no'] = $bankAccount['account_number'];

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        Queue::assertNotPushed(BankTransferCreateProcess::class);
    }

    public function testValidatePayrollBankTransferAxisForX_ValidationCall_ValidBankTransfer()
    {
        Queue::fake();

        $xPayrollServiceMock = Mockery::mock('RZP\Services\XPayroll\Service')->makePartial();

        $this->app->instance('xpayroll', $xPayrollServiceMock);

        $testData = $this->testData['testValidateBankTransferAxisForX'];
        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");
        $testData['request']['content']['Corp_code'] = '9845';
//
        $terminalAttributes = [
            'id' =>'GENERICBNKAXIS',
            'gateway' => Gateway::BT_AXIS,
            'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type'   => 'shared',
        ]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::PAYROLL_SAV]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $bankAccount = $this->createVirtualAccountForBanking('live');

        $this->assertEquals(VirtualAccount\Provider::AXIS_COMMON_IFSC, $bankAccount['ifsc']);
        $this->assertEquals(\RZP\Models\BankAccount\Entity::ACCOUNT_NUMBER_LENGTH_FOR_AXIS_BANKING_TERMINAL,
            strlen($bankAccount['account_number']));

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id'           => $balance1->getId(),
                'status'               => 'approved',
                'payer_ifsc'           => 'HDFC0000522',
                'payer_account_number' => '910910910910910'
            ]);

        $testData['request']['content']['Bene_acc_no'] = $bankAccount['account_number'];

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $dataExpected = [
            'payee_account'     => $bankAccount['account_number'],
            'payee_ifsc'        => 'UTIB0CCH274',
            'payer_name'        => 'ABC Pvt Ltd',
            'payer_account'     => '910910910910910',
            'payer_ifsc'        => 'HDFC0000522',
            'mode'              => 'neft',
            'utr'               => 'RAZP00010742429600013',
            'time'              =>  Carbon::createFromFormat('Y-m-d H:i:s', $testData['request']['content']['Req_dt_time'], Timezone::IST)->getTimestamp(),
            'amount'            => 300,
            'description'       => null,
            'narration'         => 'RAZP00010742429600013'
        ];

        $xPayrollServiceMock
            ->shouldReceive('makePayrollValidationRequest')
            ->once()
            ->withArgs([
                    '/v2/api/validate-source-account',
                    'POST',
                    $dataExpected,
                    3,
                    'live'
                ]
            )
            ->andReturn(['is_valid' => true]);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        Queue::assertNotPushed(BankTransferCreateProcess::class);
    }

    public function testValidatePayrollBankTransferAxisForX_ValidationCall_InvalidBankTransfer()
    {
        Queue::fake();

        $xPayrollServiceMock = Mockery::mock('RZP\Services\XPayroll\Service')->makePartial();

        $this->app->instance('xpayroll', $xPayrollServiceMock);

        $testData = $this->testData['testValidateBankTransferAxisForX'];
        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");
        $testData['request']['content']['Corp_code'] = '9845';

        $terminalAttributes = [
            'id' =>'GENERICBNKAXIS',
            'gateway' => Gateway::BT_AXIS,
            'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type'   => 'shared',
        ]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::PAYROLL_SAV]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $bankAccount = $this->createVirtualAccountForBanking('live');

        $this->assertEquals(VirtualAccount\Provider::AXIS_COMMON_IFSC, $bankAccount['ifsc']);
        $this->assertEquals(\RZP\Models\BankAccount\Entity::ACCOUNT_NUMBER_LENGTH_FOR_AXIS_BANKING_TERMINAL,
            strlen($bankAccount['account_number']));

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id'           => $balance1->getId(),
                'status'               => 'approved',
                'payer_ifsc'           => 'HDFC0000522',
                'payer_account_number' => '910910910910910'
            ]);

        $testData['request']['content']['Bene_acc_no'] = $bankAccount['account_number'];

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $dataExpected = [
            'payee_account'     => $bankAccount['account_number'],
            'payee_ifsc'        => 'UTIB0CCH274',
            'payer_name'        => 'ABC Pvt Ltd',
            'payer_account'     => '910910910910910',
            'payer_ifsc'        => 'HDFC0000522',
            'mode'              => 'neft',
            'utr'               => 'RAZP00010742429600013',
            'time'              =>  Carbon::createFromFormat('Y-m-d H:i:s', $testData['request']['content']['Req_dt_time'], Timezone::IST)->getTimestamp(),
            'amount'            => 300,
            'description'       => null,
            'narration'         => 'RAZP00010742429600013'
        ];

        $xPayrollServiceMock
            ->shouldReceive('makePayrollValidationRequest')
            ->once()
            ->withArgs([
                    '/v2/api/validate-source-account',
                    'POST',
                    $dataExpected,
                    3,
                    'live'
                ]
            )
            ->andReturn(['is_valid' => false]);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

        Queue::assertNotPushed(BankTransferCreateProcess::class);
    }

    public function testValidatePayrollBankTransferAxisForX_NotificationCall_ValidBankTransfer()
    {
        Mail::fake();

        $xPayrollServiceMock = Mockery::mock('RZP\Services\XPayroll\Service')->makePartial();

        $this->app->instance('xpayroll', $xPayrollServiceMock);

        $testData = $this->testData['testValidateBankTransferAxisForX'];

        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");
        $testData['request']['content']['Corp_code'] = '9845';

        $terminalAttributes = [ 'id' =>'GENERICBNKAXIS', 'gateway' => Gateway::BT_AXIS, 'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type' => 'shared',
        ]);

        $initialAmount = $balance1->getBalance();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::PAYROLL_SAV]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $bankAccount = $this->createVirtualAccountForBanking('live');

        $this->assertEquals(VirtualAccount\Provider::AXIS_COMMON_IFSC, $bankAccount['ifsc']);

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id'           => $balance1->getId(),
                'status'               => 'approved',
                'payer_ifsc'           => 'HDFC0000522',
                'payer_account_number' => '910910910910910'
            ]);

        $testData['request']['content']['Bene_acc_no'] = $bankAccount['account_number'];
        $testData['request']['content']['Req_type'] = 'notification';

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $dataExpected = [
            'payee_account'     => $bankAccount['account_number'],
            'payee_ifsc'        => 'UTIB0CCH274',
            'payer_name'        => 'ABC Pvt Ltd',
            'payer_account'     => '910910910910910',
            'payer_ifsc'        => 'HDFC0000522',
            'mode'              => 'neft',
            'utr'               => 'RAZP00010742429600013',
            'time'              =>  Carbon::createFromFormat('Y-m-d H:i:s', $testData['request']['content']['Req_dt_time'], Timezone::IST)->getTimestamp(),
            'amount'            => 300,
            'description'       => null,
            'narration'         => 'RAZP00010742429600013'
        ];

        $xPayrollServiceMock
            ->shouldReceive('makePayrollValidationRequest')
            ->once()
            ->withArgs([
                    '/v2/api/validate-source-account',
                    'POST',
                    $dataExpected,
                    3,
                    'live'
                ]
            )
            ->andReturn(['is_valid' => true]);

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true, 'live');

        $this->assertEquals($bankAccount['account_number'], $bankTransfer['payee_account']);
        $this->assertEquals(VirtualAccount\Provider::AXIS_COMMON_IFSC, $bankTransfer['payee_ifsc']);

        $balance1->reload();
        $finalAmount = $balance1->getBalance();
        $this->assertEquals($initialAmount + 300, $finalAmount);
    }

    public function testValidatePayrollBankTransferAxisForX_NotificationCall_InvalidBankTransfer() {
        Mail::fake();

        $xPayrollServiceMock = Mockery::mock('RZP\Services\XPayroll\Service')->makePartial();

        $this->app->instance('xpayroll', $xPayrollServiceMock);

        $this->enableRazorXTreatmentForNonTpvRefundsViaX();

        $this->setUpCommonMerchantForBusinessBankingLive(true);

        $commonMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id' => '100000Razorpay',
                'type'        => 'banking'
            ], 'live');

        $testData = $this->testData['testValidateBankTransferAxisForX'];
        $testData['request']['content']['Req_dt_time'] = date("Y-m-d H:i:s");
        $testData['request']['content']['Corp_code'] = '9845';

        $terminalAttributes = [ 'id' =>'GENERICBNKAXIS', 'gateway' => Gateway::BT_AXIS, 'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type' => 'shared',
        ]);

        $initialAmount = $balance1->getBalance();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);

        $this->fixtures->on('live')->merchant->addFeatures([Feature\Constants::PAYROLL_SAV]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $bankAccount = $this->createVirtualAccountForBanking('live');

        $this->assertEquals(VirtualAccount\Provider::AXIS_COMMON_IFSC, $bankAccount['ifsc']);

        $testData['request']['content']['Bene_acc_no'] = $bankAccount['account_number'];
        $testData['request']['content']['Req_type'] = 'notification';

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];


        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $dataExpected = [
            'payee_account'     => $bankAccount['account_number'],
            'payee_ifsc'        => 'UTIB0CCH274',
            'payer_name'        => 'ABC Pvt Ltd',
            'payer_account'     => '910910910910910',
            'payer_ifsc'        => 'HDFC0000522',
            'mode'              => 'neft',
            'utr'               => 'RAZP00010742429600013',
            'time'              =>  Carbon::createFromFormat('Y-m-d H:i:s', $testData['request']['content']['Req_dt_time'], Timezone::IST)->getTimestamp(),
            'amount'            => 300,
            'description'       => null,
            'narration'         => 'RAZP00010742429600013'
        ];

        $xPayrollServiceMock
            ->shouldReceive('makePayrollValidationRequest')
            ->once()
            ->withArgs([
                    '/v2/api/validate-source-account',
                    'POST',
                    $dataExpected,
                    3,
                    'live'
                ]
            )
            ->andReturn(['is_valid' => false]);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no payments were created during this request
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);
        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading + 1, $countOfPayoutsAfterFundLoading);

        // Assert that two transactions were created. One for Bank transfer and one for payout.
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1, $countOfTransactionsAfterFundLoading);

        $creditTransaction = $this->getDbEntity('transaction',
            [
                'balance_id' => $commonMerchantBankingBalance->getId(),
                'type'       => 'bank_transfer'
            ], 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['Txn_amnt'];

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
            ['id' => VirtualAccount\Entity::SHARED_ID_BANKING],
            'live');

        // Assertions on credit transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount*100, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount*100, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        // Assertions on payer bank account for bank transfer
        $payerBankAccount = $bankTransfer->payerBankAccount;
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());
        $this->assertEquals($request['content']['Sndr_acnt'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['Sndr_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['Sndr_nm'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('axis', $bankTransfer->getGateway());
        $this->assertEquals(null, $bankTransfer->getPaymentId());
        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING',
            $bankTransfer->getUnexpectedReason());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount*100, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['Sndr_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['Sndr_nm'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['Sndr_acnt'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['Bene_acc_no'], $bankTransfer->getPayeeAccount());

        //
        // Assertions for the Payout created during refund flow
        //
        $refundPayout = $this->getDbLastEntity('payout', 'live');

        $debitTransaction = $this->getDbEntity('transaction',
            [
                'balance_id' => $commonMerchantBankingBalance->getId(),
                'type'       => 'payout'
            ], 'live');

        $payoutSource = $this->getDbLastEntity('payout_source', 'live');

        $updatedCommonMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id' => '100000Razorpay',
                'type'        => 'banking'
            ], 'live');

        // Assertions on payout created
        $this->assertEquals($bankTransfer->getMerchantId(), $refundPayout->getMerchantId());
        $this->assertEquals($bankTransfer->getAmount(), $refundPayout->getAmount());
        $this->assertEquals($bankTransfer->getPayerAccount(),
            $refundPayout->fundAccount->account->getAccountNumber());
        $this->assertEquals($bankTransfer->getUtr(), $refundPayout->getReferenceId());

        // Assertions on payout sources entity
        $this->assertEquals($bankTransfer->getPublicId(), $payoutSource['source_id']);
        $this->assertEquals('bank_transfer', $payoutSource['source_type']);
        $this->assertEquals($refundPayout->getId(), $payoutSource['payout_id']);

//        // Assertions on the debit transaction entity
//        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $debitTransaction->getMerchantId());
//        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getAmount());
//        $this->assertEquals(0, $debitTransaction->getCredit());
//        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getDebit());
//
//        // Assertions on balance.
//        // The balance should not change because we'll have a credit and a debit of the exact same amount
//        $this->assertEquals($commonMerchantBankingBalance['balance'], $updatedCommonMerchantBankingBalance['balance']);
    }

    public function testValidateBankTransferAxisForXActivatedMerchant()
    {
        Queue::fake();

        $testData = $this->testData['testValidateBankTransferAxisForX'];

        $terminalAttributes = [ 'id' =>'GENERICBNKAXIS', 'gateway' => Gateway::BT_AXIS, 'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type' => 'shared',
        ]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1, 'live' => 0]);

        $this->fixtures->on('live')->create('merchant_attribute',
            [
                'merchant_id' => '10000000000000',
                'product' => 'banking',
                'type' => 'X',
                'group' => 'products_enabled',
                'value' => 'true',
            ]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $bankAccount = $this->createVirtualAccountForBanking('live');

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id'           => $balance1->getId(),
                'status'               => 'approved',
                'payer_ifsc'           => 'HDFC0000522',
                'payer_account_number' => '910910910910910'
            ]);

        $testData['request']['content']['Bene_acc_no'] = $bankAccount['account_number'];

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        Queue::assertNotPushed(BankTransferCreateProcess::class);
    }

    public function testValidateBankTransferAxisForXForNonTpvAccount()
    {
        Queue::fake();

        $testData = $this->testData['testValidateBankTransferAxisForX'];

        $terminalAttributes = [ 'id' =>'GENERICBNKAXIS', 'gateway' => Gateway::BT_AXIS, 'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type' => 'shared',
        ]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $bankAccount = $this->createVirtualAccountForBanking('live');

        $testData['request']['content']['Bene_acc_no'] = $bankAccount['account_number'];

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

        Queue::assertNotPushed(BankTransferCreateProcess::class);
    }

    public function testValidateBankTransferAxisForXForInactiveMerchant()
    {
        Queue::fake();

        $testData = $this->testData['testValidateBankTransferAxisForX'];

        $terminalAttributes = [ 'id' =>'GENERICBNKAXIS', 'gateway' => Gateway::BT_AXIS, 'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');


        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type' => 'shared',
        ]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', [
            'business_banking' => 1,
            'live' => 0
            ]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $bankAccount = $this->createVirtualAccountForBanking('live');

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id'           => $balance1->getId(),
                'status'               => 'approved',
                'payer_ifsc'           => 'HDFC0000522',
                'payer_account_number' => '910910910910910'
            ]);

        $testData['request']['content']['Bene_acc_no'] = $bankAccount['account_number'];

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('F', $response['Stts_flg']);
        $this->assertEquals('002', $response['Err_cd']);
        $this->assertEquals('Validation failed', $response['message']);

        Queue::assertNotPushed(BankTransferCreateProcess::class);
    }

    public function testProcessingBankTransferAxisForX()
    {
        Mail::fake();

        $testData = $this->testData['testValidateBankTransferAxisForX'];

        $terminalAttributes = [ 'id' =>'GENERICBNKAXIS', 'gateway' => Gateway::BT_AXIS, 'gateway_merchant_id' => '9845',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
            'account_type' => 'shared',
        ]);

        $initialAmount = $balance1->getBalance();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    '10000000000000'        => '9845',
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        $bankAccount = $this->createVirtualAccountForBanking('live');

        $this->assertEquals(VirtualAccount\Provider::AXIS_COMMON_IFSC, $bankAccount['ifsc']);

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id'           => $balance1->getId(),
                'status'               => 'approved',
                'payer_ifsc'           => 'HDFC0000522',
                'payer_account_number' => '910910910910910'
            ]);

        $testData['request']['content']['Bene_acc_no'] = $bankAccount['account_number'];
        $testData['request']['content']['Req_type'] = 'notification';

        $this->ba->directAuth();

        $request = [
            'url' => '/ecollect/validate/axis',
            'method' => 'post',
            'server' => $testData['request']['server'],
            'content' => $testData['request']['content']
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('S', $response['Stts_flg']);
        $this->assertEquals('000', $response['Err_cd']);
        $this->assertEquals('Success', $response['message']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true, 'live');

        $this->assertEquals($bankAccount['account_number'], $bankTransfer['payee_account']);
        $this->assertEquals(VirtualAccount\Provider::AXIS_COMMON_IFSC, $bankTransfer['payee_ifsc']);

        $balance1->reload();
        $finalAmount = $balance1->getBalance();
        $this->assertEquals($initialAmount + 300, $finalAmount);
    }

    public function testBankTransferProcessWithFieldsOnLiveModeWithLedgerSns()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        Mail::fake();

        $this->ba->yesbankAuth('live');

        $balance1 = $this->getDbEntity('balance',
                                       [
                                           'merchant_id' => '10000000000000',
                                       ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance1['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $ba = $this->fixtures->on('live')->create('bank_account',
                                                  [
                                                      'merchant_id'    => '10000000000000',
                                                      'entity_id'      => 'ShrdVirtualAcc',
                                                      'type'           => 'virtual_account',
                                                      'account_number' => '2224440041626905',
                                                  ]);

        $this->fixtures->on('live')->create('virtual_account',
                                            [
                                                'id'              => 'ShrdVirtualAcc',
                                                'merchant_id'     => '10000000000000',
                                                'status'          => 'active',
                                                'bank_account_id' => $ba->getId(),
                                                'balance_id'      => $balance1->getId(),
                                            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferProcessWithFieldsOnLiveMode'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->startTest();

        Mail::assertQueued(BankTransfer::class, function($mail) {
            $this->assertEquals('transaction.created', $mail->viewData['event']);
            $this->assertEquals('2224440041626905', $mail->viewData['balance']['account_number']);
            $this->assertEquals('Your RazorpayX A/C XX6905 is credited with INR 50,000.00', $mail->subject);

            return true;
        });


        $bankTransfersCreated = $this->getDbEntities('bank_transfer', [], 'live');
        $transaction = $this->getLastEntity('transaction', true, 'live');

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('live', $ledgerRequestPayload['mode']);
            $this->assertEquals($bankTransfersCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('fund_loading_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertEquals('term_SHRDBANKACC3DS', $ledgerRequestPayload['identifiers']['terminal_id']);
            $this->assertEquals('nodal', $ledgerRequestPayload['identifiers']['terminal_account_type']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
            $this->assertEquals($transaction['entity_id'], 'bt_'.$bankTransfersCreated[$index]['id']);
            $this->assertEquals($transaction['id'], 'txn_'.$bankTransfersCreated[$index]['transaction_id']);
        }
    }

    public function testBankTransferProcessWithFieldsOnLiveModeWithLedgerReverseShadow()
    {
        $this->app['config']->set('applications.ledger.enabled', false);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

//        $ledgerSnsPayloadArray = [];
//        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

//        MockQueue::fake();

//        Mail::fake();

        $this->ba->yesbankAuth('live');

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance1['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => 'ShrdVirtualAcc',
                'type'           => 'virtual_account',
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id'              => 'ShrdVirtualAcc',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferProcessWithFieldsOnLiveMode'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->startTest();

//        Mail::assertQueued(BankTransfer::class, function($mail) {
//            $this->assertEquals('transaction.created', $mail->viewData['event']);
//            $this->assertEquals('2224440041626905', $mail->viewData['balance']['account_number']);
//            $this->assertEquals('Your RazorpayX A/C XX6905 is credited with INR 50,000.00', $mail->subject);
//
//            return true;
//        });


        $bankTransfersCreated = $this->getDbLastEntity('bank_transfer', 'live');
        $bankTransfersTxn = $this->getDbLastEntity('transaction', 'live');

//        MockQueue::assertPushed(Transactions::class);

        // assert bankTransfer
        $this->assertEquals('processed', $bankTransfersCreated['status']);
        $this->assertEquals(5000000, $bankTransfersCreated['amount']);

    }

    // Test for ledger reverse shadow case when sync ledger retries are exhausted
    public function testBankTransferProcessWithFieldsOnLiveModeWithLedgerReverseShadowSyncFailure()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(4)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                "body" => [
                    "id"                => "HNjsypA96SgJKJ",
                    "created_at"        => "1623848289",
                    "updated_at"        => "1632368730",
                    "amount"            => "130.000000",
                    "base_amount"       => "130.000000",
                    "currency"          => "INR",
                    "tenant"            => "X",
                    "transactor_id"     => "bt_IwHCToefEWVgph",
                    "transactor_event"  => "fund_loading_processed",
                    "transaction_date"  => "1611132045",
                    "ledger_entry" => [
                        [
                            "id"          => "HNjsypHNXdSiei",
                            "created_at"  => "1623848289",
                            "updated_at"  => "1623848289",
                            "merchant_id" => "HN59oOIDACOXt3",
                            "journal_id"  => "HNjsypA96SgJKJ",
                            "account_id"  => "GoRNyEuu9Hl0OZ",
                            "amount"      => "130.000000",
                            "base_amount" => "130.000000",
                            "type"        => "debit",
                            "currency"    => "INR",
                            "balance"     => ""
                        ],
                        [
                            "id"          => "HNjsypHPOUlxDR",
                            "created_at"  => "1623848289",
                            "updated_at"  => "1623848289",
                            "merchant_id" => "HN59oOIDACOXt3",
                            "journal_id"  => "HNjsypA96SgJKJ",
                            "account_id"  => "HN5AGgmKu0ki13",
                            "amount"      => "130.000000",
                            "base_amount" => "130.000000",
                            "type"        => "credit",
                            "currency"    => "INR",
                            "balance"     => "",
                            'account_entities' => [
                                'account_type'       => ['payable'],
                                'fund_account_type'  => ['merchant_va'],
                            ],
                        ]
                    ]
                ]
            ]);

        $this->ba->yesbankAuth('live');

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance1['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => 'ShrdVirtualAcc',
                'type'           => 'virtual_account',
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id'              => 'ShrdVirtualAcc',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferProcessWithFieldsOnLiveMode'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->startTest();

        $bankTransfersCreated = $this->getDbLastEntity('bank_transfer', 'live');
        $bankTransfersTxn = $this->getDbLastEntity('transaction', 'live');

        // assert bankTransfer
        $this->assertEquals('processed', $bankTransfersCreated['status']);
        $this->assertEquals(5000000, $bankTransfersCreated['amount']);

    }

    // Test for ledger reverse shadow case when both sync and async failure from ledger
    public function testBankTransferProcessWithFieldsOnLiveModeWithLedgerReverseShadowSyncAsyncFailure()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(4)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $this->ba->yesbankAuth('live');

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance1['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => 'ShrdVirtualAcc',
                'type'           => 'virtual_account',
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id'              => 'ShrdVirtualAcc',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferProcessWithFieldsOnLiveMode'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->startTest();

        $bankTransfersCreated = $this->getDbLastEntity('bank_transfer', 'live');

        // assert bankTransfer
        $this->assertEquals('created', $bankTransfersCreated['status']);
        $this->assertNull($bankTransfersCreated['transaction_id']);
        $this->assertEquals(5000000, $bankTransfersCreated['amount']);
    }

    // Test for ledger reverse shadow case when sync failure and async status check no record from ledger
    public function testBankTransferProcessWithFieldsOnLiveModeWithLedgerReverseShadowStatusCheckNoRecord()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(5)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andThrow(new \RZP\Exception\RuntimeException(
                'Unexpected response code received from Ledger service.',
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'record_not_found',
                    ],
                ]
            ));

        $this->ba->yesbankAuth('live');

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance1['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => 'ShrdVirtualAcc',
                'type'           => 'virtual_account',
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id'              => 'ShrdVirtualAcc',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferProcessWithFieldsOnLiveMode'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->startTest();

        $bankTransfersCreated = $this->getDbLastEntity('bank_transfer', 'live');

        // assert bankTransfer
        $this->assertEquals('created', $bankTransfersCreated['status']);
        $this->assertNull($bankTransfersCreated['transaction_id']);
        $this->assertEquals(5000000, $bankTransfersCreated['amount']);
    }

    // Test for ledger reverse shadow case when sync failure and async status check no record from ledger and create success
    public function testBankTransferProcessWithFieldsOnLiveModeWithLedgerReverseShadowPostStatusCheckSuccess()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // ledge response
        $ledgerSuccessResponse = [
            "body" => [
                "id"                => "HNjsypA96SgJKJ",
                "created_at"        => "1623848289",
                "updated_at"        => "1632368730",
                "amount"            => "130.000000",
                "base_amount"       => "130.000000",
                "currency"          => "INR",
                "tenant"            => "X",
                "transactor_id"     => "bt_SamplePayoutId4",
                "transactor_event"  => "fund_loading_processed",
                "transaction_date"  => "1611132045",
                "ledger_entry" => [
                    [
                        "id"          => "HNjsypHNXdSiei",
                        "created_at"  => "1623848289",
                        "updated_at"  => "1623848289",
                        "merchant_id" => "HN59oOIDACOXt3",
                        "journal_id"  => "HNjsypA96SgJKJ",
                        "account_id"  => "GoRNyEuu9Hl0OZ",
                        "amount"      => "130.000000",
                        "base_amount" => "130.000000",
                        "type"        => "debit",
                        "currency"    => "INR",
                        "balance"     => "",
                        'account_entities' => [
                            'account_type'       => ['payable'],
                            'fund_account_type'  => ['merchant_va'],
                        ],
                    ],
                    [
                        "id"          => "HNjsypHPOUlxDR",
                        "created_at"  => "1623848289",
                        "updated_at"  => "1623848289",
                        "merchant_id" => "HN59oOIDACOXt3",
                        "journal_id"  => "HNjsypA96SgJKJ",
                        "account_id"  => "HN5AGgmKu0ki13",
                        "amount"      => "130.000000",
                        "base_amount" => "130.000000",
                        "type"        => "credit",
                        "currency"    => "INR",
                        "balance"     => ""
                    ]
                ]
            ]
        ];
        $mockLedger->shouldReceive('createJournal')
            ->times(5)
            ->andReturnUsing(
                function () use($ledgerSuccessResponse) {
                    static $counter = 0;
                    switch ($counter++) {
                        // 4th call is made from async job, which should succeed for this test
                        case 4:
                            return $ledgerSuccessResponse;
                            break;
                        default:
                            // 0th-3rd call is made while sync retries, which should fail for this test
                            throw new \WpOrg\Requests\Exception(
                                'Unexpected response code received from Ledger service.',
                                null,
                                [
                                    'status_code'   => 500,
                                    'response_body' => [
                                        'code' => 'invalid_argument',
                                        'msg' => 'unknown',
                                    ],
                                ]
                            );
                            break;
                    }
                }
            );

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andThrow(new \RZP\Exception\RuntimeException(
                'Unexpected response code received from Ledger service.',
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'record_not_found',
                    ],
                ]
            ));

        $this->ba->yesbankAuth('live');

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance1['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => 'ShrdVirtualAcc',
                'type'           => 'virtual_account',
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id'              => 'ShrdVirtualAcc',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferProcessWithFieldsOnLiveMode'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->startTest();

        $bankTransfersCreated = $this->getDbLastEntity('bank_transfer', 'live');
        $bankTransfersTxn = $this->getDbLastEntity('transaction', 'live');

        // assert bankTransfer
        $this->assertEquals('processed', $bankTransfersCreated['status']);
        $this->assertEquals(5000000, $bankTransfersCreated['amount']);
    }

    public function testBankTransferProcessWithIncorrectPayeeAccountLength()
    {
        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $countOfBankTransferRequestsBeforeFundLoading = count($this->getDbEntities('bank_transfer_request', [], 'live'));

        Mail::fake();

        $balance1 = $this->getDbEntity('balance',
                                       [
                                           'merchant_id' => '10000000000000',
                                       ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
        ]);

        $ba = $this->fixtures->on('live')->create('bank_account',
                                                  [
                                                      'merchant_id'    => '10000000000000',
                                                      'entity_id'      => 'ShrdVirtualAcc',
                                                      'type'           => 'virtual_account',
                                                      'account_number' => '2224440041626905',
                                                  ]);

        $this->fixtures->on('live')->create('virtual_account',
                                            [
                                                'id'              => 'ShrdVirtualAcc',
                                                'merchant_id'     => '10000000000000',
                                                'status'          => 'active',
                                                'bank_account_id' => $ba->getId(),
                                                'balance_id'      => $balance1->getId(),
                                            ]);

        // Keeping it equal to 4 digits to make this request fail.
        $accountNumber = '3434';
        $utr           = 'RANDOMUTR012345';

        $this->testData[__FUNCTION__]['request']['content']['payee_account']  = $accountNumber;
        $this->testData[__FUNCTION__]['request']['content']['payee_ifsc']     = 'ICIC0000104';
        $this->testData[__FUNCTION__]['request']['content']['transaction_id'] = $utr;

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $countOfBankTransferRequestsAfterFundLoading = count($this->getDbEntities('bank_transfer_request', [], 'live'));

        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfBankTransfersBeforeFundLoading, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading, $countOfTransactionsAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading, $countOfPayoutsAfterFundLoading);
        $this->assertEquals($countOfBankTransferRequestsBeforeFundLoading + 1, $countOfBankTransferRequestsAfterFundLoading);

        $bankTransferRequest = $this->getDbLastEntity('bank_transfer_request', 'live');

        $this->assertEquals('BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH',
                            $bankTransferRequest[Entity::ERROR_MESSAGE]);
        $this->assertEquals(false, $bankTransferRequest[Entity::IS_CREATED]);

        // This ensures that we did not create a Bank Transfer and are not sending any email for the same.
        Mail::assertNotQueued(BankTransfer::class);
    }

    public function testEcollectYesbankBatchCreate()
    {
        $data = $this->testData['ecollectYesbankBatchData'];

        $this->createExcelFile($data, 'filename', 'files/filestore');

        $this->ba->h2hAuth();

        $this->startTest();
    }

    public function testEcollectYesbankBatchCreateDuplicate()
    {
        $ifsc = Provider::IFSC[Provider::YESBANK];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = 4564562235678281;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->fixtures->on('live')->create('bank_transfer', [
            'amount' => 50000,
            'payee_account' => "4564562235678281",
            'utr' => $request['content']['transaction_id']
        ]);

        $this->ba->batchAppAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['valid']);
        $this->assertEquals($request['content']['transaction_id'], $response['transaction_id']);
        $this->assertNull($response['message']);

        $btrCount = count($this->getDbEntities('bank_transfer_request', [], 'live'));

        $this->assertEquals($btrCount, 0);
    }

    public function testEcollectIdfcBatchCreate()
    {
        $data = $this->testData['ecollectIdfcBatchData'];

        $this->createCsvFile($data, 'filename', null, 'files/filestore');

        $this->ba->h2hAuth();

        $this->startTest();
    }

    public function testPendingBankTransfer()
    {
        $this->testBankTransferProcessWithIncorrectPayeeAccountLength();

        $payerAccount = '9876543210123456789';
        $payerIfsc    = 'YESB0000022';
        $payerName    = 'Name of account holder';
        $payeeAccount = '3434';
        $payeeIfsc    = 'ICIC0000104';
        $description  = 'IMPS payment of 50,000 rupees';
        $utr          = 'RANDOMUTR012345';

        Mail::fake();

        $this->setupForIciciXFundLoading();

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id' => $this->bankingBalance->getId(),
                'status'     => 'approved',
                'is_active'  => 0,
            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');


        $bankTransferRequest = $this->getDbLastEntity('bank_transfer_request', 'live');

        $this->testData[__FUNCTION__]['request']['content']['bank_transfer_request_id']  = $bankTransferRequest->getId();

        $commonMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id' => '100000Razorpay',
                'type'        => 'banking'
            ], 'live');

        // Making pricing zero for this specific payout amount, mode etc. so that
        $this->fixtures->on('live')->edit('pricing', 'Bbg7e4oKCgaubd',
            [
                'fixed_rate' => 0
            ]);

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no payments were created during this request
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading + 1, $countOfPayoutsAfterFundLoading);

        // Assert that two transactions were created. One for Bank transfer and one for payout.
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 2, $countOfTransactionsAfterFundLoading);

        $creditTransaction = $this->getDbEntity('transaction',
            [
                'balance_id' => $commonMerchantBankingBalance->getId(),
                'type'       => 'bank_transfer'
            ], 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

        $expectedAmount = '50000' . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
            ['id' => VirtualAccount\Entity::SHARED_ID_BANKING],
            'live');

        // Assertions on credit transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals(null, $bankTransfer->getPaymentId());

        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND',
            $bankTransfer->getUnexpectedReason());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($payerAccount, $payerBankAccount->getAccountNumber());
        $this->assertEquals($payerIfsc, $payerBankAccount->getIfscCode());
        $this->assertEquals($payerName, $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($payerIfsc, $bankTransfer->getPayerIfsc());
        $this->assertEquals($payerName, $bankTransfer->getPayerName());
        $this->assertEquals($payerAccount, $bankTransfer->getPayerAccount());
        $this->assertEquals($payeeAccount, $bankTransfer->getPayeeAccount());
        $this->assertEquals($payeeIfsc, $bankTransfer->getPayeeIfsc());
        $this->assertEquals($description, $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());
        $this->assertEquals(S::PROCESSED, $bankTransfer->getStatus());

        // Since approved and active TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);

        //
        // Assertions for the Payout created during refund flow
        //
        $refundPayout = $this->getDbLastEntity('payout', 'live');

        $debitTransaction = $this->getDbEntity('transaction',
            [
                'balance_id' => $commonMerchantBankingBalance->getId(),
                'type'       => 'payout'
            ], 'live');

        $payoutSource = $this->getDbLastEntity('payout_source', 'live');

        $updatedCommonMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id' => '100000Razorpay',
                'type'        => 'banking'
            ], 'live');

        // Assertions on payout created
        $this->assertEquals($bankTransfer->getMerchantId(), $refundPayout->getMerchantId());
        $this->assertEquals($bankTransfer->getAmount(), $refundPayout->getAmount());
        $this->assertEquals($bankTransfer->getPayerAccount(),
            $refundPayout->fundAccount->account->getAccountNumber());
        $this->assertEquals($bankTransfer->getUtr(), $refundPayout->getReferenceId());

        // Assertions on payout sources entity
        $this->assertEquals($bankTransfer->getPublicId(), $payoutSource['source_id']);
        $this->assertEquals('bank_transfer', $payoutSource['source_type']);
        $this->assertEquals($refundPayout->getId(), $payoutSource['payout_id']);

        // Assertions on the debit transaction entity
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $debitTransaction->getMerchantId());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getAmount());
        $this->assertEquals(0, $debitTransaction->getCredit());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getDebit());

        // Assertions on balance.
        // The balance should not change because we'll have a credit and a debit of the exact same amount
        $this->assertEquals($commonMerchantBankingBalance['balance'], $updatedCommonMerchantBankingBalance['balance']);

    }

    public function testPendingBankTransferWithInvalidID()
    {
        $this->testData[__FUNCTION__]['request']['content']['bank_transfer_request_id']  = 'random';

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function processBankTransfer($accountNumber, $ifsc, $utr = null, $amount = null, $mode = 'test')
    {
        $this->ba->proxyAuth();

        return $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr, $amount, $mode);
    }

    protected function notifyBankTransfer($accountNumber, $ifsc, $utr = null)
    {
        $this->ba->kotakAuth();

        return $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr);
    }

    protected function getRblVaBankAccount()
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKRBL', 'gateway' => Gateway::BT_RBL, 'gateway_merchant_id' => '0001046' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount();

        return $bankAccount['account_number'];
    }

    protected function getIciciVaBankAccount($mode = 'test')
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKICI', 'gateway' => Gateway::BT_ICICI, 'gateway_merchant_id' => '2244' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount($mode);

        return $bankAccount['account_number'];
    }

    protected function getHdfcEcmsVaBankAccount()
    {
        $order = $this->fixtures->create('order');

        $bankAccount = $this->createVirtualAccountForOrder($order)['receivers'][0];

        return $bankAccount['account_number'];
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Header::ICICI_ECOLLECT_UTR                      => 'O20374917020',
                Header::ICICI_ECOLLECT_CUSTOMER_CODE           => '2233',
                Header::ICICI_ECOLLECT_CREDIT_ACCOUNT_NO        => '205025290',
                Header::ICICI_ECOLLECT_DEALER_CODE              => '444455556666',
                Header::ICICI_ECOLLECT_PAYMENT_TYPE             => 'IMPS',
                Header::ICICI_ECOLLECT_REMITTANCE_INFORMATION   => 'test remittance',
                Header::ICICI_ECOLLECT_REMITTER_ACCOUNT_NAME    => 'Test Name',
                Header::ICICI_ECOLLECT_REMITTER_ACCOUNT_NO      => '914010018542355',
                Header::ICICI_ECOLLECT_REMITTING_BANK_IFSC_CODE => 'UTIB',
                Header::ICICI_ECOLLECT_TRANSACTION_AMOUNT       => '5000',
                Header::ICICI_ECOLLECT_TRANSACTION_DATE         => '07/03/2020',
                Header::ICICI_ECOLLECT_REMITTING_BANK_UTR_NO    => '6716048037',
            ],
        ];
    }

    /**
     * We are checking banktransfer to Virtual Account of merchant which is not live.
     * in such a case, refund will be processed .
     */

    public function testBankTransferToVirtualAccountMerchantNotLiveVa()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc     = $this->bankAccount['ifsc'];

        $this->fixtures->edit('merchant', 10000000000000, ['live' => 0]);

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id' => $this->bankingBalance->getId(),
                'status'     => 'approved',
                'is_active'  => 0,
            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $request['content']['transaction_id'] = $utr;

        $commonMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id' => '100000Razorpay',
                'type'        => 'banking'
            ], 'live');

        // Making pricing zero for this specific payout amount, mode etc. so that
        $this->fixtures->on('live')->edit('pricing', 'Bbg7e4oKCgaubd',
            [
                'fixed_rate' => 0
            ]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no payments were created during this request
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);
        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading + 1, $countOfPayoutsAfterFundLoading);

        // Assert that two transactions were created. One for Bank transfer and one for payout.
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 2, $countOfTransactionsAfterFundLoading);

        $creditTransaction = $this->getDbEntity('transaction',
            [
                'balance_id' => $commonMerchantBankingBalance->getId(),
                'type'       => 'bank_transfer'
            ], 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
            ['id' => VirtualAccount\Entity::SHARED_ID_BANKING],
            'live');

        // Assertions on credit transaction entity created//
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        //Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals(null, $bankTransfer->getPaymentId());

        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('VIRTUAL_ACCOUNT_MERCHANT_NOT_LIVE',
            $bankTransfer->getUnexpectedReason());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since approved and active TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);

        // Assertions for the Payout created during refund flow
        $refundPayout = $this->getDbLastEntity('payout', 'live');

        $debitTransaction = $this->getDbEntity('transaction',
            [
                'balance_id' => $commonMerchantBankingBalance->getId(),
                'type'       => 'payout'
            ], 'live');

        $payoutSource = $this->getDbLastEntity('payout_source', 'live');

        $updatedCommonMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id' => '100000Razorpay',
                'type'        => 'banking'
            ], 'live');

        // Assertions on payout created
        $this->assertEquals($bankTransfer->getMerchantId(), $refundPayout->getMerchantId());
        $this->assertEquals($bankTransfer->getAmount(), $refundPayout->getAmount());
        $this->assertEquals($bankTransfer->getPayerAccount(),
            $refundPayout->fundAccount->account->getAccountNumber());
        $this->assertEquals($bankTransfer->getUtr(), $refundPayout->getReferenceId());

        // Assertions on payout sources entity
        $this->assertEquals($bankTransfer->getPublicId(), $payoutSource['source_id']);
        $this->assertEquals('bank_transfer', $payoutSource['source_type']);
        $this->assertEquals($refundPayout->getId(), $payoutSource['payout_id']);

        // Assertions on the debit transaction entity
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $debitTransaction->getMerchantId());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getAmount());
        $this->assertEquals(0, $debitTransaction->getCredit());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getDebit());

        // Assertions on balance.
        // The balance should not change because we'll have a credit and a debit of the exact same amount
        $this->assertEquals($commonMerchantBankingBalance['balance'], $updatedCommonMerchantBankingBalance['balance']);

    }

    /**
     * We are checking banktransfer to Virtual Account of merchant which is not live on PG but live on X.
     * in such a case, bank transfer will be processed and no refund will be sent.
     */

    public function testBankTransferToVirtualAccountMerchantNotLiveOnPgButLiveOnX()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->fixtures->edit('merchant', 10000000000000, ['live' => 0]);

        $this->fixtures->on('live')->create('merchant_attribute',
            [
                'merchant_id'   => '10000000000000',
                'product'       => 'banking',
                'group'         => 'products_enabled',
                'type'          => 'X',
                'value'         => 'true',
                'updated_at'    => time(),
                'created_at'    => time()
            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $whitelistedAccounts = [
            [
                'account_number' => $request['content']['payer_account'],
                'ifsc_code'      => $request['content']['payer_ifsc']
            ]];

        $this->setupGlobalWhitelistPayerAccounts($whitelistedAccounts);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('icici', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since this was a global whitelisted account, we shall not send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $merchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfPaymentsBeforeFundLoading + 1, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading, $countOfPayoutsAfterFundLoading);

        $payment = $this->getDbLastEntity('payment', 'live');

        $creditTransaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
                                                   ['id' => VirtualAccount\Entity::SHARED_ID],
                                                   'live');

        // Assertions on payment entity created
        $this->assertEquals('authorized', $payment->getStatus());
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $payment->getMerchantId());
        $this->assertEquals($creditTransaction->getId(), $payment->getTransactionId());
        $this->assertEquals($expectedAmount, $payment->getAmount());
        $this->assertNotNull($payment->getRefundAt());

        // Assertions on transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals($payment->getId(), $bankTransfer->getPaymentId());
        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING',
                            $bankTransfer->getUnexpectedReason());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertQueued(FundLoadingFailed::class, function($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('₹ 50000', $viewData['amount']);
            $this->assertEquals('YESB0000022', $viewData['payer_ifsc']);
            $this->assertEquals('XXXXXXXXXXXXXXX6789', $viewData['payer_account_number']);
            $this->assertEquals('XXXXXXXXXXXX3333', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = 'Fund loading of ₹ 50000 to your RazorpayX account number XXXXXXXXXXXX3333 has been rejected';

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });

        $updatedMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');

        // Assertions on balance.
        // The balance should not change since TPV failed
        $this->assertEquals($merchantBankingBalance['balance'], $updatedMerchantBankingBalance['balance']);
    }

    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButApprovedActiveTpvAccountFound()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'payer_ifsc' => 'YESB0000022',
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $merchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading, $countOfPayoutsAfterFundLoading);

        $creditTransaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals('bank_transfer', $creditTransaction->getType());
        $this->assertEquals($bankTransfer->getId(), $creditTransaction->getEntityId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('icici', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        Mail::assertNotQueued(FundLoadingFailed::class);

        $updatedMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');

        // Assertions on balance.
        // The balance should not change since TPV failed
        $this->assertEquals($merchantBankingBalance['balance'] + $bankTransfer['amount'],
                            $updatedMerchantBankingBalance['balance']);
    }

    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButPendingTpvAccountFound()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'pending',
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $merchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');


        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfPaymentsBeforeFundLoading + 1, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading, $countOfPayoutsAfterFundLoading);

        $payment = $this->getDbLastEntity('payment', 'live');

        $creditTransaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
                                                   ['id' => VirtualAccount\Entity::SHARED_ID],
                                                   'live');

        // Assertions on payment entity created
        $this->assertEquals('authorized', $payment->getStatus());
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $payment->getMerchantId());
        $this->assertEquals($creditTransaction->getId(), $payment->getTransactionId());
        $this->assertEquals($expectedAmount, $payment->getAmount());
        $this->assertNotNull($payment->getRefundAt());

        // Assertions on transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals($payment->getId(), $bankTransfer->getPaymentId());
        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING',
                            $bankTransfer->getUnexpectedReason());
        $this->assertEquals(S::PROCESSED, $bankTransfer->getStatus());
        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since approved and active TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertQueued(FundLoadingFailed::class, function($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('₹ 50000', $viewData['amount']);
            $this->assertEquals('YESB0000022', $viewData['payer_ifsc']);
            $this->assertEquals('XXXXXXXXXXXXXXX6789', $viewData['payer_account_number']);
            $this->assertEquals('XXXXXXXXXXXX3333', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = 'Fund loading of ₹ 50000 to your RazorpayX account number XXXXXXXXXXXX3333 has been rejected';

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });

        $updatedMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');

        // Assertions on balance.
        // The balance should not change since TPV failed
        $this->assertEquals($merchantBankingBalance['balance'], $updatedMerchantBankingBalance['balance']);
    }

    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButInActiveTpvAccountFound()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'is_active'  => 0,
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $merchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfPaymentsBeforeFundLoading + 1, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading, $countOfPayoutsAfterFundLoading);

        $payment = $this->getDbLastEntity('payment', 'live');

        $creditTransaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
                                                   ['id' => VirtualAccount\Entity::SHARED_ID],
                                                   'live');

        // Assertions on payment entity created
        $this->assertEquals('authorized', $payment->getStatus());
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $payment->getMerchantId());
        $this->assertEquals($creditTransaction->getId(), $payment->getTransactionId());
        $this->assertEquals($expectedAmount, $payment->getAmount());
        $this->assertNotNull($payment->getRefundAt());

        // Assertions on transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals($payment->getId(), $bankTransfer->getPaymentId());
        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING',
                            $bankTransfer->getUnexpectedReason());

        $this->assertEquals(S::PROCESSED, $bankTransfer->getStatus());
        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since approved and active TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertQueued(FundLoadingFailed::class, function($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('₹ 50000', $viewData['amount']);
            $this->assertEquals('YESB0000022', $viewData['payer_ifsc']);
            $this->assertEquals('XXXXXXXXXXXXXXX6789', $viewData['payer_account_number']);
            $this->assertEquals('XXXXXXXXXXXX3333', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = 'Fund loading of ₹ 50000 to your RazorpayX account number XXXXXXXXXXXX3333 has been rejected';

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });

        $updatedMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');

        // Assertions on balance.
        // The balance should not change since TPV failed
        $this->assertEquals($merchantBankingBalance['balance'], $updatedMerchantBankingBalance['balance']);
    }

    // Using feature flag to disable tpv flow for a specific merchant. This can be used to disable tpv flow for a
    // specific merchant without affecting the flow for other merchants.
    public function testBankTransferIciciIMPSForRazorpayXWithTpvDisabledViaFeatureFlag()
    {
        Mail::fake();

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_TPV_FLOW,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->setupForIciciXFundLoading();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('icici', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since TPV account was found, we shall not send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    // We test that fund loading is successful if it is from a globally whitelisted account number and ifsc code even
    // if tpv is enabled for the merchant and it is not added as tpv account
    public function testBankTransferIciciIMPSForRazorpayXViaGloballyWhitelistedPayerAccountWhenTpvIsEnabled()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $whitelistedAccounts = [
            [
                'account_number' => $request['content']['payer_account'],
                'ifsc_code'      => $request['content']['payer_ifsc']
            ]];

        $this->setupGlobalWhitelistPayerAccounts($whitelistedAccounts);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('icici', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since this was a global whitelisted account, we shall not send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    // We test that fund loading is successful if it is from a globally whitelisted account number and ifsc code if tpv
    // is not enabled for the merchant. This just checks that the code doesn't hamper the  existing flow.
    public function testBankTransferIciciIMPSForRazorpayXViaGloballyWhitelistedPayerAccountWhenTpvIsNotEnabled()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_TPV_FLOW,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $whitelistedAccounts = [
            [
                'account_number' => $request['content']['payer_account'],
                'ifsc_code'      => $request['content']['payer_ifsc']
            ]];

        $this->setupGlobalWhitelistPayerAccounts($whitelistedAccounts);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('icici', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since this was a global whitelisted account, we shall not send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    // We test that fund loading is successful if the account number for globally whitelisted account
    public function testBankTransferIciciIMPSForRazorpayXViaGloballyWhitelistedPayerAccountWithWrongIfscTpvEnabled()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'is_active'  => 0,
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $whitelistedAccounts = [
            [
                'account_number' => $request['content']['payer_account'],
                'ifsc_code'      => 'HDFC0000104'
            ]];

        $this->setupGlobalWhitelistPayerAccounts($whitelistedAccounts);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('icici', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since this was a global whitelisted account, we shall not send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    // We test that if payer ifsc is sent incorrect by bank like 9229 etc is sent as ifsc, we use the correct mapping
    // created by smart collect team and map it to correct ifsc and create a payer bank account and validate banking
    // account tpv based on that.
    public function testSuccessfulFundLoadingWithIncorrectPayerIfscFromYesBankWhenTpvIsEnabled()
    {
        Mail::fake();

        $this->setupForYesBankXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'payer_ifsc' => 'ICIC0002445',
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/ecollect/validate';

        $request['content']['payer_ifsc'] = '9229';

        $request['content']['mode'] = 'imps';

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->yesbankAuth('live');

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('yesbank', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals(BankCodes::NBIN_TO_IFSC_MAPPING[$request['content']['payer_ifsc']],
                            $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since banking account tpv account was found, we don't send the mail.
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    // We test that if payer ifsc is sent incorrect by bank like 9229 etc is sent as ifsc, we use the correct mapping
    // created by smart collect team and map it to correct ifsc and create a payer bank account and check for global
    // whitelisted accounts based on that. The fund loading should be successful even when tpv is enabled and tpv
    // account is not added for this merchant and balance.
    public function testSuccessfulFundLoadingWithIncorrectPayerIfscFromYesBankFromGlobalWhitelistedAccountWithTpv()
    {
        Mail::fake();

        $this->setupForYesBankXFundLoading();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/ecollect/validate';

        $request['content']['payer_ifsc'] = '9229';

        $request['content']['mode'] = 'imps';

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $whitelistedAccounts = [
            [
                'account_number' => $request['content']['payer_account'],
                'ifsc_code'      => BankCodes::NBIN_TO_IFSC_MAPPING[$request['content']['payer_ifsc']]
            ]];

        $this->setupGlobalWhitelistPayerAccounts($whitelistedAccounts);

        $this->ba->yesbankAuth('live');

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('yesbank', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals(BankCodes::NBIN_TO_IFSC_MAPPING[$request['content']['payer_ifsc']],
                            $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since this was a global whitelisted account, we shall not send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    // We test that if payer ifsc is sent incorrect by bank like 9229 etc is sent as ifsc, we use the correct mapping
    // created by smart collect team and map it to correct ifsc and create a payer bank account to which the money will
    // be refunded.
    public function testSuccessFulCreationOfPaymentAndPayerBankAccountInCaseOfIncorrectPayeeDetailsAndPayerIfsc()
    {
        Mail::fake();

        $this->setupForYesBankXFundLoading();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/ecollect/validate';

        $request['content']['payer_ifsc'] = '9229';

        $request['content']['mode'] = 'imps';

        $request['content']['payee_account'] = 2114440041626905;

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->yesbankAuth('live');

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfPaymentsBeforeFundLoading + 1, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $payment = $this->getDbLastEntity('payment', 'live');

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
                                                   ['id' => VirtualAccount\Entity::SHARED_ID],
                                                   'live');

        // Assertions on payment entity created
        $this->assertEquals('authorized', $payment->getStatus());
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $payment->getMerchantId());
        $this->assertEquals($transaction->getId(), $payment->getTransactionId());
        $this->assertEquals($expectedAmount, $payment->getAmount());
        $this->assertNotNull($payment->getRefundAt());

        // Assertions on transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('yesbank', $bankTransfer->getGateway());
        $this->assertEquals($payment->getId(), $bankTransfer->getPaymentId());
        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $bankTransfer->getUnexpectedReason());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals(BankCodes::NBIN_TO_IFSC_MAPPING[$request['content']['payer_ifsc']],
                            $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since this was an incorrect payee account number case, we won't send the fund loading mail.
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    // We test that if payer name is sent with initial spaces, it doesn't fail creation of bank transfer and saves the
    // bank transfer as is but trims the beneficiary name in payer bank account so that payer bank account creation
    // doesn't fail.
    public function testSuccessfulFundLoadingWithSpacesAroundPayerNameFromYesBankWhenTpvIsEnabled()
    {
        Mail::fake();

        $this->setupForYesBankXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'payer_ifsc' => 'ICIC0002445',
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/ecollect/validate';

        $request['content']['payer_ifsc'] = '9229';

        $request['content']['payer_name'] = '  Name of account holder ';

        $request['content']['mode'] = 'imps';

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->yesbankAuth('live');

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('yesbank', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals(BankCodes::NBIN_TO_IFSC_MAPPING[$request['content']['payer_ifsc']],
                            $payerBankAccount->getIfscCode());
        $this->assertEquals(trim($request['content']['payer_name']), $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since banking account tpv account was found, we don't send the mail.
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    // We test that if payer account number is sent with extra or less zeroes, it doesn't fail creation of bank transfer
    // and saves the bank transfer as is but finds the tpv account by trimming the zeroes in payer account number and
    // matching with the trimmed payer account number column in banking_account_tpvs table.
    public function testSuccessfulFundLoadingWithZeroesPrependedPayerAccountNumberFromYesBankWhenTpvIsEnabled()
    {
        Mail::fake();

        $this->setupForYesBankXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id'           => $this->bankingBalance->getId(),
                                                'status'               => 'approved',
                                                'payer_ifsc'           => 'ICIC0002445',
                                                'payer_account_number' => '0923847198498'
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/ecollect/validate';

        $request['content']['payer_ifsc'] = '9229';

        $request['content']['payer_name'] = '  Name of account holder ';

        $request['content']['mode'] = 'imps';

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payer_account'] = '00923847198498';

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->yesbankAuth('live');

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('yesbank', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals(BankCodes::NBIN_TO_IFSC_MAPPING[$request['content']['payer_ifsc']],
                            $payerBankAccount->getIfscCode());
        $this->assertEquals(trim($request['content']['payer_name']), $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since banking account tpv account was found, we don't send the mail.
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    // Here, since we have removed the ifsc check from tpv flow, even with incorrect ifsc but correct account number,
    // fund loading should be successful.
    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButIncorrectIfsc()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'payer_ifsc' => 'YESB0000022',
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['payer_ifsc'] = 'HDFC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals($bankTransfer->getId(), $transaction->getEntityId());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals($this->virtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('icici', $bankTransfer->getGateway());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    protected function setIciciVaBankAccountTerminalForRazorpayX()
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKICI',
                                'gateway' => Gateway::BT_ICICI,
                                'gateway_merchant_id' => '5656'];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal',
                                            $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal',
                                            $terminalAttributes);
    }

    protected function setupForIciciXFundLoading()
    {
        $this->setIciciVaBankAccountTerminalForRazorpayX();

        $this->setUpMerchantForBusinessBankingLive(true);

        $this->setUpCommonMerchantForBusinessBankingLive(true);

        $this->fixtures->on('live')->edit(
            'bank_account',
            $this->bankAccount->getId(),
            [
                'ifsc_code'         => 'ICIC0000104',
                'account_number'    => '3434111122223333'
            ]
        );

        $this->bankAccount = $this->bankAccount->reload();
    }

    protected function setYesBankVaBankAccountTerminalForRazorpayX()
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKYES',
                                'gateway' => Gateway::BT_YESBANK,
                                'gateway_merchant_id' => '5656'];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal',
                                            $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal',
                                            $terminalAttributes);
    }

    protected function setupForYesBankXFundLoading()
    {
        $this->setYesBankVaBankAccountTerminalForRazorpayX();

        $this->setUpMerchantForBusinessBankingLive(true);

        $this->fixtures->on('live')->edit(
            'bank_account',
            $this->bankAccount->getId(),
            [
                'ifsc_code' => 'YESB0CMSNOC',
            ]
        );

        $this->bankAccount = $this->bankAccount->reload();
    }

    protected function listCountOfPaymentTransactionPayoutAndBankTransferEntities(string $mode = 'test')
    {
        $countOfPayments = count($this->getDbEntities('payment', [], $mode));

        $countOfTransactions = count($this->getDbEntities('transaction', [], $mode));

        $countOfBankTransfers = count($this->getDbEntities('bank_transfer', [], $mode));

        $countOfPayouts = count($this->getDbEntities('payout', [], $mode));

        return [$countOfPayments, $countOfTransactions, $countOfBankTransfers, $countOfPayouts];
    }

    /*
     * This method sets global whitelist of payer accounts in redis key via admin auth where admin has the correct
     * permission assigned to them. Also, it asserts the response of the redis key update to check whether the update
     * was successful or not.
     *
     * The $whitelistedAccounts should be an array of arrays of the format
     * $whitelistedAccounts = [
     *  [
     *      'account_number' => {{account_number}}
     *      'ifsc_code'      => {{ifsc_code}}
     *  ],
     *  [
     *      'account_number' => {{account_number}}
     *      'ifsc_code'      => {{ifsc_code}}
     *  ],
     *  .
     *  .
     *  .
     * ]
     */
    protected function setupGlobalWhitelistPayerAccounts(array $whitelistedAccounts = [])
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:rx_globally_whitelisted_payer_accounts_for_fund_loading' => [
                ],
            ],
        ];

        foreach ($whitelistedAccounts as $whitelistedAccount)
        {
            if ((isset($whitelistedAccount['account_number'])) and
                (isset($whitelistedAccount['ifsc_code'])))
            {
                $payerAccountsToBeWhitelisted =
                    & $request['content']['config:rx_globally_whitelisted_payer_accounts_for_fund_loading'];

                $whiteListedAccountDetails = [
                    'account_number' => $whitelistedAccount['account_number'],
                    'ifsc_code'      => $whitelistedAccount['ifsc_code']
                ];

                array_push($payerAccountsToBeWhitelisted, $whiteListedAccountDetails);
            }
        }

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayKeysExist($response[0], ['key', 'old_value', 'new_value']);

        $responseForGlobalWhitelistKey = $response[0];

        // Since we only updated one key, one array should come in response
        $this->assertEquals(1, count($response));

        // Check whether the correct key was updated
        $this->assertEquals('config:rx_globally_whitelisted_payer_accounts_for_fund_loading',
                            $responseForGlobalWhitelistKey['key']);

        $whitelistedAccounts = $responseForGlobalWhitelistKey['new_value'];

        $expectedWhitelistedAccounts = ['new_values' => $payerAccountsToBeWhitelisted];

        $actualWhitelistedAccounts = ['new_values' => $whitelistedAccounts];

        // Check if all the accounts were updated correctly in redis
        $this->assertArraySelectiveEquals($expectedWhitelistedAccounts, $actualWhitelistedAccounts);
    }

    public function testMultipleWhitelistAccountAdditionByAdmin()
    {
        $whitelistedAccounts = [
            [
                'account_number' => 9876543210123456790,
                'ifsc_code'      => 'YESB0000022'
            ],
            [
                'account_number' => 9876543210123456789,
                'ifsc_code'      => 'ICIC0000022'
            ],
        ];

        $this->setupGlobalWhitelistPayerAccounts($whitelistedAccounts);
    }

    /**
     * We are trying to load funds to some random account number which does not exist in our system.
     * This will fail and get refunded back via payment to a common merchant and refund
     */
    public function testBankTransferIciciIMPSForRazorpayXWherePayeeAccountNumberDoesNotExist()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id' => $this->bankingBalance->getId(),
                'status'     => 'approved',
                'is_active'  => 0,
            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = '3434111122229999';

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfPaymentsBeforeFundLoading + 1, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $payment = $this->getDbLastEntity('payment', 'live');

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
            ['id' => VirtualAccount\Entity::SHARED_ID],
            'live');

        // Assertions on payment entity created
        $this->assertEquals('authorized', $payment->getStatus());
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $payment->getMerchantId());
        $this->assertEquals($transaction->getId(), $payment->getTransactionId());
        $this->assertEquals($expectedAmount, $payment->getAmount());
        $this->assertNotNull($payment->getRefundAt());

        // Assertions on transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $transaction->getMerchantId());
        $this->assertEquals($expectedAmount, $transaction->getAmount());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals($payment->getId(), $bankTransfer->getPaymentId());
        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $bankTransfer->getUnexpectedReason());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since approved and active TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);
    }

    /**
     * We are trying to load funds to some random account number which does not exist in our system.
     * This will fail and get refunded back via fund loading to common merchant and a payout from that merchant.
     */
    public function testBankTransferIciciIMPSForRazorpayXWherePayeeAccountNumberDoesNotExistAndRefundsViaX()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        // This makes sure that the refunds for failed fund loadings on X happen via X
        (new Service)->setConfigKeys([ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X => true]);

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'is_active'  => 0,
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = '3434111122229999';

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $commonMerchantBankingBalance = $this->getDbEntity('balance',
                                                           [
                                                               'merchant_id' => '100000Razorpay',
                                                               'type'        => 'banking'
                                                           ], 'live');

        // Making pricing zero for this specific payout amount, mode etc. so that
        $this->fixtures->on('live')->edit('pricing', 'Bbg7e4oKCgaubd',
                                          [
                                              'fixed_rate' => 0
                                          ]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no payments were created during this request
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading + 1, $countOfPayoutsAfterFundLoading);

        // Assert that two transactions were created. One for Bank transfer and one for payout.
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 2, $countOfTransactionsAfterFundLoading);

        $creditTransaction = $this->getDbEntity('transaction',
                                                [
                                                    'balance_id' => $commonMerchantBankingBalance->getId(),
                                                    'type'       => 'bank_transfer'
                                                ], 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
                                                   ['id' => VirtualAccount\Entity::SHARED_ID_BANKING],
                                                   'live');

        // Assertions on credit transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals(null, $bankTransfer->getPaymentId());

        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND',
                            $bankTransfer->getUnexpectedReason());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since approved and active TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertNotQueued(FundLoadingFailed::class);

        //
        // Assertions for the Payout created during refund flow
        //
        $refundPayout = $this->getDbLastEntity('payout', 'live');

        $debitTransaction = $this->getDbEntity('transaction',
                                               [
                                                   'balance_id' => $commonMerchantBankingBalance->getId(),
                                                   'type'       => 'payout'
                                               ], 'live');

        $payoutSource = $this->getDbLastEntity('payout_source', 'live');

        $updatedCommonMerchantBankingBalance = $this->getDbEntity('balance',
                                                                  [
                                                                      'merchant_id' => '100000Razorpay',
                                                                      'type'        => 'banking'
                                                                  ], 'live');

        // Assertions on payout created
        $this->assertEquals($bankTransfer->getMerchantId(), $refundPayout->getMerchantId());
        $this->assertEquals($bankTransfer->getAmount(), $refundPayout->getAmount());
        $this->assertEquals($bankTransfer->getPayerAccount(),
                            $refundPayout->fundAccount->account->getAccountNumber());
        $this->assertEquals($bankTransfer->getUtr(), $refundPayout->getReferenceId());

        // Assertions on payout sources entity
        $this->assertEquals($bankTransfer->getPublicId(), $payoutSource['source_id']);
        $this->assertEquals('bank_transfer', $payoutSource['source_type']);
        $this->assertEquals($refundPayout->getId(), $payoutSource['payout_id']);

        // Assertions on the debit transaction entity
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $debitTransaction->getMerchantId());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getAmount());
        $this->assertEquals(0, $debitTransaction->getCredit());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getDebit());

        // Assertions on balance.
        // The balance should not change because we'll have a credit and a debit of the exact same amount
        $this->assertEquals($commonMerchantBankingBalance['balance'], $updatedCommonMerchantBankingBalance['balance']);
    }

    /**
     * We are trying to load funds to some existing account number which does not have any TPV.
     * This will fail and get refunded back via fund loading to common merchant and a payout from that merchant.
     */
    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFoundAndRefundsViaX()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->enableRazorXTreatmentForNonTpvRefundsViaX();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $commonMerchantBankingBalance = $this->getDbEntity('balance',
                                                           [
                                                               'merchant_id' => '100000Razorpay',
                                                               'type'        => 'banking'
                                                           ], 'live');

        // Making pricing zero for this specific payout amount, mode etc. so that
        $this->fixtures->on('live')->edit('pricing', 'Bbg7e4oKCgaubd',
                                          [
                                              'fixed_rate' => 0
                                          ]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no payments were created during this request
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading + 1, $countOfPayoutsAfterFundLoading);

        // Assert that two transactions were created. One for Bank transfer and one for payout.
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 2, $countOfTransactionsAfterFundLoading);

        $creditTransaction = $this->getDbEntity('transaction',
                                                [
                                                    'balance_id' => $commonMerchantBankingBalance->getId(),
                                                    'type'       => 'bank_transfer'
                                                ], 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
                                                   ['id' => VirtualAccount\Entity::SHARED_ID_BANKING],
                                                   'live');

        // Assertions on credit transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals(null, $bankTransfer->getPaymentId());
        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING',
                            $bankTransfer->getUnexpectedReason());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertQueued(FundLoadingFailed::class, function($mail) {
            $viewData = $mail->viewData;

            $this->assertEquals('₹ 50000', $viewData['amount']);
            $this->assertEquals('YESB0000022', $viewData['payer_ifsc']);
            $this->assertEquals('XXXXXXXXXXXXXXX6789', $viewData['payer_account_number']);
            $this->assertEquals('XXXXXXXXXXXX3333', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = 'Fund loading of ₹ 50000 to your RazorpayX account number XXXXXXXXXXXX3333 has been rejected';

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });

        //
        // Assertions for the Payout created during refund flow
        //
        $refundPayout = $this->getDbLastEntity('payout', 'live');

        $debitTransaction = $this->getDbEntity('transaction',
                                               [
                                                   'balance_id' => $commonMerchantBankingBalance->getId(),
                                                   'type'       => 'payout'
                                               ], 'live');

        $payoutSource = $this->getDbLastEntity('payout_source', 'live');

        $updatedCommonMerchantBankingBalance = $this->getDbEntity('balance',
                                                                  [
                                                                      'merchant_id' => '100000Razorpay',
                                                                      'type'        => 'banking'
                                                                  ], 'live');

        // Assertions on payout created
        $this->assertEquals($bankTransfer->getMerchantId(), $refundPayout->getMerchantId());
        $this->assertEquals($bankTransfer->getAmount(), $refundPayout->getAmount());
        $this->assertEquals($bankTransfer->getPayerAccount(),
                            $refundPayout->fundAccount->account->getAccountNumber());
        $this->assertEquals($bankTransfer->getUtr(), $refundPayout->getReferenceId());

        // Assertions on payout sources entity
        $this->assertEquals($bankTransfer->getPublicId(), $payoutSource['source_id']);
        $this->assertEquals('bank_transfer', $payoutSource['source_type']);
        $this->assertEquals($refundPayout->getId(), $payoutSource['payout_id']);

        // Assertions on the debit transaction entity
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $debitTransaction->getMerchantId());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getAmount());
        $this->assertEquals(0, $debitTransaction->getCredit());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getDebit());

        // Assertions on balance.
        // The balance should not change because we'll have a credit and a debit of the exact same amount
        $this->assertEquals($commonMerchantBankingBalance['balance'], $updatedCommonMerchantBankingBalance['balance']);
    }

    /**
     * We are trying to load funds to some existing account number which has a TPV but in pending state.
     * This will fail and get refunded back via fund loading to common merchant and a payout from that merchant.
     */
    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButPendingTpvAccountFoundAndRefundsViaX()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->enableRazorXTreatmentForNonTpvRefundsViaX();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'pending',
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFoundAndRefundsViaX'];

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $commonMerchantBankingBalance = $this->getDbEntity('balance',
                                                           [
                                                               'merchant_id' => '100000Razorpay',
                                                               'type'        => 'banking'
                                                           ], 'live');

        // Making pricing zero for this specific payout amount, mode etc. so that
        $this->fixtures->on('live')->edit('pricing', 'Bbg7e4oKCgaubd',
                                          [
                                              'fixed_rate' => 0
                                          ]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no payments were created during this request
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading + 1, $countOfPayoutsAfterFundLoading);

        // Assert that two transactions were created. One for Bank transfer and one for payout.
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 2, $countOfTransactionsAfterFundLoading);

        $creditTransaction = $this->getDbEntity('transaction',
                                                [
                                                    'balance_id' => $commonMerchantBankingBalance->getId(),
                                                    'type'       => 'bank_transfer'
                                                ], 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
                                                   ['id' => VirtualAccount\Entity::SHARED_ID_BANKING],
                                                   'live');

        // Assertions on credit transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals(null, $bankTransfer->getPaymentId());
        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING',
                            $bankTransfer->getUnexpectedReason());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since approved and active TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertQueued(FundLoadingFailed::class, function($mail) {
            $viewData = $mail->viewData;

            $this->assertEquals('₹ 50000', $viewData['amount']);
            $this->assertEquals('YESB0000022', $viewData['payer_ifsc']);
            $this->assertEquals('XXXXXXXXXXXXXXX6789', $viewData['payer_account_number']);
            $this->assertEquals('XXXXXXXXXXXX3333', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = 'Fund loading of ₹ 50000 to your RazorpayX account number XXXXXXXXXXXX3333 has been rejected';

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });

        //
        // Assertions for the Payout created during refund flow
        //
        $refundPayout = $this->getDbLastEntity('payout', 'live');

        $debitTransaction = $this->getDbEntity('transaction',
                                               [
                                                   'balance_id' => $commonMerchantBankingBalance->getId(),
                                                   'type'       => 'payout'
                                               ], 'live');

        $payoutSource = $this->getDbLastEntity('payout_source', 'live');

        $updatedCommonMerchantBankingBalance = $this->getDbEntity('balance',
                                                                  [
                                                                      'merchant_id' => '100000Razorpay',
                                                                      'type'        => 'banking'
                                                                  ], 'live');

        // Assertions on payout created
        $this->assertEquals($bankTransfer->getMerchantId(), $refundPayout->getMerchantId());
        $this->assertEquals($bankTransfer->getAmount(), $refundPayout->getAmount());
        $this->assertEquals($bankTransfer->getPayerAccount(),
                            $refundPayout->fundAccount->account->getAccountNumber());
        $this->assertEquals($bankTransfer->getUtr(), $refundPayout->getReferenceId());

        // Assertions on payout sources entity
        $this->assertEquals($bankTransfer->getPublicId(), $payoutSource['source_id']);
        $this->assertEquals('bank_transfer', $payoutSource['source_type']);
        $this->assertEquals($refundPayout->getId(), $payoutSource['payout_id']);

        // Assertions on the debit transaction entity
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $debitTransaction->getMerchantId());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getAmount());
        $this->assertEquals(0, $debitTransaction->getCredit());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getDebit());

        // Assertions on balance.
        // The balance should not change because we'll have a credit and a debit of the exact same amount
        $this->assertEquals($commonMerchantBankingBalance['balance'], $updatedCommonMerchantBankingBalance['balance']);
    }

    /**
     * We are trying to load funds to some existing account number which has a TPV but it is inactive.
     * This will fail and get refunded back via fund loading to common merchant and a payout from that merchant.
     */
    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButInActiveTpvAccountFoundAndRefundsViaX()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->enableRazorXTreatmentForNonTpvRefundsViaX();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'is_active'  => 0,
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFoundAndRefundsViaX'];

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $commonMerchantBankingBalance = $this->getDbEntity('balance',
                                                           [
                                                               'merchant_id' => '100000Razorpay',
                                                               'type'        => 'banking'
                                                           ], 'live');

        // Making pricing zero for this specific payout amount, mode etc. so that
        $this->fixtures->on('live')->edit('pricing', 'Bbg7e4oKCgaubd',
                                          [
                                              'fixed_rate' => 0
                                          ]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no payments were created during this request
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading + 1, $countOfPayoutsAfterFundLoading);

        // Assert that two transactions were created. One for Bank transfer and one for payout.
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 2, $countOfTransactionsAfterFundLoading);

        $creditTransaction = $this->getDbEntity('transaction',
                                                [
                                                    'balance_id' => $commonMerchantBankingBalance->getId(),
                                                    'type'       => 'bank_transfer'
                                                ], 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        $sharedVirtualAccount = $this->getDbEntity('virtual_account',
                                                   ['id' => VirtualAccount\Entity::SHARED_ID_BANKING],
                                                   'live');

        // Assertions on transaction entity created
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $bankTransfer->getMerchantId());
        $this->assertEquals($sharedVirtualAccount->getId(), $bankTransfer->getVirtualAccountId());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals(null, $bankTransfer->getPaymentId());
        $this->assertEquals(false, $bankTransfer->isExpected());
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING',
                            $bankTransfer->getUnexpectedReason());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($request['content']['payer_account'], $payerBankAccount->getAccountNumber());
        $this->assertEquals($request['content']['payer_ifsc'], $payerBankAccount->getIfscCode());
        $this->assertEquals($request['content']['payer_name'], $payerBankAccount->getBeneficiaryName());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        // Since approved and active TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertQueued(FundLoadingFailed::class, function($mail) {
            $viewData = $mail->viewData;

            $this->assertEquals('₹ 50000', $viewData['amount']);
            $this->assertEquals('YESB0000022', $viewData['payer_ifsc']);
            $this->assertEquals('XXXXXXXXXXXXXXX6789', $viewData['payer_account_number']);
            $this->assertEquals('XXXXXXXXXXXX3333', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = 'Fund loading of ₹ 50000 to your RazorpayX account number XXXXXXXXXXXX3333 has been rejected';

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });

        //
        // Assertions for the Payout created during refund flow
        //
        $refundPayout = $this->getDbLastEntity('payout', 'live');

        $debitTransaction = $this->getDbEntity('transaction',
                                               [
                                                   'balance_id' => $commonMerchantBankingBalance->getId(),
                                                   'type'       => 'payout'
                                               ], 'live');

        $payoutSource = $this->getDbLastEntity('payout_source', 'live');

        $updatedCommonMerchantBankingBalance = $this->getDbEntity('balance',
                                                                  [
                                                                      'merchant_id' => '100000Razorpay',
                                                                      'type'        => 'banking'
                                                                  ], 'live');

        // Assertions on payout created
        $this->assertEquals($bankTransfer->getMerchantId(), $refundPayout->getMerchantId());
        $this->assertEquals($bankTransfer->getAmount(), $refundPayout->getAmount());
        $this->assertEquals($bankTransfer->getPayerAccount(),
                            $refundPayout->fundAccount->account->getAccountNumber());
        $this->assertEquals($bankTransfer->getUtr(), $refundPayout->getReferenceId());

        // Assertions on payout sources entity
        $this->assertEquals($bankTransfer->getPublicId(), $payoutSource['source_id']);
        $this->assertEquals('bank_transfer', $payoutSource['source_type']);
        $this->assertEquals($refundPayout->getId(), $payoutSource['payout_id']);

        // Assertions on the debit transaction entity
        $this->assertEquals($sharedVirtualAccount->getMerchantId(), $debitTransaction->getMerchantId());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getAmount());
        $this->assertEquals(0, $debitTransaction->getCredit());
        $this->assertEquals($refundPayout->getAmount() + $refundPayout->getFees(), $debitTransaction->getDebit());

        // Assertions on balance.
        // The balance should not change because we'll have a credit and a debit of the exact same amount
        $this->assertEquals($commonMerchantBankingBalance['balance'], $updatedCommonMerchantBankingBalance['balance']);
    }

    protected function setUpCommonMerchantForBusinessBankingLive(
        bool $skipFeatureAddition = false,
        int $balance = 0,
        string $balanceType = AccountType::SHARED,
        $channel = Channel::YESBANK)
    {
        // Activate merchant with business_banking flag set to true.
        $this->fixtures->on('live')->merchant->edit('100000Razorpay', ['business_banking' => 1]);
        $this->fixtures->on('live')->merchant->activate();

        // Creates banking balance
        $bankingBalance = $this->fixtures->on('live')->merchant->createBalanceOfBankingType(
            $balance, '100000Razorpay', $balanceType, $channel);

        // Creates virtual account, its bank account receiver on new banking balance.
        // `HMwb1lgZD9N5Gm` is the virtual account id on production. Using the same here.
        $virtualAccount = $this->fixtures->on('live')->create('virtual_account', [
            'id'          => 'HMwb1lgZD9N5Gm',
            'merchant_id' => '100000Razorpay'
        ]);
        $bankAccount    = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'id'             => '1000001lcustba',
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '5656111122223333',
                'ifsc_code'      => 'ICIC0000104',
            ]);

        $virtualAccount->bankAccount()->associate($bankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        $bankingAccount = $this->fixtures->on('live')->create(
            'banking_account',
            [
                'id'             => '1000001lcustba',
                'account_type'   => $balanceType,
                'merchant_id'    => '100000Razorpay',
                'account_number' => '5656111122223333',
                'account_ifsc'   => 'ICIC0000104',
                'status'         => 'activated'
            ]);

        $bankingAccount->balance()->associate($bankingBalance);
        $bankingAccount->save();

        $defaultFreePayoutsCount = $this->getDefaultFreePayoutsCount($bankingBalance);

        $this->fixtures->on('live')->create('counter', [
            'account_type'          => $balanceType,
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => $defaultFreePayoutsCount,
        ]);

        // Updates banking balance's account number after bank account creation.
        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

        // Enables required features on merchant
        if ($skipFeatureAddition === false)
        {
            $this->fixtures->on('live')->merchant->addFeatures(['virtual_accounts', 'payout']);
        }

        $this->setupRedisConfigKeysForTerminalSelection();
    }

    protected function enableRazorXTreatmentForNonTpvRefundsViaX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === 'non_tpv_refunds_via_x')
                    {
                        return 'on';
                    }

                    return 'control';
                }));
    }

    public function testBankTransferIciciWherePayerAccountIsExtractedFromPayerName()
    {
        $this->setupForIciciXFundLoading();

        $data = $this->testData[__FUNCTION__]['request']['content'];

        $utr = $data['transaction_id'];

        $expectedPayerAccount = '073560123123';

        $payerIfsc = $data['payer_ifsc'];

        $payerName = 'Name of account holder';

        $payeeAccount = $data['payee_account'];

        $payeeIfsc = $data['payee_ifsc'];

        $description = $data['description'];

        $expectedAmount = $data['amount'] . '00';

        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::PAYER_ACCOUNT_NAME_INVALID_REGEXES => ['HSBC', '-']
        ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $countOfBankTransferRequestsBeforeFundLoading = count($this->getDbEntities('bank_transfer_request', [], 'live'));

        Mail::fake();

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '3434123412341234',
        ]);

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => 'ShrdVirtualAcc',
                'type'           => 'virtual_account',
                'account_number' => '3434123412341234',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id'              => 'ShrdVirtualAcc',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $this->fixtures->on('live')->create('banking_account_tpv',
            [
                'balance_id' => $balance1->getId(),
                'status'     => 'approved',
                'payer_ifsc' => 'HSBC0560002',
                'payer_account_number' => '73560123123'
            ]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment or new payout was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading, $countOfPayoutsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $countOfBankTransferRequestsAfterFundLoading = count($this->getDbEntities('bank_transfer_request', [], 'live'));
        $this->assertEquals($countOfBankTransferRequestsBeforeFundLoading + 1, $countOfBankTransferRequestsAfterFundLoading);

        $creditTransaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals('bank_transfer', $creditTransaction->getType());
        $this->assertEquals($bankTransfer->getId(), $creditTransaction->getEntityId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals('ShrdVirtualAcc', $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('icici', $bankTransfer->getGateway());
        $this->assertEquals(S::PROCESSED, $bankTransfer->getStatus());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($expectedPayerAccount, $payerBankAccount->getAccountNumber());
        $this->assertEquals($payerIfsc, $payerBankAccount->getIfscCode());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($payerIfsc, $bankTransfer->getPayerIfsc());
        $this->assertEquals($payerName, $bankTransfer->getPayerName());
        $this->assertEquals($expectedPayerAccount, $bankTransfer->getPayerAccount());
        $this->assertEquals($payeeAccount, $bankTransfer->getPayeeAccount());
        $this->assertEquals($payeeIfsc, $bankTransfer->getPayeeIfsc());
        $this->assertEquals($description, $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        Mail::assertNotQueued(FundLoadingFailed::class);

        $updatedMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');

        // Assertions on balance.
        $this->assertEquals($balance1['balance'] + $bankTransfer['amount'],
            $updatedMerchantBankingBalance['balance']);
    }


    public function testBankTransferYesBankWhenPayerAccountContainsPayerNameForPJSB()
    {
        $this->setupForYesBankXFundLoading();

        $data = $this->testData[__FUNCTION__]['request']['content'];

        $utr = $data['transaction_id'];

        $expectedPayerAccount = '123456543217890';

        $payerIfsc = $data['payer_ifsc'];

        $payerName = $data['payer_name'];

        $payeeAccount = $data['payee_account'];

        $payeeIfsc = $data['payee_ifsc'];

        $description = $data['description'];

        $expectedAmount = $data['amount'] . '00';

        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::PAYER_ACCOUNT_NAME_INVALID_REGEXES => ['HSBC', '-']
        ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $countOfBankTransferRequestsBeforeFundLoading = count($this->getDbEntities('bank_transfer_request', [], 'live'));

        Mail::fake();

        $balance1 = $this->getDbEntity('balance',[
            'merchant_id' => '10000000000000',
        ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '3434123412341234',
        ]);

        $ba = $this->fixtures->on('live')->create('bank_account',[
            'merchant_id'    => '10000000000000',
            'entity_id'      => 'ShrdVirtualAcc',
            'type'           => 'virtual_account',
            'account_number' => '3434123412341234',
        ]);

        $this->fixtures->on('live')->create('virtual_account',[
            'id'              => 'ShrdVirtualAcc',
            'merchant_id'     => '10000000000000',
            'status'          => 'active',
            'bank_account_id' => $ba->getId(),
            'balance_id'      => $balance1->getId(),
        ]);

        $this->fixtures->on('live')->create('banking_account_tpv',[
            'balance_id' => $balance1->getId(),
            'status'     => 'approved',
            'payer_ifsc' => 'PJSB0000055',
            'payer_account_number' => '123456543217890'
        ]);

        $this->ba->yesbankAuth('live');

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no new payment or new payout was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading, $countOfPayoutsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $countOfBankTransferRequestsAfterFundLoading = count($this->getDbEntities('bank_transfer_request', [], 'live'));
        $this->assertEquals($countOfBankTransferRequestsBeforeFundLoading + 1, $countOfBankTransferRequestsAfterFundLoading);

        $creditTransaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

        $merchantId = $this->bankingBalance->getMerchantId();

        // Assertions on transaction entity created
        $this->assertEquals($merchantId, $creditTransaction->getMerchantId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals('bank_transfer', $creditTransaction->getType());
        $this->assertEquals($bankTransfer->getId(), $creditTransaction->getEntityId());
        $this->assertEquals($expectedAmount, $creditTransaction->getAmount());
        $this->assertEquals($expectedAmount, $creditTransaction->getCredit());
        $this->assertEquals(0, $creditTransaction->getDebit());

        // Assertions on bank transfer entity created (Internal linking)
        $this->assertEquals($merchantId, $bankTransfer->getMerchantId());
        $this->assertEquals('ShrdVirtualAcc', $bankTransfer->getVirtualAccountId());
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals('yesbank', $bankTransfer->getGateway());
        $this->assertEquals(S::PROCESSED, $bankTransfer->getStatus());

        // Assertions on payer bank account for bank transfer
        $this->assertNotNull($bankTransfer->getPayerBankAccountId());

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $this->assertEquals($expectedPayerAccount, $payerBankAccount->getAccountNumber());
        $this->assertEquals($payerIfsc, $payerBankAccount->getIfscCode());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($payerIfsc, $bankTransfer->getPayerIfsc());
        $this->assertEquals($payerName, $bankTransfer->getPayerName());
        $this->assertEquals($expectedPayerAccount, $bankTransfer->getPayerAccount());
        $this->assertEquals($payeeAccount, $bankTransfer->getPayeeAccount());
        $this->assertEquals($payeeIfsc, $bankTransfer->getPayeeIfsc());
        $this->assertEquals($description, $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        Mail::assertNotQueued(FundLoadingFailed::class);

        $updatedMerchantBankingBalance = $this->getDbEntity('balance',
            [
                'merchant_id'   => '10000000000000',
                'type'          => 'banking'
            ], 'live');

        // Assertions on balance.
        $this->assertEquals($balance1['balance'] + $bankTransfer['amount'],
            $updatedMerchantBankingBalance['balance']);
    }

    public function testBankTransferProcessWithBeneficiaryNameOfLengthOne()
    {
        $requestData = $this->testData[__FUNCTION__]['request']['content'];

        $this->mockLedgerSns(0);

        Mail::fake();

        $this->ba->yesbankAuth('live');

        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type'           => 'banking',
            'account_number' => '2224440041626905',
        ]);

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id'    => '10000000000000',
                'entity_id'      => 'ShrdVirtualAcc',
                'type'           => 'virtual_account',
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id'              => 'ShrdVirtualAcc',
                'merchant_id'     => '10000000000000',
                'status'          => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id'      => $balance1->getId(),
            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->startTest();

        Mail::assertQueued(BankTransfer::class, function($mail) {
            $this->assertEquals('transaction.created', $mail->viewData['event']);
            $this->assertEquals('2224440041626905', $mail->viewData['balance']['account_number']);
            $this->assertEquals('Your RazorpayX A/C XX6905 is credited with INR 50,000.00', $mail->subject);

            return true;
        });

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $bankTransfer = $bankTransfer->toArray();

        $this->assertEquals($requestData['payer_name'], $bankTransfer['payer_name']);
    }

    /**
     * Fund Loading failure happens due to banking Account TPV not present, but during refund since payer bank account
     * entity not built due to validation failure, it fails.
     */
    public function testBankTransferIciciIMPSForRazorpayXWithPayerBankAccountCreationFailure()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->enableRazorXTreatmentForNonTpvRefundsViaX();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $commonMerchantBankingBalance = $this->getDbEntity('balance',
                                                           [
                                                               'merchant_id' => '100000Razorpay',
                                                               'type'        => 'banking'
                                                           ], 'live');

        // Making pricing zero for this specific payout amount, mode etc. so that
        $this->fixtures->on('live')->edit('pricing', 'Bbg7e4oKCgaubd',
                                          [
                                              'fixed_rate' => 0
                                          ]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no payments were created during this request
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading, $countOfPayoutsAfterFundLoading);

        // Assert that two transactions were created. One for Bank transfer and one for payout.
        $this->assertEquals($countOfTransactionsBeforeFundLoading, $countOfTransactionsAfterFundLoading);

        // Since TPV account was not found, we shall send the Fund Loading failed email
        Mail::assertQueued(FundLoadingFailed::class, function($mail) {
            $viewData = $mail->viewData;

            $this->assertEquals('₹ 50000', $viewData['amount']);
            $this->assertEquals('YESB0000011', $viewData['payer_ifsc']);
            $this->assertEquals('XXXXXXXXXXXXXXXXXX6789', $viewData['payer_account_number']);
            $this->assertEquals('XXXXXXXXXXXX3333', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = 'Fund loading of ₹ 50000 to your RazorpayX account number XXXXXXXXXXXX3333 has been rejected';

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });
    }

    /**
     * Fund Loading success happens even though payer bank account is not built due to validaiton failure
     * on payer account number.
     */
    public function testBankTransferIciciIMPSForRazorpayXWithPayerBankAccountCreationFailureButFundLoadingSuccess()
    {
        Mail::fake();

        $this->setupForIciciXFundLoading();

        $this->enableRazorXTreatmentForNonTpvRefundsViaX();

        $this->fixtures->merchant->addFeatures([Feature\Constants::DISABLE_TPV_FLOW]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading,
            $countOfPayoutsBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $request = &$this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $commonMerchantBankingBalance = $this->getDbEntity('balance',
                                                           [
                                                               'merchant_id' => '100000Razorpay',
                                                               'type'        => 'banking'
                                                           ], 'live');

        // Making pricing zero for this specific payout amount, mode etc. so that
        $this->fixtures->on('live')->edit('pricing', 'Bbg7e4oKCgaubd',
                                          [
                                              'fixed_rate' => 0
                                          ]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading,
            $countOfPayoutsAfterFundLoading
            ) = $this->listCountOfPaymentTransactionPayoutAndBankTransferEntities('live');

        // Assert that no payments were created during this request
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfPayoutsBeforeFundLoading, $countOfPayoutsAfterFundLoading);

        // Assert that two transactions were created. One for Bank transfer and one for payout.
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1, $countOfTransactionsAfterFundLoading);

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $expectedAmount = $request['content']['amount'] . '00';

        // Assertions on payer bank account for bank transfer
        $this->assertNull($bankTransfer->getPayerBankAccountId());

        // Assertions on bank transfer entity created (Request Params)
        $this->assertEquals($expectedAmount, $bankTransfer->getAmount());
        $this->assertEquals($request['content']['payer_ifsc'], $bankTransfer->getPayerIfsc());
        $this->assertEquals($request['content']['payer_name'], $bankTransfer->getPayerName());
        $this->assertEquals($request['content']['payer_account'], $bankTransfer->getPayerAccount());
        $this->assertEquals($request['content']['payee_account'], $bankTransfer->getPayeeAccount());
        $this->assertEquals($request['content']['payee_ifsc'], $bankTransfer->getPayeeIfsc());
        $this->assertEquals($request['content']['description'], $bankTransfer->getDescription());
        $this->assertEquals($utr, $bankTransfer->getUtr());

        Mail::assertQueued(FundLoadingFailed::class, 0);
    }
    public function testBankTransferProcessWebhookFiredWhenMerchantNotInReverseShadowLatestTxnBalanceExperiment()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);
        $testData = &$this->testData['testBankTransferTransactionCreation'];
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);
        $ledgerResponse = $testData['payload'];
        $transaction = [
            'id' => "P1RAkL8FKwWGKg",
            'entity_id' => "P1RAk9VXfqAkNM",
            'type' => "bank_transfer",
            'merchant_id' => "10000000000000",
            'amount' => 5000000,
            'fee' => 0,
            'mdr' => null,
            'tax' => 0,
            'pricing_rule_id' => null,
            'debit' => 0,
            'credit' => 5000000,
            'currency' => "INR",
            'balance' => 5000000,
            'gateway_amount' => null,
            'gateway_fee' => null,
            'gateway_service_tax' => null,
            'api_fee' => null,
            'gratis' => 0,
            'fee_credits' => 0,
            'escrow_balance' => null,
            'channel' => "yesbank",
            'fee_bearer' => -1,
            'fee_model' => -1,
            'credit_type' => "default",
            'on_hold' => 0,
            'settled' => 0,
            'settled_at' => null,
            'gateway_settled_at' => null,
            'settlement_id' => null,
            'reconciled_at' => null,
            'reconciled_type' => null,
            'balance_id' => "10000000000000",
            'reference3' => null,
            'reference4' => null,
            'balance_updated' => null,
            'reference6' => null,
            'reference7' => null,
            'reference8' => null,
            'reference9' => null,
            'posted_at' => 1727275597,
            'created_at' => 1727275598,
            'updated_at' => 1727275598
        ];
        $txn = new TransactionEntity();
        $txn->forceFill($transaction);
        $mockLedger->shouldReceive('createJournal')->andReturn(['body'=>$ledgerResponse,'code'=>200]);
        $mockLedger->shouldReceive('fetchTransactionFromLedger')->andReturn($txn);
        $this->ba->yesbankAuth('live');
        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type' => 'banking',
            'account_number' => '2224440041626905',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id' => 'ABCde1234ABCde',
            'account_number' => '2224440041626905',
            'balance_id' => $balance1['id'],
            'account_type' => 'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id' => '10000000000000',
                'entity_id' => 'ShrdVirtualAcc',
                'type' => 'virtual_account',
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id' => 'ShrdVirtualAcc',
                'merchant_id' => '10000000000000',
                'status' => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id' => $balance1->getId(),
            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferProcessWithFieldsOnLiveMode'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->expectWebhookEventOneTime('transaction.created');
        $this->startTest();
        $bankTransfersCreated = $this->getDbLastEntity('bank_transfer', 'live');


        // assert bankTransfer
        $this->assertEquals('processed', $bankTransfersCreated['status']);
        $this->assertEquals(5000000, $bankTransfersCreated['amount']);
    }
    public function testBankTransferProcessWebhookNotFiredWhenMerchantInReverseShadowLatestTxnBalanceExperiment()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);
        $testData = &$this->testData['testBankTransferTransactionCreation'];
        $this->setMockSplitzTreatmnt([RazorxTreatment::LEDGER_REVERSE_SHADOW_LATEST_TXN_BALANCE =>'enable']);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);
        $ledgerResponse = $testData['payload'];
        $transaction = [
            'id' => "P1RAkL8FKwWGKg",
            'entity_id' => "P1RAk9VXfqAkNM",
            'type' => "bank_transfer",
            'merchant_id' => "10000000000000",
            'amount' => 5000000,
            'fee' => 0,
            'mdr' => null,
            'tax' => 0,
            'pricing_rule_id' => null,
            'debit' => 0,
            'credit' => 5000000,
            'currency' => "INR",
            'balance' => 5000000,
            'gateway_amount' => null,
            'gateway_fee' => null,
            'gateway_service_tax' => null,
            'api_fee' => null,
            'gratis' => 0,
            'fee_credits' => 0,
            'escrow_balance' => null,
            'channel' => "yesbank",
            'fee_bearer' => -1,
            'fee_model' => -1,
            'credit_type' => "default",
            'on_hold' => 0,
            'settled' => 0,
            'settled_at' => null,
            'gateway_settled_at' => null,
            'settlement_id' => null,
            'reconciled_at' => null,
            'reconciled_type' => null,
            'balance_id' => "10000000000000",
            'reference3' => null,
            'reference4' => null,
            'balance_updated' => null,
            'reference6' => null,
            'reference7' => null,
            'reference8' => null,
            'reference9' => null,
            'posted_at' => 1727275597,
            'created_at' => 1727275598,
            'updated_at' => 1727275598
        ];
        $txn = new TransactionEntity();
        $txn->forceFill($transaction);
        $mockLedger->shouldReceive('createJournal')->andReturn(['body'=>$ledgerResponse,'code'=>200]);
        $mockLedger->shouldReceive('fetchTransactionFromLedger')->andReturn($txn);
        $this->ba->yesbankAuth('live');
        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type' => 'banking',
            'account_number' => '2224440041626905',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id' => 'ABCde1234ABCde',
            'account_number' => '2224440041626905',
            'balance_id' => $balance1['id'],
            'account_type' => 'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id' => '10000000000000',
                'entity_id' => 'ShrdVirtualAcc',
                'type' => 'virtual_account',
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id' => 'ShrdVirtualAcc',
                'merchant_id' => '10000000000000',
                'status' => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id' => $balance1->getId(),
            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferProcessWithFieldsOnLiveMode'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->dontExpectAnyWebhookEvent();
        $this->startTest();
        $bankTransfersCreated = $this->getDbLastEntity('bank_transfer', 'live');


        // assert bankTransfer
        $this->assertEquals('processed', $bankTransfersCreated['status']);
        $this->assertEquals(5000000, $bankTransfersCreated['amount']);
    }
    // experiments  - LEDGER_REVERSE_SHADOW_LATEST_TXN_BALANCE,TRANSACTION_CREATED_WEBHOOK_SYNC_FIRE_EXPERIMENT
    public function testBankTransferProcessWebhookFired()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);
        $testData = &$this->testData['testBankTransferTransactionCreation'];
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);
        $ledgerResponse = $testData['payload'];
        $transaction = [
            'id' => "P1RAkL8FKwWGKg",
            'entity_id' => "P1RAk9VXfqAkNM",
            'type' => "bank_transfer",
            'merchant_id' => "10000000000000",
            'amount' => 5000000,
            'fee' => 0,
            'mdr' => null,
            'tax' => 0,
            'pricing_rule_id' => null,
            'debit' => 0,
            'credit' => 5000000,
            'currency' => "INR",
            'balance' => 5000000,
            'gateway_amount' => null,
            'gateway_fee' => null,
            'gateway_service_tax' => null,
            'api_fee' => null,
            'gratis' => 0,
            'fee_credits' => 0,
            'escrow_balance' => null,
            'channel' => "yesbank",
            'fee_bearer' => -1,
            'fee_model' => -1,
            'credit_type' => "default",
            'on_hold' => 0,
            'settled' => 0,
            'settled_at' => null,
            'gateway_settled_at' => null,
            'settlement_id' => null,
            'reconciled_at' => null,
            'reconciled_type' => null,
            'balance_id' => "10000000000000",
            'reference3' => null,
            'reference4' => null,
            'balance_updated' => null,
            'reference6' => null,
            'reference7' => null,
            'reference8' => null,
            'reference9' => null,
            'posted_at' => 1727275597,
            'created_at' => 1727275598,
            'updated_at' => 1727275598
        ];
        $txn = new TransactionEntity();
        $txn->forceFill($transaction);
        $mockLedger->shouldReceive('createJournal')->andReturn(['body'=>$ledgerResponse,'code'=>200]);
        $mockLedger->shouldReceive('fetchTransactionFromLedger')->andReturn($txn);
        $this->ba->yesbankAuth('live');
        $balance1 = $this->getDbEntity('balance',
            [
                'merchant_id' => '10000000000000',
            ], 'live');

        $this->fixtures->on('live')->edit('balance', $balance1->getId(), [
            'type' => 'banking',
            'account_number' => '2224440041626905',
        ]);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id' => 'ABCde1234ABCde',
            'account_number' => '2224440041626905',
            'balance_id' => $balance1['id'],
            'account_type' => 'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $ba = $this->fixtures->on('live')->create('bank_account',
            [
                'merchant_id' => '10000000000000',
                'entity_id' => 'ShrdVirtualAcc',
                'type' => 'virtual_account',
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->on('live')->create('virtual_account',
            [
                'id' => 'ShrdVirtualAcc',
                'merchant_id' => '10000000000000',
                'status' => 'active',
                'bank_account_id' => $ba->getId(),
                'balance_id' => $balance1->getId(),
            ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferProcessWithFieldsOnLiveMode'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->expectWebhookEventOneTime('transaction.created');
        $this->startTest();
        $bankTransfersCreated = $this->getDbLastEntity('bank_transfer', 'live');


        // assert bankTransfer
        $this->assertEquals('processed', $bankTransfersCreated['status']);
        $this->assertEquals(5000000, $bankTransfersCreated['amount']);
    }

    protected function enableRazorXTreatment($whiteListFeatures)
    {


        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) use ($whiteListFeatures) {
                    if (in_array($feature,$whiteListFeatures))
                    {
                        return 'on';
                    }

                    return 'control';
                }));
    }
    protected function enableSplitzExperiment($experimentId){
        $input =[
            "id"=>'10000000000000',
            'experiment_id' => $this->app['config']->get($experimentId)

        ];
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];
        $this->mockSplitzTreatment($input,$output);

    }
}
