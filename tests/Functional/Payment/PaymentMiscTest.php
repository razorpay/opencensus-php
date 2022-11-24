<?php

namespace RZP\Tests\Functional\Payment;

use Redis;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Gateway\Downtime\Webhook\Constants\Vajra;

class PaymentMiscTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
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

        $this->mockCardVaultWithCryptogram(null, true);

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

        $this->mockCardVaultWithCryptogram(null, true);

        $response = $this->runRequestResponseFlow($flowsData);

        $this->assertEquals($response['offers'], [$offer2->getPublicId()]);
    }

    protected function setupRedisMock($getValue)
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get'])
                          ->getMock();

        Redis::shouldReceive('connection')
             ->andReturn($redisMock);

        $redisMock->method('set')
                  ->will($this->returnValue(true));

        $redisMock->method('get')
                  ->will($this->returnValue($getValue));
    }

    public function testVajraCpsDowntime()
    {
        // Vajra status OK
        $request = [
            'content' => [
                Vajra::STATUS_KEY => Vajra::STATUS_OK,
            ],
            'method' => 'POST',
            'url' => '/gateway/cps/webhook/vajra'
        ];

        $this->ba->vajraAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue((bool) $response[0]['new_value']);

        $request = [
            'content' => [
                Vajra::STATUS_KEY => Vajra::STATUS_ALERTING,
            ],
            'method' => 'POST',
            'url' => '/gateway/cps/webhook/vajra'
        ];

        $this->ba->vajraAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertFalse((bool) $response[0]['new_value']);
    }
}
