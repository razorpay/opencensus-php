<?php

namespace RZP\Tests\Functional\BankTransfer;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Status;
use RZP\Models\Pricing\Fee;
use RZP\Models\VirtualAccount\Provider;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankTransfer\Entity as E;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;

class BankTransferTest extends TestCase
{
    use AttemptTrait;
    use DbEntityFetchTrait;
    use AttemptReconcileTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BankTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->createAccount("BankAccountMer");

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

    public function testBankTransferTerminalDataMigration()
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
        $this->assertNotNull($bankTransfer['payment_id']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('SHRDBANKACC3DS', $payment['terminal_id']);

        $this->fixtures->payment->edit($payment['id'], ['terminal_id' => null]);
        $payment =  $this->getLastEntity('payment', true);
        $this->assertNull($payment['terminal_id']);

        $request = [
            'method'    => 'POST',
            'url'       => '/payment/bank_transfer_terminal_backfill',
            'content'   => []
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('SHRDBANKACC3DS', $payment['terminal_id']);
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
        $this->assertEquals(Refund\Status::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['processed_at']);
        $this->assertEquals(1, $refund['attempts']);
        $this->assertNotNull($attempt['utr']);
    }

    public function testBankTransferImps()
    {
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

    public function testBankTransferImpsWithNbin()
    {
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

    public function testBankTransferImpsFromRogueBankStripAccount()
    {
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
                'name' => "Test Merchant",
                'entity' => "virtual_account",
                'status' => "active",
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
                'block_bank_transfers_for_crypto' => '1',
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
        $this->assertEquals(Attempt\Status::INITIATED, $attempt[Attempt\Entity::STATUS]);

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

    protected function createVirtualAccount($mode = 'test', $merchantId = '10000000000000')
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

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
        $this->assertNotNull($bankTransfer['payment_id']);
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

        $response = $this->refundPayment($payment['id']);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals($payment['id'], $refund['payment_id']);

        $this->assertEquals('initiated', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

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
}
