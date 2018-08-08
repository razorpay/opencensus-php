<?php

namespace RZP\Tests\Functional\Offer;

use Carbon\Carbon;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OffersPaymentTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/OffersTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->mockTokenex();

        // This is set to 1 March 2018. Because in test
        // cases offers start date is set to Feb 2018
        // and payments should always during offer period
        Carbon::setTestNow("1-3-2018 00:00:00");
    }

    public function testOfferPayment()
    {
        $offer = $this->fixtures->create('offer');

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $payment = $this->getOrderPaymentArray($order);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(90000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 90000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(90000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        // Payment Offer row got created
        $entityOffers = $this->getEntities('entity_offer', ['entity_type' => 'payment'], true);
        $entityOffer = $entityOffers['items'][0];

        $this->assertEquals($offer->getId(), $entityOffer['offer_id']);
        $this->assertEquals($payment['entity'], $entityOffer['entity_type']);
        $this->assertEquals($payment['id'], 'pay_' . $entityOffer['entity_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(10000, $discount['amount']);
        $this->assertEquals($payment['id'], $discount['payment_id']);
        $this->assertEquals($order['id'], $discount['order_id']);
        $this->assertEquals($offer['id'], $discount['offer_id']);
    }

    public function testOfferPaymentWithMerchantSub()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $offer = $this->fixtures->create('offer:emi_subvention', ['issuer' => 'HDFC', 'payment_network' => null]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'amount'      => 500000,
            'force_offer' => true,
        ]);

        $payment = $this->getOrderPaymentArray($order);

        $payment['method'] = 'emi';
        $payment['emi_duration'] = 9;
        $payment['card']['number'] = '41476700000006';

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(474100, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 500000, 'INR', 474100);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(474100, $payment['amount']);
        $this->assertEquals(474100, $payment['base_amount']);
        $this->assertEquals('captured', $payment['status']);

        // Payment Offer row got created
        $entityOffers = $this->getEntities('entity_offer', ['entity_type' => 'payment'], true);
        $entityOffer = $entityOffers['items'][0];

        $this->assertEquals($offer->getId(), $entityOffer['offer_id']);
        $this->assertEquals($payment['entity'], $entityOffer['entity_type']);
        $this->assertEquals($payment['id'], 'pay_' . $entityOffer['entity_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(500000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(25900, $discount['amount']);
        $this->assertEquals($payment['id'], $discount['payment_id']);
        $this->assertEquals($order['id'], $discount['order_id']);
        $this->assertEquals($offer['id'], $discount['offer_id']);
    }

    public function testPaymentWithMerchantSubWrongEMIPlan()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $offer = $this->fixtures->create('offer:emi_subvention', ['issuer' => 'HDFC', 'payment_network' => null]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'amount'      => 500000,
            'force_offer' => true,
        ]);

        $payment = $this->getOrderPaymentArray($order);

        $payment['method'] = 'emi';
        $payment['emi_duration'] = 6;
        $payment['card']['number'] = '41476700000006';

        $this->expectException(BadRequestValidationFailureException::class);

        $this->doAuthPayment($payment);
    }

    public function testPaymentWithMerchantSubWrongIssuer()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $offer = $this->fixtures->create('offer:emi_subvention', ['issuer' => 'UTIB', 'payment_network' => null]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'amount'      => 500000,
            'force_offer' => true,
        ]);

        $payment = $this->getOrderPaymentArray($order);

        $payment['method'] = 'emi';
        $payment['emi_duration'] = '9';
        $payment['card']['number'] = '41476700000006';

        $this->expectException(BadRequestValidationFailureException::class);

        $this->doAuthPayment($payment);
    }

    public function testOfferPaymentMultipleOffers()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer2);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(90000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 90000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(90000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(10000, $discount['amount']);
        $this->assertEquals($payment['id'], $discount['payment_id']);
        $this->assertEquals($order['id'], $discount['order_id']);
        $this->assertEquals($offer['id'], $discount['offer_id']);
    }

    public function testOfferPaymentCustomerFeeBearer()
    {
        // Skipped because the payments/fees endpoint needs to be updated to
        // first discount the amount, then calculate fees and return it.
        $this->markTestSkipped('discounting for cust_fee_bearer flow is currently not supported');

        $this->fixtures->merchant->setFeeBearer('customer');

        $offer = $this->fixtures->create('offer');

        $order = $this->fixtures->create('order:with_offer_applied', [
            'offer_id' => $offer->getId()
        ]);

        $payment = $this->getOrderPaymentArray($order);

        $feesArray = $this->createAndGetFeesForPayment($payment);
        $amount = $payment['amount'];
        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];
        $payment['fee'] = $feesArray['input']['fee'];

        $this->doAuthAndCapturePayment($payment, $order->getAmount());

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(91800, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);

        $offer = $this->getLastEntity('offer', true);

        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(10000, $discount['amount']);
        $this->assertEquals($payment['id'], $discount['payment_id']);
        $this->assertEquals($order['id'], $discount['order_id']);
        $this->assertEquals($offer['id'], $discount['offer_id']);
    }

    public function testOfferFixForAttemptedOrders()
    {
        $payment = $this->setUpOfferFailedPayment();

        $this->testData[__FUNCTION__]['request']['content']['payment_ids'] = (array) $payment['id'];

        $this->startTest();

        $order = $this->getLastEntity('order', true);

        $this->assertEquals($order['status'], 'paid');

        $discount = $this->getLastEntity('discount', true);
        $offer = $this->getLastEntity('offer', true);
        $this->assertEquals(100000, $discount['amount']);
        $this->assertEquals($payment['id'], $discount['payment_id']);
        $this->assertEquals($order['id'], $discount['order_id']);
        $this->assertEquals($offer['id'], $discount['offer_id']);
    }

    protected function getOrderPaymentArray($order)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        return $payment;
    }

    protected function getOfferPaymentArray($order, $offer)
    {
        $payment = $this->getOrderPaymentArray($order);

        $payment['offer_id'] = $offer->getPublicId();

        return $payment;
    }

    protected function setUpOfferFailedPayment()
    {
        $offer = $this->fixtures->create('offer');

        $order = $this->fixtures->create('order');

        $payment = $this->getOrderPaymentArray($order);

        $this->doAuthAndCapturePayment($payment);

        $this->fixtures->create('entity_offer', [
                'entity_id'   => $order->getId(),
                'entity_type' => 'order',
                'offer_id'    => $offer->getId(),
            ]);

        $orderAttributes = [
            'status' => 'attempted',
            'force_offer' => true,
            'discount'  => true,
        ];

        $this->fixtures->edit('order', $order->getId(), $orderAttributes);

        $paymentAttributes = [
            'amount' => 90000,
        ];

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->edit('payment', $payment['id'], $paymentAttributes);

        return $payment;
    }
}
