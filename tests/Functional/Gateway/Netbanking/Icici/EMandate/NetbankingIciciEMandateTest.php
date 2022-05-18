<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Icici\EMandate;

use Carbon\Carbon;
use RZP\Constants\Entity;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Order\Entity as Order;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Customer\Token\Entity as Token;
use RZP\Models\Customer\Token\RecurringStatus;
use RZP\Models\Payment\Method as PaymentMethod;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Customer\GatewayToken\Entity as GatewayToken;

class NetbankingIciciEMandateTest extends TestCase
{
    use PaymentTrait;

    protected $gateway;

    protected $fixtures;

    protected $payment;

    const ACCOUNT_NUMBER    = '914010009305862';
    const IFSC              = 'ICIC0002766';
    const NAME              = 'Test account';

    // TODO: Test global customer / token flow

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIciciEMandateTestData.php';

        parent::setUp();

        $this->gateway = Gateway::NETBANKING_ICICI;

        $this->fixtures->create('terminal:shared_emandate_icici_terminal');
        $this->fixtures->create('terminal:shared_emandate_axis_terminal');

        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->fixtures->merchant->enableEmandate();

        $this->payment = $this->getEmandateNetbankingRecurringPaymentArray(IFSC::ICIC);

        $this->payment['bank_account'] = [
            'account_number'    => self::ACCOUNT_NUMBER,
            'ifsc'              => self::IFSC,
            'name'              => self::NAME,
            'account_type'      => 'savings',
        ];

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');

        $this->mockCardVault();
    }

    public function testEMandateInitialPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();
    }

    public function testEMandateInitialPaymentTamperedPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'auth')
            {
                $content['PAID'] = 'N';
                $content['AMT'] = '2000';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testEMandateInitialPaymentLateAuth()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'auth')
            {
                throw new \RZP\Exception\GatewayTimeoutException('Gateway timed out');
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->authorizedFailedPayment($payment['id']);

        $token = $this->getLastEntity(Entity::TOKEN, true);

        $this->assertArraySelectiveEquals(
            [
                Token::RECURRING_STATUS => RecurringStatus::CONFIRMED,
                Token::METHOD           => 'emandate',
                Token::BANK             => 'ICIC',
                Token::GATEWAY_TOKEN    => '123123123',
            ],
            $token
        );
    }

    public function testEMandateScheduledPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(1180, $paymentEntity[Payment::FEE]);
        $this->assertEquals(180, $paymentEntity[Payment::TAX]);

        $payment[Payment::TOKEN] = $paymentEntity[Payment::TOKEN_ID];
        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        //
        // Second auth payment for the recurring product
        //
        $this->doS2SRecurringPayment($payment);

        $this->assertEMandateEntities(false);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(2360, $payment[Payment::FEE]);
        $this->assertEquals(360, $payment[Payment::TAX]);
    }

    public function testEMandateScheduledPaymentWithoutMethod()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $payment[Payment::TOKEN] = $paymentEntity[Payment::TOKEN_ID];
        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        unset($payment[Payment::METHOD]);
        unset($payment[Payment::AUTH_TYPE]);
        unset($payment['bank_account']);

        //
        // Second auth payment for the recurring product
        //
        $this->doS2SRecurringPayment($payment);

        $this->assertEMandateEntities(false);
    }

    public function testEMandateScheduledPaymentFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order',
                                         [
                                             'amount' => $payment['amount'],
                                             'method' => 'emandate',
                                             'payment_capture' => true
                                         ]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        // Assert that this is a normal SI initial payment request
        $this->assertEMandateEntities();

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $payment[Payment::TOKEN] = $paymentEntity[Payment::TOKEN_ID];
        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockScheduledPaymentFailure();

        $data = $this->testData[__FUNCTION__];

        //
        // Second auth payment for the recurring product
        //
        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });

        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals('failed', $payment[Payment::STATUS]);

        // For failed payments, si requests and debit requests, the status is a N
        $this->assertEquals(true, $netbanking[Netbanking::RECEIVED]);
        $this->assertEquals('N', $netbanking[Netbanking::STATUS]);
        $this->assertEquals(null, $netbanking[Netbanking::BANK_PAYMENT_ID]);

        $this->assertEquals(1, $token[Token::USED_COUNT]);
        $this->assertEquals(true, $token[Token::RECURRING]);
        $this->assertEquals(RecurringStatus::CONFIRMED, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);
    }

    /**
     * This is the case that the SI registration step failed on the bank's end
     */
    public function testEMandateSiRejected()
    {
        $this->mockRejectedToken();

        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $this->assertEMandateRejectedToken();
    }

    public function testScheduledPaymentWithRejectedToken()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockRejectedToken();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $netbanking1 = $this->getLastEntity(Entity::NETBANKING, true);
        $token1 = $this->getLastEntity(Entity::TOKEN, true);

        $payment[Payment::TOKEN] = $paymentEntity[Payment::TOKEN_ID];
        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockRejectedToken(false);

        //
        // Second auth payment for the recurring product.
        // Since an invalid recurring token is used here, the
        // payment will go through like a regular non-recurring payment.
        // This is because it is not a "Second Recurring" payment
        //
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });

        $netbanking2 = $this->getLastEntity(Entity::NETBANKING, true);
        $token2 = $this->getLastEntity(Entity::TOKEN, true);

        // If a rejected token is used to initiate a recurring payment, the payment will fail before
        // hitting the gateway, as we throw an exception in Authorize.php (validateSecondRecurringNetbanking)
        // Therefore, below we assert that no new netbanking payment was created
        $this->assertEquals($netbanking1[Netbanking::PAYMENT_ID], $netbanking2[Netbanking::PAYMENT_ID]);

        // We assert that the new payment was initiated using the saved token
        $this->assertEquals($token1[Token::ID], $token2[Token::ID]);
    }

    /**
     * When the SI registration status is not set,
     * we throw an exception. Therefore, the token is not saved with the
     * recurring fields, and no gateway token is created
     */
    public function testSiRecurringStatusNotSet()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockSiRecurringStatusNotSet();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $this-> assertEMandateStrangeStatus();
    }

    /**
     * This is the case where the SI registration case was successful, but
     * we were unable to receive a gateway token in the response.
     * Therefore, when the merchant initiates a recurring request,
     * the payment will fail before even reaching the gateway in
     * Authorize.php (validateSecondRecurringNetbanking)
     */
    public function testAuthSecondRecurringNullGatewayToken()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockSiRecurringGatewayTokenNotSet();

        $this->doAuthPayment($payment);

        $firstPayment = $this->getLastEntity('payment', true);

        $payment[Payment::TOKEN] = $firstPayment['token_id'];
        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });

        $this->assertSiNullGatewayToken();
    }

    /**
     * This test case tests the netbanking first recurring flow.
     * In netbanking recurring payments, we create a new token
     * for each and every new first recurring payment.
     * We also create a new gateway token for each of them as well,
     * even if the customer, merchant and terminal are all the same
     */
    public function testTwoEMandateRegistrationPayments()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        // First E Mandate registration payment
        $this->doAuthPayment($payment);

        $token1 = $this->getLastEntity(Entity::TOKEN, true);
        $gatewayToken1 = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        // Second E Mandate registration payment
        $this->doAuthPayment($payment);

        $token2 = $this->getLastEntity(Entity::TOKEN, true);
        $gatewayToken2 = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        // Assert that both the tokens are different
        // Also assert their gateway_tokens are different
        $this->assertNotEquals($token1[Token::ID], $token2[Token::ID]);
        $this->assertNotEquals($token1[Token::GATEWAY_TOKEN], $token2[Token::GATEWAY_TOKEN]);

        // Assert that the customer, merchant and terminal are the same
        $this->assertEquals($token1[Token::CUSTOMER_ID], $token2[Token::CUSTOMER_ID]);
        $this->assertEquals($token1[Token::MERCHANT_ID], $token2[Token::MERCHANT_ID]);
        $this->assertEquals($token1[Token::TERMINAL_ID], $token2[Token::TERMINAL_ID]);

        // Assert that both the gateway tokens are different
        // Also assert that their token id's are different
        $this->assertNotEquals($gatewayToken1[GatewayToken::ID], $gatewayToken2[GatewayToken::ID]);
        $this->assertNotEquals($gatewayToken1[GatewayToken::TOKEN_ID], $gatewayToken2[GatewayToken::TOKEN_ID]);

        // Assert that the merchant and terminal are the same
        $this->assertEquals($gatewayToken1[GatewayToken::MERCHANT_ID], $gatewayToken2[GatewayToken::MERCHANT_ID]);
        $this->assertEquals($gatewayToken1[GatewayToken::TERMINAL_ID], $gatewayToken2[GatewayToken::TERMINAL_ID]);
    }

    /**
     * This is the case that a debit request was made with a valid recurring token,
     * but failed on the banks end - this results in an exception
     */
    public function testDebitRequestFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $token1 = $this->getLastEntity(Entity::TOKEN, true);

        $gatewayToken1 = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        $data = $this->testData[__FUNCTION__];

        $this->mockDebitRequestFailure();

        $payment[Payment::TOKEN] = $token1[TOKEN::ID];
        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });

        $this->assertDebitRequestFailure($token1, $gatewayToken1);
    }

    public function testPaymentVerify()
    {
        //
        // temp fix: failed recurring payments are getting marked as success on verify on ICICI's end
        //
        $this->markTestSkipped();

        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        // We successfully created a token that can be used for recurring
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $this->assertEquals(true, $token[Token::RECURRING]);
        $this->assertEquals(RecurringStatus::CONFIRMED, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);

        // We are verifying a payment made with a valid recurring token
        $verify = $this->verifyPayment($payment[Payment::ID]);

        // When the payment is a recurring payment, then we send RID in the verify request
        $this->assertNotNull($verify['gateway']['verifyResponseContent']['RID']);

        // We assert that the RID used for verification is the same as SI Ref ID saved during initial payment
        $this->assertEquals($verify['gateway']['verifyResponseContent']['RID'], $netbanking[Netbanking::SI_TOKEN]);
    }

    public function testPaymentVerifyFailed()
    {
        //
        // temp fix: failed recurring payments are getting marked as success on verify on ICICI's end
        //
        $this->markTestSkipped();

        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->mockVerifyFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment::ID]);
            });
    }

    /**
     * This test is to check if the default error code is picked when a un-documented error is returned by the bank
     */
    public function testUnknownReasonSecondRecurringFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $payment[Payment::TOKEN] = $paymentEntity[Payment::TOKEN_ID];
        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockScheduledPaymentFailure('Random Error');

        $data = $this->testData[__FUNCTION__];

        //
        // Second auth payment for the recurring product
        //
        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });

        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);

        // For failed payments, si requests and debit requests, the status is a N
        $this->assertEquals('N', $netbanking[Netbanking::STATUS]);
    }

    /**
     * This test is to check if the right error is thrown when a null
     * response is returned by the second recurring call
     */
    public function testNullSecondRecurringResponse()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $payment[Payment::TOKEN] = $paymentEntity[Payment::TOKEN_ID];
        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockEmptySecondRecurringResponse();

        $data = $this->testData[__FUNCTION__];

        //
        // Second auth payment for the recurring product
        //
        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });
    }

    /**
     * This test is to ensure that if a token is used for a first recurring
     * payment, the validate netbanking recurring method should throw an error
     */
    public function testTokenPassedInFirstRecurringPayment()
    {
        $timestamp = Carbon::now()->timestamp;

        $token = $this->fixtures->create(
            Entity::TOKEN,
            [
                Token::USED_AT => $timestamp,
                Token::RECURRING => true,
                Token::USED_COUNT => 1
            ]);

        $payment = $this->payment;
        $payment[Payment::TOKEN] = 'token_' . $token[Token::ID];
        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $data = $this->testData[__FUNCTION__];

        //
        // Second auth payment for the recurring product
        //
        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    public function testDirectDebitFlowSuccess()
    {
        $orderInput = [
            Order::AMOUNT          => 4000,
            Order::METHOD          => Method::EMANDATE,
            Order::PAYMENT_CAPTURE => true,
        ];

        $order = $this->createOrder($orderInput);

        $paymentInput = $this->getEmandateNetbankingRecurringPaymentArray(IFSC::ICIC);

        $paymentInput[Payment::AMOUNT] = $orderInput[Order::AMOUNT];

        $paymentInput[Payment::BANK_ACCOUNT] = $this->payment[Payment::BANK_ACCOUNT];

        $paymentInput[Payment::ORDER_ID] = $order[Order::ID];

        $this->doAuthPayment($paymentInput);

        $this->assertEMandateEntities();
    }

    protected function assertSiNullGatewayToken()
    {
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);

        $this->assertEquals($payment[Payment::TOKEN_ID], $token[Token::ID]);

        // gateway token is set to null
        $this->assertEquals(null, $token[Token::GATEWAY_TOKEN]);

        // We are making a SI debit request with a valid token, but without a gateway token
        $this->assertEquals(true, $token[Token::RECURRING]);
        $this->assertEquals(RecurringStatus::CONFIRMED, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);

        // Registration succeeded
        $this->assertNotNull($netbanking[Netbanking::SI_TOKEN]);
        $this->assertEquals('Y', $netbanking[Netbanking::SI_STATUS]);
        $this->assertEquals(null, $netbanking[Netbanking::BANK_PAYMENT_ID]);
    }

    protected function mockScheduledPaymentFailure($status = 'PaymentDateOverdue')
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($status)
            {
                if ($action === 'second_recurring')
                {
                    $content['PAID'] = 'N';
                    $content['STATUS'] = $status;
                    unset($content['BID']);
                }
            });
    }

    protected function assertEMandateEntities($initial = true)
    {
        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);
        $order = $this->getLastEntity(Entity::ORDER, true);

        // Assert Netbanking Entity
        $this->assertNotNull($netbanking[Netbanking::SI_TOKEN]);
        $this->assertEquals('authorize', $netbanking[Netbanking::ACTION]);

        // For registration-only auth, status of payment is null

        $this->assertEquals(IFSC::ICIC, $netbanking[Netbanking::BANK]);

        if ($initial === true)
        {
            if ($payment['amount'] > 0)
            {
                $this->assertEquals('9999999999', $netbanking[Netbanking::BANK_PAYMENT_ID]);
                $this->assertEquals('Y', $netbanking[Netbanking::STATUS]);
            }
            else
            {
                $this->assertEquals(null, $netbanking[Netbanking::BANK_PAYMENT_ID]);
                $this->assertEquals('null', $netbanking[Netbanking::STATUS]);
            }
            // For registration-only auth, the payment id field send by bank is "null"
            $this->assertEquals('Y', $netbanking[Netbanking::SI_STATUS]);
            $this->assertEquals('SUC', $netbanking[Netbanking::SI_MSG]);

            $usedCount = 1;
        }
        else
        {
            // For every SI execution payment we increment the used count
            $usedCount = 2;

            $this->assertEquals('Y', $netbanking[Netbanking::STATUS]);
            $this->assertEquals(null, $netbanking[Netbanking::SI_STATUS]);
            $this->assertEquals(null, $netbanking[Netbanking::SI_MSG]);
        }

        // Assert Token Entity
        $this->assertEquals($netbanking[Netbanking::SI_TOKEN], $token[Token::GATEWAY_TOKEN]);
        $this->assertEquals($payment[Payment::TOKEN_ID], $token[Token::ID]);
        $this->assertEquals(true, $token[Token::RECURRING]);
        // TODO: Uncomment this line when we start accepting max amount as input
        // $this->assertEquals(Token::DEFAULT_MAX_AMOUNT, $token[Token::MAX_AMOUNT]);
        $this->assertEquals(RecurringStatus::CONFIRMED, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);
        $this->assertEquals($usedCount, $token[Token::USED_COUNT]);
        // TODO: The token received is an older one for some reason.
        // The used_at does not get updated. Need to fix this!
        // $this->assertEquals($payment[Payment::CREATED_AT], $token[Token::USED_AT]);
        $this->assertEquals($payment[Payment::MERCHANT_ID], $token[Token::MERCHANT_ID]);
        $this->assertEquals('NIcRecurringTl', $payment[Payment::TERMINAL_ID]);
        $this->assertEquals($payment[Payment::TERMINAL_ID], $token[Token::TERMINAL_ID]);
        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_' . $token[Token::CUSTOMER_ID]);
        $this->assertEquals(IFSC::ICIC, $payment[Payment::BANK]);
        $this->assertEquals(PaymentMethod::EMANDATE, $token[Token::METHOD]);
        $this->assertEquals(IFSC::ICIC, $token[Token::BANK]);

        $this->assertEquals($payment[Payment::AMOUNT], $order[Order::AMOUNT]);

        // Assert GatewayToken entity
        $this->assertEquals($token[Token::ID], 'token_' . $gatewayToken[GatewayToken::TOKEN_ID]);
        $this->assertEquals(null, $gatewayToken[GatewayToken::REFERENCE]);
        $this->assertEquals($token[Token::RECURRING], $gatewayToken[GatewayToken::RECURRING]);
        $this->assertEquals($payment[Payment::TERMINAL_ID], $gatewayToken[GatewayToken::TERMINAL_ID]);
        $this->assertNotNull($token['expired_at']);
    }

    /**
     * If a payment fails, we do not update token / gateway token because
     * the status check fails before we even get to that stage.
     */
    protected function assertEMandateInitialFailEntities()
    {
        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        // Gateway Token is not created
        $this->assertEquals(null, $gatewayToken);
        $this->assertEquals($payment[Payment::TOKEN_ID], $token[Token::ID]);

        $this->assertEquals(null, $token[Token::GATEWAY_TOKEN]);

        // When recurring is false and recurring status is not set,
        // we don't get recurring, related items in the tokens array
        $this->assertFalse($token[Token::RECURRING]);
        $this->assertNotNull($token[Token::RECURRING_DETAILS]);

        // SI success but payment status failed
        $this->assertEquals('N', $netbanking[Netbanking::SI_STATUS]);
        $this->assertEquals('N', $netbanking[Netbanking::STATUS]);
        $this->assertEquals(true, $netbanking[Netbanking::RECEIVED]);
        $this->assertEquals(null, $netbanking[Netbanking::BANK_PAYMENT_ID]);
    }

    protected function assertEMandateRejectedToken()
    {
        // Assert that the token was rejected
        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        // Netbanking entity updated with SI failure status and message
        $this->assertEquals('N', $netbanking[Netbanking::SI_STATUS]);
        $this->assertEquals('Failure', $netbanking[Netbanking::SI_MSG]);

        // Recurring remains in false, recurring status = rejected and gateway token
        $this->assertEquals($payment[Payment::TOKEN_ID], $token[Token::ID]);
        $this->assertEquals(false, $token[Token::RECURRING]);
        $this->assertEquals(RecurringStatus::REJECTED, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        // We update gateway token only if token recurring status is confirmed
        $this->assertEquals(null, $token[Token::GATEWAY_TOKEN]);
        $this->assertEquals($netbanking[Netbanking::SI_MSG], $token[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);

        // Assert GatewayToken entity
        $this->assertEquals($token[Token::ID], 'token_' . $gatewayToken[GatewayToken::TOKEN_ID]);
        $this->assertEquals(null, $gatewayToken[GatewayToken::REFERENCE]);
        // Gateway token recurring will be false, as token recurring is false
        $this->assertEquals($token[Token::RECURRING], $gatewayToken[GatewayToken::RECURRING]);
        $this->assertEquals($payment[Payment::TERMINAL_ID], $gatewayToken[GatewayToken::TERMINAL_ID]);

        $this->assertEquals($payment[Payment::MERCHANT_ID], $token[Token::MERCHANT_ID]);
        $this->assertEquals($payment[Payment::TERMINAL_ID], $token[Token::TERMINAL_ID]);
        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_' . $token[Token::CUSTOMER_ID]);
        $this->assertEquals(IFSC::ICIC, $payment[Payment::BANK]);
        $this->assertEquals(PaymentMethod::EMANDATE, $token[Token::METHOD]);
        $this->assertEquals(IFSC::ICIC, $token[Token::BANK]);
    }

    protected function assertEMandateInitialTokenSaveFailed($expectedSiStatus)
    {
        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        $this->assertEquals('failed', $payment[Payment::STATUS]);

        // Gateway token is not set here
        $this->assertEquals($payment[Payment::TOKEN_ID], $token[Token::ID]);
        $this->assertEquals(null, $token[Token::GATEWAY_TOKEN]);

        // Since recurring status is not set, the token entity will not contain recurring fields
        $this->assertArrayNotHasKey(Token::RECURRING, $token);
        $this->assertArrayNotHasKey(Token::RECURRING_DETAILS, $token);

        $this->assertNotNull($netbanking[Netbanking::SI_TOKEN]);
        $this->assertEquals($expectedSiStatus, $netbanking[Netbanking::SI_STATUS]);
        $this->assertEquals('9999999999', $netbanking[Netbanking::BANK_PAYMENT_ID]);

        // No new gateway token created - exception is thrown before creation of new gateway token
        $this->assertEquals(null, $gatewayToken);
    }

    protected function assertDebitRequestFailure(array $token1, array $gatewayToken1)
    {
        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);
        $token2 = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken2 = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        $this->assertArraySelectiveEquals(
            [
                'status'              => 'failed',
                'method'              => 'emandate',
                'recurring_type'      => 'auto',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER',
                'error_description'   => 'The payment could not be completed as it was cancelled by the customer.'
            ],
            $payment
        );

        // Asserting that the failed second recurring payment was
        // made with the same token id as above
        $this->assertEquals($token1[Token::ID], $token2[Token::ID]);
        $this->assertEquals($payment[Payment::TOKEN_ID], $token2[Token::ID]);

        // Asserting that the payment was attempted with a valid token
        $this->assertNotNull($token2[Token::GATEWAY_TOKEN]);
        $this->assertEquals(true, $token2[Token::RECURRING]);
        $this->assertEquals(RecurringStatus::CONFIRMED, $token2[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token2[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);

        // Reference ID sent across will sent be back
        $this->assertNotNull($netbanking[Netbanking::SI_TOKEN]);
        // SI status not saved in second recurring payment flow and neither is SI message
        $this->assertEquals(null, $netbanking[Netbanking::SI_STATUS]);
        $this->assertEquals(null, $netbanking[Netbanking::SI_MSG]);
        $this->assertEquals('9999999999', $netbanking[Netbanking::BANK_PAYMENT_ID]);

        // Gateway Token is not created - as it is only created during SI registration flow
        $this->assertArraySelectiveEquals($gatewayToken1, $gatewayToken2);
    }

    protected function assertEMandateStrangeStatus()
    {
        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        // Gateway token is not set here
        $this->assertEquals($payment[Payment::TOKEN_ID], $token[Token::ID]);
        $this->assertEquals(null, $token[Token::GATEWAY_TOKEN]);

        // Since recurring status is not set, the token entity will not contain recurring fields
        $this->assertEquals(false, $token[Token::RECURRING]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);

        $this->assertNotNull($netbanking[Netbanking::SI_TOKEN]);
        $this->assertEquals('C', $netbanking[Netbanking::SI_STATUS]);
        $this->assertEquals(null, $netbanking[Netbanking::BANK_PAYMENT_ID]);

        // Assert gateway token was created
        $this->assertNull($gatewayToken);
    }

    protected function mockEmptySecondRecurringResponse()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                if ($action === 'second_recurring_xml')
                {
                    $content = "";
                }
            });
    }

    protected function mockSiPaymentFailure()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content['PAID'] = 'N';
                $content['SCHSTATUS'] = 'N';
                unset($content['BID']);
            });
    }

    /**
     * Marking SCHSTATUS as C allows the recurring status to be set to null
     */
    protected function mockSiRecurringStatusNotSet()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                // This maps to a null recurring status
                $content['SCHSTATUS'] = 'C';
            });
    }

    protected function mockSiRecurringMessageNotSet()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content['SCHSTATUS'] = 'N';
                $content['SCHMSG'] = '';
            });
    }

    protected function mockFailedPayment()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content['PAID'] = 'N';
            });
    }

    protected function mockRejectedToken($apply = true)
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null) use ($apply)
            {
                if ($apply === true)
                {
                    $content['SCHSTATUS'] = 'N';
                    $content['SCHMSG'] = 'Failure';
                }
            });
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content['STATUS'] = 'Failed';
            });
    }

    protected function mockSiRecurringGatewayTokenNotSet($set = true)
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null) use ($set)
            {
                if ($set === true)
                {
                    $content['RID'] = '';
                }
            });
    }

    protected function mockDebitRequestFailure()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                if ($action === 'second_recurring')
                {
                    $content['PAID'] = 'N';
                    $content['STATUS'] = 'PaymentStoppedByCustomer';
                }
            });
    }
}
