<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Models\Customer\Token;
use RZP\Models\UpiMandate\Entity;
use RZP\Models\UpiMandate\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Payment\UpiMetadata\Entity as MetaData;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;

class UpiAxisInitialRecurringTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use PaymentsUpiRecurringTrait;

    protected $payment;

    /**
     * @var Terminal\Entity
     */
    protected $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:dedicated_axis_recurring_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();

        $this->mockSplitzTreatmentForAutopayPricing('variant_on');
    }

    public function testRecurringMandateValidateVPA()
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

        $this->payment['recurring'] = 1;

        $this->payment['upi']['flow'] = 'collect';

        $this->payment['upi']['vpa'] = 'test@okhdfcbank';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $upiMandate->reload();

        $token = $this->getDbLastEntity('token');

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertArraySubset([
            \RZP\Models\PaymentsUpi\Vpa\Entity::USERNAME => 'test'
        ], $vpa->toArray());

        $this->assertArraySubset([
            Payment\Entity::GATEWAY => 'upi_axis'
        ], $payment->toArray());

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
    }

    public function testRecurringMandateValidateVPANegative()
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

        $this->payment['recurring'] = 1;

        $this->payment['upi']['flow'] = 'collect';

        $this->payment['upi']['vpa'] = 'test@okhdfcbank';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upiMandate->reload();

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'validate_vpa')
            {
                $content['success'] = false;

                $content['data']['status'] = "failed";
            }
        });

        $payment = $this->getDbLastPayment();

        $this->assertEquals('created', $payment['status']);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertEquals('created', $upiMandate['status']);
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

    public function testRecurringMandateValidateVPAIntent()
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

        $this->payment['recurring'] = 1;

        $this->payment['upi']['flow'] = 'collect';

        $this->payment['upi']['vpa'] = 'test@okhdfcbank';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $upiMandate->reload();

        $token = $this->getDbLastEntity('token');

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertArraySubset([
            \RZP\Models\PaymentsUpi\Vpa\Entity::USERNAME => 'test'
        ], $vpa->toArray());

        $this->assertArraySubset([
            Payment\Entity::GATEWAY => 'upi_axis'
        ], $payment->toArray());

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
    }

    public function testRecurringMandateCreateCollectFlow()
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

        $this->payment['upi']['flow'] = 'collect';

        $this->payment['upi']['vpa'] = 'test@okhdfcbank';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

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
                'act'     => 'create',
                'ano'     => 1,
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
                'act'     => 'execte',
                'ano'     => 1,
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

    public function testRecurringMandateCreateViaIntent()
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_axis_intent_recurring_terminal');

        $orderId = $this->createUpiRecurringOrder();

        $upiMandate = $this->getDbLastEntity('upi_mandate');
        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::STATUS          => Status::CREATED,
            Entity::USED_COUNT      => 0,
            Entity::GATEWAY_DATA    => null,
        ], $upiMandate->toArray(), true);

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Just to validate that a proper coproto is being send
        $this->assertArraySubset([
            'type'      => 'intent',
            'request'   => [
                'method' => 'GET'
            ],
        ], $response);

        $this->assertFalse(empty($response['data']['intent_url']), 'Intent URL not set in the response');

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $upiMandate->reload();

        $token = $this->getDbLastEntity('token');

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS   => 'authenticate_initiated',
            MetaData::FLOW              => 'intent',
            MetaData::VPA               => null,
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated',
            Token\Entity::VPA_ID           => null,
        ], $token->toArray());

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'created',
            Payment\Entity::TERMINAL_ID     => '103UAXISRcrTml'
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::STATUS          => Status::CREATED,
            Entity::TOKEN_ID        => $token['id'],
            Entity::USED_COUNT      => 1,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW    => 'intent',
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION        => 'authenticate',
            Base\Entity::TYPE          => 'intent',
            Base\Entity::PAYMENT_ID    => $payment['id'],
            Base\Entity::VPA           => null,
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'create',
                'ano'       => 1,
                'sno'       => 1,
            ]
        ], $upi->toArray());

        $asserted = false;

        $this->mockServerRequestFunction(function (& $content, $action) use (& $asserted)
        {
            if ($action === 'pay_init')
            {
                assertTrue(isset($content['payment']['id']));
                assertTrue(isset($content['payment']['vpa']));
                assertTrue(isset($content['payment']['amount']));
                assertTrue(isset($content['upi_mandate']['umn']));
                assertTrue(isset($content['terminal']['gateway_merchant_id']));
                assertTrue(isset($content['terminal']['gateway_terminal_password']));

                $asserted = true;
            }
        });

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $token->reload();

        $upi = $this->getDbLastEntity('upi');

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'created',
            Payment\Entity::VPA             => null,
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::TOKEN_ID        => $token['id'],
            Entity::STATUS          => Status::CONFIRMED,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW    => 'intent',
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'intent',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'execte',
                'ano'       => 1,
                'sno'       => 1,
                'ext'       => null,
            ],
        ], $upi->toArray());

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS => 'pending_for_authorize',
            MetaData::FLOW => 'intent',
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        $this->firstDebitCallback($payment);

        $payment->reload();

        $token->reload();

        $upiMandate->reload();

        $upi->reload();

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS => 'authorized',
            MetaData::FLOW => 'intent',
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'captured',
        ], $payment->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING        => true,
            Token\Entity::RECURRING_STATUS => 'confirmed',
        ], $token->toArray());

        $this->assertNotNull($upiMandate[Entity::UMN]);
        $this->assertNotNull($upiMandate[Entity::RRN]);
    }

    public function testRecurringMandateCreateViaIntentRejected()
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_axis_intent_recurring_terminal');

        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPayment($this->payment);

        // Just to validate that a proper coproto is being send
        $this->assertArraySubset([
            'type'      => 'intent',
            'request'   => [
                'method' => 'get'
            ],
        ], $response);

        $this->assertFalse(empty($response['data']['intent_url']), 'Intent URL not set in the response');

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'created',
        ], $payment->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION        => 'authenticate',
            Base\Entity::TYPE          => 'intent',
            Base\Entity::PAYMENT_ID    => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'create',
                'ano'       => 1,
                'sno'       => 1,
            ]
        ], $upi->toArray());

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals('created', $upiMandate['status']);

        $this->assertEquals(Token\RecurringStatus::INITIATED, $token[Token\Entity::RECURRING_STATUS]);

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'auth_verify')
            {
                $content['success'] = false;
                $content['data']['status'] = 'callback_failed';
                $content['data']['upi']['vpa'] = '';
                $content['error']['internal_error_code'] = ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_REJECTED;
            }
        });

        $this->expectWebhookEvent('token.rejected');

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $upiMetadata->reload();

        $token->reload();

        $upi->reload();

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authenticate',
            Base\Entity::TYPE        => 'intent',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::VPA         => null,
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'create',
                'ano'       => 1,
                'sno'       => 1,
                'ext'       => null,
            ],
        ], $upi->toArray());

        $payment = $this->assertUpiDbLastEntity('payment', [
            'vpa'                   => null,
            'status'                => 'failed',
            'reference16'           => null,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_REJECTED,
        ]);

        $this->assertNotEmpty($payment->getVerifyAt());

        $this->assertEquals(Token\RecurringStatus::REJECTED, $token[Token\Entity::RECURRING_STATUS]);
        $this->assertNull($token[Token\Entity::VPA_ID]);

        $this->assertArraySubset([
            Entity::STATUS          => Status::REJECTED,
            Entity::TOKEN_ID        => $token['id'],
            Entity::USED_COUNT      => 1,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW    => 'intent',
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS   => 'failed',
            MetaData::FLOW              => 'intent',
            MetaData::VPA               => null,
        ], $upiMetadata->toArray());

        // Assert if the Description is correct for rejected mandates.
        $this->assertEquals("Payment was unsuccessful as an error occurred at the UPI app. Any amount deducted will be refunded within 5-7 working days.", $token['recurring_failure_reason']);

        // Assert if it is equal to payment error description.
        $this->assertEquals($payment->getErrorDescription(), $token['recurring_failure_reason']);
    }

    public function testRecurringMandateCreateViaIntentDebitFailed()
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_axis_intent_recurring_terminal');

        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPayment($this->payment);

        // Just to validate that a proper coproto is being send
        $this->assertArraySubset([
            'type'      => 'intent',
            'request'   => [
                'method' => 'get'
            ],
        ], $response);

        $this->assertFalse(empty($response['data']['intent_url']), 'Intent URL not set in the response');

        $payment = $this->getDbLastPayment();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertEquals('created', $upiMandate['status']);

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'pay_init')
            {
                $content['success'] = false;
                $content['data']['status'] = "debit_failed";
                $content['error']['internal_error_code'] = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
            }
        });

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $upiMetadata->reload();

        $upi = $this->getDbLastEntity('upi');

        $token = $this->getDbLastEntity('token');

        $payment = $this->assertUpiDbLastEntity('payment', [
            'vpa'                   => null,
            'status'                => 'failed',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_BANK_OFFLINE,
        ]);
        $this->assertNotEmpty($payment->getVerifyAt());

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::TOKEN_ID        => $token['id'],
            Entity::STATUS          => Status::CONFIRMED,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW    => 'intent'
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS   => 'failed',
            MetaData::FLOW              => 'intent',
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'intent',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'execte',
                'ano'       => 1,
                'sno'       => 1,
                'ext'       => null,
            ],
        ], $upi->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING        => false,
            Token\Entity::RECURRING_STATUS => 'initiated',
        ], $token->toArray());
    }

    public function testRecurringMandateCallbackSuccess()
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
                'act'     => 'create',
                'ano'     => 1,
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
                'act'     => 'execte',
                'ano'     => 1,
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

        $this->assertNotNull($payment[Payment\Entity::REFERENCE16]);

    }

    public function testRecurringMandateCreateCallbackRejected()
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

                $content['data']['status'] = "REJECTED";

                $content['data']['status_desc'] = "RESPAUTHMANDATE DECLINED BY PSP";

                $content['error']['internal_error_code'] = ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_REJECTED;

            }
        });

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertEquals('failed', $upiMetadata['internal_status']);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('rejected', $upiMandate['status']);
    }

    public function testRecurringMandateCreateCallbackRevoked()
    {

        $this->testRecurringMandateCallbackSuccess();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $this->mandateRevokeCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::REVOKED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CANCELLED, $token['recurring_status']);

    }

    public function testRecurringMandateCreateCallbackPause()
    {

        $this->testRecurringMandateCallbackSuccess();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $this->mandatePauseCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::PAUSED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::PAUSED, $token['recurring_status']);

    }

    public function testRecurringMandateCreateCallbackResume()
    {

        $this->testRecurringMandateCreateCallbackPause();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $this->mandateResumeCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::CONFIRMED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token['recurring_status']);

    }

    public function testRecurringInitialDebitFailed()
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

    public function testRecurringInitialDebitSuccess()
    {
        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertEquals('created', $upiMandate['status']);

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals('created', $payment['status']);

        $this->assertEquals('confirmed', $upiMandate['status']);

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::STATUS          => Status::CONFIRMED,
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'collect',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'     => 'execte',
                'ano'     => 1,
            ]
        ], $upi->toArray());
    }

    public function testVerifyForFirstDebitSuccess()
    {
        $this->testRecurringMandateCallbackSuccess();

        $payment = $this->getDbLastPayment();

        $this->assertEquals($payment['verified'], 0);

        $response = $this->verifyPayment($payment->getPublicId());

        $payment->reload();

        $this->assertEquals($payment['verified'], 1);

        $this->assertEquals($response['gateway']["verifyResponseContent"]["success"], true);
    }

    public function testRecurringOneTimeMandateCreate()
    {
        $orderId = $this->createUpiRecurringOneTimeOrder();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'one_time',
            Entity::STATUS          => Status::CREATED,
            Entity::USED_COUNT      => 0,
            Entity::GATEWAY_DATA    => null,
        ], $upiMandate->toArray(), true);

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        $response = $this->doAuthPayment($this->payment);

        // Just to validate that a proper coproto is being send
        $this->assertArraySubset([
            'type'      => 'async',
            'request'   => [
                'method' => 'get'
            ],
        ], $response);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $upiMandate->reload();

        $this->upiMandate = $upiMandate;

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
            Entity::FREQUENCY       => 'one_time',
            Entity::STATUS          => Status::CREATED,
            Entity::TOKEN_ID        => $token['id'],
            Entity::USED_COUNT      => 1,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW    => 'collect',
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION        => 'authenticate',
            Base\Entity::TYPE          => 'collect',
            Base\Entity::PAYMENT_ID    => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'create',
                'ano'       => 1,
                'sno'       => 1,
            ]
        ], $upi->toArray());

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $token->reload();

        $this->token = $token;

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'failed',
            Payment\Entity::INTERNAL_ERROR_CODE => ErrorCode::BAD_REQUEST_DUMMY_PAYMENT
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'one_time',
            Entity::TOKEN_ID        => $token['id'],
            Entity::STATUS          => Status::CONFIRMED,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW        => 'collect',
                Entity::VPA         => $this->payment['vpa'],
            ]
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'confirmed'
        ], $token->toArray());


        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS => 'failed'
        ], $upiMetadata->toArray());

        $this->assertNotNull($upiMandate[Entity::UMN]);
        $this->assertNotNull($upiMandate[Entity::RRN]);
    }

    public function testRecurringOneTimeIntentMandateCreate()
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_axis_intent_recurring_terminal');

        $orderId = $this->createUpiRecurringOneTimeOrder();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'one_time',
            Entity::STATUS          => Status::CREATED,
            Entity::USED_COUNT      => 0,
            Entity::GATEWAY_DATA    => null,
        ], $upiMandate->toArray(), true);

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Just to validate that a proper coproto is being send
        $this->assertArraySubset([
            'type'      => 'intent',
            'request'   => [
                'method' => 'GET'
            ],
        ], $response);

        $this->assertFalse(empty($response['data']['intent_url']), 'Intent URL not set in the response');

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
            Entity::FREQUENCY       => 'one_time',
            Entity::STATUS          => Status::CREATED,
            Entity::TOKEN_ID        => $token['id'],
            Entity::USED_COUNT      => 1,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW    => 'intent',
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION        => 'authenticate',
            Base\Entity::TYPE          => 'intent',
            Base\Entity::PAYMENT_ID    => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'create',
                'ano'       => 1,
                'sno'       => 1,
            ]
        ], $upi->toArray());

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $token->reload();

        $this->token = $token;

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'failed',
            Payment\Entity::INTERNAL_ERROR_CODE => ErrorCode::BAD_REQUEST_DUMMY_PAYMENT
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'one_time',
            Entity::TOKEN_ID        => $token['id'],
            Entity::STATUS          => Status::CONFIRMED,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW        => 'intent',
            ]
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'confirmed'
        ], $token->toArray());

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS => 'failed'
        ], $upiMetadata->toArray());

        $this->assertNotNull($upiMandate[Entity::UMN]);
        $this->assertNotNull($upiMandate[Entity::RRN]);
    }

    public function testRecurringOneTimeMandateExecute()
    {
        $this->testRecurringOneTimeMandateCreate();

        $input = $this->getDbUpiAutoRecurringPayment();

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'upi_axis',
            'cps_route' => 0,
        ]);

        $this->assertArraySubset([
            'razorpay_payment_id'   => $payment->getPublicId(),
            'razorpay_order_id'     => $this->order->getPublicId(),
        ], $response);

        $this->assertArrayHasKey('razorpay_signature', $response);

        $this->assertUpiDbLastEntity('upi', [
            'action'        => 'authorize',
            'status_code'   => '00',
            'gateway_data'  => [
                'act'   => 'execte',
                'ano'   => 1,
                'ext'   => null,
                'sno'   => 1,
            ]
        ], false);

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'created',
            'reference1'    => null,
            'reference16'   => null,
        ], false);

        $content = $this->mockServer()->getAsyncCallbackResponseFirstDebitForAxis($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_axis', true);

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'captured',
            'reference1'    => '32131429',
            'reference16'   => '32131429',
        ], false);

        $this->assertUpiDbLastEntity('upi', [
            'action'                => 'authorize',
            'merchant_reference'    => $payment->getId(),
            'gateway_payment_id'    => '32131429',
            'status_code'           => '00',
            'npci_txn_id'           => '32131429',
            'npci_reference_id'     => '32131429',
            'gateway_error'         => [
                'gatewayStatusCode'     => '00',
                'gatewayStatusDesc'     => 'Success',
            ],
        ]);

        $this->assertUpiDbLastEntity('token', [
            'recurring_status' => 'cancelled',
            'recurring_failure_reason' => 'Mandate execution is completed.'
        ]);

        $this->assertUpiDbLastEntity('upi_mandate', [
            'status' => 'expired'
        ]);
    }

    public function testRecurringOneTimeMultipleMandateCreateFailedOnSameOrder()
    {
        $this->testRecurringOneTimeMandateCreate();

        $order = $this->getDbLastOrder();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertArraySubset([
            Entity::ORDER_ID        => $order->getId(),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'one_time',
            Entity::STATUS          => Status::CONFIRMED,
            Entity::USED_COUNT      => 1,
        ], $upiMandate->toArray(), true);

        $this->payment['order_id'] = $order->getPublicId();

        $this->payment['customer_id'] = 'cust_100000customer';

        try {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
            $this->fail('Expected exception ' . BadRequestException::class . ' was not thrown');
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("Your payment has been declined as the One-time mandate is already created with the order. Please initiate the payment with a new order for new mandate creation.", $e->getMessage());
        }
    }

    public function testRecurringOneTimeMultipleMandateCreateFailedOnSameOrderAfterExpired()
    {
        $this->testRecurringOneTimeMandateExecute();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertArraySubset([
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'one_time',
            Entity::STATUS          => Status::EXPIRED,
            Entity::USED_COUNT      => 2,
        ], $upiMandate->toArray(), true);

        $this->payment['order_id'] = 'order_'.$upiMandate['order_id'];

        $this->payment['customer_id'] = 'cust_100000customer';

        try
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
            $this->fail('Expected exception ' . BadRequestException::class . ' was not thrown');
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("Your payment has been declined as the One-time mandate is already created with the order. Please initiate the payment with a new order for new mandate creation.", $e->getMessage());
        }
    }

    /** all mock callbacks */
    protected function mandateCreateCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseMandateCreate($payment);

        $this->makeS2SCallbackAndGetContent($content, 'upi_axis', true);
    }

    protected function mandatePauseCallback($mandate)
    {
        $content = $this->mockMozartServer()->getAsyncCallbackResponsePause($mandate);

        $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, 'upi_axis', true);
    }

    protected function mandateResumeCallback($mandate)
    {
        $content = $this->mockMozartServer()->getAsyncCallbackResponseResume($mandate);

        $this->makeS2sCallbackAndGetContent($content, 'upi_axis', true);
    }

    protected function mandateRevokeCallback($mandate)
    {
        $content = $this->mockMozartServer()->getAsyncCallbackResponseRevoke($mandate);

        $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, 'upi_axis', true);
    }

    protected function firstDebitCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseFirstDebitForAxis($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_axis', true);
    }
}
