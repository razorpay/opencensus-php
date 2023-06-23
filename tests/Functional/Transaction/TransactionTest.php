<?php

namespace RZP\Tests\Functional\Transaction;

use Mail;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Mail\Merchant\BalanceThresholdAlert;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\Entity;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Pricing\Calculator\Tax\Base;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class TransactionTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TestsBusinessBanking;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TransactionData.php';

        parent::setUp();

        $this->setAdminForInternalAuth();

        $this->ba->proxyAuth();
    }

    public function testGetAdjustmentWithTransaction()
    {
        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $adj = $this->startTest();

        $txn = $this->getLastTransaction(true);

        return [
                    'adjustment'  => $adj,
                    'transaction' => $txn
                ];
    }

    public function testAddAdjustment()
    {
        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $adj = $this->startTest();

        $testData = $this->testData['testGetAdjustment'];
        $testData['request']['url'] = '/adjustments/'.$adj['id'];

        $this->ba->proxyAuth();

        $adj = $this->runRequestResponseFlow($testData);

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterAddingAdjustment'];
        $testData['entity_id'] = $adj['id'];
        $testData['balance_id'] = '10000000000000';

        $this->assertArraySelectiveEquals($testData, $txn);

        $adjustment = $this->getDbLastEntity('adjustment');
        $this->assertEquals($testData['balance_id'], $adjustment->getBalanceId());

        return $adj;
    }

    public function testFetchTransactionByAdjustmentId()
    {
        $data = $this->testGetAdjustmentWithTransaction();

        $adjustment = $data['adjustment'];
        $txn = $data['transaction'];

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $balanceId = $this->bankingBalance->getId();

        $txn = $this->fixtures->edit('transaction', $adjustment['transaction_id'], ['balance_id' => $balanceId]);


        $this->testData['testFetchTransactionByAdjustmentId']['request']['content'] = [
                                                                                        'account_number' => '2224440041626905',
                                                                                        'adjustment_id'  => $adjustment['id'],
                                                                                    ];

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->ba->proxyAuth();

        $transactions = $this->startTest();

        $this->assertEquals('txn_'.$txn['id'], $transactions['items'][0]['id']);

        $this->assertEquals($transactions['entity'], 'collection');

        $this->assertEquals(1, $transactions['count']);

        $this->assertNotEquals($transactions['items'], null);
    }

    public function testAddNegativeAdjustment()
    {
        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $adj = $this->startTest();

        $txn = $this->getLastTransaction(true);

        $this->assertEquals(abs($adj['amount']), $txn['debit']);
    }

    public function testAddAdjustmentBalanceDoesNotExist()
    {
        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $adj = $this->startTest();

        return $adj;
    }

    public function testAddReverseAdjustment()
    {
        $adj = $this->testAddAdjustment();

        $testData = $this->testData['testAddReverseAdjustment'];
        $testData['request']['content']['ids'] = [
            $adj['id']
        ];

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->setAdminPermission('reverse_bulk_merchant_adjustment');

        $response = $this->runRequestResponseFlow($testData);

        $rev = $this->getLastEntity('adjustment', true);

        $this->assertEquals($rev['amount'] + $adj['amount'], 0);
    }

    public function testTransactionAfterCapturingPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterCapturingPayment'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testTransactionAfterCapturingPaymentMalaysia()
    {
        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'MY', 'convert_currency' => null]);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->iin->edit('401200', [
            'country' => "MY",
            'network' => "Union Pay"
            ]);

        $payment['currency'] = "MYR";

        $this->app['config']->set('applications.pg_router.mock', true);
        $paymentInit = $this->fixtures->create('payment:authorized', [
            'currency' => 'MYR',
            'amount'   => $payment['amount']
        ]);

        $payment['id'] = $paymentInit->getId();

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "MYR", 0, true);

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterCapturingPaymentMalaysia'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testTransactionAfterCapturingPaymentMerchantIndia()
    {
        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'IN']);

        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "INR");

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterCapturingPayment'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testTransactionAfterCapturingPaymentMerchantIndiaDfbPostPaidFlat()
    {
        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'IN']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid','fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $orderEntity = $this->runRequestResponseFlow($this->testData['testTransactionAfterCapturingPaymentMerchantIndiaDfbPostPaidFlat']);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $orderEntity['id'];

        $payment['amount'] = $payment['amount'] + 200;

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "INR");

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterCapturingPaymentDfbPostPaid'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testTransactionAfterCapturingPaymentMerchantIndiaDfbPostPaidPercent()
    {
        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'IN']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid','fee_bearer' => 'dynamic']);

        $this->fixtures->merchant->addFeatures(['customer_fee_dont_settle']);

        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['pricing_plan_id' => $plan->getPlanId()]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();

        $payment['amount'] = '10120';

        $payment['fee'] = 0;

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "INR");

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterCapturingPaymentDfbPostPaidPercent'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    // for vas merchant for direct settlement payment credit and debit both should be zero
    // fee will be non-zero as same needs to be collected by the acquiring bank and not merchant
    public function testTransactionAfterCapturingPaymentForVasMerchant()
    {
        $this->fixtures->merchant->addFeatures(['vas_merchant']);

        $payment = $this->createDirectSettlementPayment();

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['testTransactionAfterCapturingPaymentForVasMerchant'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testFetchPaymentTransaction()
    {
        $payment = $this->doAuthAndCapturePayment();

        $request = [
            'url'     => '/payments/'.$payment['id'].'/transaction',
            'method'  => 'GET',
            'content' => [],
        ];

        $this->ba->privateAuth();

        $txn = $this->makeRequestAndGetContent($request);

        $this->assertEquals($txn['entity_id'], $payment['id']);
        $this->assertEquals($txn['type'], 'payment');
        return $payment;
    }


    /*
     * In this test case fee calculated is as below ->
     *
     * Payment Amount => 500000
     * Pricing plan => default => 2%
     * Fee => 2 % of 500000 => 10000
     * Tax => 18 % of Fee => 18 % of 10000 => 1800
     * MDR => Fee + Tax => 10000 + 1800 => 11800
     */

    public function testFetchPaymentTransactionIndiaMerchant()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['amount'] = 500000;

        $payment = $this->doAuthAndCapturePayment($paymentArray);

        $txn = $this->getLastTransaction(true);

        $feeBreakUp = $this->getLastEntity('fee_breakup', true);

        $this->assertEquals($feeBreakUp["amount"], "1800");

        $this->assertEquals($feeBreakUp["percentage"], "1800");

        $this->assertEquals($payment["id"], $txn["entity_id"]);

        $testData = $this->testData['testFetchPaymentTransactionIndiaMerchant'];

        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testFetchPaymentTransactionMalaysiaMerchant()
    {
        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'MY', 'convert_currency' => null]);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->iin->edit('401200', ['country' => "MY"]);

        $payment['currency'] = "MYR";

        $payment["amount"] = 500000;

        $this->app['config']->set('applications.pg_router.mock', true);
        $paymentInit = $this->fixtures->create('payment:authorized', [
            'currency' => 'MYR',
            'amount'   => $payment['amount']
        ]);

        $payment['id'] = $paymentInit->getId();

        $payment = $this->doAuthAndCapturePayment($payment, $payment['amount'], "MYR", 0, true);

        $txn = $this->getLastTransaction(true);

        $feeBreakUp = $this->getLastEntity('fee_breakup', true);

        $this->assertEquals($payment["id"], $txn["entity_id"]);

        $this->assertEquals($feeBreakUp["percentage"], 0);

        $this->assertEquals($feeBreakUp["amount"], 0);

        $testData = $this->testData['testFetchPaymentTransactionMalaysiaMerchant'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testFetchAuthPaymentTransaction()
    {
        $payment = $this->doAuthPayment();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/'.$payment['razorpay_payment_id'].'/transaction';

        $this->ba->privateAuth();

        $this->startTest($testData);
    }

    public function testTransactionCreateForOldPayment()
    {
        $this->markTestSkipped();

        $payment = $this->fixtures->times(5)->create('payment:authorized',
            ['created_at' => 1467301400]);

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['testTransactionCreateForOldPayment'];
        $testData['fee'] = $txn['fee'];
        $testData['tax'] = $txn['tax'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testTransactionAfterRefund()
    {
        $refund = $this->doAuthCaptureAndRefundPayment();

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterRefundingPayment'];
        $testData['entity_id'] = $refund['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $refund;
    }

    public function testAuthOnlyTransactionAfterRefund()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");

        $payment = $this->doAuthPayment($payment);

        // This time we do not capture the payment and refund it
        $refund = $this->refundAuthorizedPayment($payment['razorpay_payment_id']);

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterRefundingAuthOnlyPayment'];
        $testData['entity_id'] = $refund['id'];

        // Balance is set to Merchant Balance and
        // Debit Amount is set to zero for this scenario.
        // Note: Merchant is not charged any fees in this scenario.
        $this->assertArraySelectiveEquals($testData, $txn);
    }

    public function testCreateDisputeWithDeduct()
    {
        $this->markTestSkipped('Skipped as deduct_at_set is ignored');

        $payment = $this->fixtures->create('payment:captured');

        $dispute = $this->disputePayment($payment, 1);

        $txn = $this->getLastTransaction(true);

        $adjustment = $this->getLastEntity('adjustment');

        $testData = $this->testData['txnDataAfterDisputingPayment'];
        $testData['entity_id'] = $adjustment['id'];
        $testData['balance_id'] = '10000000000000';

        $this->assertArraySelectiveEquals($testData, $txn);

        return $dispute;
    }

    public function testCreateDisputeWithoutDeduct()
    {
        $this->app['config']->set('services.disputes.mock', true);

        $payment = $this->fixtures->create('payment:captured');

        $dispute = $this->disputePayment($payment);

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterDisputingPaymentWithoutDeduct'];
        $testData['balance_id'] = '10000000000000';
        $this->assertArraySelectiveEquals($testData, $txn);

        return $dispute;
    }

    public function testMarkTransactionPostpaid()
    {
        $this->ba->adminAuth();

        $payment = $this->fixtures->create('payment:captured');
        $payment2 = $this->fixtures->create('payment:captured');

        $txn = $this->getEntityById('transaction', $payment->getTransactionId(), true);
        $txn2 = $this->getEntityById('transaction', $payment2->getTransactionId(), true);

        $txnIds = [$payment->getTransactionId(), $payment2->getTransactionId()];

        $transaction = [
            'transaction_ids' => $txnIds,
        ];

        $request = [
            'content' => $transaction,
            'url'     => '/transactions/postpaid',
            'method'  => 'POST',
        ];

        $this->assertEquals('prepaid', $txn['fee_model']);
        $this->assertEquals('prepaid', $txn2['fee_model']);

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($txnIds, $response['success_ids']);
        $this->assertEmpty($response['failed_ids']);

        $txn = $this->getEntityById('transaction', $payment->getTransactionId(), true);
        $txn2 = $this->getEntityById('transaction', $payment2->getTransactionId(), true);

        $this->assertEquals('postpaid', $txn['fee_model']);
        $this->assertEquals('postpaid', $txn2['fee_model']);
    }

    public function testDirectSettlementPartialAmountCredits()
    {
        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 10000,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->merchant->editCredits('10000', '10000000000000');

        $oldBalance = $this->getEntityById('balance', '10000000000000', true);

        $payment = $this->createDirectSettlementPayment();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);
        $this->assertEquals(0, $transaction['credit']);
        // As the fee will charged to the merchant in case of partial amount credit
        $this->assertNotEquals(0, $transaction['debit']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);
        $this->assertEquals('prepaid', $transaction['fee_model']);
        $this->assertEquals('default', $transaction['credit_type']);
        $this->assertEquals($oldBalance['balance'] - $payment['fee'], $balance['balance']);
    }

    public function testDirectSettlementAmountCredits()
    {
        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 50000,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->merchant->editCredits('50000', '10000000000000');

        $oldBalance = $this->getEntityById('balance', '10000000000000', true);

        $payment = $this->createDirectSettlementPayment();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);
        $this->assertEquals('prepaid', $transaction['fee_model']);
        $this->assertEquals('amount', $transaction['credit_type']);

        $this->assertEquals($oldBalance['credits'] - $transaction['amount'], $balance['credits']);
    }

    public function testDirectSettlementFeeCredits()
    {
         $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $oldBalance = $this->getEntityById('balance', '10000000000000', true);

        $payment = $this->createDirectSettlementPayment();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);
        $this->assertEquals('prepaid', $transaction['fee_model']);
        $this->assertEquals('fee', $transaction['credit_type']);
        $this->assertEquals($payment['fee'], $transaction['fee_credits']);
        $this->assertEquals($oldBalance['fee_credits'] - $payment['fee'], $balance['fee_credits']);
    }

    public function testDirectSettlementMerchnatBalance()
    {
         $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 0,
            'merchant_id' => '10000000000000',
        ]);

        $oldBalance = $this->getEntityById('balance', '10000000000000', true);

        $payment = $this->createDirectSettlementPayment();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals($payment['fee'], $transaction['debit']);
        $this->assertEquals('prepaid', $transaction['fee_model']);
        $this->assertEquals('default', $transaction['credit_type']);
        $this->assertEquals($oldBalance['balance'] - $payment['fee'], $transaction['balance']);
        $this->assertEquals($oldBalance['balance'] - $payment['fee'], $balance['balance']);
    }

    public function testDirectSettlementMerchnatBalanceAuthorizedPayment()
    {
         $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 0,
            'merchant_id' => '10000000000000',
        ]);

        $payment = $this->createDirectSettlementPayment();

        $oldBalance = $this->getEntityById('balance', '10000000000000', true);

        $this->fixtures->base->editEntity('payment', $payment['id'], ['status' => 'authorized', 'captured_at' => null]);

        $authPayment = $this->getLastEntity('payment', true);

        $this->assertEquals($authPayment['id'], $payment['id']);

        $this->assertEquals('authorized', $authPayment['status']);

        $this->refundAuthorizedPayment($authPayment['id']);

        $refundPayment = $this->getLastEntity('payment', true);

        $this->assertEquals($refundPayment['id'], $authPayment['id']);

        $this->assertEquals('refunded', $refundPayment['status']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($refundPayment['amount'], $refund['amount']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($transaction['debit'], $refund['amount']);

        $this->assertEquals(0, $transaction['fee']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals($oldBalance['balance'] - $refund['amount'], $balance['balance']);
    }

    public function testDirectSettlementNoMerchnatBalance()
    {
        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 0,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->merchant->editBalance('100', '10000000000000');

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertTrue($balance['balance'] === 100);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->makeRequestAndCatchException(function ()
        {
            $this->createDirectSettlementPayment();
        },BadRequestException::class, 'Payment failed');

        $payment = $this->getLastEntity('payment', true);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);

        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);
        $this->assertEquals('authorized', $payment['status']);
    }

    public function testDirectSettlementZeroMerchnatBalance()
    {
         $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 0,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->merchant->editBalance(0, '10000000000000');

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertTrue($balance['balance'] === 0);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                           ->willReturn('on');

        $this->makeRequestAndCatchException(function ()
        {
            $this->createDirectSettlementPayment();
        },BadRequestException::class, 'Payment failed');

        $payment = $this->getLastEntity('payment', true);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);

        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);
        $this->assertEquals('authorized', $payment['status']);
    }

    public function testDirectSettlementNegativeMerchnatBalance()
    {
        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 0,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->merchant->editBalance(-1000, '10000000000000');

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertTrue($balance['balance'] === -1000);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->makeRequestAndCatchException(function ()
        {
            $this->createDirectSettlementPayment();
        },BadRequestException::class, 'Payment failed');

        $payment = $this->getLastEntity('payment', true);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);

        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);
        $this->assertEquals('authorized', $payment['status']);
    }

    public function testDirectSettlementPostPaid()
    {
        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $payment = $this->createDirectSettlementPayment();

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals('postpaid', $transaction['fee_model']);
        $this->assertEquals('default', $transaction['credit_type']);
        $this->assertEquals(0, $transaction['fee_credits']);
        $this->assertEquals($payment['fee'], $transaction['fee']);
    }

    public function testTransactionsBulkUpdateBalanceId()
    {
        $this->markTestSkipped('The flakiness in the testcase needs to be fixed. Skipping as its impacting dev-productivity.');

        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->assertEmpty($response);

        $this->createMultipleTransactions();

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content']['totalUpdatedRowCounts'] = 5;

        $this->ba->adminAuth();

        $this->startTest($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content'] = [];

        $response = $this->startTest($testData);

        $this->assertEmpty($response);

        $this->createMultipleTransactions();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['merchantIds'] = ['10000000000000'];
        $testData['response']['content']['totalUpdatedRowCounts'] = 5;

        $this->ba->adminAuth();

        $this->startTest($testData);

        $this->createMultipleTransactions();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_limit'] = 0;
        $testData['response']['content'] = [];

        $this->ba->adminAuth();

        $response = $this->startTest($testData);

        $this->assertEmpty($response);
    }

    public function testTransactionsBulkUpdateBalanceIdLimitTest()
    {
        $this->createMultipleTransactions();

        $testData = $this->testData['testTransactionsBulkUpdateBalanceId'];

        $testData['request']['content']['limit'] = 1;
        $testData['response']['content']['totalUpdatedRowCounts'] = 1;

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function testReconcilePaymentTransaction()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthPayment($payment);
        $refund = $this->refundAuthorizedPayment($payment['razorpay_payment_id']);
        $this->assertEquals($refund['payment_id'], $payment['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('refunded', $payment->getStatus());

        [$txn, $feeSplit] = $this->fixtures->payment->createTxnForAuthPayment($payment);

        $this->assertEquals($payment->getId(), $txn->getEntityId());
        $this->assertEquals(0, $txn->getFee());
    }

    public function testHandleAsyncMerchantBalanceUpdate()
    {
        $this->mockRazorx();

        $this->fixtures->merchant->addFeatures(['async_balance_update']);
        $payment = $this->getDefaultPaymentArray();

        $oldBalance = $this->getEntityById('balance', '10000000000000', true);

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($payment['id'], $transaction['entity_id']);
        $this->assertTrue($transaction['balance_updated']);
        $this->assertEquals($oldBalance['balance']+ ($payment['amount'] - $payment['fee']), $balance['balance']);
    }

    public function testHandleAsyncTxnFillDetailsMerchantBalanceUpdate()
    {
        $this->mockRazorx();

        $this->fixtures->merchant->addFeatures(['async_txn_fill_details']);
        $payment = $this->getDefaultPaymentArray();

        $oldBalance = $this->getEntityById('balance', '10000000000000', true);

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($payment['id'], $transaction['entity_id']);
        $this->assertTrue($transaction['balance_updated']);
        $this->assertEquals($oldBalance['balance']+ ($payment['amount'] - $payment['fee']), $balance['balance']);
    }

    public function testHandleAsyncMerchantBalanceUpdateWithAuthPayment()
    {
        $this->mockRazorx();

        $this->fixtures->merchant->addFeatures(['async_balance_update']);
        $payment = $this->getDefaultPaymentArray();

        $oldBalance = $this->getEntityById('balance', '10000000000000', true);

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertNotEquals($oldBalance['balance']+ ($payment['amount'] - $payment['fee']), $balance['balance']);
    }

    public function testTransactionHold()
    {
        $transaction = $this->fixtures->create('transaction', ['merchant_id' => '10000000000000']);

        $request = [
            'content' => [
                'transaction_ids' => [$transaction->getId()],
                'reason'          => 'Faulty Transaction',
            ],
            'url' => '/transactions/hold',
            'method' => 'PATCH',
        ];

        $this->ba->adminAuth();

        $result = $this->makeRequestAndGetContent($request);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertTrue($txn['on_hold']);

        $this->assertEquals(1, $result['total_requests']);

        $this->assertEquals(1, $result['successfully_updated']);

        $this->assertEquals(0, $result['failed']);

    }

    public function testTransactionRelease()
    {
        $transaction = $this->fixtures->create('transaction', ['merchant_id' => '10000000000000']);

        $request = [
            'content' => [
                'transaction_ids' => [$transaction->getId()],
                'reason'          => 'Faulty Transaction',
            ],
            'url' => '/transactions/hold',
            'method' => 'PATCH',
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertTrue($txn['on_hold']);

        $request2 = [
            'content' => [
                'transaction_ids' => [$transaction->getId()],
            ],
            'url' => '/transactions/release',
            'method' => 'PATCH',
        ];

        $this->ba->adminAuth();

        $result = $this->makeRequestAndGetContent($request2);

        $txn2 = $this->getLastEntity('transaction', true);

        $this->assertFalse($txn2['on_hold']);

        $this->assertEquals(1, $result['total_requests']);

        $this->assertEquals(1, $result['successfully_updated']);

        $this->assertEquals(0, $result['failed']);
    }

    public function testCreateCreditRepayment()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $response = $this->startTest();

        // make 2nd request with same input.
        $response2 = $this->startTest();

        // response of previous api request & 2nd should match. (id, created_at etc)
        $this->assertArraySelectiveEquals($response, $response2);

        $this->assertNotNull($response['id']);
        $this->assertNotNull($response['created_at']);
        $this->assertNotNull($response['settled_at']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals([
            'entity_id'         => 'repay_G1SRTbSC6fQOHo',
            'type'              => 'credit_repayment',
            'merchant_id'       => '10000000000000',
            'amount'            => 10000,
            'fee'               => 0,
            'mdr'               => 0,
            'tax'               => 0,
            'debit'             => 10000,
            'credit'            => 0,
            'currency'          => 'INR',
            'balance'           => 990000,
            'channel'           => 'axis',
            'fee_bearer'        => 'platform',
            'fee_model'         => "prepaid",
            'credit_type'       => "default",
            'on_hold'           => false,
            'settled'           => false,
            'settlement_id'     => null,
            'reconciled_type'   => 'na',
            'balance_id'        => '10000000000000',
            'balance_updated'   => null,
            'entity'            => 'transaction',
        ], $txn);
    }

    public function testCreateCreditRepaymentWithLowBalance()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $this->startTest();
    }

    public function testCreateCapitalBalanceTransactionNegativeAmount()
    {
        $balance = $this->fixtures->create('balance', [
            Entity::MERCHANT_ID => '10000000000000',
            Entity::TYPE        => Type::PRINCIPAL,
            Entity::BALANCE     => 100000,
        ]);

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance['id'];

        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $response = $this->startTest();

        // make 2nd request with same input.
        $response2 = $this->startTest();

        // response of previous api request & 2nd should match. (id, created_at etc)
        $this->assertArraySelectiveEquals($response, $response2);

        $this->assertNotNull($response['id']);
        $this->assertNotNull($response['created_at']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals([
            'entity_id'         => 'G1SRTbSC6fQOHo',
            'type'              => 'repayment_breakup',
            'merchant_id'       => '10000000000000',
            'amount'            => 1000,
            'fee'               => 0,
            'mdr'               => 0,
            'tax'               => 0,
            'debit'             => 1000,
            'credit'            => 0,
            'currency'          => 'INR',
            'balance'           => 99000,
            'channel'           => 'axis',
            'fee_bearer'        => 'na',
            'fee_model'         => 'na',
            'credit_type'       => 'default',
            'on_hold'           => false,
            'settled'           => false,
            'settlement_id'     => null,
            'reconciled_type'   => 'na',
            'balance_id'        => $balance['id'],
            'balance_updated'   => null,
            'entity'            => 'transaction',
        ], $txn);
    }

    public function testCreateCapitalBalanceTransactionNegativeAmountWithNegativeBalance()
    {
        $balance = $this->fixtures->create('balance', [
            Entity::MERCHANT_ID => '10000000000000',
            Entity::TYPE        => Type::PRINCIPAL,
            Entity::BALANCE     => 100000,
        ]);

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance['id'];

        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $response = $this->startTest();

        // make 2nd request with same input.
        $response2 = $this->startTest();

        // response of previous api request & 2nd should match. (id, created_at etc)
        $this->assertArraySelectiveEquals($response, $response2);

        $this->assertNotNull($response['id']);
        $this->assertNotNull($response['created_at']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals([
            'entity_id'         => 'G1SRTbSC6fQOHo',
            'type'              => 'repayment_breakup',
            'merchant_id'       => '10000000000000',
            'amount'            => 250000,
            'fee'               => 0,
            'mdr'               => 0,
            'tax'               => 0,
            'debit'             => 250000,
            'credit'            => 0,
            'currency'          => 'INR',
            'balance'           => -150000,
            'channel'           => 'axis',
            'fee_bearer'        => 'na',
            'fee_model'         => 'na',
            'credit_type'       => 'default',
            'on_hold'           => false,
            'settled'           => false,
            'settlement_id'     => null,
            'reconciled_type'   => 'na',
            'balance_id'        => $balance['id'],
            'balance_updated'   => null,
            'entity'            => 'transaction',
        ], $txn);
    }

    public function testCreateCapitalBalanceTransactionNegativeAmountWithNegativeBalanceOnInterest()
    {
        $balance = $this->fixtures->create('balance', [
            Entity::MERCHANT_ID => '10000000000000',
            Entity::TYPE        => Type::INTEREST,
            Entity::BALANCE     => 100000,
        ]);

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance['id'];

        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $this->startTest();
    }

    public function testCreateCapitalBalanceTransactionPositiveAmount()
    {
        $balance = $this->fixtures->create('balance', [
            Entity::MERCHANT_ID => '10000000000000',
            Entity::TYPE        => Type::PRINCIPAL,
            Entity::BALANCE     => 100000,
        ]);

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance['id'];

        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $response = $this->startTest();

        // make 2nd request with same input.
        $response2 = $this->startTest();

        // response of previous api request & 2nd should match. (id, created_at etc)
        $this->assertArraySelectiveEquals($response, $response2);

        $this->assertNotNull($response['id']);
        $this->assertNotNull($response['created_at']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertArraySelectiveEquals([
            'entity_id'         => 'G1SRTbSC6fQOHo',
            'type'              => 'repayment_breakup',
            'merchant_id'       => '10000000000000',
            'amount'            => 1000,
            'fee'               => 0,
            'mdr'               => 0,
            'tax'               => 0,
            'debit'             => 0,
            'credit'            => 1000,
            'currency'          => 'INR',
            'balance'           => 101000,
            'channel'           => 'axis',
            'fee_bearer'        => 'na',
            'fee_model'         => 'na',
            'credit_type'       => 'default',
            'on_hold'           => false,
            'settled'           => false,
            'settlement_id'     => null,
            'reconciled_type'   => 'na',
            'balance_id'        => $balance['id'],
            'balance_updated'   => null,
            'entity'            => 'transaction',
        ], $txn);
    }

    public function testCreateMultipleCapitalBalanceTransactionPositiveAmountWithoutPrimaryBalance()
    {
        $this->getEntityObjectForMode('balance')->where([
                Entity::MERCHANT_ID => '10000000000000',
                Entity::TYPE        => Type::PRIMARY,
            ])->delete();

        $this->testCreateMultipleCapitalBalanceTransactionPositiveAmount();
    }

    public function testCreateMultipleCapitalBalanceTransactionPositiveAmount()
    {
        $principalBal = $this->fixtures->create('balance', [
            Entity::MERCHANT_ID => '10000000000000',
            Entity::TYPE        => Type::PRINCIPAL,
            Entity::BALANCE     => 100000,
        ]);

        $interestBal = $this->fixtures->create('balance', [
            Entity::MERCHANT_ID => '10000000000000',
            Entity::TYPE        => Type::INTEREST,
            Entity::BALANCE     => 500,
        ]);

        $this->testData[__FUNCTION__]['request']['content']['repayment_breakups'][0]['balance_id'] = $principalBal['id'];
        $this->testData[__FUNCTION__]['request']['content']['repayment_breakups'][1]['balance_id'] = $interestBal['id'];

        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $response = $this->startTest();

        // make 2nd request with same input.
        $response2 = $this->startTest();

        $this->assertArraySelectiveEquals($response, $response2);

        /** @var PublicCollection $entities */
        $entities = $this->getDbEntities('transaction');

        $items = $entities->toArrayAdmin()['items'];

        $this->assertCount(2, $items);

        $this->assertArraySelectiveEquals([
            [
                'entity_id'         => 'G1SRTbSC6fQOHo',
                'type'              => 'repayment_breakup',
                'merchant_id'       => '10000000000000',
                'amount'            => 1000,
                'fee'               => 0,
                'mdr'               => 0,
                'tax'               => 0,
                'debit'             => 1000,
                'credit'            => 0,
                'currency'          => 'INR',
                'balance'           => 99000,
                'channel'           => 'axis',
                'fee_bearer'        => 'na',
                'fee_model'         => 'na',
                'credit_type'       => 'default',
                'on_hold'           => false,
                'settled'           => false,
                'settlement_id'     => null,
                'reconciled_type'   => 'na',
                'balance_id'        => $principalBal['id'],
                'balance_updated'   => null,
                'entity'            => 'transaction',
            ],
            [
                'entity_id'         => 'G1SRTbSC6fQOHp',
                'type'              => 'repayment_breakup',
                'merchant_id'       => '10000000000000',
                'amount'            => 9,
                'fee'               => 0,
                'mdr'               => 0,
                'tax'               => 0,
                'debit'             => 9,
                'credit'            => 0,
                'currency'          => 'INR',
                'balance'           => 491,
                'channel'           => 'axis',
                'fee_bearer'        => 'na',
                'fee_model'         => 'na',
                'credit_type'       => 'default',
                'on_hold'           => false,
                'settled'           => false,
                'settlement_id'     => null,
                'reconciled_type'   => 'na',
                'balance_id'        => $interestBal['id'],
                'balance_updated'   => null,
                'entity'            => 'transaction',
            ],
        ], $items);
    }

    protected function createMultipleTransactions()
    {
        $payments = $this->fixtures->times(5)->create(
                'payment:captured',
                [
                    'merchant_id' => '10000000000000',
                    'amount' => '10000',
                ]
            );

        foreach ($payments as $payment)
        {
            $txn = $payment->transaction;
            $this->fixtures->edit('transaction', $txn->getId(), ['balance_id' => null]);
        }
    }

    protected function createDirectSettlementPayment()
    {
        $this->fixtures->create('terminal:direct_settlement_hdfc_terminal');
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");

        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);

        return $payment;
    }

    protected function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }

    private function mockRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');
    }

    public function testPaymentCaptureTransactionsCreateInternal()
    {
        $this->ba->appAuth();

        $terminal = $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['payment']['terminal_id'] = $terminal['id'];

        $this->startTest();
    }

    protected function setAdminPermission($permissionName)
    {
        $admin = $this->ba->getAdmin();

        $roleOfAdmin = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => $permissionName]);

        $roleOfAdmin->permissions()->attach($perm->getId());
    }

    public function testBalanceThresholdAlerts()
    {
        Mail::fake();

        $this->fixtures->merchant->editBalance('50000', '10000000000000');

        $this->fixtures->merchant->editBalanceThreshold('100000', '10000000000000');

        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment1 = $this->getDefaultNetbankingPaymentArray("HDFC");

        $payment1 = $this->doAuthAndCapturePayment($payment1);

        $payment2 = $this->getDefaultNetbankingPaymentArray("HDFC");

        $payment2 = $this->doAuthAndCapturePayment($payment2);

        $this->refundPayment($payment1['id']);

        //Ist Alert
        Mail::assertQueued(BalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals(['test@razorpay.com'], $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals('dashboard.razorpay.in', $viewData['org_hostname']);

            $this->assertEquals('emails.merchant.balance_threshold_alert', $mail->view);

            return true;
        });

        $this->refundPayment($payment2['id']);

        //IInd Alert
        Mail::assertQueued(BalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;
            $this->assertEquals(['test@razorpay.com'], $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals('emails.merchant.balance_threshold_alert', $mail->view);

            return true;
        });
    }

    public function testBalanceThresholdAlertNotFiredCases()
    {
        Mail::fake();

        $this->fixtures->merchant->editBalance('50000', '10000000000000');

        $this->fixtures->merchant->editBalanceThreshold('40000', '10000000000000');

        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment1 = $this->getDefaultNetbankingPaymentArray("HDFC");

        $payment1 = $this->doAuthAndCapturePayment($payment1);

        $this->refundPayment($payment1['id']);

        Mail::assertNotQueued(BalanceThresholdAlert::class);
    }

    public function testGetTaxCalculatorForEntityAsNull()
    {
        try{
            $response = Base::getTaxCalculator(null, 1000);
        }
        catch (\Throwable $e)
        {
            // error code 0 is used when exception is raised without any error code
            // In our case it is raised because $entity as first parameter is passed null here : RZP\Models\Pricing\Calculator\Tax\Base::__construct()
            $this->assertEquals($e->getCode(), 0);
        }
    }

    public function testGetTaxCalculatorForEntityWithoutMerchant()
    {
        $merchant = Merchant\Entity::find('10000000000000');

        $payment = $this->createPaymentEntity($merchant);

        $payment->merchant = null;

        $response = Base::getTaxCalculator($payment, 1000);

        $this->assertEquals(get_class($response), "RZP\Models\Pricing\Calculator\Tax\IN\Calculator");
    }

    // helper methods
    protected function createPaymentEntity(Merchant\Entity $merchant): Payment\Entity
    {
        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';

        $payment = (new Payment\Entity)->fill($paymentArray);

        if (isset($paymentArray['international']) === true) {
            $payment->setAttribute(Payment\Entity::INTERNATIONAL, $paymentArray['international']);
        }

        $payment->merchant()->associate($merchant);

        return $payment;
    }
}
