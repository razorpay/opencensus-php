<?php

namespace RZP\Tests\Functional\Offer;

use Carbon\Carbon;
use phpDocumentor\Reflection\Types\This;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Order\Entity;
use RZP\Tests\Functional\Order\OrderTest;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OffersPaymentTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/OffersTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->mockCardVault();

        // This is set to 1 March 2018. Because in test
        // cases offers start date is set to Feb 2018
        // and payments should always during offer period
        Carbon::setTestNow("1-3-2018 00:00:00");
    }

    public function testOfferPayment()
    {
        $this->mockCardVaultWithCryptogram();

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
        $this->mockCardVaultWithCryptogram();

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $offer = $this->fixtures->create('offer:emi_subvention', ['issuer' => 'HDFC',
            'payment_network' => null, 'type' => 'instant']);

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
        $this->mockCardVaultWithCryptogram();

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
        $this->mockCardVaultWithCryptogram();

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

    public function testOfferNotApplicableWithPaymentBlockedFlagNotSet()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['601200'],'block' => false,
            'type' => 'instant']);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['501200'],'block' => false,
            'type' => 'instant']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer1);


        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 100000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);

    }

    public function testCashbackOfferWithInstantOffer()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['601200'],'block' => false, 'type' => 'instant']);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false, 'type' => 'deferred']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer2);

        $this->mockCardVaultWithCryptogram();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 100000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);

    }

    public function testOfferApplicableWithPaymentBlockedFlagNotSet()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer2);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(90000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 90000, 'INR', 90000);

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

    public function testAlreadyDiscountedOffersWithBlockedSetFalse()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false,
            'type' => 'already_discounted']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer1);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 100000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);
    }

    public function testAlreadyDiscountedOffersNotValidWithBlockedSetFalse()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['501200'],'block' => false,
            'type' => 'already_discounted']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer1);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 100000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);
    }

    public function testAlreadyDiscountedOffersWithBlockedSetTrue()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => true,
            'type' => 'already_discounted']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer1);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 100000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);
    }

    public function testCashbackOffersWithBlockedSetFalse()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false,
            'type' => 'deferred']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer1);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 100000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);
    }

    public function testCashbackOffersNotValidWithBlockedSetFalse()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['501200'],'block' => false,
            'type' => 'deferred']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer1);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 100000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);
    }

    public function testCashbackOffersWithBlockedSetTrue()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => true,
            'type' => 'deferred']);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer1);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 100000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);
    }

    public function testInstantOfferWithBlockSetFalse()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer2);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(90000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 90000, 'INR', 90000);

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

    public function testInstantOfferNotValidWithBlockSetFalse()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['501200'],'block' => false]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer2);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 100000, 'INR', 100000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(100000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $offer = $this->getLastEntity('offer', true);
        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);

    }


    public function testInstantDefaultOfferWithBlockSetFalse()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false,
            'default_offer' => true, 'type' => 'instant']);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false]);

        $order = $this->createOrder();

        $payment = $this->getOrderPaymentArrayFromOrderApi($order, $offer1);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(45000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 45000, 'INR', 45000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(45000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(50000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(5000, $discount['amount']);
        $this->assertEquals($payment['id'], $discount['payment_id']);
        $this->assertEquals($order['id'], $discount['order_id']);
        $this->assertEquals($order['offer_id'], $discount['offer_id']);
    }

    public function testInstantDefaultOfferNotValidWithBlockSetFalse()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['501200'],'block' => false,
            'default_offer' => true, 'type' => 'instant']);

        $order = $this->createOrder();

        $payment = $this->getOrderPaymentArrayFromOrderApi($order, $offer1);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 50000, 'INR', 50000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(50000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);
    }



    public function testAlreadyDiscountedDefaultOfferWithBlockSetFalse()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => false,
            'default_offer' => true, 'type' => 'already_discounted']);

        $order = $this->createOrder();

        $payment = $this->getOrderPaymentArrayFromOrderApi($order, $offer1);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $this->capturePayment($payment['id'], 50000, 'INR', 50000);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals(50000, $order['amount']);
        $this->assertEquals('paid', $order['status']);

        $discount = $this->getLastEntity('discount', true);
        $this->assertEquals(null, $discount);
    }


    public function testOfferNotApplicableWithPaymentBlockedFlagSet()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['601200'],'block' => true]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['501200'],'block' => true]);

        $order = $this->fixtures->order->createWithOffers([
            $offer2
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer2);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $response = $this->doAuthPayment($payment);
            },
            \RZP\Exception\BadRequestValidationFailureException::class);
    }

    public function testMaxOfferUsage()
    {
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => true]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => true,
            'max_offer_usage' => 1]);

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


        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer2);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $response = $this->doAuthPayment($payment);
            },
            \RZP\Exception\BadRequestValidationFailureException::class,
            PublicErrorDescription::OFFER_MAX_OFFER_LIMIT_EXCEEDED);
    }

    public function testMaxCardUsage()
    {
        //Will fix this later
        $this->markTestSkipped();
        $this->mockCardVaultWithCryptogram();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => true]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],'block' => true,
            'max_payment_count' => 1]);

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


        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $payment = $this->getOfferPaymentArray($order, $offer2);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $response = $this->doAuthPayment($payment);
            },
            \RZP\Exception\BadRequestValidationFailureException::class,
            PublicErrorDescription::OFFER_MAX_CARD_USAGE_LIMIT_EXCEEDED);
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

        $this->mockCardVaultWithCryptogram();

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

        $this->mockCardVaultWithCryptogram();

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

    protected function getOrderPaymentArrayFromOrderApi($order)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['offer_id'] = $order['offer_id'];
        $payment['order_id'] = $order['id'];

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
        $offer = $this->fixtures->create('offer', ['type'=> 'instant']);

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

    public function testValidateOffersBeforeCheckout()
    {
        $order = $this->fixtures->create('order');

        $firstOffer = $this->fixtures->create('offer', ['type' => 'instant', 'current_offer_usage' => 2, 'max_offer_usage' => 5]);

        $secondOffer = $this->fixtures->create('offer', ['type' => 'instant', 'active' => 0]);

        $input['order_id'] = 'order_' . $order->getId();

        $input['amount'] = '6000';

        $input['method'] = 'card';

        $card = [];

        $card['number'] = '411111';

        $input['card'] = $card;

        $this->fixtures->create('entity_offer', [
            'entity_id' => $order->getId(),
            'entity_type' => 'order',
            'offer_id' => $firstOffer->getId(),
        ]);

        $this->fixtures->create('entity_offer', [
            'entity_id' => $order->getId(),
            'entity_type' => 'order',
            'offer_id' => $secondOffer->getId(),
        ]);

        $offers = [];

        $offers[0] = $firstOffer->getPublicId();

        $offers[1] = $secondOffer->getPublicId();

        $input['offers'] = $offers;

        $callback = null;

        $content = [];

        $content = array_merge($content, $input);

        $request = [
            'method' => 'POST',
            'url' => '/validate/checkout/offers',
            'content' => $content
        ];

        $this->ba->publicAuth();

        $this->mockCardVaultWithCryptogram(null, true);

        $response = $this->makeRequestAndGetContent($request, $callback);

        $this->assertContains($firstOffer->getPublicId(), $response);

        $this->assertNotContains($secondOffer->getPublicId(), $response);
    }

    public function testFindOffersInPaymentResponseWithExpandsForPrivateAuth()
    {
        $this->mockCardVaultWithCryptogram();

        $offer = $this->fixtures->create('offer');

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $payment = $this->getOrderPaymentArray($order);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['url'] .= $payment['id'];

        $response = $this->startTest();

        $this->assertArrayHasKey('offers', $response);

        $this->assertEquals($offer->getPublicId(), $response['offers']['items'][0]['id']);
    }

    public function testPaymentResponseWithNoExpandsForPrivateAuth()
    {
        $this->mockCardVaultWithCryptogram();

        $offer = $this->fixtures->create('offer');

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $payment = $this->getOrderPaymentArray($order);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['url'] .= $payment['id'];

        $response = $this->startTest();

        $this->assertArrayNotHasKey('offers', $response);
    }

    public function testPaymentWithReward()
    {
        $reward = $this->fixtures->create('reward');

        $this->fixtures->create('merchant_reward', ['reward_id' => $reward->id, 'status' => 'live',
            'activated_at' => Carbon::today()->getTimestamp(), 'accepted_at' => Carbon::today()->getTimestamp()]);

        $order = $this->fixtures->order->create();

        $payment = $this->getOrderPaymentArray($order);

        $payment['reward_ids'][] = 'reward_'.$reward->getId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);

        // Payment Offer row got created
        $entityOffers = $this->getEntities('entity_offer', ['entity_type' => 'payment'], true);
        $entityOffer = $entityOffers['items'][0];

        $this->assertEquals($reward->getId(), $entityOffer['offer_id']);
        $this->assertEquals($payment['entity'], $entityOffer['entity_type']);
        $this->assertEquals($payment['id'], 'pay_' . $entityOffer['entity_id']);
    }

    public function testInternationalNonApplicableOfferPayment()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => '1']);

        $offer = $this->fixtures->create('offer', ['international' => 1, 'block' => 0]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
            'currency' => 'USD',
            'payment_capture' => 1,
        ]);

        $payment = $this->getOrderPaymentArray($order);

        $payment['currency'] = 'USD';

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('1000000', $payment['base_amount']);
        $this->assertEquals('100000', $payment['amount']);
    }

}
