<?php

namespace RZP\Tests\Functional\Refund;

use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

// Tests all the helpers on API for refund creation to happen directly on scrooge. Refund entity will not be created on API
class ScroogeRefundCreationTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ScroogeRefundCreationTestData.php';

        parent::setUp();
    }

    public function testRefundsPaymentUpdate()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $dummyRefundId = 'dummyRefundId0';
        $internalPaymentId = substr($payment['id'], 4);

        $this->ba->scroogeAuth();

        $this->testData['callRefundsPaymentUpdate']['request']['content'] = [
            'refunds' => [
                [
                    'id' => $dummyRefundId,
                    'payment_id' => $internalPaymentId,
                    'amount' => '50000',
                    'base_amount' => '50000',
                ]
            ]
        ];

        $response = $this->runRequestResponseFlow($this->testData['callRefundsPaymentUpdate']);

        $this->assertNull($response[$dummyRefundId]['error']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(50000, $payment['amount_refunded']);
        $this->assertEquals(50000, $payment['base_amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals('full', $payment['refund_status']);

        $this->testData['callRefundsPaymentUpdate']['request']['content'] = [
            'refunds' => [
                [
                    'id' => $dummyRefundId,
                    'payment_id' => $internalPaymentId,
                    'amount' => '-10000',
                    'base_amount' => '-10000',
                ]
            ]
        ];

        $response = $this->runRequestResponseFlow($this->testData['callRefundsPaymentUpdate']);

        $this->assertNull($response[$dummyRefundId]['error']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(40000, $payment['amount_refunded']);
        $this->assertEquals(40000, $payment['base_amount_refunded']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);

        $this->testData['callRefundsPaymentUpdate']['request']['content'] = [
            'refunds' => [
                [
                    'id' => $dummyRefundId,
                    'payment_id' => $internalPaymentId,
                    'amount' => '-40000',
                    'base_amount' => '-40000',
                ]
            ]
        ];

        $response = $this->runRequestResponseFlow($this->testData['callRefundsPaymentUpdate']);

        $this->assertNull($response[$dummyRefundId]['error']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(0, $payment['amount_refunded']);
        $this->assertEquals(0, $payment['base_amount_refunded']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['refund_status']);

        $this->testData['callRefundsPaymentUpdate']['request']['content'] = [
        'refunds' => [
            [
                'id' => $dummyRefundId,
                'payment_id' => $internalPaymentId,
                'amount' => '30000',
                'base_amount' => '30000',
            ]
        ]
    ];

        $response = $this->runRequestResponseFlow($this->testData['callRefundsPaymentUpdate']);

        $this->assertNull($response[$dummyRefundId]['error']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(30000, $payment['amount_refunded']);
        $this->assertEquals(30000, $payment['base_amount_refunded']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);

        $response = $this->runRequestResponseFlow($this->testData['callRefundsPaymentUpdate']);

        $this->assertEquals('SERVER_ERROR_LOGICAL_ERROR', $response[$dummyRefundId]['error']['code']);
        $this->assertEquals('Refund amount should be less than or equal to amount not refunded yet', $response[$dummyRefundId]['error']['message']);
    }

    public function testScroogeNormalFullRefundTransactionCreate()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $dummyRefundId = 'dummyRefundId0';
        $internalPaymentId = substr($payment['id'], 4);

        $payment = $this->getDbLastEntity('payment');

        $this->ba->scroogeAuth();

        // full refund
        $this->testData['callScroogeRefundTransactionCreate']['request']['content'] = [
            'id'               => $dummyRefundId,
            'payment_id'       => $internalPaymentId,
            'amount'           => '50000',
            'base_amount'      => '50000',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'normal',
        ];

        $response = $this->runRequestResponseFlow($this->testData['callScroogeRefundTransactionCreate']);

        $this->assertNull($response['error']);

        $this->assertNotNull($response['data']['transaction_id']);
        $this->assertFalse($response['data']['compensate_payment']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(50000, $payment['amount_refunded']);
        $this->assertEquals(50000, $payment['base_amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals('full', $payment['refund_status']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertEquals(50000, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['fee_credits']);
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);
    }

    public function testScroogeNormalFullRefundTransactionCreateForMY()
    {
        $payment = $this->defaultAuthPaymentForMY();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $dummyRefundId = 'dummyRefundId0';
        $internalPaymentId = substr($payment['id'], 4);

        $payment = $this->getDbLastEntity('payment');

        $this->ba->scroogeAuth();

        // full refund
        $this->testData['callScroogeRefundTransactionCreate']['request']['content'] = [
            'id'               => $dummyRefundId,
            'payment_id'       => $internalPaymentId,
            'amount'           => '50000',
            'base_amount'      => '50000',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'normal',
        ];

        $response = $this->runRequestResponseFlow($this->testData['callScroogeRefundTransactionCreate']);

        $this->assertNull($response['error']);

        $this->assertNotNull($response['data']['transaction_id']);
        $this->assertFalse($response['data']['compensate_payment']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(50000, $payment['amount_refunded']);
        $this->assertEquals(50000, $payment['base_amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals('full', $payment['refund_status']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertEquals(50000, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['fee_credits']);
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);
    }

    public function testScroogeNormalPartialRefundTransactionCreate()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $dummyRefundId = 'dummyRefundId0';
        $internalPaymentId = substr($payment['id'], 4);

        $payment = $this->getDbLastEntity('payment');

        $this->ba->scroogeAuth();

        // partial refund
        $this->testData['callScroogeRefundTransactionCreate']['request']['content'] = [
            'id'               => $dummyRefundId,
            'payment_id'       => $internalPaymentId,
            'amount'           => '30000',
            'base_amount'      => '30000',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'normal',
        ];

        $response = $this->runRequestResponseFlow($this->testData['callScroogeRefundTransactionCreate']);

        $this->assertNull($response['error']);

        $this->assertNotNull($response['data']['transaction_id']);
        $this->assertFalse($response['data']['compensate_payment']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(30000, $payment['amount_refunded']);
        $this->assertEquals(30000, $payment['base_amount_refunded']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertEquals(30000, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['fee_credits']);
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);
    }

    public function testScroogeInstantRefundTransactionCreate()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $dummyRefundId = 'dummyRefundId0';
        $internalPaymentId = substr($payment['id'], 4);

        $payment = $this->getDbLastEntity('payment');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->ba->scroogeAuth();

        $this->testData['callScroogeRefundTransactionCreate']['request']['content'] = [
            'id'               => $dummyRefundId,
            'payment_id'       => $internalPaymentId,
            'amount'           => '50000',
            'base_amount'      => '50000',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'optimum',
            'mode'             => 'IMPS',
        ];

        $response = $this->runRequestResponseFlow($this->testData['callScroogeRefundTransactionCreate']);

        $this->assertNull($response['error']);

        $this->assertNotNull($response['data']['transaction_id']);
        $this->assertFalse($response['data']['compensate_payment']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(50000, $payment['amount_refunded']);
        $this->assertEquals(50000, $payment['base_amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals('full', $payment['refund_status']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertEquals(50943, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['fee_credits']);
        $this->assertEquals(943, $transaction['fee']);
        $this->assertEquals(144, $transaction['tax']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(799, $feesBreakup[0]['amount']);
        $this->assertEquals(144, $feesBreakup[1]['amount']);
    }

    public function testScroogePartialInstantRefundOnRefundCreditsTransactionCreate()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $dummyRefundId = 'dummyRefundId0';
        $internalPaymentId = substr($payment['id'], 4);

        $payment = $this->getDbLastEntity('payment');

        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 60000
            ]);

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();

        $this->ba->scroogeAuth();

        $this->testData['callScroogeRefundTransactionCreate']['request']['content'] = [
            'id'               => $dummyRefundId,
            'payment_id'       => $internalPaymentId,
            'amount'           => '20000',
            'base_amount'      => '20000',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'optimum',
            'mode'             => 'IMPS',
        ];

        $response = $this->runRequestResponseFlow($this->testData['callScroogeRefundTransactionCreate']);

        $this->assertNull($response['error']);

        $this->assertNotNull($response['data']['transaction_id']);
        $this->assertFalse($response['data']['compensate_payment']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(20000, $payment['amount_refunded']);
        $this->assertEquals(20000, $payment['base_amount_refunded']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('partial', $payment['refund_status']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(20943, $transaction['fee_credits']);
        $this->assertEquals(943, $transaction['fee']);
        $this->assertEquals(144, $transaction['tax']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(799, $feesBreakup[0]['amount']);
        $this->assertEquals(144, $feesBreakup[1]['amount']);
    }

    public function testScroogeInstantRefundTransactionCreateWithModeLevelPricing()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $dummyRefundId = 'dummyRefundId0';
        $internalPaymentId = substr($payment['id'], 4);

        $payment = $this->getDbLastEntity('payment');

        $this->fixtures->pricing->createInstantRefundsDefaultPricingV2Plan();
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlanV2NEFT();

        $this->ba->scroogeAuth();

        $this->testData['callScroogeRefundTransactionCreate']['request']['content'] = [
            'id'               => $dummyRefundId,
            'payment_id'       => $internalPaymentId,
            'amount'           => '50000',
            'base_amount'      => '50000',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'optimum',
            'mode'             => 'NEFT',
        ];

        $response = $this->runRequestResponseFlow($this->testData['callScroogeRefundTransactionCreate']);

        $this->assertNull($response['error']);

        $this->assertNotNull($response['data']['transaction_id']);
        $this->assertFalse($response['data']['compensate_payment']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(50000, $payment['amount_refunded']);
        $this->assertEquals(50000, $payment['base_amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals('full', $payment['refund_status']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertEquals(50414, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['fee_credits']);
        $this->assertEquals(414, $transaction['fee']);
        $this->assertEquals(64, $transaction['tax']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);

        $feesBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $transaction['id']]);

        $this->assertEquals('refund', $feesBreakup[0]['name']);
        $this->assertEquals('tax', $feesBreakup[1]['name']);
        $this->assertEquals(350, $feesBreakup[0]['amount']);
        $this->assertEquals(64, $feesBreakup[1]['amount']);
    }

    public function testCapturedPaymentScroogeRefundBalanceCheck()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        // set balance to 0
        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 0]);

        $dummyRefundId = 'dummyRefundId0';
        $internalPaymentId = substr($payment['id'], 4);

        $payment = $this->getDbLastEntity('payment');

        $this->ba->scroogeAuth();

        // full refund
        $this->testData['callScroogeRefundTransactionCreate']['request']['content'] = [
            'id'               => $dummyRefundId,
            'payment_id'       => $internalPaymentId,
            'amount'           => '50000',
            'base_amount'      => '50000',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'normal',
        ];

        $response = $this->runRequestResponseFlow($this->testData['callScroogeRefundTransactionCreate']);

        $this->assertEquals('BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE', $response['error']['code']);
        $this->assertEquals('Your account does not have enough balance to carry out the refund operation. You can add funds to your account from your Razorpay dashboard or capture new payments.', $response['error']['message']);
    }

    public function testAuthorisedPaymentScroogeRefundBalanceCheck()
    {
        $payment = $this->defaultAuthPayment();

        // set balance to 0
        $this->fixtures->base->editEntity('balance', '10000000000000', ['balance' => 0]);

        $dummyRefundId = 'dummyRefundId0';
        $internalPaymentId = substr($payment['id'], 4);

        $payment = $this->getDbLastEntity('payment');

        $this->ba->scroogeAuth();

        // full refund
        $this->testData['callScroogeRefundTransactionCreate']['request']['content'] = [
            'id'               => $dummyRefundId,
            'payment_id'       => $internalPaymentId,
            'amount'           => '50000',
            'base_amount'      => '50000',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'normal',
        ];

        $response = $this->runRequestResponseFlow($this->testData['callScroogeRefundTransactionCreate']);

        $this->assertNull($response['error']);

        $this->assertNull($response['data']['transaction_id']);
        $this->assertFalse($response['data']['compensate_payment']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(50000, $payment['amount_refunded']);
        $this->assertEquals(50000, $payment['base_amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals('full', $payment['refund_status']);
    }

    public function testRelationalLoadFromScroogeService()
    {
        $this->enableScroogeRelationalLoadConfig();
        $payment = $this->defaultAuthPayment();
        $capPayment = $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(substr($capPayment['id'],4) ,$payment['id']);

        $response = $this->refundPayment($payment->getPublicId(), $payment['amount'], ['is_fta' => true]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($refund['id'], $response['id']);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);
        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'entity_relational_load_from_scrooge')
                    {
                        return 'on';
                    }
                    return 'off';
                }));

        $this->ba->scroogeAuth();

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(50000, $payment['amount_refunded']);
        $this->assertEquals(50000, $payment['base_amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals('full', $payment['refund_status']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertEquals(50000, $transaction['debit']);
        $this->assertEquals(0, $transaction['credit']);
        $this->assertEquals(0, $transaction['fee_credits']);
        $this->assertEquals(0, $transaction['fee']);
        $this->assertEquals(0, $transaction['tax']);
        $this->assertEquals('10000000000000', $transaction['balance_id']);

        $transactionSource = $transaction->source;

        $this->assertEquals($refund['id'], $transactionSource->getPublicId());

        $refunds = $payment->refunds;

        foreach ($refunds as $refund)
        {
            $this->assertEquals($payment->getId(), $refund->getPaymentId());
        }

        $this->disableScroogeRelationalLoadConfig();
    }

    public function testRefundBackWriteOnApi()
    {
        $payment = $this->defaultAuthPayment();

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $paymentId = substr($payment['id'], 4);

        $refundId = 'JiAaQf2FX6tBYE';

        $this->ba->scroogeAuth();

        // create transaction
        $this->testData['callScroogeRefundTransactionCreate']['request']['content'] = [
            'id'               => $refundId,
            'payment_id'       => $paymentId,
            'amount'           => '100',
            'base_amount'      => '100',
            'gateway'          => $payment['gateway'],
            'speed_decisioned' => 'normal',
        ];

        $response = $this->runRequestResponseFlow($this->testData['callScroogeRefundTransactionCreate']);

        $this->assertNull($response['error']);

        $this->assertNotNull($response['data']['transaction_id']);
        $this->assertFalse($response['data']['compensate_payment']);

        $transactionId = $response['data']['transaction_id'];

        $input = [
            "amount"=> 100,
            "attempts"=> 1,
            "base_amount"=> 100,
            "created_at"=> 1651856887,
            "currency"=> "INR",
            "fee"=> 0,
            "gateway"=> "hdfc",
            "id"=> $refundId,
            "last_attempted_at"=> 1655703199,
            "merchant_id"=> "10000000000000",
            "is_scrooge"=> 1,
            "notes"=> [
                "a"=> 1,
            ],
            "payment_id"=> $paymentId,
            "processed_at"=> 1655703199,
            "reference1"=> "74332742172217179755346",
            "settled_by"=> "Razorpay",
            "speed_processed"=> "normal",
            "speed_decisioned"=> "normal",
            "speed_requested"=> "normal",
            "status"=> "processed",
            "tax"=> 0,
            "transaction_id"=> $transactionId,
            "updated_at"=> 1655878224,
        ];

        $this->testData[__FUNCTION__]['request']['content'] = $input;

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        $this->assertEquals($refundId, $response['id']);
        $this->assertEquals($paymentId, $response['payment_id']);
    }
}
