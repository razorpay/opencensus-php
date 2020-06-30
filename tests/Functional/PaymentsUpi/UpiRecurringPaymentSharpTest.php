<?php

namespace RZP\Tests\Functional\PaymentsUpi;

use Carbon\Carbon;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Feature;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class UpiRecurringPaymentSharpTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use InteractsWithSession;
    use PaymentsUpiRecurringTrait;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
    }

    public function testCreateFirstUpiRecurringPaymentSuccess()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($payment);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('confirmed', $upiMandate['status']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isCaptured());
        $this->assertNotNull($payment->getReference16());
        $this->assertEquals('vishnu@icici', $payment->getVpa());
    }

    public function testCreateFirstUpiRecurringPaymentFailure()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';

        $payment['vpa'] = 'failure@razorpay';

        $this->doAuthPayment($payment);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isFailed());
        $this->assertNull($payment->getReference16());
        $this->assertEquals('failure@razorpay', $payment->getVpa());
    }

    public function testCreateFirstUpiRecurringPaymentRejected()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';

        $payment['vpa'] = 'rejected@razorpay';

        $this->doAuthPayment($payment);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isFailed());
        $this->assertNull($payment->getReference16());
        $this->assertEquals('rejected@razorpay', $payment->getVpa());
    }
}
