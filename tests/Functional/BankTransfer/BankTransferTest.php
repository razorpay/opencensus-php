<?php

namespace RZP\Tests\Functional\BankTransfer;

use DB;
use Mail;
use Cache;
use Carbon\Carbon;
use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Models\Pricing\Fee;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Header;
use RZP\Models\Payment\Refund;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Status;
use RZP\Models\VirtualAccount;
use RZP\Models\Payment\Gateway;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Transaction\BankTransfer;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\BankTransfer\Entity as E;
use RZP\Models\BankTransfer\Status as S;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\VirtualAccount\UnexpectedPaymentReason;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
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
        // Empty IFSC received for Payer Account
        $this->assertEquals('', $bankAccount['ifsc']);
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
        // Check Payer Bank Account after IMPS Transaction
        $this->assertEquals($ifsc, $bankAccount['ifsc']);
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
        // Check Payer Bank Account after IMPS Transaction
        $this->assertEquals($ifsc, $bankAccount['ifsc']);
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
        // Check Payer Bank Account after IMPS Transaction
        $this->assertEquals($ifsc, $bankAccount['ifsc']);
        $this->assertSame('00000000000123456', $bankAccount['account_number']);

        $response = $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/bank_transfers/payer_bank_account/strip',
            'content' => [
                'payer_account' => '00000000000123456',
                'mode'       => 'imps'
            ]
        ]);

        $this->assertContains($bankTransfer['id'], $response);

        $bankAccount = $this->getDbLastEntity('bank_account');
        $bankAccount = $bankAccount->toArray();
        $this->assertEquals($ifsc, $bankAccount['ifsc']);
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

    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
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

        $attributes = [
            'merchant_id'   => '10000000000000',
            'gateway'       => $payment['gateway'],
        ];

        $terminal   = $this->fixtures->create(
            'terminal', $attributes);

        $terminalId = $terminal['id'];

        $this->deleteTerminal('10000000000000', $terminalId);

        $this->fixtures->edit(
            'payment',
            $paymentEntity['id'],
            ['terminal_id' => $terminalId]);

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
        // Check Payer IFSC
        $this->assertEquals('UTIB0000002', $bankAccount['ifsc']); //Default IFSC for UTIB
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
        $pricingPlanId = $this->fixtures->create('pricing:bank_transfer_fixed_pricing_plan', ['fee_bearer' => 'customer']);

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

        $response = $this->processBankTransfer('1112229988776655', 'RAZR0000002', null, 1);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer = $this->getDbLastEntity('bank_transfer');
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('Fee calculated is greater than the payment amount.', $bankTransfer['unexpected_reason']);
        $this->assertNotNull($bankTransfer['payment_id']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);
        $this->assertEquals('authorized', $payment['status']);

        $this->runBankTransferRequestAssertions(
            true,
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

    public function testBankTransferProcessPgWithTerminalCaching()
    {
        // Skip Issue: https://razorpay.atlassian.net/browse/EPA-605
        $this->markTestSkipped('Skipping because Smart Collect Terminal Caching is Disabled');

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

    public function testBankTransferPaymentCaptureOnClosingVa()
    {
        $closeByDate = Carbon::now(Timezone::IST)->addDays(-15)->getTimestamp();

        $this->fixtures->edit('virtual_account', $this->virtualAccountId, [
            'close_by' => $closeByDate,
        ]);
        $this->closeVirtualAccountsByCloseBy();

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc          = $this->bankAccount['ifsc'];

        $this->disableUnexpectedPaymentRefundImmediately();

        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_DUE_TO_BE_CLOSED', $bankTransfer['unexpected_reason']);

        // Payment is automatically refunded
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertNotNull($payment['refund_at']);

        // $this->expectException(BadRequestValidationFailureException::class);
        // $this->expectExceptionMessage("Payment done on closed customer identifier cannot be captured.");

        $this->capturePayment($payment['id'], $payment['amount']);
    }

    public function testBankTransferProcessUpdateTransactionPostRecon()
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
        $paymentId = substr($bankTransfer['payment_id'], 4);

        // Payment is automatically captured
        $payment =  $this->getDbLastEntity('payment');
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($paymentId, $payment['id']);

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

        // Assert Transaction is not reconciled
        $transaction = $this->getDbLastEntity('transaction');
        $this->assertNotNull($transaction);
        $this->assertEquals($transaction["entity_id"], $paymentId);
        $this->assertNull($transaction["reconciled_at"]);
        $this->assertNull($transaction["reconciled_type"]);

        // Send reconciliation request
        $requestData = $this->testData["bankTransferArtReconPayload"];
        $requestData["content"]["payment_id"] = $paymentId;
        $requestData["content"]["amount"] = 500;
        $reconTimestamp = strval(Carbon::now()->getTimestamp());
        $requestData["content"]["reconciled_at"] = $reconTimestamp;
        $requestData["content"]["gateway_settled_at"] = $reconTimestamp;

        $this->ba->appAuth();

        $response = $this->makeRequestAndGetContent($requestData);
        $this->assertNotNull($response);
        $this->assertTrue($response['success']);

        // assert transaction is reconciled
        $transaction->reload();
        $this->assertEquals($transaction["reconciled_at"], $reconTimestamp);
        $this->assertEquals($transaction["reconciled_type"], $requestData["content"]["reconciled_type"]);
        $this->assertEquals($transaction["gateway_settled_at"], $requestData["content"]["gateway_settled_at"]);

        // assert payment refund_at is not set for expected payment
        $payment->reload();
        $this->assertNull($payment['refund_at']);
    }

    public function testBankTransferProcessUpdateRefundForUnexpectedPaymentPostRecon()
    {
        $accountNumber = 'TEST123567890';
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals('va_ShrdVirtualAcc', $bankTransfer['virtual_account_id']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $bankTransfer['unexpected_reason']);
        $this->assertEquals(S::PROCESSED, $bankTransfer['status']);

        $this->assertNotNull($bankTransfer['payment_id']);
        $paymentId = substr($bankTransfer['payment_id'], 4);

        // Payment will be at authorized state
        $payment =  $this->getDbLastEntity('payment');
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($paymentId, $payment['id']);

        // Assert Transaction is not reconciled
        $transaction = $this->getDbLastEntity('transaction');
        $this->assertNotNull($transaction);
        $this->assertEquals($transaction["entity_id"], $paymentId);
        $this->assertNull($transaction["reconciled_at"]);
        $this->assertNull($transaction["reconciled_type"]);

        // Send reconciliation request
        $requestData = $this->testData["bankTransferArtReconPayload"];
        $requestData["content"]["payment_id"] = $paymentId;
        $requestData["content"]["amount"] = 500;
        $reconTimestamp = strval(Carbon::now()->getTimestamp());
        $requestData["content"]["reconciled_at"] = $reconTimestamp;
        $requestData["content"]["gateway_settled_at"] = $reconTimestamp;

        $this->ba->appAuth();

        $response = $this->makeRequestAndGetContent($requestData);
        $this->assertNotNull($response);
        $this->assertTrue($response['success']);

        // assert transaction is reconciled
        $transaction->reload();
        $this->assertEquals($transaction["reconciled_at"], $reconTimestamp);
        $this->assertEquals($transaction["reconciled_type"], $requestData["content"]["reconciled_type"]);
        $this->assertEquals($transaction["gateway_settled_at"], $requestData["content"]["gateway_settled_at"]);

        // assert payment refund_at is set
        $payment->reload();
        $this->assertNotNull($payment['refund_at']);
    }

}
