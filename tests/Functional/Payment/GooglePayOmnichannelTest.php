<?php

namespace RZP\Tests\Functional\Payment;

use Mockery;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\UpiMetadata;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GooglePayOmnichannelTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testUpiOmnichannelPayment()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures('google_pay_omnichannel');

        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_mindgate',
            'upi'                       => 1,
            'gateway_merchant_id'       => 'HDFC000012340818',
            'gateway_merchant_id2'      => 'abc@hdfcbank',
            'type'                      => [
                Terminal\Type::COLLECT        => '1',
                Terminal\Type::PAY            => '1',
                Terminal\Type::NON_RECURRING  => '1',
            ],
            'enabled'                   => 1,
            'deleted_at'                => null,
        ];

        $this->fixtures->create('terminal', $attributes);

        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'google_pay',
            'gateway_merchant_id'       => 'HDFC000012340818',
            'gateway_merchant_id2'      => 'abc@hdfcbank',
            'vpa'                       => 'abc@hdfcbank',
            'omnichannel'               => 1,
            'enabled'                   => 1,
            'deleted_at'                => null,
        ];

        $this->fixtures->create('terminal', $attributes);

        $payment = $this->getDefaultUpiPaymentArray();

        unset($payment['description']);

        unset($payment['vpa']);

        $payment['_']['flow'] = 'intent';

        $payment['upi_provider'] = 'google_pay';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentId, $payment['id']);

        $this->assertEquals($payment['status'], 'created');

        $this->assertNull($payment['vpa']);

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            UpiMetadata\Entity::FLOW     => 'intent',
            UpiMetadata\Entity::PROVIDER => 'google_pay',
            UpiMetadata\Entity::TYPE     => 'default'
        ], $upiMetadata->toArray());
    }
}
