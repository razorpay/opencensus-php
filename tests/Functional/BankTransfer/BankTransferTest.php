<?php

namespace RZP\Tests\Functional\BankTransfer;

use DB;
use Mail;
use Cache;
use Mockery;
use Carbon\Carbon;
use Razorpay\IFSC\IFSC;
use RZP\Constants\Mode;
use RZP\Models\Order;
use RZP\Models\Admin;
use RZP\Models\Feature;
use Queue as MockQueue;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Jobs\Transactions;
use RZP\Models\Pricing\Fee;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Header;
use RZP\Models\Admin\Service;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Payment\Refund;
use RZP\Services\RazorXClient;
use RZP\Services\Mock\Mozart;
use RZP\Models\Payment\Status;
use RZP\Models\VirtualAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Gateway;
use RZP\Models\Currency\Currency;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Error\PublicErrorDescription;
use Illuminate\Support\Facades\Queue;
use RZP\Mail\Transaction\BankTransfer;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\BankTransfer\Entity as E;
use RZP\Models\BankTransfer\Status as S;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\BankTransferRequest\Entity;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Mail\Merchant\RazorpayX\FundLoadingFailed;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\VirtualAccount\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class BankTransferTest extends TestCase
{
    use AttemptTrait;
    use CreatesInvoice;
    use FileHandlerTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use TestsBusinessBanking;
    use AttemptReconcileTrait;

    protected $virtualAccountId;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/BankTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

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

    public function testBankTransferProcess()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $this->assertNotNull($bankTransfer['payment_id']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('SHRDBANKACC3DS', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('bt_dashboard', $payment['gateway']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);

        $this->runBankTransferRequestAssertions(
            true,
            '',
            [
                'intended_virtual_account_id'   => $bankTransfer['virtual_account_id'],
                'actual_virtual_account_id'     => $bankTransfer['virtual_account_id'],
                'merchant_id'                   => $bankTransfer['merchant_id'],
                'bank_transfer_id'              => $bankTransfer['id'],
                'payment_id'                    => $bankTransfer['payment_id'],
            ]
        );
    }

    public function testBankTransferYesBankAfterGatewayDisabled()
    {
        $bankAccount1 = $this->createVirtualAccount('live', 'BankAccountMer');

        $accountNumber = $bankAccount1['account_number'];
        $ifsc1 = $bankAccount1['ifsc'];

        $bankAccount2 = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'merchant_id'       => 'BankAccountMer',
                'entity_id'         => substr($this->virtualAccountId, 3, strlen($this->virtualAccountId)),
                'type'              => 'virtual_account',
                'account_number'    => $accountNumber,
                'ifsc_code'         => Provider::IFSC[Provider::RBL],
            ]
        );

        $this->fixtures->on('live')->edit(
            'virtual_account',
            $this->virtualAccountId,
            ['bank_account_id_2' => $bankAccount2->getId()]
        );

        $this->fixtures->on('live')->edit(
            'bank_account',
            $bankAccount1['id'],
            ['deleted_at' => '1648527137']
        );

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc1, null , null, 'live');

        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer =  $this->getDbLastEntity('bank_transfer', 'live');

        $this->assertEquals(5000000, $bankTransfer['amount']);
        $this->assertEquals(UnexpectedPaymentReason::VIRTUAL_ACCOUNT_PAYMENT_FAILED_GATEWAY_DISABLED,
                            $bankTransfer['unexpected_reason']);

        $payment =  $this->getDbLastEntity('payment', 'live');
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals('bt_yesbank', $payment['gateway']);
        $this->assertEquals('refunded', $payment['status']);

        $refund =  $this->getDbLastEntity('refund', 'live');
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(5000000, $refund['amount']);
        $this->assertEquals('Yes Bank Virtual Account is closed', $refund['notes']['refund_reason']);
    }

    public function testBankTransferYesBank()
    {
        $this->markTestSkipped('The flakiness in the testcase needs to be fixed. Skipping as its impacting dev-productivity.');

        $bankAccount = $this->createVirtualAccount('live', 'BankAccountMer');

        $accountNumber = $bankAccount['account_number'];
        $ifsc = $bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc, null , null, 'live');

        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer =  $this->getDbLastEntity('bank_transfer', 'live');

        $this->assertEquals(5000000, $bankTransfer['amount']);
        $this->assertEquals(UnexpectedPaymentReason::VIRTUAL_ACCOUNT_PAYMENT_FAILED_GATEWAY_DISABLED,
                            $bankTransfer['unexpected_reason']);

        $payment =  $this->getDbLastEntity('payment', 'live');
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals('bt_yesbank', $payment['gateway']);
        $this->assertEquals('refunded', $payment['status']);

        $refund =  $this->getDbLastEntity('refund', 'live');
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(5000000, $refund['amount']);
        $this->assertEquals('Yes Bank Virtual Account is closed', $refund['notes']['refund_reason']);
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

    public function testFetchPaymentsPostRblMigration()
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

//        bank Tranfer on RBL

        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $accountNumber;

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer2 =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer2['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer2['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals($bankAccount2->getId(), $payment['receiver_id']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $this->ba->proxyAuth();
        $request = [
            'url' => '/virtual_accounts/'.$this->virtualAccountId.'/payments',
            'method' => 'get',
            'content' => []
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals($response['items'][0]['id'], $payment['id']);
    }

    public function testBankTransferProcessPgWhenLedgerReverseShadowEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);
//        Queue::fake();

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getDbLastEntity('payment');
        $this->assertEquals('SHRDBANKACC3DS', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], 'pay_'.$payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('bt_dashboard', $payment['gateway']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);

        $this->runBankTransferRequestAssertions(
            true,
            '',
            [
                'intended_virtual_account_id'   => $bankTransfer['virtual_account_id'],
                'actual_virtual_account_id'     => $bankTransfer['virtual_account_id'],
                'merchant_id'                   => $bankTransfer['merchant_id'],
                'bank_transfer_id'              => $bankTransfer['id'],
                'payment_id'                    => $bankTransfer['payment_id'],
            ]
        );

//        Queue::assertPushed(Transactions::class, 0);
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

    public function testHidePayerDetailsWithFeatureFlag()
    {
        $this->testBankTransferProcess();

        $payment =  $this->getLastEntity('payment', true);

        $request = [
            'method'  => 'GET',
            'url'     => '/payments/'.$payment['id'].'/bank_transfer',
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('payer_bank_account', $response);

        $this->assertArrayHasKey('id', $response['payer_bank_account']);

        $this->fixtures->merchant->addFeatures(['hide_va_payer_bank_detail']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayNotHasKey('payer_bank_account', $response);
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
    }

    public function testBankTransferProcessForTinyAmount()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc, null, 0.99);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertEquals(99, $bankTransfer['amount']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(99, $payment['amount']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('bt_dashboard', $payment['gateway']);
    }

    public function testPaymentProcessRblWithNoSenderName()
    {
        $testData = $this->testData['testBankTransferRbl'];
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();
        $testData['request']['content']['Data'][0]['senderName'] = '';

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getDbLastEntityToArray('bank_transfer', 'test');
        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);
        $payment =  $this->getDbLastEntityToArray('payment', 'test');
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
    }

    public function testBankTransferProcessForDisabledMethod()
    {
        // New merchant account, VA created
        $this->fixtures->merchant->createAccount('MethodEnbleTst');
        $virtualAccount = $this->fixtures->create('virtual_account', [
            'merchant_id' => 'MethodEnbleTst',
            'status'      => 'active',
        ]);
        $bankAccount    = $this->fixtures->create('bank_account', [
            'type'           => 'virtual_account',
            'merchant_id'    => 'MethodEnbleTst',
            'entity_id'      => $virtualAccount->getId(),
            'account_number' => '11122275867',
            'ifsc_code'      => 'RAZRB000000',
        ]);
        $this->fixtures->edit('virtual_account', $virtualAccount->getId(), [
            'bank_account_id' => $bankAccount->getId(),
        ]);

        // Bank transfer now disabled, before payment is created
        $this->fixtures->merchant->disableMethod('MethodEnbleTst', 'bank_transfer');

        // Process API always returns true
        $response = $this->processBankTransfer('11122275867', 'RAZRB000000');
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $payment =  $this->getLastEntity('payment', true);
        // Created bank transfer is an unexpected one
        $this->assertEquals('11122275867', $bankTransfer['payee_account']);
        $this->assertEquals('RAZRB000000', $bankTransfer['payee_ifsc']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertEquals('va_ShrdVirtualAcc', $bankTransfer['virtual_account_id']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is made to test merchant and left authorized
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('10000000000000', $payment['merchant_id']);
    }

    public function testBankTransferWithInActiveAccount()
    {

        $bankAccount = $this->createVirtualAccount('live', 'BankAccountMer');

        $this->fixtures->on('live')->merchant->edit('BankAccountMer', ['live' => false]);

        $accountNumber = $bankAccount['account_number'];
        $ifsc = $bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc, null , null, 'live');
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true, 'live');
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals('va_ShrdVirtualAcc', $bankTransfer['virtual_account_id']);
        $this->assertEquals('VIRTUAL_ACCOUNT_MERCHANT_NOT_LIVE', $bankTransfer['unexpected_reason']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $this->runBankTransferRequestAssertions(
            true,
            'VIRTUAL_ACCOUNT_MERCHANT_NOT_LIVE',
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => 'va_ShrdVirtualAcc',
                'merchant_id'                   => 'BankAccountMer',
                'bank_transfer_id'              => $bankTransfer['id'],
                'payment_id'                    => $bankTransfer['payment_id'],
            ],
            'live'
        );
        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true, 'live');
        $this->assertEquals('SHRDBANKACC3DS', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('bt_yesbank', $payment['gateway']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account', 'live');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);
    }

    public function testBankTransferMultiPricingPlan()
    {
        // This creates a plan with 2 rules:
        // Amount 1   - 100            : Percent rate 16%
        // Amount 100 - 1,00,00,000    : Flat rate 15, plus percent rate 1%
        $pricingPlanId = $this->fixtures->create('pricing:bank_transfer_multi_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $pricingPlanId]);

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Amount less than 100
        $this->processBankTransfer($accountNumber, $ifsc, null, 80);
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $transaction = $this->getLastEntity('transaction', true);
        // Pricing 16%
        $this->assertEquals($transaction['amount'] * 16 / 100, $transaction['fee'] - $transaction['tax']);

        // Amount greater than 100
        $this->processBankTransfer($accountNumber, $ifsc, null, 10000);
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $transaction = $this->getLastEntity('transaction', true);
        // Pricing 15 + 1%
        $this->assertEquals(1500 + $transaction['amount'] * 1 / 100, $transaction['fee'] - $transaction['tax']);

        // Amount exactly 100
        $this->processBankTransfer($accountNumber, $ifsc, null, 100);
        $transaction = $this->getLastEntity('transaction', true);
        // Both rules amount to the same
        $this->assertEquals($transaction['amount'] * 16 / 100, $transaction['fee'] - $transaction['tax']);
        $this->assertEquals(1500 + $transaction['amount'] * 1 / 100, $transaction['fee'] - $transaction['tax']);
    }

    public function testBankTransferRefund()
    {
        $channel = Channel::AXIS;

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
    }

    public function testBankTransferRefundWithDeletedTerminal()
    {
        $channel = Channel::AXIS;

        $this->createRefundWithDeletedTerminal($channel);

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

    public function testBankTransferRefundYesbank()
    {
        $channel = Channel::YESBANK;

        $this->createRefund($channel);

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

    public function testBankTransferRefundYesbankTpvPayment()
    {
        $channel = Channel::YESBANK;

        $this->createTpvRefund();

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

        $refund = $this->getLastEntity('refund', true);

        // Adding this since post reconciliation, we update the status at scrooge side,
        // post which scrooge sends an update status request to API
        $this->scroogeUpdateRefundStatus($refund, 'processed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(Refund\Status::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['processed_at']);
        $this->assertEquals(1, $refund['attempts']);
        $this->assertNotNull($attempt['utr']);
    }

    public function testBankTransferImps()
    {
        $this->markTestSkipped();

        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = Provider::IFSC[Provider::KOTAK];

        $this->fixtures->base->editEntity(
            'bank_account',
            $this->bankAccount['id'],
            [
                'ifsc_code' => $ifsc
            ]);

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $request['server']['REMOTE_ADDR'] = '14.141.97.12';

        $this->cloud = false;

        $this->ba->kotakAuth();

        $response = $this->makeRequestAndGetContent($request);

        $utr = $response['transaction_id'];

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        // Testing if gateway is correct
        $this->assertEquals('kotak', $bankTransfer['gateway']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        // Set to mapped IFSC code for HDFC Bank code
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        // IMPS refunds are permitted
        $this->refundPayment($payment['id'], 4000000);
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('initiated', $refund['status']);
        $this->assertEquals(4000000, $refund['amount']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);
    }

    public function testBankTransferWithoutModifyingContact()
    {
        $this->fixtures->edit(
            'customer',
            '100000customer',
            ['contact' => '000000000']
        );

        $virtualAccount = $this->getLastEntity('virtual_account', true);

        $this->fixtures->base->editEntity(
            'virtual_account',
            $virtualAccount['id'],
            [
                'customer_id' => '100000customer'
            ]);

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('SHRDBANKACC3DS', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('bt_dashboard', $payment['gateway']);

        $this->assertEquals('cust_100000customer', $payment['customer_id']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);
    }

    public function testBankTransferImpsWithNbin()
    {
        $this->markTestSkipped('Skipped due to yesbank disablement');

        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = Provider::IFSC[Provider::YESBANK];

        $this->fixtures->base->editEntity(
            'bank_account',
            $this->bankAccount['id'],
            [
                'ifsc_code' => $ifsc
            ]);

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->cloud = false;

        $this->ba->yesbankAuth();

        $response = $this->makeRequestAndGetContent($request);

        $utr = $response['transaction_id'];

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        // Testing if gateway is correct
        $this->assertEquals('yesbank', $bankTransfer['gateway']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        // Set to mapped IFSC code for PAYTM Nbin
        $this->assertEquals('PYTM0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        // IMPS refunds are permitted now
        $this->refundPayment($payment['id'], 4000000);
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('initiated', $refund['status']);
        $this->assertEquals(4000000, $refund['amount']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);
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


    public function testBankTransferImpsUnmappedBankCode()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        // Null, because IFSC was not received for IMPS transaction
        $this->assertNull($bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $this->gateway = $payment['gateway'];

        // For scrooge flow tests
        $ftaData = [
            'is_fta'   => true,
            'fta_data' => [
                'bank_account' => [
                    'account_number'   => $accountNumber,
                    'beneficiary_name' => $this->bankAccount['name'],
                    'ifsc_code'        => $this->bankAccount['ifsc'],
                ]
            ]
        ];

        $this->refundPayment($payment['id'], 4000000, $ftaData);

        // IMPS refunds are permitted...
        // ...but they don't actually work
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(4000000, $refund['amount']);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);
    }

    public function testBankTransferRefundRetry()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $utr = $response['transaction_id'];

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        // Null, because IFSC was not received for IMPS transaction
        $this->assertNull($bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        $data = $this->testData['bankTransferImpsFailedRefund'];

        $this->gateway = $payment['gateway'];

        // For scrooge flow tests
        $ftaData = [
            'is_fta'   => true,
            'fta_data' => [
                'bank_account' => [
                    'account_number'   => $accountNumber,
                    'beneficiary_name' => $this->bankAccount['name'],
                    'ifsc_code'        => $this->bankAccount['ifsc'],
                ]
            ]
        ];

        $this->refundPayment($payment['id'], 4000000, $ftaData);

        // IMPS refunds are permitted...
        // ...but they don't actually work
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(4000000, $refund['amount']);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);

        $this->ba->cronAuth();

        $this->fixtures->base->editEntity(
            'bank_account',
            $bankAccount['id'],
            [
                'ifsc_code' => 'RAZR0000001'
            ]);

        $response = $this->makeRequestAndGetContent([
            'method'  => 'POST',
            'url'     => '/bank_transfers/refunds/retry',
        ]);

        // Refund is now marked created again,
        // because payer bank acc now has an IFSC
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);

        // IFSC updated for payer bank account
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('RAZR0000001', $bankAccount['ifsc']);

        // Fund transfer attempt created for refund
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $this->initiateTransferViaFileAndAssertSuccess(
            Channel::AXIS, Attempt\Purpose::REFUND, 0, Attempt\Type::REFUND);

        $channel = Channel::YESBANK;
        $content = $this->initiateTransferAndAssertSuccess(
            $channel,
            Attempt\Purpose::REFUND,
            1,
            Attempt\Type::REFUND);
    }

    public function testBankTransferRefundRetryManual()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $channel = Channel::AXIS;
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

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
                    'account_number'   => $accountNumber,
                    'beneficiary_name' => $this->bankAccount['name'],
                    'ifsc_code'        => $this->bankAccount['ifsc'],
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

        $content = $this->initiateTransferViaFileAndAssertSuccess(
            $channel, Attempt\Purpose::REFUND, 0, Attempt\Type::REFUND);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Status::CREATED, $attempt['status']);

        $this->ba->cronAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/bank_transfers/refunds/retry',
            'content' => [
                'ids' => [
                    $refund['id']
                ],
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEmpty($response['status']);

        // Only failed refunds can be retried
        $this->fixtures->refund->edit($refund['id'], ['status' => 'failed']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($response['status']);

        // Refund is now marked created again
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('initiated', $refund['status']);

        //  Another fund transfer attempt created for refund
        $oldAttempt = $attempt;
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertNotEquals($oldAttempt['id'], $attempt['id']);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $content = $this->initiateTransferViaFileAndAssertSuccess(
            $channel, Attempt\Purpose::REFUND, 0, Attempt\Type::REFUND);

        $channel = Channel::YESBANK;

        $content = $this->initiateTransferAndAssertSuccess(
            $channel,
            Attempt\Purpose::REFUND,
            1,
            Attempt\Type::REFUND);
    }

    public function testBankTransferRefundRetryToDifferentAccount()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $channel = Channel::AXIS;
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

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
                    'account_number'   => $accountNumber,
                    'beneficiary_name' => $this->bankAccount['name'],
                    'ifsc_code'        => $this->bankAccount['ifsc'],
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

        $content = $this->initiateTransferViaFileAndAssertSuccess(
            $channel, Attempt\Purpose::REFUND, 0, Attempt\Type::REFUND);

        $channel = Channel::YESBANK;
        $content = $this->initiateTransferAndAssertSuccess(
            $channel,
            Attempt\Purpose::REFUND,
            1,
            Attempt\Type::REFUND);

        // Only failed refunds can be retried
        $this->fixtures->refund->edit($refund['id'], ['status' => 'failed']);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id'], [
            'bank_account' => [
                'account_number'   => '1234567890987654321',
                'ifsc_code'        => 'HDFC0000002',
                'beneficiary_name' => 'New Bank Account',
            ],
        ]);

        // Refund is now marked created again
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('initiated', $refund['status']);

        // Another bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000002', $bankAccount['ifsc']);
        $this->assertEquals('1234567890987654321', $bankAccount['account_number']);
        $this->assertEquals('New Bank Account', $bankAccount['name']);

        //  Another fund transfer attempt created for refund
        $oldAttempt = $attempt;
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertNotEquals($oldAttempt['id'], $attempt['id']);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $this->initiateTransferViaFileAndAssertSuccess(
            Channel::AXIS, Attempt\Purpose::REFUND, 0, Attempt\Type::REFUND);

        $channel = Channel::YESBANK;
        $content = $this->initiateTransferAndAssertSuccess(
            $channel,
            Attempt\Purpose::REFUND,
            1,
            Attempt\Type::REFUND);
    }

    public function testBankTransferRemoveSpaces()
    {
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->proxyAuth();

        $this->makeRequestAndGetContent($request);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals('RZRPAY123', $bankTransfer['payee_account']);
    }

    public function testBankTransferImpsFromRogueBankNullAccount()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->proxyAuth();

        $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEmpty($bankTransfer['payer_account']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account did not get created
        $this->assertNull($bankTransfer['payer_bank_account_id']);

        $payment =  $this->getLastEntity('payment', true);

        $data = $this->testData['bankTransferImpsFailedRefund'];

        // IMPS refunds are not permitted when we don't even have an account number
        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundPayment($payment['id'], 4000000);
        });

        $request = [
            'method'  => 'PUT',
            'url'     => '/bank_transfers/'.$bankTransfer['id'].'/payer_bank_account',
            'content' => [
                'account_number' => '123456',
                'ifsc_code'      => 'HDFC0000002',
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000002', $bankAccount['ifsc']);
        $this->assertEquals('123456', $bankAccount['account_number']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($bankAccount['id'], $bankTransfer['payer_bank_account_id']);
    }

    public function testBankTransferImpsFromRogueBankInvalidAccount()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->proxyAuth();

        $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals('123', $bankTransfer['payer_account']);
        $this->assertEquals('RTGS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account did not get created
        $this->assertNull($bankTransfer['payer_bank_account_id']);

        $payment =  $this->getLastEntity('payment', true);

        $data = $this->testData['bankTransferImpsFailedRefund'];

        // Refunds are not permitted when we haven't created a payer bank account
        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundPayment($payment['id'], 4000000);
        });
    }

    public function testBankTransferToReallyReallyLongPayeeAccount()
    {
        $this->ba->proxyAuth();

        $this->startTest();

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $payment      =  $this->getLastEntity('payment', true);

        // Payment is left authorized
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Created bank transfer is an unexpected one
        $this->assertEquals('11122200123456781112220012345678', $bankTransfer['payee_account']);
        $this->assertEquals('va_ShrdVirtualAcc', $bankTransfer['virtual_account_id']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $bankTransfer['unexpected_reason']);
        $this->assertNotNull($payment['id'], 'pay_'.$bankTransfer['payment_id']);
    }

    public function testBankTransferImpsFromRogueBankStripAccount()
    {
        $this->markTestSkipped();

        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = Provider::IFSC[Provider::KOTAK];

        $this->fixtures->base->editEntity(
            'bank_account',
            $this->bankAccount['id'],
            [
                'ifsc_code' => $ifsc
            ]);

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $request['server']['REMOTE_ADDR'] = '14.141.97.12';

        $this->cloud = false;

        $this->ba->kotakAuth();

        $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertSame('00000000000123456', $bankTransfer['payer_account']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account created, but with the zeroes stripped
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('CNRB0000002', $bankAccount['ifsc']);
        $this->assertSame('0000000123456', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);
    }

    public function testBankTransferSpecialCharsInAccNumber()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->proxyAuth();

        $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        // Null, because IFSC was not received for IMPS transaction
        $this->assertNull($bankAccount['ifsc']);
        $this->assertEquals('123123123', $bankAccount['account_number']);
    }

    public function testBankTransferStripPayerBankAccount()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->proxyAuth();

        $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertNull($bankAccount['ifsc']);
        $this->assertSame('00000000000123456', $bankAccount['account_number']);

        $response = $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/bank_transfers/payer_bank_account/strip',
            'content' => [
                'payer_ifsc' => 'ABC',
                'mode'       => 'imps'
            ]
        ]);

        $this->assertContains($bankTransfer['id'], $response);

        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertNull($bankAccount['ifsc']);
        $this->assertSame('0000000123456', $bankAccount['account_number']);
    }

    public function testBankTransferProcessAndFetchDetails()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(5000000, $virtualAccount['amount_paid']);
        $this->assertEquals('active', $virtualAccount['status']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $request = [
            'method'  => 'GET',
            'url'     => '/payments/'.$payment['id'].'/bank_transfer',
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        $expectedResponse = [
            'id'                 => $bankTransfer['id'],
            'entity'             => 'bank_transfer',
            'payment_id'         => $payment['id'],
            'virtual_account_id' => $virtualAccount['id'],
            'amount'             => 5000000,
            'bank_reference'     => $bankTransfer[E::BANK_REFERENCE],
            'mode'               => $bankTransfer[E::MODE],
            'payer_bank_account' => [
                'entity'         => 'bank_account',
                'account_number' => '9876543210123456789',
                'ifsc'           => 'HDFC0000001',
            ],
            'virtual_account'    => [
                'name'      => 'Test Merchant',
                'entity'    => 'virtual_account',
                'status'    => 'active',
                'receivers' => [
                    [
                        'entity'         => 'bank_account',
                        'ifsc'           => 'RAZR0000001',
                    ],
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
    }

    public function testPaymentFetchOnBanfReference()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $this->processBankTransfer($accountNumber, $ifsc);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $payment = $this->getLastPayment();

        $this->ba->proxyAuth();

        $request = [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'bank_reference' => $bankTransfer[E::BANK_REFERENCE],
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($payment['id'], $response['items'][0]['id']);
        $this->assertEquals('NEFT payment of 50,000 rupees', $response['items'][0]['description']);
        $this->assertEquals('bank_transfer', $response['items'][0]['method']);
        $this->assertEquals('captured',$response['items'][0]['status']);
    }

    public function testBankTransferProcessDuplicateUtr()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        $utr = $response['transaction_id'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc, $utr);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // No new entity created
        $oldBankTransferId = $bankTransfer['id'];
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($oldBankTransferId, $bankTransfer['id']);

        $differentAccountNumber = $this->createVirtualAccount()['account_number'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $differentAccountNumber;
        $request['content']['payee_ifsc'] = $ifsc;
        $request['content']['payer_ifsc'] = $ifsc;
        $request['content']['transaction_id'] = $utr;

        $this->ba->proxyAuth();
        // Another payment, same UTR, made to a different account, from a different account
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // New entity created, as this is not a duplicate payment
        $oldBankTransferId = $bankTransfer['id'];
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertNotEquals($oldBankTransferId, $bankTransfer['id']);
        $this->assertEquals($differentAccountNumber, $bankTransfer['payee_account']);
    }

    public function testBankTransferProcessCryptoBlock()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $this->fixtures->merchant->edit('10000000000000', ['category2' => 'cryptocurrency']);

        $this->ba->adminAuth();
        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:block_bank_transfers_for_crypto' => '1',
            ],
        ]);

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('HDFC Bank', $bankAccount['bank_name']);

        // Created bank transfer is an unexpected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals($bankAccount['id'], $bankTransfer['payer_bank_account_id']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Invalid account forced creation of a temp acc for default merchant
        $virtualAccount =  $this->getLastEntity('virtual_account', true);
        $this->assertEquals('10000000000000', $virtualAccount['merchant_id']);
        $this->assertEquals(5000000, $virtualAccount['amount_paid']);
        $this->assertEquals(5000000, $virtualAccount['amount_received']);
        $this->assertEquals('va_ShrdVirtualAcc', $virtualAccount['id']);
        $this->assertEquals('active', $virtualAccount['status']);

        // Payment is not captured, but left in authorized state for auto-refund
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertNotNull($payment['receiver_type']);

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();

            $input = ['amount' => $payment['amount']];
            $refund = $this->refundAuthorizedPayment($payment['id'], $input);
            $this->assertPassportKeyExists('consumer.id'); // just check for presence of passport

            $this->assertEquals('processed', $refund['status']); // refunds get processed
            $this->assertEquals(5000000, $refund['amount']);
        }
        else
        {
            $this->refundAuthorizedPayment($payment['id']);

             // Refund is created
            $refund = $this->getLastEntity('refund', true);
            $this->assertEquals('initiated', $refund['status']);
            $this->assertEquals(5000000, $refund['amount']);

            // Transaction is created for refund
            $transaction = $this->getLastEntity('transaction', true);
            $this->assertEquals('refund', $transaction['type']);
            $this->assertEquals($refund['id'], $transaction['entity_id']);

            // Fund transfer attempt created for refund
            $attempt = $this->getLastEntity('fund_transfer_attempt', true);
            $this->assertEquals('created', $attempt['status']);
            $this->assertEquals($refund['id'], $attempt['source']);
            $this->assertEquals('10000000000000', $attempt['merchant_id']);
            $this->assertEquals('ACC DOESNT EXIST-'.$bankTransfer['utr'], $attempt['narration']);

        }

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals($payment['id'], $refund['payment_id']);

    }

    public function testBankTransferProcessInvalidAccount()
    {
        $accountNumber = 'RZRPYAINVALIDACCOUNT';
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('HDFC Bank', $bankAccount['bank_name']);

        // Created bank transfer is an unexpected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals($bankAccount['id'], $bankTransfer['payer_bank_account_id']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Invalid account forced creation of a temp acc for default merchant
        $virtualAccount =  $this->getLastEntity('virtual_account', true);
        $this->assertEquals('10000000000000', $virtualAccount['merchant_id']);
        $this->assertEquals(5000000, $virtualAccount['amount_paid']);
        $this->assertEquals(5000000, $virtualAccount['amount_received']);
        $this->assertEquals(null, $virtualAccount['amount_expected']);
        $this->assertEquals('active', $virtualAccount['status']);
        $this->assertEquals('va_ShrdVirtualAcc', $virtualAccount['id']);

        // Payment is not captured, but left in authorized state for auto-refund
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Terminal should be 0 so fallback terminal should be assigned to this payment
        $this->assertEquals('GENERICABNKACC', $payment['terminal_id']);

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();

            $input = ['amount' => $payment['amount']];
            $refund = $this->refundAuthorizedPayment($payment['id'], $input);
            $this->assertPassportKeyExists('consumer.id'); // just check for presence of passport

            $this->assertEquals('processed', $refund['status']); // refunds get processed
            $this->assertEquals(5000000, $refund['amount']);

            // COME BACK TO THIS LATER
            // transactions and fta's are not asserted
        }
        else
        {
            $this->refundAuthorizedPayment($payment['id']);

            // Refund is created
            $refund = $this->getLastEntity('refund', true);
            $this->assertEquals($payment['id'], $refund['payment_id']);
            $this->assertEquals('initiated', $refund['status']);
            $this->assertEquals(5000000, $refund['amount']);

             // Transaction is created for refund
            $transaction = $this->getLastEntity('transaction', true);
            $this->assertEquals('refund', $transaction['type']);
            $this->assertEquals($refund['id'], $transaction['entity_id']);

            // Fund transfer attempt created for refund
            $attempt = $this->getLastEntity('fund_transfer_attempt', true);
            $this->assertEquals('created', $attempt['status']);
            $this->assertEquals($refund['id'], $attempt['source']);
            $this->assertEquals('10000000000000', $attempt['merchant_id']);
            $this->assertEquals('ACC DOESNT EXIST-'.$bankTransfer['utr'], $attempt['narration']);
        }

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);

        $this->runBankTransferRequestAssertions(
            true,
            'VIRTUAL_ACCOUNT_NOT_FOUND',
            [
                'intended_virtual_account_id'   => null,
                'actual_virtual_account_id'     => $bankTransfer['virtual_account_id'],
                'merchant_id'                   => null,
                'bank_transfer_id'              => $bankTransfer['id'],
                'payment_id'                    => $payment['id'],
            ]
        );
    }

    public function testBankTransferYesBankRefundsNotAllowed()
    {
        $this->markTestSkipped("Yesbank refunds are allowed now");

        // Refunds flow has changed.
        // Before removing skip test, make sure Razorx experiment is turned ON.

        $accountNumber = $this->bankAccount['account_number'];

        $data =$this->testData[__FUNCTION__];

        $data['request']['content']['payee_account'] = $accountNumber;

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($data['request']);

        $utr = $response['transaction_id'];

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundAuthorizedPayment($payment['id']);
        });
    }

    public function testBankTransferProcessFailure()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testBankTransferNotify()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $this->processBankTransfer($accountNumber, $ifsc);

        // Created bank transfer is an expected one, but initially not marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertEquals(false, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Notify API always returns true
        $response = $this->notifyBankTransfer($accountNumber, $ifsc, $bankTransfer['utr']);
        $this->assertEquals(true, $response['success']);
        $this->assertNull($response['message']);

        // Bank transfer is now marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertEquals(true, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);
    }

    public function testBankTransferNotifyAgain()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $this->processBankTransfer($accountNumber, $ifsc);

        // Created bank transfer is an expected one, but initially not marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertEquals(false, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $utr = $bankTransfer['utr'];

        // Notify API always returns true
        $response = $this->notifyBankTransfer($accountNumber, $ifsc, $utr);
        $this->assertEquals(true, $response['success']);
        $this->assertNull($response['message']);

        // Bank transfer is now marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertEquals(true, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Notify API still returns true
        $response = $this->notifyBankTransfer($accountNumber, $ifsc, $utr);
        $this->assertEquals(true, $response['success']);
        $this->assertNull($response['message']);

        // Bank transfer is still marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertEquals(true, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
    }

    public function testBankTransferNotifyNonFailure()
    {
        $this->ba->kotakAuth();
        $this->startTest();
    }

    public function testBankTransferPublicAuth()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['method'] = 'bank_transfer';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $response = $this->doAuthPayment($payment);
        });
    }

    public function testBankTransferRefundReconciliation()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $channel = Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $this->processBankTransfer($accountNumber, $ifsc);
        $payment =  $this->getLastEntity('payment', true);

        $this->gateway = $payment['gateway'];

        // For scrooge flow tests
        $ftaData = [
            'is_fta'   => true,
            'fta_data' => [
                'bank_account' => [
                    'account_number'   => $accountNumber,
                    'beneficiary_name' => $this->bankAccount['name'],
                    'ifsc_code'        => $this->bankAccount['ifsc'],
                ]
            ]
        ];

        $this->refundPayment($payment['id'], 4000000, $ftaData);

        $content = $this->initiateTransferViaFileAndAssertSuccess(
            $channel, Attempt\Purpose::REFUND, 0, Attempt\Type::REFUND);

        $channel = Channel::YESBANK;
        $content = $this->initiateTransferAndAssertSuccess(
            $channel,
            Attempt\Purpose::REFUND,
            1,
            Attempt\Type::REFUND);

        $data = $this->reconcileOnlineSettlements($channel, false);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertNotNull($attempt['utr']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt[Attempt\Entity::STATUS]);

        // Process entities
        $this->reconcileEntitiesForChannel($channel);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
    }

    public function testBankTransferInsert()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->adminAuth();
        $response = $this->makeRequestAndGetContent($request);

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

        $this->initiateTransferViaFileAndAssertSuccess(
            Channel::AXIS, Attempt\Purpose::REFUND, 0, Attempt\Type::REFUND);

        $channel = Channel::YESBANK;
        $content = $this->initiateTransferAndAssertSuccess(
            $channel,
            Attempt\Purpose::REFUND,
            1,
            Attempt\Type::REFUND);
    }

    public function testBankTransferFloatingPointImprecision()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->proxyAuth();
        $response = $this->makeRequestAndGetContent($request);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(57930, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(57930, $payment['amount']);
    }

    public function testBankTransferRbl()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $payerBankAccount = $this->getEntityById('bank_account', $bankTransfer['payer_bank_account']['id'], true);
        $this->assertEquals($testData['request']['content']['Data'][0]['senderAccountNumber'], $payerBankAccount['account_number']);
    }

    public  function testBankTransferRblViaScService()
    {
        $this->enableRazorXTreatmentForRoutingApiToScService();

        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals("Success", $response['Status']);

        $this->ba->smartCollectAuth();

        $processInternalTestData = $this->testData[__FUNCTION__];

        $this->startTest($processInternalTestData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
    }

    protected function enableRazorXTreatmentForRoutingApiToScService()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::SMARTCOLLECT_SERVICE_BANK_TRANSFER)
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    public function testBankTransferRblJSW()
    {
        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblJSWVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);
        $this->assertEquals(Provider::IFSC[Provider::RBL_JSW], $bankTransfer['payee_ifsc']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals(Gateway::BT_RBL_JSW, $payment['gateway']);
    }

    /**
     * Account number is less than 16 characters in length for some RBL VAs.
     */
    public function testRblBankTransferWithShortPayeeAccount()
    {
        $testData = $this->testData['testBankTransferRbl'];
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = '222333004335048';

        $this->startTest($testData);
    }

    /**
     * Account number is greater than 16 characters in length for some RBL VAs.
     */
    public function testRblBankTransferWithLongPayeeAccount()
    {
        $testData = $this->testData['testBankTransferRbl'];
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = '22233300433504890';

        $this->startTest($testData);
    }

    public function testRblBankTransferWithAlphanumericPayeeAccount()
    {
        $testData = $this->testData['testBankTransferRbl'];
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = '222333AB43350485';

        $this->startTest($testData);
    }

    public function testRblBankTransferWithEmptyPayeeAccount()
    {
        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testRblBankTransferWithNonAlphanumericPayeeAccount()
    {
        $testData = $this->testData['testRblBankTransferWithEmptyPayeeAccount'];
        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = '2223330_43350485';

        $this->startTest($testData);
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

    public function testHdfcEcmsBankTransferCallback()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['UniqueID']);
        $this->assertEquals(1000000, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(1000000, $payment['amount']);
        $this->assertEquals('bt_hdfc_ecms', $payment['gateway']);
    }

    public function testHdfcFailVAOnValidation()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ["amount" => 2000]);

        $virtualAccount = $this->createVirtualAccountForOrder($order)['receivers'][0];

        $this->fixtures->create('feature', [
            'name' => Feature\Constants::FAIL_VA_ON_VALIDATION,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);


        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['account_number'];

        $this->ba->hdfcEcmsAuth();


        $responseData = $this->runRequestResponseFlow($testData);

        $updatedVirtualAccount = $this->getLastEntity('virtual_account', true);

        $this->assertEquals(VirtualAccount\Status::CLOSED, $updatedVirtualAccount['status']);
    }

    public function testHdfcFailVAOnValidationWithoutFeatureFlag()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ["amount" => 2000]);

        $virtualAccount = $this->createVirtualAccountForOrder($order)['receivers'][0];

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['account_number'];

        $this->ba->hdfcEcmsAuth();


        $responseData = $this->runRequestResponseFlow($testData);

        $updatedVirtualAccount = $this->getLastEntity('virtual_account', true);

        $this->assertEquals(VirtualAccount\Status::ACTIVE, $updatedVirtualAccount['status']);
    }

    public function testIciciBankTransferCallbackInvalid()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->ba->iciciAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackBadRequest()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferDuplicateTransaction()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $request = $testData['request'];

        $request['content']['UniqueID'] = '02081900018';

        $this->makeRequestAndGetContent($request);

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferTransactionNotFound()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $testData = $this->testData[__FUNCTION__];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackAlreadyProcessed()
    {

        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');


        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $request = $testData['request'];

        $this->makeRequestAndGetContent($request);

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackExpiry()
    {
        $this->fixtures->create('feature', [
            'name' => Feature\Constants::SET_VA_DEFAULT_EXPIRY,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);

        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');


        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testIciciBankTransferCallbackBadRequest()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->ba->iciciAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackMaxAmountThresholdExceeded()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1000010]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureHigherAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureHigherAmountFailure()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureLowerAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::ACCEPT_LOWER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureLowerAmountFailure()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::ACCEPT_LOWER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureHigherAndLowerAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');
        $this->fixtures->merchant->addFeatures(Feature\Constants::ACCEPT_LOWER_AMOUNT, '10000000000000');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Virtual_Account_No'] = $this->getHdfcEcmsVaBankAccount();

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackFeatureHigherWithPartialPayment()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ['partial_payment' => true]);

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature/
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');
        $virtualAccount = $this->createVirtualAccountForOrder($order)['receivers'][0];

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['account_number'];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
        }

    public function testHdfcEcmsBankTransferCallbackPartialPaymentExceedsOrderAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ['partial_payment' => true]);

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature/
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $virtualAccount = $this->createVirtualAccountForOrder($order);

        $this->fixtures->order->edit($order['id'], ['amount_paid' => 1150000]);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['receivers'][0]['account_number'];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsBankTransferCallbackPartialPaymentExceedsThresholdAmount()
    {
        $this->fixtures->create('terminal:hdfc_ecms_bank_account_terminal');

        $order = $this->fixtures->create('order', ['partial_payment' => true]);

        $this->merchantId = '10000000000000';

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 1200000]);

        //add feature/
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT, '10000000000000');

        $virtualAccount = $this->createVirtualAccountForOrder($order);

        $this->fixtures->order->edit($order['id'], ['amount_paid' => 115000]);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['Virtual_Account_No'] = $virtualAccount['receivers'][0]['account_number'];

        $this->ba->hdfcEcmsAuth();

        $this->startTest($testData);
    }

    public function testHdfcEcmsSingleVAwithFeatureExcessPayment()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT);

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');

        $order1 = $this->fixtures->create('order');

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1);
        $this->fixtures->base->editEntity(
            'virtual_account',
            $virtualAccount1['id'],
            [
                'customer_id' => '100000customer'
            ]);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => ($order1['amount_due'] / 100) + 100]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity1['status']);
        $this->assertEquals($virtualAccount1['amount_expected']+10000, $virtualAccountEntity1['amount_paid']);

        $order = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);

        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $virtualAccountEntity1['customer_id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);
        $this->assertEquals(VirtualAccount\Status::ACTIVE, $virtualAccount2['status']);

        $this->payVirtualAccount($virtualAccount2['id'], ['amount' => $order2['amount_due'] / 100]);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity2['status']);
        $this->assertEquals($virtualAccount2['amount_expected'], $virtualAccountEntity2['amount_paid']);

        $order = $this->getEntityById('order', $order2['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);
    }

    public function testHdfcEcmsSingleVAwithFeatureLowerPayment()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);
        $this->fixtures->merchant->addFeatures(Feature\Constants::ACCEPT_LOWER_AMOUNT);

        $order1 = $this->fixtures->create('order');

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1);
        $this->fixtures->base->editEntity(
            'virtual_account',
            $virtualAccount1['id'],
            [
                'customer_id' => '100000customer'
            ]);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => ($order1['amount_due'] / 100) - 100]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity1['status']);
        $this->assertEquals($virtualAccount1['amount_expected'] - 10000, $virtualAccountEntity1['amount_paid']);

        $order = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);

        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $virtualAccountEntity1['customer_id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);
        $this->assertEquals(VirtualAccount\Status::ACTIVE, $virtualAccount2['status']);

        $this->payVirtualAccount($virtualAccount2['id'], ['amount' => $order2['amount_due'] / 100]);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity2['status']);
        $this->assertEquals($virtualAccount2['amount_expected'], $virtualAccountEntity2['amount_paid']);

        $order = $this->getEntityById('order', $order2['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);
    }

    public function testHdfcEcmsSingleVAwithFeatureHigherPaymentAndPartialPayment()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);
        $this->fixtures->merchant->addFeatures(Feature\Constants::EXCESS_ORDER_AMOUNT);

        $order1 = $this->fixtures->create('order',['partial_payment' => true]);

        $virtualAccount1 = $this->createVirtualAccountForOrder($order1);
        $this->fixtures->base->editEntity(
            'virtual_account',
            $virtualAccount1['id'],
            [
                'customer_id' => '100000customer'
            ]);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => ($order1['amount_due'] / 100) - 100]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::ACTIVE, $virtualAccountEntity1['status']);
        $this->assertEquals($virtualAccount1['amount_expected'] - 10000, $virtualAccountEntity1['amount_paid']);

        $order = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals(Order\Status::ATTEMPTED, $order['status']);

        $this->payVirtualAccount($virtualAccount1['id'], ['amount' => 1000]);

        $virtualAccountEntity1 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity1['status']);

        $order = $this->getEntityById('order', $order1['id'], true);
        $this->assertEquals(Order\Status::PAID, $order['status']);


        $order2 = $this->fixtures->create('order', ['amount' => 50000]);

        $virtualAccount2 = $this->createVirtualAccountForOrder($order2, ['customer_id' => $virtualAccountEntity1['customer_id']]);

        $this->assertEquals($virtualAccount1['id'], $virtualAccount2['id']);
        $this->assertEquals(VirtualAccount\Status::ACTIVE, $virtualAccount2['status']);

        $this->payVirtualAccount($virtualAccount2['id'], ['amount' => $order2['amount_due'] / 100]);

        $virtualAccountEntity2 = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(VirtualAccount\Status::PAID, $virtualAccountEntity2['status']);
        $this->assertEquals($virtualAccount2['amount_expected'], $virtualAccountEntity2['amount_paid']);

        $order = $this->getEntityById('order', $order2['id'], true);
        $this->assertEquals(VirtualAccount\Status::PAID, $order['status']);
    }

    public function testBankTransferRblRefund()
    {
        $this->createRblRefund(__FUNCTION__);
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

    public function testBankTransferIciciWithInvalidPrefixForPayerAccount()
    {
        $this->setupForIciciXFundLoading();

        $data = $this->testData[__FUNCTION__]['request']['content'];

        $utr = $data['transaction_id'];

        $expectedPayerAccount = '073523524001';

        $payerIfsc = $data['payer_ifsc'];

        $payerName = $data['payer_name'];

        $payeeAccount = $data['payee_account'];

        $payeeIfsc = $data['payee_ifsc'];

        $description = $data['description'];

        $expectedAmount = $data['amount'] . '00';

        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::PAYER_ACCOUNT_NUMBER_INVALID_REGEXES => ['INHSBC', '-']
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
                'payer_account_number' => '73523524001'
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

    public function testEcollectRblBatchCreate()
    {
        $data = $this->testData['ecollectRblBatchData'];

        $this->createExcelFile($data, 'filename', 'files/filestore');

        $this->ba->h2hAuth();

        $this->startTest();
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

    public function testBankTransferRblImps()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals($bankTransfer['utr'], '006713653919');

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
    }

    public function testBankTransferRblUpi()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals($bankTransfer['utr'], '006713070094');

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $this->ba->privateAuth();
    }

    public function testBankTransferRblIft()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals($bankTransfer['utr'], '006713070094');

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
    }

    public function testBankTransferRblWithInvalidData()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);
    }

    public function testBankTransferRblWithMissingHeader()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);
    }

    public function testBankTransferRblWithDuplicateUtr()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $this->startTest($testData);
    }

    public function testBankTransferRblWithInternalServerError()
    {
        $this->ba->directAuth();

        $this->startTest();
    }

    public function testBankTransferRblWithEmptyFields()
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);
    }

    public function testBankTransferNotDuplicateDiffUTR()
    {
        $utr1 = 'utr_one';

        $utr2 = 'utr_two';

        $accountNumber1 = $this->bankAccount['account_number'];

        $ifsc1 = 'HDFC0000001';

        $ifsc2 = 'HDFC0000002';

        $request = $this->testData['testBankTransferProcessDuplicateUtr'];

        $request['content']['payee_account']  = $accountNumber1;

        $request['content']['transaction_id'] = $utr1;

        $request['content']['payee_ifsc']     = $ifsc1;

        $this->ba->proxyAuth();

        $response1 = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response1['valid']);

        $bankTransfer1 = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals(true, $bankTransfer1['expected']);

        $this->assertEquals(null, $bankTransfer1['unexpected_reason']);

        $this->assertEquals($utr1, $bankTransfer1['utr']);

        $this->assertEquals($accountNumber1, $bankTransfer1['payee_account']);

        $request['content']['transaction_id'] = $utr2;

        $request['content']['payee_ifsc']    = $ifsc2;

        $this->ba->proxyAuth();

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response2['valid']);

        $bankTransfer2 = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals(true, $bankTransfer2['expected']);

        $this->assertEquals(null, $bankTransfer2['unexpected_reason']);

        $this->assertEquals($utr2, $bankTransfer2['utr']);

        $this->assertEquals($accountNumber1, $bankTransfer2['payee_account']);
    }

    public function testBankTransferNotDuplicateDiffAccount()
    {
        $utr1 = 'utr_one';

        $accountNumber1 = $this->bankAccount['account_number'];

        $accountNumber2 = $this->createVirtualAccount()['account_number'];

        $ifsc1 = 'HDFC0000001';

        $ifsc2 = 'HDFC0000002';

        $request = $this->testData['testBankTransferProcessDuplicateUtr'];

        $request['content']['payee_account']  = $accountNumber1;

        $request['content']['transaction_id'] = $utr1;

        $request['content']['payee_ifsc']     = $ifsc1;

        $this->ba->proxyAuth();

        $response1 = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response1['valid']);

        $bankTransfer1 = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals(true, $bankTransfer1['expected']);

        $this->assertEquals(null, $bankTransfer1['unexpected_reason']);

        $this->assertEquals($utr1, $bankTransfer1['utr']);

        $this->assertEquals($accountNumber1, $bankTransfer1['payee_account']);

        $request['content']['payee_account'] = $accountNumber2;

        $request['content']['payee_ifsc']    = $ifsc2;

        $this->ba->proxyAuth();

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response2['valid']);

        $bankTransfer2 = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals(true, $bankTransfer2['expected']);

        $this->assertEquals(null, $bankTransfer2['unexpected_reason']);

        $this->assertEquals($utr1, $bankTransfer2['utr']);

        $this->assertEquals($accountNumber2, $bankTransfer2['payee_account']);
    }

    public function testBankTransferDuplicate()
    {
        $utr = 'utr_one';

        $accountNumber = $this->bankAccount['account_number'];

        $ifsc1 = 'HDFC0000001';

        $ifsc2 = 'HDFC0000002';

        $request = $this->testData['testBankTransferProcessDuplicateUtr'];

        $request['content']['payee_account']  = $accountNumber;

        $request['content']['transaction_id'] = $utr;

        $request['content']['payee_ifsc'] = $ifsc1;

        $this->ba->proxyAuth();

        $response1 = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response1['valid']);

        $bankTransfer1 = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals(true, $bankTransfer1['expected']);

        $this->assertEquals(null, $bankTransfer1['unexpected_reason']);

        $this->assertEquals($utr, $bankTransfer1['utr']);

        $this->assertEquals($accountNumber, $bankTransfer1['payee_account']);

        $this->assertEquals($ifsc1, $bankTransfer1['payee_ifsc']);

        $this->ba->proxyAuth();

        $request['content']['payee_ifsc'] = $ifsc2;

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response2['valid']);

        $bankTransfer2 = $this->getLastEntity('bank_transfer', true);

        $this->assertNotEquals($ifsc2, $bankTransfer2['payee_ifsc']);
    }

    public function testBankTransferEditPayerBankAccount()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);

        $request = [
            'method'  => 'PUT',
            'url'     => '/bank_transfers/'.$bankTransfer['id'].'/payer_bank_account',
            'content' => [
                'account_number' => '123456',
                'ifsc_code'      => 'HDFC0000002',
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('HDFC0000002', $response['payer_bank_account']['ifsc']);

        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000002', $bankAccount['ifsc']);
        $this->assertEquals('123456', $bankAccount['account_number']);
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

    public function testBankTransferProcessWithExtraFields()
    {
        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->ba->proxyAuth();
        $this->startTest();

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
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

    public function testBankTransferProcessWithFieldsOnLiveMode()
    {
        // 0 Ledger SNS calls because even though the request is to live mode,
        // the ledger journal write feature isn't present.
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

    protected function createRblRefund($callee)
    {
        $testData = $this->testData[$callee];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);

        $this->gateway = 'bt_rbl';

        $this->refundPayment($payment['id'], 343946, ['is_fta' => true]);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(343946, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getDBLastEntity('refund');
        $this->assertEquals($payment['id'], 'pay_' . $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(343946, $refund['amount']);

        // Transaction is created for refund
        $transaction = $this->getLastEntity('transaction', true);
        $this->assertEquals('refund', $transaction['type']);
        $this->assertEquals('rfnd_' . $refund['id'], $transaction['entity_id']);

        // Fund transfer attempt created for refund
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals('rfnd_' . $refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertEquals('Test Merchant-CMS480098890', $attempt['narration']);
    }

    protected function createRefundWithDeletedTerminal($channel = null)
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

        $paymentEntity = $this->getDbEntityById('payment', $payment['id']);

        // Disable foreign key checks to allow testing buggy case
        DB::statement("SET foreign_key_checks = 0");

        $this->fixtures->edit(
            'payment',
            $paymentEntity['id'],
            ['terminal_id' => 'B2K2t8JD9z98vh']);

        // Enable foreign key checks
        DB::statement("SET foreign_key_checks = 1");

        $this->gateway = $payment['gateway'];

        // For scrooge flow tests
        $ftaData = [
            'is_fta'   => true,
            'fta_data' => [
                'bank_account' => [
                    'account_number'   => $accountNumber,
                    'beneficiary_name' => $this->bankAccount['name'],
                    'ifsc_code'        => $this->bankAccount['ifsc'],
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

    protected function createTpvRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $this->gateway = 'atom';

        $terminal = $this->fixtures->create('terminal:shared_atom_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTpv();

        $data = $this->testData[__FUNCTION__];

        $order =  $this->runRequestResponseFlow($data);

        $payment['order_id'] = $order['id'];

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['bank_txn'] = '99999999';
            $content['bank_name'] = 'SBIN';
        });

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->fixtures->merchant->disableTPV();

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);

        $this->fixtures->merchant->addFeatures(['bank_transfer_refund']);

        $response = $this->refundPayment($payment['id'], $payment['amount'], ['is_fta' => true]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals($payment['id'], $refund['payment_id']);

        $this->assertEquals('created', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fundTransferAttempt['narration']);

        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();

        $this->assertEquals('SBIN0010411', $bankAccount['ifsc_code']);

        $this->assertEquals($order['account_number'], $bankAccount['account_number']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function testUpdateReceiverData()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);

        $payment = $this->getLastEntity('payment', true);

        $receiverId = $payment['receiver_id'];

        $this->fixtures->payment->edit($payment['id'], ['receiver_id' => null, 'receiver_type' => null]);

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'url'    => '/payment/bank_transfer_backfill',
                'method' => 'post',
            ]
        );

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['receiver_id'], $receiverId);

        $this->assertEquals($payment['receiver_type'], 'bank_account');
    }

    public function testBankTransferWithCustomerFeeBearer()
    {
        $this->fixtures->merchant->enableConvenienceFeeModel('10000000000000');

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => 'customer']);

        // In the below scenario Virtual Account doesn't have any order associated.

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        // To make sure payment is created with fee even when
        // virtual account did not have any associated order.
        $this->assertNotNull($payment['fee']);
        $this->assertNull($payment['order_id']);
    }

    public function testBankTransferWithDynamicFeeBearer()
    {
        $this->fixtures->merchant->enableDynamicFeeModel('10000000000000');

        // In the below scenario Virtual Account doesn't have any order associated.

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertNotNull($bankTransfer['payment_id']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        // To make sure payment is created with fee even when
        // virtual account did not have any associated order.
        $this->assertNotNull($payment['fee']);
        $this->assertNull($payment['order_id']);
    }

    public function testBankTransferProcessWithPayerBankAccountOf4Chars()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $utr = strtoupper(random_alphanum_string(22));

        $request['content']['transaction_id'] = $utr;

        $request['content']['amount'] = 50000;

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($utr, $response['transaction_id']);

        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('SHRDBANKACC3DS', $payment['terminal_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('bt_dashboard', $payment['gateway']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('UDAN', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);
    }

    public function testBankTransferPreferences()
    {
        $methods = $this->getPreferences()['methods'];

        // Key is not present in preferences, even though method is enabled
        $this->assertArrayNotHasKey('bank_transfer', $methods);

        $this->fixtures->merchant->addFeatures(['bank_transfer_on_checkout']);

        $methods = $this->getPreferences()['methods'];

        // Key becomes available when feature is enabled
        $this->assertArrayHasKey('bank_transfer', $methods);;
    }

    public function testRblFallbackTerminal()
    {
        $testData = $this->testData[__FUNCTION__];

        $terminalAttributes = [
            'id'                    =>'RblBtFlbkTrmnl',
            'gateway'               => Gateway::BT_RBL,
            'gateway_merchant_id'   => '1112',
            'gateway_merchant_id2'  => '',
        ];

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('va_ShrdVirtualAcc', $bankTransfer['virtual_account_id']);

        $this->assertEquals('bt_rbl', $payment['gateway']);

        $this->assertEquals('RblBtFlbkTrmnl', $payment['terminal_id']);

        $this->assertEquals(100000, $payment['amount']);
    }

    public function testRblBankTransferWithNoMatchingTerminal()
    {
        $terminalAttributes = [
            'id'                    =>'RblBtShrdTrmnl',
            'gateway'               => Gateway::BT_RBL,
            'gateway_merchant_id'   => '111222',
            'gateway_merchant_id2'  => '',
            'shared'                => true,
            'bank_transfer'         => true,
        ];

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $this->startTest();

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals('va_ShrdVirtualAcc', $bankTransfer['virtual_account_id']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bt_rbl', $payment['gateway']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('RblBtShrdTrmnl', $payment['terminal_id']);
    }

    protected function getRblVaBankAccount()
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKRBL', 'gateway' => Gateway::BT_RBL, 'gateway_merchant_id' => '0001046' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount();

        return $bankAccount['account_number'];
    }

    protected function getRblJSWVaBankAccount()
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKRBL', 'gateway' => Gateway::BT_RBL_JSW, 'gateway_merchant_id' => 'VAJSW' ];
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

    public function testProcessBankTransferInvalidPayerIfsc()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc          = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;
        $request['content']['payee_ifsc']    = $ifsc;

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account created
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        // Null, because IFSC was not received for IMPS transaction
        $this->assertEquals('UTIB0001918', $bankAccount['ifsc']); //Default IFSC for UTIB
    }

    protected function getPreferences()
    {
        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent([
            'url'    => '/preferences',
            'method' => 'get',
            'content' => [
                'currency' => 'INR'
            ]
        ]);

        return $response;
    }

    public function testBankTransferToClosedVa()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $this->closeVirtualAccount($this->virtualAccountId);

        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_CLOSED', $bankTransfer['unexpected_reason']);

        // Payment is automatically refunded
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertNull($payment['refund_at']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(5000000, $refund['amount']);
        $this->assertEquals('Virtual Account is closed', $refund['notes']['refund_reason']);

        $this->runBankTransferRequestAssertions(
            true,
            'VIRTUAL_ACCOUNT_CLOSED',
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $bankTransfer['virtual_account_id'],
                'merchant_id'                   => '10000000000000',
                'bank_transfer_id'              => $bankTransfer['id'],
            ]
        );

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

    public function testBankTransferForCustomerFeeBearerWithPercentRate()
    {
        $pricingPlanId = $this->fixtures->create('pricing:bank_transfer_percent_pricing_plan', ['fee_bearer' => 'customer']);

        $this->fixtures->merchant->createAccount('20000000000000');
        $this->fixtures->merchant->enableMethod('20000000000000', 'bank_transfer');
        $this->fixtures->merchant->enableConvenienceFeeModel('20000000000000');
        $this->fixtures->merchant->edit('20000000000000', ['pricing_plan_id' => $pricingPlanId]);

        $virtualAccount = $this->fixtures->create(
            'virtual_account',
            [
                'merchant_id'   => '20000000000000',
                'status'        => 'active',
            ]
        );
        $bankAccount = $this->fixtures->create(
            'bank_account',
            [
                'merchant_id'       => '20000000000000',
                'entity_id'         => $virtualAccount->getId(),
                'type'              => 'virtual_account',
                'account_number'    => '1112229988776655',
                'ifsc_code'         => 'RAZR0000002',
            ]
        );
        $this->fixtures->edit(
            'virtual_account',
            $virtualAccount->getId(),
            ['bank_account_id' => $bankAccount->getId()]
        );

        $response = $this->processBankTransfer('1112229988776655', 'RAZR0000002', null, 100);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer = $this->getDbLastEntity('bank_transfer');
        $this->assertEquals('1112229988776655', $bankTransfer['payee_account']);
        $this->assertEquals('RAZR0000002', $bankTransfer['payee_ifsc']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('Payment failed because fees or tax was tampered', $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('authorized', $payment['status']);

        $this->runBankTransferRequestAssertions(
            true,
            'Payment failed because fees or tax was tampered',
            [
                'intended_virtual_account_id'   => $virtualAccount->getPublicId(),
                'actual_virtual_account_id'     => 'va_ShrdVirtualAcc',
                'merchant_id'                   => '20000000000000',
                'bank_transfer_id'              => $bankTransfer->getPublicId(),
            ]
        );
    }

    public function testBankTransferForCustomerFeeBearerWithPaymentLessThanFee()
    {
        $this->fixtures->merchant->enableConvenienceFeeModel('10000000000000');

        $this->fixtures->pricing->editDefaultPlan(
            [
                'fee_bearer'    => 'customer',
                'percent_rate'  => '0',
                'fixed_rate'    => '1000',
            ]
        );

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $response = $this->processBankTransfer($accountNumber, $ifsc, null, 5);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $this->runBankTransferRequestAssertions(
            false,
            'Fee calculated is greater than the payment amount.'
        );
    }

    public function testBankTransferForPlatformFeeBearerWithPaymentLessThanFee()
    {
        $pricingPlanId = $this->fixtures->create('pricing:bank_transfer_fixed_pricing_plan', ['fee_bearer' => 'platform']);

        $this->fixtures->merchant->createAccount('20000000000000');
        $this->fixtures->merchant->enableMethod('20000000000000', 'bank_transfer');
        $this->fixtures->merchant->edit('20000000000000', ['pricing_plan_id' => $pricingPlanId]);

        $virtualAccount = $this->fixtures->create(
            'virtual_account',
            [
                'merchant_id'   => '20000000000000',
                'status'        => 'active',
            ]
        );
        $bankAccount = $this->fixtures->create(
            'bank_account',
            [
                'merchant_id'       => '20000000000000',
                'entity_id'         => $virtualAccount->getId(),
                'type'              => 'virtual_account',
                'account_number'    => '1112229988776655',
                'ifsc_code'         => 'RAZR0000002',
            ]
        );
        $this->fixtures->edit(
            'virtual_account',
            $virtualAccount->getId(),
            ['bank_account_id' => $bankAccount->getId()]
        );

        $response = $this->processBankTransfer('1112229988776655', 'RAZR0000002', null, 1);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer = $this->getDbLastEntity('bank_transfer');
        $this->assertEquals('1112229988776655', $bankTransfer['payee_account']);
        $this->assertEquals('RAZR0000002', $bankTransfer['payee_ifsc']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('The fees calculated for payment is greater than the payment amount. Please provide a higher amount', $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('authorized', $payment['status']);

        $this->runBankTransferRequestAssertions(
            true,
            'The fees calculated for payment is greater than the payment amount. Please provide a higher amount'
        );
    }

    public function testBankTransferForCancelledInvoice()
    {
        $this->createInvoice(['status' => 'issued']);

        $order = $this->getDbLastEntity('order');

        $response = $this->createVirtualAccountForOrder($order);

        $intendedVirtualAccountId = $response['id'];

        $accountNumber = $response['receivers'][0]['account_number'];
        $ifsc = $response['receivers'][0]['ifsc'];

        $request = $this->testData['cancelInvoice'];

        $this->ba->privateAuth();
        $this->makeRequestAndGetContent($request);

        $response = $this->processBankTransfer($accountNumber, $ifsc, null, 1000);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer = $this->getDbLastEntity('bank_transfer');
        $this->assertEquals('10000000000000', $bankTransfer['merchant_id']);
        $this->assertEquals('ShrdVirtualAcc', $bankTransfer['virtual_account_id']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('Invoice is not payable in cancelled status.', $bankTransfer['unexpected_reason']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $this->runBankTransferRequestAssertions(
            true,
            'Invoice is not payable in cancelled status.',
            [
                'intended_virtual_account_id'   => $intendedVirtualAccountId,
                'actual_virtual_account_id'     => 'va_ShrdVirtualAcc',
                'merchant_id'                   => '10000000000000',
                'bank_transfer_id'              => $bankTransfer->getPublicId(),
                'order_id'                      => null,
            ]
        );
    }

    protected function runBankTransferRequestAssertions(bool $isCreated, string $errorMessage, $expectedValues = [], $mode = 'test')
    {
        $bankTransferRequest = $this->getDbLastEntity('bank_transfer_request', $mode);

        $this->assertNotNull($bankTransferRequest['request_payload']);
        $this->assertEquals($isCreated, $bankTransferRequest['is_created']);
        $this->assertEquals($errorMessage, $bankTransferRequest['error_message']);

        if (empty($expectedValues) === true)
        {
            return;
        }

        $this->ba->adminAuth($mode);
        $testData = $this->testData['adminFetchBankTransferRequest'];
        $testData['request']['url'] .= $bankTransferRequest->getPublicId();
        $bankTransferRequest = $this->startTest($testData);

        $this->assertArraySelectiveEquals($expectedValues, $bankTransferRequest);
    }

    public function testBankTransferValidateTpvWithValidPayerDetails()
    {
        $this->processBankTransferForVaWithTpvEnabled($this->testData['bankTransferValidateTpv']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
    }

    public function testBankTransferValidateTpvWithZeroPreceedingPayerDetails()
    {
        $bankTransferTestData = $this->testData['bankTransferValidateTpv'];
        $bankTransferTestData['request']['content']['payer_account'] = '123499988';
        $bankTransferTestData['request']['content']['payer_ifsc'] = 'UTIB0000013';
        $this->processBankTransferForVaWithTpvEnabled($bankTransferTestData);

        $bankTransfer = $this->getDbLastEntityToArray('bank_transfer');
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        // Payment is automatically captured
        $payment = $this->getDbLastEntityToArray('payment');
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
    }

    public function testBankTransferValidateTpvWithZeroPreceedingAllowedPayerDetails()
    {
        $bankTransferTestData = $this->testData['bankTransferValidateTpv'];
        $bankTransferTestData['request']['content']['payer_account'] = '000765432123456789';
        $this->processBankTransferForVaWithTpvEnabled($bankTransferTestData);

        $bankTransfer = $this->getDbLastEntityToArray('bank_transfer');
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);

        // Payment is automatically captured
        $payment = $this->getDbLastEntityToArray('payment');
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
    }

    public function testBankTransferValidateTpvWithInvalidPayerDetails()
    {
        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        $flag = true;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
            $payment = $this->getDbLastEntityPublic('payment');
        }

        $testData = $this->testData['bankTransferValidateTpv'];
        $testData['request']['content']['payer_account'] = strtoupper(random_alphanum_string(16));

        $this->processBankTransferForVaWithTpvEnabled($testData);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_PAYMENT_TPV_FAILED', $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);
        $this->runBankTransferRequestAssertions(
            true,
            'VIRTUAL_ACCOUNT_PAYMENT_TPV_FAILED'
        );

        if ($flag === true)
        {
            $this->updatePaymentStatus($bankTransfer['payment_id'], [], true);
        }

        // Payment is automatically refunded
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
    }

    public function testBankTransferValidateTpvWithPayerBankCode()
    {
        $testData = $this->testData['bankTransferValidateTpv'];
        $testData['request']['content']['payer_ifsc']    = 'HDFC0000000';

        $this->processBankTransferForVaWithTpvEnabled($testData);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
    }

    public function processBankTransferForVaWithTpvEnabled($testData)
    {
        $bankAccount = $this->createVirtualAccount('test', '10000000000000', $this->testData['createVAWithAllowedPayer']);

        $testData['request']['content']['payee_account'] = $bankAccount['account_number'];
        $testData['request']['content']['payee_ifsc']    = $bankAccount['ifsc'];

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testWebhookVirtualAccountCreditedWithAllowedPayer()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('virtual_account.credited', $expectedEvent);

        $this->processBankTransferForVaWithTpvEnabled($this->testData['bankTransferValidateTpv']);
    }

    public function testWebhookRefundProcessedForTpvFailure()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];
        $this->expectWebhookEventWithContents('refund.processed', $expectedEvent);

        $testData = $this->testData['bankTransferValidateTpv'];
        $testData['request']['content']['payer_account'] = strtoupper(random_alphanum_string(16));

        $this->processBankTransferForVaWithTpvEnabled($testData);
    }

    public function testBankTransferImpsWithNbinValidateTpv()
    {
        $this->fixtures->terminal->createBankAccountTerminal();

        $bankAccount = $this->createVirtualAccount('test', '10000000000000', $this->testData['createVAWithAllowedPayer']);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['payee_account'] = $bankAccount['account_number'];
        $testData['request']['content']['payee_ifsc'] = $bankAccount['ifsc'];

        $this->ba->yesbankAuth();
        $this->startTest($testData);

        $bankTransfer = $this->getDbLastEntity('bank_transfer');
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testBankTransferForVaOnCheckoutWithCustomerDetails()
    {
        $this->fixtures->merchant->addFeatures(['checkout_va_with_customer']);

        $order = $this->fixtures->create('order');

        $response = $this->createVirtualAccountForOrder($order, ['customer' => ['contact' => '1234567890', 'email' => 'test@test.com']]);

        $accountNumber = $response['receivers'][0]['account_number'];
        $ifsc          = $response['receivers'][0]['ifsc'];

        $response = $this->processBankTransfer($accountNumber, $ifsc, null, 10000);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer = $this->getDbLastEntity('bank_transfer');
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals($payment['email'], 'test@test.com');
        $this->assertEquals('captured', $payment['status']);
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
            ->setMethods(['getTreatment'])
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

    public function testAdminTestBankTransferPayment()
    {
        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = $this->bankAccount['ifsc'];

        $amount = 100;

        $mode = 'test';

        $this->ba->adminProxyAuth();

        $this->ba->setType('proxy');

        $response = $this->processOrNotifyBankTransfer($accountNumber,$ifsc, 'awesome_utr', $amount, $mode);

        $this->assertTrue($response['valid']);

        $bankTransfer =  $this->getDbLastEntityToArray('bank_transfer', 'test');

        $this->assertEquals($bankTransfer['utr'], 'awesome_utr');

        $this->assertEquals(10000, $bankTransfer['amount']);

        $payment =  $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals(10000, $payment['amount']);
    }

    public function testBankTransferRefundFailPaymentSuccess()
    {
        $this->fixtures->merchant->editBalance(0);

        $this->fixtures->merchant->editCredits('29000','10000000000000');

        $this->fixtures->pricing->editDefaultPlan(
            [
                'fee_bearer'    => 'customer',
                'percent_rate'  => '0',
                'fixed_rate'    => '1000',
            ]
        );

        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = $this->bankAccount['ifsc'];

        $response = $this->processBankTransfer($accountNumber, $ifsc, null,1,'test');

        $bankTransferRequestArray = $this->getDbLastEntityToArray('bank_transfer_request');

        $this->assertEquals('REFUND_OR_CAPTURE_PAYMENT_FAILED',$bankTransferRequestArray['error_message']);

        $this->assertTrue($bankTransferRequestArray['is_created']);
    }

    protected function processBankTransferProcessWithDifferentAmount($amount)
    {
        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = $this->bankAccount['ifsc'];

        $mode = 'test';

        $this->ba->proxyAuth();

        $response = $this->processOrNotifyBankTransfer($accountNumber,$ifsc, 'awesome_utr', $amount, $mode);

        $this->assertTrue($response['valid']);

        $bankTransfer =  $this->getDbLastEntityToArray('bank_transfer', 'test');

        $this->assertEquals($bankTransfer['utr'], 'awesome_utr');

        $this->assertEquals($amount * 100, $bankTransfer['amount']);

        $payment =  $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals($amount * 100, $payment['amount']);
    }

    public function testBankTransferProcessWithDifferentAmount()
    {
        $this->processBankTransferProcessWithDifferentAmount(100);
        $this->processBankTransferProcessWithDifferentAmount(200);
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

    public function testBankTransferProcessPgWithTerminalCaching()
    {
        $this->enableRazorXTreatmentForCaching();

        $cacheKey = VirtualAccount\Constant::TERMINAL_CACHE_PREFIX . '_' . '10000000000000';

        $store = $this->app['cache'];

        $pickedFromTerminalCache = false;

        \Cache::shouldReceive('driver')
              ->andReturnUsing(function($driver = null) use ($store) {
                  return $store;
              });

        \Cache::shouldReceive('get')
              ->andReturnUsing(function($key, $default = null) use ($cacheKey, $store, &$pickedFromTerminalCache) {
                  if ($key === $cacheKey)
                  {
                      $pickedFromTerminalCache = true;
                      return [
                          [
                              'id'                     => 'SHRDBANKACC3DS',
                              Terminal\Entity::GATEWAY => 'bt_dashboard'
                          ]
                      ];
                  }

                  return $store->get($key, $default);

              })
              ->shouldReceive('store')
              ->withAnyArgs()
              ->andReturn($store)
              ->shouldReceive('put')
              ->withAnyArgs()
              ->andReturn($store);

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc          = $this->bankAccount['ifsc'];

        // Process API always returns true
        $this->processBankTransfer($accountNumber, $ifsc);

        $this->assertTrue($pickedFromTerminalCache);
    }

    protected function enableRazorXTreatmentForCaching()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::SMART_COLLECT_TERMINAL_CACHING)
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
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

    public function testBankTransferPaymentCaptureOnClosedVa()
    {
        $this->closeVirtualAccount($this->virtualAccountId);

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc          = $this->bankAccount['ifsc'];

        $this->disableUnexpectedPaymentRefundImmediately();

        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_CLOSED', $bankTransfer['unexpected_reason']);

        // Payment is automatically refunded
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage("Payment done on closed customer identifier cannot be captured.");

        $this->capturePayment($payment['id'], $payment['amount']);
    }

}
