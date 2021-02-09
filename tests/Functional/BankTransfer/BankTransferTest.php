<?php

namespace RZP\Tests\Functional\BankTransfer;

use DB;
use Mail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

use RZP\Models\Feature;
use RZP\Models\Pricing\Fee;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Header;
use RZP\Models\Payment\Refund;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Status;
use RZP\Models\VirtualAccount;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Transaction\BankTransfer;
use RZP\Models\BankingAccountTpv\Type;
use RZP\Models\VirtualAccount\Provider;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\BankTransfer\Entity as E;
use RZP\Mail\Merchant\RazorpayX\FundLoadingFailed;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
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

    public function setUp()
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

        $this->ba->appAuth();
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

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(99, $payment['amount']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('bt_dashboard', $payment['gateway']);
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

        // Payment is made to test merchant and left authorized
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('10000000000000', $payment['merchant_id']);
    }

    public function testBankTransferWithInActiveAccount()
    {
        $this->markTestSkipped('Skipped due to yesbank disablement');

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
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);

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
        $transaction = $this->getLastEntity('transaction', true);
        // Pricing 16%
        $this->assertEquals($transaction['amount'] * 16 / 100, $transaction['fee'] - $transaction['tax']);

        // Amount greater than 100
        $this->processBankTransfer($accountNumber, $ifsc, null, 10000);
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

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(Refund\Status::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['processed_at']);
        $this->assertEquals(1, $refund['attempts']);

        $this->assertEquals($attempt['utr'], $refund['reference1']);
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

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(Refund\Status::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['processed_at']);
        $this->assertEquals(1, $refund['attempts']);
        $this->assertNotNull($attempt['utr']);
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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);
    }

    public function testBankTransferImpsUnmappedBankCode()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $this->ba->appAuth();

        $response = $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals('IMPS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

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

        // IMPS refunds are permitted...
        $this->refundPayment($payment['id'], 4000000);

        // ...but they don't actually work
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('failed', $refund['status']);
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

        $this->ba->appAuth();

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

        // IMPS refunds are permitted...
        $this->refundPayment($payment['id'], 4000000);

        // ...but they don't actually work
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('failed', $refund['status']);
        $this->assertEquals(4000000, $refund['amount']);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);

        $this->ba->appAuth();

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
        $this->assertEquals('initiated', $refund['status']);

        // IFSC updated for payer bank account
        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals('RAZR0000001', $bankAccount['ifsc']);

        // Fund transfer attempt created for refund
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
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

        $this->refundPayment($payment['id'], 4000000);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('initiated', $refund['status']);
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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $content = $this->initiateTransferViaFileAndAssertSuccess(
            $channel, Attempt\Purpose::REFUND, 0, Attempt\Type::REFUND);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Status::CREATED, $attempt['status']);

        $this->ba->appAuth();

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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
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

        $this->refundPayment($payment['id'], 4000000);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('initiated', $refund['status']);
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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
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

        $this->ba->appAuth();

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

        $this->ba->appAuth();

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

        $this->ba->appAuth();

        $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals('533/1 NEFT CASH FOR NON CUSTOMER', $bankTransfer['payer_account']);
        $this->assertEquals('RTGS', $bankTransfer['mode']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

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
        $this->ba->appAuth();

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

        $this->ba->appAuth();

        $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

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

        $this->ba->appAuth();

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
        $this->ba->appAuth();
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

        $this->refundAuthorizedPayment($payment['id']);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);

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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
        $this->assertEquals('ACC DOESNT EXIST-'.$bankTransfer['utr'], $attempt['narration']);
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

        $this->refundAuthorizedPayment($payment['id']);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);

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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
        $this->assertEquals('ACC DOESNT EXIST-'.$bankTransfer['utr'], $attempt['narration']);

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

        $accountNumber = $this->bankAccount['account_number'];

        $data =$this->testData[__FUNCTION__];

        $data['request']['content']['payee_account'] = $accountNumber;

        $this->ba->appAuth();

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
    }

    public function testBankTransferNotifyNonFailure()
    {
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
        $this->refundPayment($payment['id'], 4000000);

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

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(Refund\Status::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['processed_at']);
        $this->assertEquals(1, $refund['attempts']);
        $this->assertEquals($attempt['utr'], $refund['reference1']);
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

        $this->refundPayment($payment['id'], 4000000);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('initiated', $refund['status']);
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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
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

    public function testIciciBankTransferCallbackBadRequest()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->ba->iciciAuth();

        $this->startTest($testData);
    }

    public function testBankTransferRblRefund()
    {
        $this->createRblRefund(__FUNCTION__);
    }

    public function testBankTransferIcici()
    {
        $this->processOrNotifyBankTransfer(
            $this->getIciciVaBankAccount(),
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

    public function testBankTransferIciciWithIfscAsBankCode()
    {
        $this->processOrNotifyBankTransfer(
            $this->getIciciVaBankAccount(),
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
        $this->processOrNotifyBankTransfer(
            $this->getIciciVaBankAccount(),
            'ICIC0000104',
            'awesome_utr'
        );

        $bankTransfer =  $this->getLastEntity('bank_transfer', true, 'live');
        $this->assertEquals(null, $bankTransfer);
    }

    public function testCheckEcollectIciciBatchCreate()
    {
        Queue::fake();

        $this->ba->appAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testEcollectRblBatchCreate()
    {
        $data = $this->testData['ecollectRblBatchData'];

        $this->createExcelFile($data, 'filename', 'files/filestore');

        $this->ba->h2hAuth();

        $this->startTest();
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

        $this->ba->appAuth();

        $response1 = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response1['valid']);

        $bankTransfer1 = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals(true, $bankTransfer1['expected']);

        $this->assertEquals(null, $bankTransfer1['unexpected_reason']);

        $this->assertEquals($utr1, $bankTransfer1['utr']);

        $this->assertEquals($accountNumber1, $bankTransfer1['payee_account']);

        $request['content']['transaction_id'] = $utr2;

        $request['content']['payee_ifsc']    = $ifsc2;

        $this->ba->appAuth();

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

        $this->ba->appAuth();

        $response1 = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response1['valid']);

        $bankTransfer1 = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals(true, $bankTransfer1['expected']);

        $this->assertEquals(null, $bankTransfer1['unexpected_reason']);

        $this->assertEquals($utr1, $bankTransfer1['utr']);

        $this->assertEquals($accountNumber1, $bankTransfer1['payee_account']);

        $request['content']['payee_account'] = $accountNumber2;

        $request['content']['payee_ifsc']    = $ifsc2;

        $this->ba->appAuth();

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

        $this->ba->appAuth();

        $response1 = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response1['valid']);

        $bankTransfer1 = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals(true, $bankTransfer1['expected']);

        $this->assertEquals(null, $bankTransfer1['unexpected_reason']);

        $this->assertEquals($utr, $bankTransfer1['utr']);

        $this->assertEquals($accountNumber, $bankTransfer1['payee_account']);

        $this->assertEquals($ifsc1, $bankTransfer1['payee_ifsc']);

        $this->ba->appAuth();

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

        $this->startTest();

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
    }

    public function testBankTransferProcessWithFieldsOnTestMode()
    {
        Mail::fake();

        // Since this test also uses banking balance, we need to disable tpv flow for it.
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
            'off',
            'on',
            'on',
            'off',
            'control',
            'on'// just set this on, leave everything as default
        );

        $balance = $this->getDbEntity('balance',
                                      [
                                          'merchant_id'  => '10000000000000',
                                      ], 'test');

        $this->fixtures->edit('balance', $balance->getId(), [
            'type' => 'banking',
        ]);

        $accountNumber = $this->bankAccount['account_number'];

        $this->testData[__FUNCTION__]['request']['content']['payee_account'] = $accountNumber;

        $this->startTest();

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(null, $bankTransfer['unexpected_reason']);

        Mail::assertNotQueued(BankTransfer::class);
    }

    public function testBankTransferProcessWithFieldsOnLiveMode()
    {
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

    protected function processBankTransfer($accountNumber, $ifsc, $utr = null, $amount = null, $mode = 'test')
    {
        return $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr, $amount, $mode);
    }

    protected function notifyBankTransfer($accountNumber, $ifsc, $utr = null)
    {
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

        $this->ba->appAuth();

        if ($mode === 'live')
        {
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

        $this->refundPayment($payment['id'], 4000000);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('initiated', $refund['status']);
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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
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

        $this->refundPayment($payment['id'], 4000000);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000000, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('initiated', $refund['status']);
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
        $this->assertEquals($refund['bank_account_id'], $attempt['bank_account_id']);
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

        $gatewayEntity = $this->getLastEntity('atom', true);

        $this->assertArraySelectiveEquals(
            $this->testData['tpvPaymentNetbankingEntity'], $gatewayEntity);

        $this->assertEquals($gatewayEntity['account_number'],
                            $data['request']['content']['account_number']);

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

        $this->assertEquals($bankAccount['id'], $refund['bank_account_id']);

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

        $this->ba->appAuth();

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

        $this->ba->appAuth();

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

    protected function getIciciVaBankAccount()
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKICI', 'gateway' => Gateway::BT_ICICI, 'gateway_merchant_id' => '2244' ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $bankAccount = $this->createVirtualAccount();

        return $bankAccount['account_number'];
    }

    protected function getHdfcEcmsVaBankAccount()
    {
        $order = $this->fixtures->create('order');

        $bankAccount = $this->createVirtualAccountForOrder($order)['receivers'][0];

        return $bankAccount['account_number'];
    }

    protected function createAndPutExcelFileInRequest(array $entries, string $callee)
    {
        $url = $this->writeToExcelFile($entries, 'file', 'files/batch');

        $uploadedFile = $this->createUploadedFileForBatch($url);

        $this->testData[$callee]['request']['files']['attachment-1'] = $uploadedFile;
    }

    public function createUploadedFileForBatch(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            filesize($url),
            null,
            true);
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

        $this->ba->appAuth();

        $response = $this->makeRequestAndGetContent($request);

        // Created bank transfer is an expected one
        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertNotNull($bankTransfer['payment_id']);

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
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $bankTransfer['unexpected_reason']);

        $this->runBankTransferRequestAssertions(
            true,
            'VIRTUAL_ACCOUNT_NOT_FOUND',
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $bankTransfer['virtual_account_id'],
                'merchant_id'                   => '10000000000000',
                'bank_transfer_id'              => $bankTransfer['id'],
            ]
        );
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
        $this->assertEquals(false, $response['valid']);
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

    protected function runBankTransferRequestAssertions(bool $isCreated, string $errorMessage, $expectedValues = [])
    {
        $bankTransferRequest = $this->getDbLastEntity('bank_transfer_request');

        $this->assertNotNull($bankTransferRequest['request_payload']);
        $this->assertEquals($isCreated, $bankTransferRequest['is_created']);
        $this->assertEquals($errorMessage, $bankTransferRequest['error_message']);

        if (empty($expectedValues) === true)
        {
            return;
        }

        $this->ba->adminAuth();
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

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
    }

    public function testBankTransferValidateTpvWithInvalidPayerDetails()
    {
        $testData = $this->testData['bankTransferValidateTpv'];
        $testData['request']['content']['payer_account'] = strtoupper(random_alphanum_string(16));

        $this->processBankTransferForVaWithTpvEnabled($testData);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_PAYMENT_TPV_FAILED', $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $this->runBankTransferRequestAssertions(
            true,
            'VIRTUAL_ACCOUNT_PAYMENT_TPV_FAILED'
        );

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

        $this->ba->appAuth();

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
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('bank_account', $payment['receiver_type']);
        $this->assertEquals('captured', $payment['status']);
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

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals($payment['email'], 'test@test.com');
        $this->assertEquals('captured', $payment['status']);
    }

    public function testBankTransferRblAsyncProcessing()
    {
        $this->enableRazorXTreatmentForRblBankTransferProcess();

        $testData = $this->testData['testBankTransferRbl'];

        $testData['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $this->getRblVaBankAccount();

        $this->ba->directAuth();

        $this->startTest($testData);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($bankTransfer['narration'], $testData['request']['content']['Data'][0]['UTRNumber']);
        $this->assertEquals(343946, $bankTransfer['amount']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals('bt_rbl', $payment['gateway']);
    }

    // No razorx experiment and no feature flag added -- it means tpv is enabled.
    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound()
    {
        Mail::fake();

        $this->setupForXFundLoading();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->appAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfPaymentsBeforeFundLoading + 1, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $payment = $this->getDbLastEntity('payment', 'live');

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

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
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING', $bankTransfer->getUnexpectedReason());

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
            $this->assertEquals('9876543210123456789', $viewData['payer_account_number']);
            $this->assertEquals('2224440041626905', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = "Fund loading failed on your RazorpayX account number <2224440041626905>";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });
    }

    // No razorx experiment and no feature flag added -- it means tpv is enabled.
    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButApprovedActiveTpvAccountFound()
    {
        Mail::fake();

        $this->setupForXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'payer_ifsc' => 'YESB0000022',
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->appAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

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

    // No razorx experiment and no feature flag added -- it means tpv is enabled.
    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButPendingTpvAccountFound()
    {
        Mail::fake();

        $this->setupForXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'pending',
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->appAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfPaymentsBeforeFundLoading + 1, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $payment = $this->getDbLastEntity('payment', 'live');

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

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
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING', $bankTransfer->getUnexpectedReason());

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
            $this->assertEquals('9876543210123456789', $viewData['payer_account_number']);
            $this->assertEquals('2224440041626905', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = "Fund loading failed on your RazorpayX account number <2224440041626905>";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });
    }

    // No razorx experiment and no feature flag added -- it means tpv is enabled.
    public function testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButInActiveTpvAccountFound()
    {
        Mail::fake();

        $this->setupForXFundLoading();

        $this->fixtures->on('live')->create('banking_account_tpv',
                                            [
                                                'balance_id' => $this->bankingBalance->getId(),
                                                'status'     => 'approved',
                                                'is_active'  => 0,
                                            ]);

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->appAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfPaymentsBeforeFundLoading + 1, $countOfPaymentsAfterFundLoading);
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $payment = $this->getDbLastEntity('payment', 'live');

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

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
        $this->assertEquals('TPV_NOT_FOUND_FOR_BANKING_ACCOUNT_FUND_LOADING', $bankTransfer->getUnexpectedReason());

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
            $this->assertEquals('9876543210123456789', $viewData['payer_account_number']);
            $this->assertEquals('2224440041626905', $viewData['payee_account_number']);
            $this->assertEquals(FundLoadingFailed::URL, $viewData['url']);

            $mailSubject = "Fund loading failed on your RazorpayX account number <2224440041626905>";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.merchant.razorpayx.fund_loading_failed', $mail->view);

            return true;
        });
    }

    // Razorx returns 'on' and flow is disabled for the merchants. This can be used to disable tpv flow for all
    // merchants instantly at global level.
    public function testBankTransferIciciIMPSForRazorpayXWithTpvDisabledViaRazorx()
    {
        Mail::fake();

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
            'off',
            'on',
            'on',
            'off',
            'control',
            'on'// just set this on, leave everything as default
        );

        $this->setupForXFundLoading();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->appAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

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

    // Using feature flag to disable tpv flow for a specific merchant. This can be used to disable tpv flow for a
    // specific merchant without affecting the flow for other merchants.
    public function testBankTransferIciciIMPSForRazorpayXWithTpvDisabledViaFeatureFlag()
    {
        Mail::fake();

        $this->mockRazorxTreatment();

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_TPV_FLOW,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->setupForXFundLoading();

        list($countOfPaymentsBeforeFundLoading,
            $countOfTransactionsBeforeFundLoading,
            $countOfBankTransfersBeforeFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        $utr = strtoupper(random_alphanum_string(22));

        $payeeAccount = $this->bankAccount;

        $this->testData[__FUNCTION__] = $this->testData['testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound'];

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['payee_account'] = $payeeAccount->getAccountNumber();

        $request['content']['payee_ifsc'] = 'ICIC0000104';

        $request['content']['transaction_id'] = $utr;

        $this->ba->appAuth();

        $response = $this->startTest();

        $this->assertEquals($utr, $response['transaction_id']);

        list($countOfPaymentsAfterFundLoading,
            $countOfTransactionsAfterFundLoading,
            $countOfBankTransfersAfterFundLoading
            ) = $this->listCountOfPaymentTransactionAndBankTransferEntities('live');

        // Assert that no new payment was created.
        $this->assertEquals($countOfPaymentsBeforeFundLoading, $countOfPaymentsAfterFundLoading);

        // Assert that exactly one of these entities was created during fund loading request.
        $this->assertEquals($countOfBankTransfersBeforeFundLoading + 1, $countOfBankTransfersAfterFundLoading);
        $this->assertEquals($countOfTransactionsBeforeFundLoading + 1,  $countOfTransactionsAfterFundLoading);

        $transaction = $this->getDbLastEntity('transaction', 'live');

        $bankTransfer = $this->getDbLastEntity('bank_transfer', 'live');

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

    protected function enableRazorXTreatmentForRblBankTransferProcess()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === 'bank_transfer_queue')
                    {
                        return 'on';
                    }

                    return 'off';
                }));
    }

    protected function setIciciVaBankAccountTerminalForRazorpayX()
    {
        $terminalAttributes = [ 'id' =>'GENERICBANKICI', 'gateway' => Gateway::BT_ICICI, 'gateway_merchant_id' => '5656'];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
    }

    protected function setupForXFundLoading()
    {
        $this->setIciciVaBankAccountTerminalForRazorpayX();

        $this->setUpMerchantForBusinessBankingLive(true);

        $this->fixtures->on('live')->edit(
            'bank_account',
            $this->bankAccount->getId(),
            [
                'ifsc_code' => 'ICIC0000104',
            ]
        );

        $this->bankAccount = $this->bankAccount->reload();
    }

    protected function listCountOfPaymentTransactionAndBankTransferEntities(string $mode = 'test')
    {
        $countOfPayments = count($this->getDbEntities('payment', [], $mode));

        $countOfTransactions = count($this->getDbEntities('transaction', [], $mode));

        $countOfBankTransfers = count($this->getDbEntities('bank_transfer', [], $mode));

        return [$countOfPayments, $countOfTransactions, $countOfBankTransfers];
    }
}
