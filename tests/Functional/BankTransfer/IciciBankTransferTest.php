<?php

namespace RZP\Tests\Functional\BankTransfer;

use DB;
use Mail;
use Cache;
use RZP\Models\Pricing\Fee;
use RZP\Models\Batch\Header;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use Illuminate\Support\Facades\Queue;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class IciciBankTransferTest extends TestCase
{
    use AttemptTrait;
    use CreatesInvoice;
    use FileHandlerTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use AttemptReconcileTrait;
    use ReconTrait;

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

        $this->enableRazorXTreatmentForBanKTransferDisableGateway();
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

    public function enableRazorXTreatmentForBanKTransferDisableGateway()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
               ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
               {
                   if ($featureFlag === (RazorxTreatment::BANK_TRANSFER_DISABLE_GATEWAY))
                   {
                       return 'on';
                   }
                   return 'control';
               });
    }

    public function testBankTransferRefundIcici()
    {
        $channel = Channel::ICICI;

        $this->createRefund($channel);

        $content = $this->initiateTransferViaFileAndAssertSuccess(
            $channel,
            Attempt\Purpose::REFUND,
            0,
            Attempt\Type::REFUND);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Attempt\Status::CREATED, $attempt[Attempt\Entity::STATUS]);

        $channel = Channel::YESBANK;
        $content = $this->initiateTransferAndAssertSuccess(
            $channel,
            Attempt\Purpose::REFUND,
            1,
            Attempt\Type::REFUND);
        $data = $this->reconcileOnlineSettlements($channel, false);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertNotNull($attempt['utr']);

        // Process entities
        $this->reconcileEntitiesForChannel($channel);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
    }

    public function testIciciBankTransferCallback()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_Number_Verification_IN'][0]['payee_account'] =  $this->getIciciVaBankAccount();

        $this->ba->iciciAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Virtual_Account_Number_Verification_IN'][0]['transaction_id']);
        $this->assertEquals(100000, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('bt_icici', $payment['gateway']);
    }

    public function testBankTransferIcici()
    {
        $accountNumber = $this->getIciciVaBankAccount();

        $this->ba->batchAppAuth();

        $this->processOrNotifyBankTransfer(
            $accountNumber,
            'ICIC0000104',
            'awesome_utr'
        );

        $bankTransfer =  $this->getLastEntity('bank_transfer', true, 'live');
        $this->assertEquals(5000000, $bankTransfer['amount']);
        $this->assertEquals("ICIC0000104", $bankTransfer['payer_ifsc']);

        $payment =  $this->getLastEntity('payment', true, 'live');
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals('bt_icici', $payment['gateway']);
    }

    public function testBankTransferIciciMigration()
    {
        $accountNumber = $this->getIciciVaBankAccount();

        $terminalAttributes = [ 'id' =>'GENERICBANKRBL', 'gateway' => Gateway::BT_RBL, 'gateway_merchant_id' => '0001046' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount1LastEntity = $this->getDbLastEntity('bank_account');

//       replicated current bank account and set IFSC to RBL.
        $bankAccount2 = $this->fixtures->create(
            'bank_account',
            [
                'merchant_id'       => '10000000000000',
                'entity_id'         => substr($this->virtualAccountId, 3, strlen($this->virtualAccountId)),
                'type'              => 'virtual_account',
                'account_number'    =>  $accountNumber,
                'ifsc_code'         => 'RATN0VAAPIS',
            ]
        );

        $this->fixtures->edit(
            'virtual_account',
            $this->virtualAccountId,
            ['bank_account_id_2' => $bankAccount2->getId()]
        );

        $virtualAccount = $this->getDbLastEntity('virtual_account');
        $this->assertTrue($virtualAccount->hasBankAccount2());
        $this->assertEquals($virtualAccount->getAttribute('bank_account_id_2'), $bankAccount2->getId());
        $this->assertEquals($virtualAccount->getAttribute('bank_account_id'), $bankAccount1LastEntity->getId());

        $this->ba->batchAppAuth();

        $this->processOrNotifyBankTransfer(
            $accountNumber,
            'ICIC0000104',
            'awesome_utr'
        );

        $bankTransfer =  $this->getLastEntity('bank_transfer', true, 'live');
        $this->assertEquals(5000000, $bankTransfer['amount']);
        $this->assertEquals("ICIC0000104", $bankTransfer['payer_ifsc']);

        $payment =  $this->getLastEntity('payment', true, 'live');
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals('bt_icici', $payment['gateway']);

//        bank Tranfer on RBL

        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $accountNumber;

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer2 =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer2['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer2['amount']);

        $payment2 =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment2['amount']);
//         $this->assertEquals($bankAccount2->getId(), $payment2['receiver_id']);
        $this->assertEquals('bt_rbl', $payment2['gateway']);
    }

    public function testBankTransferIciciWithIfscAsBankCode()
    {
        $accountNumber = $this->getIciciVaBankAccount();

        $this->ba->batchAppAuth();

        $this->processOrNotifyBankTransfer(
            $accountNumber,
            'ICIC0000104',
            'awesome_utr'
        );

        $bankTransfer =  $this->getLastEntity('bank_transfer', true, 'live');
        $this->assertEquals(5000000, $bankTransfer['amount']);

        $this->assertEquals("SBIN0010411", $bankTransfer['payer_ifsc']);


        $payment =  $this->getLastEntity('payment', true, 'live');
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals('bt_icici', $payment['gateway']);
    }

    public function testBankTransferIciciWithIfscAsInvalidBankCode()
    {
        $accountNumber = $this->getIciciVaBankAccount();

        $this->ba->batchAppAuth();

        $this->processOrNotifyBankTransfer(
            $accountNumber,
            'ICIC0000104',
            'awesome_utr'
        );

        $bankTransfer =  $this->getLastEntity('bank_transfer', true, 'live');
        $this->assertEquals(null, $bankTransfer);
    }

    public function testCheckEcollectIciciBatchCreate()
    {
        Queue::fake();

        $this->ba->h2hAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createExcelFile($entries, 'filename', 'files/filestore');

        $this->startTest();
    }

    public function testIciciBankTransferCallbackInvalid()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->ba->iciciAuth();

        $this->startTest($testData);
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

    protected function processOrNotifyBankTransfer($accountNumber, $ifsc, $utr, $amount = null, $mode = 'test')
    {
        $request = $this->testData[__FUNCTION__];

        $name = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        $request['url'] = $this->testData[$name]['url'];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        if (isset($this->testData[$name]['content']['payer_ifsc']) === true)
        {
            $request['content']['payer_ifsc'] = $this->testData[$name]['content']['payer_ifsc'];
        }

        $utr = $utr ?: strtoupper(random_alphanum_string(22));

        $request['content']['transaction_id'] = $utr;

        $request['content']['amount'] = $amount ?: 50000;

        if ($mode === 'live')
        {
            $request['url'] = '/ecollect/validate';

            $this->ba->yesbankAuth('live');
        }

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($utr, $response['transaction_id']);

        return $response;
    }

    protected function createRefund($channel = null)
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $response = $this->processBankTransfer($accountNumber, $ifsc);

        $utr = $response['transaction_id'];

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);

        $payment =  $this->getLastEntity('payment', true);

        $this->gateway = $payment['gateway'];

        // For scrooge flow tests
        $ftaData = [
            'is_fta'   => true,
            'fta_data' => [
                'bank_account' => [
                    'account_number'   => $bankAccount['account_number'],
                    'beneficiary_name' => $bankAccount['name'],
                    'ifsc_code'        => $bankAccount['ifsc'],
                ]
            ]
        ];

        $this->refundPayment($payment['id'], 4000000, $ftaData);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(4000000, $refund['amount']);

        // Transaction is created for refund
        $transaction = $this->getLastEntity('transaction', true);
        $this->assertEquals('refund', $transaction['type']);
        $this->assertEquals($refund['id'], $transaction['entity_id']);

        // Fund transfer attempt created for refund
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);
    }

    protected function getIciciVaBankAccount($mode = 'test')
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKICI', 'gateway' => Gateway::BT_ICICI, 'gateway_merchant_id' => '2244' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount($mode);

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
}
