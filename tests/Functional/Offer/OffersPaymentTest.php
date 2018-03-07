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

        $order = $this->fixtures->create('order:with_offer_applied', [
            'offer_id' => $offer->getId()
        ]);

        $payment = $this->getOfferPaymentArray($order);

        $this->doAuthAndCapturePayment($payment, $order->getAmount());

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(90000, $payment['amount']);
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

    public function testOfferPaymentCustomerFeeBearer()
    {
        $this->fixtures->merchant->setFeeBearer('customer');

        $offer = $this->fixtures->create('offer');

        $order = $this->fixtures->create('order:with_offer_applied', [
            'offer_id' => $offer->getId()
        ]);

        $payment = $this->getOfferPaymentArray($order);

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

    protected function getOfferPaymentArray($order)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $this->getAmountForOrderWithDiscountApplied($order);

        return $payment;
    }

    protected function getAmountForOrderWithDiscountApplied($order)
    {
        $request = [
            'url'    => '/preferences?order_id=' . $order->getPublicId(),
            'method' => 'get',
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response['offers'][0]['amount'];
    }
}
