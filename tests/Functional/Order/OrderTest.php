<?php

namespace RZP\Tests\Functional\Order;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OrderTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/OrderTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function setUpBillDeskGateway()
    {
        $this->fixtures->create('terminal:shared_billdesk_tpv_terminal');

        $this->setMockGatewayTrue();
    }

    public function setUpSharpGateway()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'sharp';
    }

    public function testCreateOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateOrderWithNegativeAmount()
    {
        $this->startTest();
    }

    public function testCreateAutoCaptureOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateTPVOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateTPVOrderWithInvalidAccountNumber()
    {
        $this->startTest();
    }

    public function testGetOrder()
    {
        $order = $this->testCreateOrder();

        $order = $this->getEntityById('order', $order['id']);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__], $order);
    }

    public function testGetMultipleOrders()
    {
        $createdOrders = $this->fixtures->times(2)->create('order');
        $createdOrders = array_reverse($createdOrders);
        $collection = new \RZP\Models\Base\PublicCollection($createdOrders);
        $array = $collection->toArrayPublic();

        $this->testData[__FUNCTION__]['response']['content'] = $array;

        $this->startTest();
    }

    public function testRetrieveOrderWithReceipt()
    {
        $order = $this->fixtures->create('order');

        $this->ba->proxyAuth();

        $orders = $this->retrieveOrdersDefault();

        //GIVEN
        $receipt = $orders['items'][0]['receipt'];

        $order = $this->retrieveOrdersDefault(['receipt' => $receipt]);

        $this->assertEquals($receipt, $order['items'][0]['receipt']);
    }

    public function testStatusAfterPayment()
    {
        $order = $this->testCreateOrder();
        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'created');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $rzpPayment = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_order_id', $rzpPayment);
        $this->assertArrayHasKey('razorpay_signature', $rzpPayment);

        $payment = $this->getLastEntity('payment');
        $this->assertEquals($order['id'], $rzpPayment['razorpay_order_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'attempted');
        $this->assertEquals($order['authorized'], true);

        // If a payment is requested for an already authorised order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        // If a payment is requested for an already paid order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testStatusAfterAutoCapturePaymentWoCallback()
    {
        $order = $this->testCreateAutoCaptureOrder();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $response = $this->doAuthPayment($payment);

        $this->assertAutoCaptureResponse($response, $payment, $order);
    }

    public function testAutoCaptureFeeBearerCustomer()
    {
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $payment = $this->getDefaultPaymentArray();
        $this->ba->publicAuth();
        $feesArray = $this->validateFees($payment);

        $this->ba->privateAuth();

        $amount = $payment['amount'];

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];
        $payment['fee'] = $feesArray['input']['fee'];

        $order = $this->testCreateAutoCaptureOrder();

        $payment['order_id'] = $order['id'];

        $response = $this->doAuthPayment($payment);

        $this->assertAutoCaptureResponse($response, $payment, $order);
    }

    public function testStatusAfterAutoCapturePaymentWCallback()
    {
        // Sharp gateway will make payment go via callback flow
        $this->setUpSharpGateway();

        $this->testStatusAfterAutoCapturePaymentWoCallback();
    }

    protected function assertAutoCaptureResponse(array $response, $payment, $order)
    {
        $actualSignature = $response['razorpay_signature'];

        unset($response['razorpay_signature']);

        ksort($response);
        $exceptedSignature = $this->getSignature($response, 'TheKeySecretForTests');

        $this->assertEquals($actualSignature, $exceptedSignature);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(true, $payment['auto_captured']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($order['authorized'], true);
    }

    public function testOrderAndPaymentAmountMismatch()
    {
        $order = $this->testCreateOrder();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = '1000';

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentForTPVMerchantWithoutOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'UTIB';

        // Not adding order_id in payment

        $this->runRequestResponseFlow(
            $this->testData[__FUNCTION__],
            function () use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $this->fixtures->merchant->disableTPV();
    }

    public function testCardPaymentForTPVMerchantWithoutOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpSharpGateway();

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $this->fixtures->merchant->disableTPV();
    }

    public function testPaymentWithIncorrectBankForTPVMerchantWithOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $order = $this->testCreateTPVOrder();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'CORP';

        $payment['order_id'] = $order['id'];

        $this->runRequestResponseFlow(
            $this->testData[__FUNCTION__],
            function () use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $this->fixtures->merchant->disableTPV();
    }

    public function testPaymentForTPVMerchantWithOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $order = $this->testCreateTPVOrder();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'UTIB';

        $payment['order_id'] = $order['id'];

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment');

        $this->assertEquals($order['id'], $payment['order_id']);

        $this->fixtures->merchant->disableTPV();
    }

    public function testPreferencesForTPVMerchants()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $this->testCreateTPVOrder();

        $order = $this->getLastEntity('order', true);

        $this->ba->publicAuth();

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];

        $preferences = $this->startTest($testData);

        $this->fixtures->merchant->disableTPV();
    }

    public function testCreateOrderWithOffer()
    {
        $offer = $this->fixtures->create('offer:live_card', ['iins' => ["401200"]]);

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['offer_id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testCreateOrderWithNotApplicableOffer()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testCreateOrderWithExpiredOffer()
    {
        $offer = $this->fixtures->create('offer:expired');

        $this->testData[__FUNCTION__]['request']['content']['offer_id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testPaymentWithOfferAppliedOnOrder()
    {
        $this->mockTokenex();
        $this->testCreateOrderWithOffer();

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'created');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];

        $rzpPayment = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $rzpPayment);
        $this->assertArrayHasKey('razorpay_signature', $rzpPayment);
        $this->assertEquals($order['id'], $rzpPayment['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');
    }

    public function testPaymentWithFailedOfferCheck()
    {
        $this->fixtures->merchant->enableMobikwik();

        $this->testCreateOrderWithOffer();

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'created');

        $payment = $this->getDefaultWalletPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->fixtures->merchant->disableMobikwik();
    }

    public function testPaymentOnOfferWithNullMethod()
    {
        $this->mockTokenex();
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $offer = $this->fixtures->create('offer', [
            'starts_at' => Carbon::now(Timezone::IST)->subMonth()->timestamp,
            'issuer' => 'HDFC',
        ]);

        $order = $this->fixtures->create('order', [
            'merchant_id' => Account::TEST_ACCOUNT,
            'offer_id' => $offer->getId(),
            'amount' => 1000,
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $res = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $res);
        $this->assertArrayHasKey('razorpay_signature', $res);
        $this->assertEquals($order->getPublicId(), $res['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($res['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        $order = $this->fixtures->create('order', [
            'merchant_id' => Account::TEST_ACCOUNT,
            'offer_id'    => $offer->getId(),
            'amount'      => 1000,
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $res = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $res);
        $this->assertArrayHasKey('razorpay_signature', $res);
        $this->assertEquals($order->getPublicId(), $res['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($res['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');
    }

    public function testPaymentWithFailedOfferCheckOnNullMethodOffer()
    {
        $offer = $this->fixtures->create('offer', [
            'starts_at' => Carbon::now(Timezone::IST)->subMonth()->timestamp,
            'issuer' => 'HDFC',
            'error_message' => 'Custom error message'
        ]);

        $this->fixtures->merchant->enableMobikwik();

        $order = $this->fixtures->create('order', [
            'merchant_id' => '10000000000000',
            'offer_id' => $offer->getId(),
            'amount' => 1000,
        ]);

        $payment = $this->getDefaultWalletPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->fixtures->merchant->disableMobikwik();
    }

    public function testPaymentWithFailedOfferWithCustomErrorMessage()
    {
        $this->fixtures->merchant->enableMobikwik();

        $offer = $this->fixtures->create('offer:card', ['error_message' => 'Custom error message']);

        $order = $this->fixtures->create('order:with_offer_applied', ['offer_id' => $offer->getId()]);

        $payment = $this->getDefaultWalletPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->fixtures->merchant->disableMobikwik();
    }

    public function testPaymentWithBlockPaymentDisabledOnOffer()
    {
        $offer = $this->fixtures->create('offer:card', ['block' => false]);

        $order = $this->fixtures->create('order:with_offer_applied', ['offer_id' => $offer->getId()]);

        $this->fixtures->merchant->enableMobikwik();

        $payment = $this->getDefaultWalletPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $this->fixtures->terminal->createSharedMobikwikTerminal();

        $rzpPayment = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_order_id', $rzpPayment);
        $this->assertArrayHasKey('razorpay_signature', $rzpPayment);
        $this->assertEquals($order->getPublicId(), $rzpPayment['razorpay_order_id']);

        $payment = $this->getLastEntity('payment');
        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        $this->fixtures->merchant->disableMobikwik();
    }

    public function testPartialPaymentOnOrderWithNoPartialPaymentFlag()
    {
        $order = $this->fixtures->create('order');

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 500000;

        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionCode(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH);
        $this->expectExceptionMessage(
                'Payment amount provided does not match with the amount in order');

        $payment = $this->doAuthAndGetPayment($payment);
    }

    public function testPartialPayment()
    {
        $order = $this->fixtures->create(
                                    'order',
                                    [
                                        'payment_capture' => true,
                                        'partial_payment' => true,
                                    ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 500000;

        $expectedPaymentResponse = [
            'status'   => 'captured',
            'order_id' => $order->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);

        $this->assertEquals(500000, $order['amount_paid']);
        $this->assertEquals(500000, $order['amount_due']);
    }

    /**
     * Having run above test(made a partial payment), this test attempts to
     * make another payment with amount greater than the current due of order.
     */
    public function testPartialPaymentTooMuchAmount()
    {
        $this->testPartialPayment();

        $order = $this->getLastEntity('order');

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 500001;

        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionCode(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_MORE_THAN_ORDER_AMOUNT_DUE);
        $this->expectExceptionMessage(
                'Payment amount is greater than the amount due for order');

        $payment = $this->doAuthAndGetPayment($payment);
    }

    public function testMultiplePartialPayments()
    {
        $this->testPartialPayment();

        $order = $this->getLastEntity('order');

        // Order was for 1000000.
        // Amount due: 500000, as 500000 was paid in above first payment.

        // Will make another 2 payments to make amount due 0 and check the order
        // status at both stage.

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 250000;

        $expectedPaymentResponse = [
            'status'   => 'captured',
            'order_id' => $order['id'],
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);

        $this->assertEquals(750000, $order['amount_paid']);
        $this->assertEquals(250000, $order['amount_due']);

        // 2nd payment >

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 250000;

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals(1000000, $order['amount_paid']);
        $this->assertEquals(0, $order['amount_due']);
    }

    public function testPartialPaymentAndRefund()
    {
        $this->testPartialPayment();

        $payment = $this->getLastEntity('payment');

        // Payment was done for 500000 (above ^). Due amount is 500000.

        // Will refund the payment in 2 calls (partial refunds) and check
        // order's attributes at both stage.
        //
        // Refund should not affect order's attributes in any way.

        $this->refundPayment($payment['id'], 250000);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);

        $this->assertEquals(500000, $order['amount_paid']);
        $this->assertEquals(500000, $order['amount_due']);

        // 2nd refund

        $this->refundPayment($payment['id']);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);

        $this->assertEquals(500000, $order['amount_paid']);
        $this->assertEquals(500000, $order['amount_due']);
    }

    public function testPartialPaymentAndNoAutoCapture()
    {
        $order = $this->fixtures->create(
                                    'order',
                                    [
                                        'payment_capture' => false,
                                        'partial_payment' => true,
                                    ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 500000;

        $expectedPaymentResponse = [
            'status'   => 'authorized',
            'order_id' => $order->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $order = $this->getLastEntity('order');

        $this->assertEquals('attempted', $order['status']);
        $this->assertEquals(1, $order['attempts']);

        $this->assertEquals(0, $order['amount_paid']);
        $this->assertEquals(1000000, $order['amount_due']);

        // Capture the payment

        $this->capturePayment($payment['id'], $payment['amount']);

        $order = $this->getLastEntity('order');

        $this->assertEquals(500000, $order['amount_paid']);
        $this->assertEquals(500000, $order['amount_due']);
    }

    public function testPaymentWithMaxPaymentCountOfferAppliedOnOrderWithNoCardSaving()
    {
        $this->setUpTerminals();

        $offer = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'starts_at' => time(),
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer);

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer);

        $testData = $this->testData[__FUNCTION__];

        $content = $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentWithMaxPaymentCountAppliedOnOrderWithGlobalSavedCard()
    {
        $this->setUpTerminals();
        $this->mockSession();

        $offer = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'starts_at' => time(),
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer, [
            'save' => 1
        ]);

        $this->doAuthAndCapturePayment($payment);

        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer, [
            'card' => ['cvv' => 111],
            'token' => $token['token'],
        ]);

        $testData = $this->testData[__FUNCTION__];

        $content = $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentWithMaxPaymentCountAppliedOnOrderWithLocallySavedCard()
    {
        $this->setUpTerminals();
        $this->mockSession();

        $offer = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'starts_at' => time(),
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer, [
            'save' => 1,
            'customer_id' => 'cust_100000customer',
        ]);

        $this->doAuthAndCapturePayment($payment);

        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer, [
            'customer_id' => 'cust_100000customer',
            'card' => ['cvv' => 111],
            'token' => $token['token'],
        ]);

        $testData = $this->testData[__FUNCTION__];

        $content = $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentWithMaxPaymentCountOfferButPaymentsAlreadyMadeOnLinkedOffers()
    {
        $this->setUpTerminals();

        $offer1 = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'starts_at' => time(),
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer1);

        $this->doAuthAndCapturePayment($payment);

        $offer2 = $this->fixtures->create('offer:card', [
            'max_payment_count' => 1,
            'iins' => ['401200'],
            'linked_offer_ids' => (array) $offer1->getId(),
            'starts_at' => time(),
        ]);

        $payment = $this->createOrderWithOfferAppliedAndGetPaymentArray($offer2);

        $testData = $this->testData[__FUNCTION__];

        $content = $this->runRequestResponseFlow($testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    protected function setUpTerminals()
    {
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->mockTokenex();
    }

    protected function createOrderWithOfferAppliedAndGetPaymentArray($offer, array $additionalPaymentAttributes = [])
    {
        $order = $this->fixtures->create('order:with_offer_applied', [
            'offer_id' => $offer->getId(),
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $payment = array_merge($payment, $additionalPaymentAttributes);

        return $payment;
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }

    protected function retrieveOrdersDefault(array $content = [], $method = 'GET')
    {
        $request = array(
            'method'  => $method,
            'url'     => '/orders',
            'content' => $content
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function validateFees($payment)
    {
        $feesArray = $this->createAndGetFeesForPayment($payment);

        if ($payment['amount'] === 50000)
        {
            $this->assertEquals(1173, $feesArray['input']['fee']);

            $this->assertEquals(1.49, $feesArray['display']['tax']);
        }

        return $feesArray;
    }
}
