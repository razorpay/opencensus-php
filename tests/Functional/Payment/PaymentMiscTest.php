<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentMiscTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testPaymentMetadataRoute()
    {
        $payment = $this->getDefaultPaymentArray();
        $cardIin = substr($payment['card']['number'], 0, 6);

        $payment = $this->doAuthPayment();

        $content = ['otp_read' => '1'];

        $content = $this->addPaymentMetadata($payment['razorpay_payment_id'], $content);

        $iin = $this->getEntityById('iin', $cardIin, true);
        $this->assertEquals($iin['otp_read'], true);
    }

    public function testPaymentFlowsRoute()
    {
        $offer1 = $this->fixtures->create('offer:live_card', [
            'name'                => 'Test Offer 1',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'VISA',
            'issuer'              => 'HDFC',
            'international'       => false,
            'iins'                => [],
        ]);

        $offer2 = $this->fixtures->create('offer:live_card', [
            'name'                => 'Test Offer 2',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'VISA',
            'issuer'              => 'UTIB',
            'international'       => false,
            'iins'                => [],
        ]);

        $iin = $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
                'otp'  => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['atm_pin_auth', 'axis_express_pay']);

        $orderData = [
            'content' => [
                'amount'   => 10000,
                'currency' => 'INR',
                'receipt'  => 'rcp123',
                'offers'   => [$offer1->getPublicId(), $offer2->getPublicId()],
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ];

        $this->ba->privateAuth();

        $this->sendRequest($orderData);

        $order = $this->getLastEntity('order', true);

        $flowsData = [
            'request' => [
                'method'  => 'GET',
                'url'     => '/payment/flows?iin=' . $iin->getIin() . '&order_id=' . $order['id'],
            ],
            'response' => [
                'content' => [
                    'pin' => true,
                    'otp' => true,
                ],
                'status_code' => 200,
            ]
        ];

        $this->ba->publicAuth();

        $response = $this->runRequestResponseFlow($flowsData);

        $this->assertEquals($response['offers'], [$offer2->getPublicId()]);
    }
}
