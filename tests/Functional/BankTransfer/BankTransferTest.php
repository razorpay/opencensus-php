<?php

namespace RZP\Tests\Functional\BankTransfer;

use Redis;
use Mockery;
use Closure;
use RZP\Models\Payment\Refund;
use RZP\Models\BankTransfer\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Tests\Functional\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;

class BankTransferTest extends TestCase
{
    use PaymentTrait;
    use PayoutTrait;
    use SettlementTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BankTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->bankAccount = $this->createVirtualAccount();

        $this->ba->appAuth();
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
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // Customer bank account created
        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('Name of account holder', $bankAccount['name']);
    }

    public function testBankTransferRefund()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $response = $this->processBankTransfer($accountNumber, $ifsc);

        $utr = $response['transaction_id'];

        // Customer bank account created
        $bankAccount = $this->getLastEntity('bank_account', true);
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
        $this->assertEquals($bankAccount['id'], 'ba_'.$attempt['bank_account_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $content = $this->initiatePayouts();
        $this->assertNotNull($content['kotak']['payout_text_file']);
        $this->assertEquals(1, $content['kotak']['count']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('initiated', $attempt['status']);
    }

    public function testBankTransferImps()
    {
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
        $bankAccount = $this->getLastEntity('bank_account', true);
        // Set to mapped IFSC code for HDFC Bank code
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);

        $payment =  $this->getLastEntity('payment', true);

        // IMPS refunds are permitted
        $this->refundPayment($payment['id'], 4000000);
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(4000000, $refund['amount']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$attempt['bank_account_id']);
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
        $bankAccount = $this->getLastEntity('bank_account', true);
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
    }

    public function testBankTransferRefundRetry()
    {
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
        $bankAccount = $this->getLastEntity('bank_account', true);
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

        $response = $this->makeRequestAndGetContent([
            'method'  => 'POST',
            'url'     => '/bank_transfers/refunds/retry',
        ]);

        // Refund is now marked created again
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);

        // IFSC updated for payer bank account
        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals('RAZR0000001', $bankAccount['ifsc']);

        // Fund transfer attempt created for refund
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$attempt['bank_account_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $content = $this->initiatePayouts();
        $this->assertNotNull($content['kotak']['payout_text_file']);
        $this->assertEquals(1, $content['kotak']['count']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('initiated', $attempt['status']);
    }

    public function testBankTransferRefundRetryManual()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $response = $this->processBankTransfer($accountNumber, $ifsc);

        $utr = $response['transaction_id'];

        // Customer bank account created
        $bankAccount = $this->getLastEntity('bank_account', true);
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
        $this->assertEquals($bankAccount['id'], 'ba_'.$attempt['bank_account_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $content = $this->initiatePayouts();
        $this->assertNotNull($content['kotak']['payout_text_file']);
        $this->assertEquals(1, $content['kotak']['count']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('initiated', $attempt['status']);

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
        $this->fixtures->refund->edit($refund['id'], ['status'=>'failed']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($response['status']);

        // Refund is now marked created again
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);

        //  Another fund transfer attempt created for refund
        $oldAttempt = $attempt;
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertNotEquals($oldAttempt['id'], $attempt['id']);
        $this->assertEquals('created', $attempt['status']);
        $this->assertEquals($refund['id'], $attempt['source']);
        $this->assertEquals('10000000000000', $attempt['merchant_id']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$attempt['bank_account_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $content = $this->initiatePayouts();
        $this->assertNotNull($content['kotak']['payout_text_file']);
        $this->assertEquals(1, $content['kotak']['count']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('initiated', $attempt['status']);
    }

    public function testBankTransferRefundRetryToDifferentAccount()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $response = $this->processBankTransfer($accountNumber, $ifsc);

        $utr = $response['transaction_id'];

        // Customer bank account created
        $bankAccount = $this->getLastEntity('bank_account', true);
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
        $this->assertEquals($bankAccount['id'], 'ba_'.$attempt['bank_account_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $content = $this->initiatePayouts();
        $this->assertNotNull($content['kotak']['payout_text_file']);
        $this->assertEquals(1, $content['kotak']['count']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('initiated', $attempt['status']);

        // Only failed refunds can be retried
        $this->fixtures->refund->edit($refund['id'], ['status'=>'failed']);

        $response = $this->retryFailedRefund($refund['id'], [
            'bank_account' => [
                'account_number'   => '1234567890987654321',
                'ifsc_code'        => 'HDFC0000002',
                'beneficiary_name' => 'New Bank Account',
            ],
        ]);

        // Refund is now marked created again
        $refund =  $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);

        // Another bank account created
        $bankAccount = $this->getLastEntity('bank_account', true);
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
        $this->assertEquals($bankAccount['id'], 'ba_'.$attempt['bank_account_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $content = $this->initiatePayouts();
        $this->assertNotNull($content['kotak']['payout_text_file']);
        $this->assertEquals(1, $content['kotak']['count']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('initiated', $attempt['status']);
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
        $bankAccount = $this->getLastEntity('bank_account', true);
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
        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals('CNRB0000002', $bankAccount['ifsc']);
        $this->assertEquals('00000000000123456', $bankAccount['account_number']);

        $response = $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/bank_transfers/payer_bank_account/strip',
        ]);

        $this->assertContains($bankTransfer['id'], $response);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals('CNRB0000002', $bankAccount['ifsc']);
        $this->assertEquals('123456', $bankAccount['account_number']);
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

        $request = [
            'method'  => 'GET',
            'url'     => '/payments/'.$payment['id'].'/bank_transfer',
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        $expectedResponse = [
            'payment_id'         => $payment['id'],
            'virtual_account_id' => $virtualAccount['id'],
            'amount'             => 5000000,
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

        // Mode and UTR are currently not public, uncomment this when they are.
        // $this->assertEquals('NEFT', $response['mode']);
        // $this->assertNotNull($response['utr']);
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

        // Created bank transfer is not expected, not linked to a payment
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertNull($bankTransfer['payment_id']);
    }

    public function testBankTransferProcessInvalidAccount()
    {
        $accountNumber = 'RAZORPINVALIDACCOUNT';
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Customer bank account created
        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        $this->assertEquals('HDFC Bank', $bankAccount['bank_name']);

        // Created bank transfer is an unexpected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$bankTransfer['payer_bank_account_id']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);

        // Invalid account forced creation of a temp acc for default merchant
        $virtualAccount =  $this->getLastEntity('virtual_account', true);
        $this->assertEquals('10000000000000', $virtualAccount['merchant_id']);
        $this->assertEquals(5000000, $virtualAccount['amount_paid']);
        $this->assertEquals(5000000, $virtualAccount['amount_received']);
        $this->assertEquals(5000000, $virtualAccount['amount_expected']);
        $this->assertEquals('paid', $virtualAccount['status']);

        // Payment is not captured, but left in authorized state for auto-refund
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        $this->refundAuthorizedPayment($payment['id']);

        // Payment is refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);

        // Refund is created
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
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
        $this->assertEquals($bankAccount['id'], 'ba_'.$attempt['bank_account_id']);
        $this->assertEquals('ACC DOESNT EXIST-'.$bankTransfer['utr'], $attempt['narration']);
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

    public function testBankTransferNotifyFailure()
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

    public function testBankTransferReservedAccount()
    {
        $accountNumber = 'RZRNODAL123';
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // No bank transfer created
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertNull($bankTransfer);

        // No payment created
        $payment =  $this->getLastEntity('payment', true);
        $this->assertNull($payment);
    }

    public function testBankTransferRefundReconciliation()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $this->processBankTransfer($accountNumber, $ifsc);
        $payment =  $this->getLastEntity('payment', true);
        $this->refundPayment($payment['id'], 4000000);
        $content = $this->initiatePayouts();

        $reconFile = $this->generateSetlReconciliationFile($content['kotak']['payout_text_file']);

        $data = $this->reconcileSettlements($reconFile);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertNotNull($attempt['utr']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(Refund\Status::PROCESSED, $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
        $this->assertEquals($attempt['utr'], $refund['arn']);

        $batch = $this->getLastEntity('batch_fund_transfer', true);

        $this->assertEquals($attempt['batch_fund_transfer_id'], $batch['id']);
        $this->assertEquals($content['kotak']['payout_text_file'], $batch['urls']['kotak_payout_txt']);
        $this->assertEquals('refund', $batch['type']);
    }

    public function testBankTransferInsert()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        $response = $this->makeRequestAndGetContent($request);

        $utr = $response['transaction_id'];

        // Customer bank account created
        $bankAccount = $this->getLastEntity('bank_account', true);
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
        $this->assertEquals($bankAccount['id'], 'ba_'.$attempt['bank_account_id']);
        $this->assertStringEndsWith($utr, $attempt['narration']);

        $content = $this->initiatePayouts();
        $this->assertNotNull($content['kotak']['payout_text_file']);
        $this->assertEquals(1, $content['kotak']['count']);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('initiated', $attempt['status']);
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
        $bankAccount = $this->getLastEntity('bank_account', true);
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

        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertEquals('HDFC0000002', $bankAccount['ifsc']);
        $this->assertEquals('123456', $bankAccount['account_number']);
    }

    protected function createVirtualAccount()
    {
        $this->ba->privateAuth();

        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }

    protected function processBankTransfer($accountNumber, $ifsc, $utr = null)
    {
        return $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr);
    }

    protected function notifyBankTransfer($accountNumber, $ifsc, $utr = null)
    {
        return $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr);
    }

    protected function processOrNotifyBankTransfer($accountNumber, $ifsc, $utr)
    {
        $request = $this->testData[__FUNCTION__];

        $name = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        $request['url'] = $this->testData[$name]['url'];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        if (isset($utr) === false)
        {
            $utr = strtoupper(random_alphanum_string(22));
        }

        $request['content']['transaction_id'] = $utr;

        $this->ba->appAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($utr, $response['transaction_id']);

        return $response;
    }
}
