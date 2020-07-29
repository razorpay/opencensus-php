<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Gateway\Upi\Base;
use RZP\Models\Customer\Token;
use RZP\Models\UpiMandate\Entity;
use RZP\Models\UpiMandate\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;

class UpiIciciRecurringTest extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiRecurringTrait;

    protected $payment;
    protected $terminal;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/UpiIciciRecurringTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:shared_icici_recurring_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();
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
            Base\Entity::ACTION        => 'authenticate',
            Base\Entity::TYPE          => 'collect',
            Base\Entity::PAYMENT_ID    => $payment['id'],
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
                'id'     => $payment['id']. 'execte0',
                'act'    => 'execte',
                'ano'    => 0,
            ],
        ], $upi->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        $this->firstDebitCallback($payment);

        $payment->reload();

        $token->reload();

        $upi->reload();

        $this->assertArraySubset([
            Token\Entity::RECURRING        => true,
            Token\Entity::RECURRING_STATUS => 'confirmed'
        ], $token->toArray());

        //$this->assertNotNull($upi[Base\Entity::NPCI_REFERENCE_ID]);

        //$this->assertNotNull($upi[Base\Entity::GATEWAY_PAYMENT_ID]);

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

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->mandateCreateCallback($payment);
        });

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

        $data = $this->testData['testRecurringMandateCreateRejected'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->mandateCreateCallback($payment);
        });

        $payment->reload();

        $upiMandate->reload();

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('confirmed', $upiMandate['status']);
    }

    public function testRecurringMandateCreateDebitCallbackFailed()
    {
        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertEquals('created', $upiMandate['status']);

        $this->mandateCreateCallback($payment);

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'pay_verify')
            {
                $content['success'] = false;

                $content['data']['status'] = "debit_failed";
            }
        });

        $data = $this->testData['testRecurringMandateCreateRejected'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->firstDebitCallback($payment);
        });

        $payment->reload();

        $upiMandate->reload();

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('confirmed', $upiMandate['status']);
    }

    public function testRevokeMandate()
    {
        $this->testRecurringMandateCreate();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->revokeUpiRecurringMandate($token->getPublicId());

        $mandate->reload();

        $this->assertEquals(Status::REVOKED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CANCELLED, $token['recurring_status']);
    }

    public function testRevokeCreatedMandate()
    {
        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($this->payment);

        $mandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $data = $this->testData['testRevokeCreatedMandate'];

        $this->runRequestResponseFlow($data, function() use ($token)
        {
            $this->revokeUpiRecurringMandate($token->getPublicId());
        });

        $mandate->reload();

        $this->assertEquals(Status::CREATED, $mandate['status']);
    }

    public function testPauseMandate()
    {
        $this->testRecurringMandateCreate();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $this->mandatePauseCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::PAUSED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::PAUSED, $token['recurring_status']);
    }

    public function testResumeMandate()
    {
        $this->testPauseMandate();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $this->mandateResumeCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::CONFIRMED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token['recurring_status']);
    }

    public function testRevokeMandateViaCallback()
    {
        $this->testRecurringMandateCreate();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $this->mandateRevokeCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::REVOKED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CANCELLED, $token['recurring_status']);
    }

    protected function revokeUpiRecurringMandate(string $tokenId)
    {
        $this->ba->privateAuth();

        $request = [
            'method'  => 'PUT',
            'content' => [],
            'url' => '/customers/cust_100000customer/tokens/' . $tokenId . '/cancel',
        ];

        $this->makeRequestAndGetContent($request);
    }

    protected function mandateCreateCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseMandateCreateForIcici($payment);

        $this->makeS2SCallbackAndGetContent($content, 'upi_icici');
    }

    protected function firstDebitCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseFirstDebitForIcici($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_icici');
    }

    protected function mandatePauseCallback($mandate)
    {
        $content = $this->mockServer()->getAsyncCallbackResponsePauseForIcici($mandate);

        $this->makeS2SCallbackAndGetContent($content, 'upi_icici');
    }

    protected function mandateResumeCallback($mandate)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseResumeForIcici($mandate);

        $this->makeS2SCallbackAndGetContent($content, 'upi_icici');
    }

    protected function mandateRevokeCallback($mandate)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseRevokeForIcici($mandate);

        $this->makeS2SCallbackAndGetContent($content, 'upi_icici');
    }
}
