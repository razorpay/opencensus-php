<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Gateway\Upi\Base;
use RZP\Services\RazorXClient;
use RZP\Models\Customer\Token;
use RZP\Models\UpiMandate\Entity;
use RZP\Models\UpiMandate\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiMindgateRecurringTestOld extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->fixtures->create('terminal:dedicated_mindgate_recurring_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();

        // use old autopay pricing for old test cases
        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'upi_autopay_pricing_blacklist', 'on');
        });
    }

    public function testRecurringMandateCreate()
    {
        $orderId = $this->createUpiRecurringOrder();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::STATUS          => Status::CREATED,
        ], $upiMandate->toArray());

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $upiMandate->reload();

        $token = $this->getDbLastEntity('token');

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'created',
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::STATUS          => Status::CREATED,
            Entity::TOKEN_ID        => $token['id'],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authenticate',
            Base\Entity::TYPE        => 'collect',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'id'      => $payment['id']. 'create0',
                'act'     => 'create',
                'ano'     => 0,
            ]
        ], $upi->toArray());

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $token->reload();

        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'created',
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::TOKEN_ID        => $token['id'],
            Entity::STATUS          => Status::CONFIRMED,
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'collect',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'id'      => $payment['id']. 'execte0',
                'act'     => 'execte',
                'ano'     => 0,
            ]
        ], $upi->toArray());

        $this->firstDebitCallback($payment);

        $payment->reload();
        $token->reload();

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'captured',
        ], $payment->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING        => true,
            Token\Entity::RECURRING_STATUS => 'confirmed'
        ], $token->toArray());

        $this->assertNotNull($upi[Base\Entity::NPCI_REFERENCE_ID]);

        $this->assertNotNull($upi[Base\Entity::GATEWAY_PAYMENT_ID]);

        $this->assertNotNull($upiMandate[Entity::UMN]);
        $this->assertNotNull($upiMandate[Entity::RRN]);
        $this->assertNotNull($upiMandate[Entity::NPCI_TXN_ID]);

        $this->assertNotNull($payment[Payment\Entity::REFERENCE16]);
    }

    public function testRecurringMandateCreateRejected()
    {
        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertEquals('created', $upiMandate['status']);

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'auth_verify')
            {
                $content['success'] = false;

                $content['data']['status'] = "callback_failed";
            }
        });

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('created', $upiMandate['status']);
    }

    public function testRecurringMandateCreateDebitFailed()
    {
        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertEquals('created', $upiMandate['status']);

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'pay_init')
            {
                $content['success'] = false;

                $content['data']['status'] = "debit_failed";
            }
        });

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('confirmed', $upiMandate['status']);
    }

    protected function mandateCreateCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseMandateCreate($payment);

        $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');
    }

    protected function createUpiRecurringOrder(array $override = [])
    {
        $this->ba->privateAuth();

        $content =  [
            'amount'          => 50000,
            'currency'        => 'INR',
            'method'          => 'upi',
            'customer_id'     => 'cust_100000customer',
            'payment_capture' => 1,
            'token'           => [
                'max_amount'      => 150000,
                'frequency'       => 'monthly',
                'start_at'        => Carbon::now()->addDay(1)->getTimestamp(),
                'expire_at'          => Carbon::now()->addDay(60)->getTimestamp(),
            ]
        ];

        $content = array_merge($content, $override);

        $request = [
            'method'  => 'POST',
            'content' => $content,
            'url' => '/orders',
        ];

        $order = $this->makeRequestAndGetContent($request);

        return $order['id'];
    }

    protected function firstDebitCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseFirstDebitForMindgate($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_mindgate');
    }

    /**
     * returns a mock response of the razorx request
     *
     * @param string $inputFeature
     * @param string $expectedFeature
     * @param string $variant
     * @return string
     */
    protected function getRazoxVariant(string $inputFeature, string $expectedFeature, string $variant): string
    {
        if ($expectedFeature === $inputFeature)
        {
            return $variant;
        }

        return 'control';
    }

    /**
     * sets the razox mock
     *
     * @param [type] $closure
     * @return void
     */
    protected function setRazorxMock($closure)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback($closure));
    }
}

