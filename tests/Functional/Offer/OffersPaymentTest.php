<?php

namespace RZP\Tests\Functional\Offer;

use Carbon\Carbon;
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
}
