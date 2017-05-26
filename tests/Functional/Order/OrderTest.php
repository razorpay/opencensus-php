<?php

namespace RZP\Tests\Functional\Order;

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
        $payment1 = $this->getDefaultPaymentArray();
        $payment1['order_id'] = $order['id'];
        $this->runRequestResponseFlow($testData, function() use ($payment1)
        {
            $this->doAuthPayment($payment1);
        });

        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        // If a payment is requested for an already paid order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment1)
        {
            $this->doAuthPayment($payment1);
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

            $this->assertEquals(1.49, $feesArray['display']['service_tax']);
        }

        return $feesArray;
    }
}
