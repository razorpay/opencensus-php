<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Support\Facades\App;
use Mockery;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Queue;

use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Payment\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Jobs\OneCCShopifyCreateOrder;

class OneCCShopifyCreateOrderTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->merchant->enableCoD();

        $this->fixtures->pricing->create([
            'plan_id'        => 'DefaltCodRleId',
            'payment_method' => 'cod',
        ]);

        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
    }

    public function testDispatchShopifyJobFor1ccShopifyOrderWithCard()
    {
        Queue::fake();

        $res = $this->createOrderAndPayment(true, ['storefront_id' => 'abcdef']);

        $order = $res['order'];

        $payment = $res['payment'];

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastPayment(true);

        Queue::assertPushed(OneCCShopifyCreateOrder::class, 1);
    }

    public function testDispatchShopifyJobFor1ccShopifyOrderWithCod()
    {
        Queue::fake();

        $res = $this->initiatePaymentForCod(true, ['storefront_id' => 'abcdef']);

        Queue::assertPushed(OneCCShopifyCreateOrder::class, 1);
    }

    public function testDispatchShopifyJobFor1ccNonShopifyOrder()
    {
        Queue::fake();

        $res = $this->createOrderAndPayment(true);

        $payment = $res['payment'];

        $paymentFromResponse = $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastPayment(true);

        Queue::assertNotPushed(OneCCShopifyCreateOrder::class);
    }

    public function testDispatchShopifyJobForNon1ccOrder()
    {
        Queue::fake();

        $res = $this->createOrderAndPayment(false);

        $payment = $res['payment'];

        $this->doAuthAndCapturePayment($payment);

        Queue::assertNotPushed(OneCCShopifyCreateOrder::class);
    }

    public function testDispatchShopifyJobForNon1ccOrderWithCod()
    {
        Queue::fake();

        $res = $this->initiatePaymentForCod(false);

        Queue::assertNotPushed(OneCCShopifyCreateOrder::class);
    }

    public function testDispatchShopifyJobForPaymentWithoutOrder()
    {
        Queue::fake();

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        Queue::assertNotPushed(OneCCShopifyCreateOrder::class);
    }

    protected function createOrderAndPayment($oneCC = true, array $notes = [])
    {
        $order = $this->createOrder($oneCC, $notes);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();

        $payment['amount'] = $order->getAmount();

        return ['order' => $order, 'payment' => $payment];
    }

    protected function initiatePaymentForCod($oneCC = true, array $notes = [])
    {
        $order = $this->createOrder($oneCC, $notes);

        $request = $this->getPaymentCreateRequest($order);

        $response = $this->makeRequestParent($request)->json();

        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        return $response;
    }

    protected function getPaymentCreateRequest($order): array
    {
        return [
            'method'  => 'POST',
            'content' => $this->getDefaultCoDPaymentArray([
                'order_id' => $order->getPublicId(),
                'capture'  => 1,
                'amount'   => $order->getAmount(),
            ]),
            'url'     => '/payments',
        ];
    }

    protected function getDefaultCoDPaymentArray($attributes): array
    {
        $defaults = $this->getDefaultPaymentArrayNeutral();

        $defaults['method'] = 'cod';

        return array_merge($defaults, $attributes);
    }

    protected function createOrder($oneCC = true, array $notes = [])
    {
        $order = $this->fixtures->order->create([
            'receipt' => 'receipt',
            'notes'   => $notes,
        ]);

        if ($oneCC === true)
        {
            $this->fixtures->create('order_meta',
                [
                    'order_id' => $order->getId(),
                    'value'    => $this->getOrderMetaValue(),
                    'type'     => 'one_click_checkout',
                ]
            );
        }
        return $order;
    }

    protected function getOrderMetaValue()
    {
        $app = App::getFacadeRoot();

        $shipping_address = [
            'line1'         => 'some line one',
            'line2'         => 'some line two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'in',
            'type'          => 'shipping_address',
            'primary'       => true
        ];

        $billing_address = [
            'line1'         => 'some line one',
            'line2'         => 'some line two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'in',
            'type'          => 'billing_address',
            'primary'       => true
        ];

        $customer = [
            'contact'           => '+9191111111111',
            'email'             => 'john.doe@razorpay.com',
            'shipping_address'  => $shipping_address,
            'billing_address'   => $billing_address

        ];

        return [
            'cod_fee'           => 0,
            'net_price'         => 50000,
            'sub_total'         => 50000,
            'shipping_fee'      => 0,
            'customer_details'  => $app['encrypter']->encrypt($customer),
            'line_items_total'  => 50000,
        ];
    }
}
