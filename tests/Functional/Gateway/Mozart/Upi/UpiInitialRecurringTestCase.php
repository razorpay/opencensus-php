<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Services\RazorXClient;
use RZP\Models\UpiMandate\Entity;
use RZP\Exception\LogicException;
use RZP\Models\UpiMandate\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mozart\Mock\Server;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\UpiMetadata\Entity as MetaData;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;

class UpiInitialRecurringTestCase extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;
    use PaymentsUpiRecurringTrait;

    /**
     * @var array
     */

    protected $payment;
    /**
     * @var Terminal\Entity
     */
    protected $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        // set the pre-processing through mozart as true
        // we are testing if the recurring flows works even if icici normal
        // payments pre-processing is set do through mozart
        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $this->setAutopayPricing();

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();
    }

    public function testRecurringMandateCreate($encrypted=false, $tpv=false, $bankAccount=[])
    {
        // If $tpv flag set to true then create order for TPV with bank_account details
        if ($tpv === true) {
            $orderId = $this->createUpiRecurringTpvOrder([
                'bank_account' => $bankAccount
            ]);
        }
        else
        {
            $orderId = $this->createUpiRecurringOrder();
        }

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

        $this->mandateCreateCallback($payment, $encrypted);

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
            Entity::GATEWAY_DATA    => [
                Entity::FLOW        => 'collect',
                Entity::VPA         => $this->payment['vpa'],
            ]
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'collect',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'execte',
                'ano'       => 1,
                'sno'       => 1,
                'ext'       => null,
            ],
        ], $upi->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        $this->firstDebitCallback($payment);

        $payment->reload();

        $token->reload();

        $upi->reload();

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS => 'authorized'
        ], $upiMetadata->toArray());

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

    public function testRecurringMandateCreateViaIntent($encrypted=false, $tpv=false, $bankAccount=[])
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_icici_intent_recurring_terminal');

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
            Payment\Entity::TERMINAL_ID     => '103IciciRcrTml'
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

        $this->mandateCreateCallback($payment, $encrypted);

        $this->assertTrue($asserted, 'The request contents for pay_init were not asserted');

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
                Entity::VPA     => 'test@icici',
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'intent',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::VPA         => 'test@icici',
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'execte',
                'ano'       => 1,
                'sno'       => 1,
                'ext'       => null,
            ],
        ], $upi->toArray());

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS => 'authorize_initiated',
            MetaData::FLOW => 'intent',
            MetaData::VPA  => 'test@icici',
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        $this->assertNotNull($token->getVpaId());

        $this->firstDebitCallback($payment);

        $payment->reload();

        $token->reload();

        $upiMandate->reload();

        $upi->reload();

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS => 'authorized',
            MetaData::FLOW => 'intent',
            MetaData::VPA  => 'test@icici',
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'captured',
            Payment\Entity::VPA             => 'test@icici',
        ], $payment->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING        => true,
            Token\Entity::RECURRING_STATUS => 'confirmed',
        ], $token->toArray());

        $this->assertNotNull($token->getVpaId());

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
                $content['error']['internal_error_code'] = ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_REJECTED;
            }
        });

        $this->expectWebhookEvent('token.rejected');

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $upiMetadata->reload();

        $token->reload();

        $payment = $this->assertUpiDbLastEntity('payment', [
            'status'                => 'failed',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_REJECTED,
        ]);
        $this->assertNotEmpty($payment->getVerifyAt());

        $this->assertEquals(Token\RecurringStatus::REJECTED, $token[Token\Entity::RECURRING_STATUS]);

        $this->assertEquals(Status::REJECTED, $upiMandate['status']);

        $this->assertEquals('failed', $upiMetadata['internal_status']);

        // Assert if the Description is correct for rejected mandates.
        $this->assertEquals("Payment was unsuccessful as an error occurred at the UPI app. Any amount deducted will be refunded within 5-7 working days.", $token['recurring_failure_reason']);

        // Assert if it is equal to payment error description.
        $this->assertEquals($payment->getErrorDescription(), $token['recurring_failure_reason']);
    }

    public function testRecurringMandateCreateViaIntentRejected()
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_icici_intent_recurring_terminal');

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
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_icici_intent_recurring_terminal');

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
                Entity::FLOW    => 'intent',
                Entity::VPA     => 'test@icici',
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS   => 'failed',
            MetaData::FLOW              => 'intent',
            MetaData::VPA               => 'test@icici',
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'intent',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::VPA         => 'test@icici',
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

        $this->assertNotNull($token->getVpaId());
    }

    public function testRecurringMandateCreateViaIntentDebitCallbackFailed()
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_icici_intent_recurring_terminal');

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

        $this->assertEquals('created', $upiMandate['status']);

        $this->mandateCreateCallback($payment);

        $this->assertUpiMetadataStatus('authorize_initiated');

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'pay_verify')
            {
                $content['success'] = false;
                $content['data']['status'] = "debit_failed";
                $content['error']['internal_error_code'] = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
            }
        });

        $this->firstDebitCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

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
                Entity::FLOW    => 'intent',
                Entity::VPA     => 'test@icici',
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS   => 'failed',
            MetaData::FLOW              => 'intent',
            MetaData::VPA               => 'test@icici',
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'intent',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::VPA         => 'test@icici',
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

        $this->assertNotNull($token->getVpaId());
    }

    public function testRecurringMandateCreateDebitFailed()
    {
        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($this->payment);

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

        $payment = $this->assertUpiDbLastEntity('payment', [
            'status'                => 'failed',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_BANK_OFFLINE,
        ]);
        $this->assertNotEmpty($payment->getVerifyAt());

        $this->assertEquals('confirmed', $upiMandate['status']);

        $this->assertEquals('failed', $upiMetadata['internal_status']);
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

        $this->assertUpiMetadataStatus('authorize_initiated');

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'pay_verify')
            {
                $content['success'] = false;
                $content['data']['status'] = "debit_failed";
                $content['error']['internal_error_code'] = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
            }
        });

        $this->firstDebitCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $payment = $this->assertUpiDbLastEntity('payment', [
            'status'                => 'failed',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_BANK_OFFLINE,
        ]);
        $this->assertNotEmpty($payment->getVerifyAt());

        $this->assertEquals('confirmed', $upiMandate['status']);

        $this->assertUpiMetadataStatus('failed');
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

        $this->makeRequestAndCatchException(
            function() use ($token)
            {
                $this->revokeUpiRecurringMandate($token->getPublicId());
            },
            BadRequestException::class,
            'Token invalid, cannot be revoked');

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

    public function testVerifyRecurringMandateCreatePayment()
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

        $time = Carbon::now(Timezone::IST)->addMinutes(4);

        Carbon::setTestNow($time);

        $response = $this->verifyAllPayments();

        $payment->reload();

        $upiMandate->reload();

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
            Entity::STATUS          => Status::CONFIRMED,
        ], $upiMandate->toArray());

        $this->firstDebitCallback($payment);

        $payment->reload();

        $token = $this->getDbLastEntity('token');

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'captured',
            Payment\Entity::LATE_AUTHORIZED  => true,
        ], $payment->toArray());

        //TODO:: set payment late authorized here.
        $this->assertArraySubset([
            Token\Entity::RECURRING        => true,
            Token\Entity::RECURRING_STATUS => 'confirmed'
        ], $token->toArray());
    }

    public function testVerifyRecurringFirstDebitPayment()
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

        $this->mandateCreateCallback($payment);

        $upiMandate->reload();

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::STATUS          => Status::CONFIRMED,
        ], $upiMandate->toArray());

        $time = Carbon::now(Timezone::IST)->addMinutes(4);

        Carbon::setTestNow($time);

        $response = $this->verifyAllPayments();

        $payment->reload();

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID         => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID      => '100000customer',
            Payment\Entity::STATUS           => 'captured',
            Payment\Entity::LATE_AUTHORIZED  => true,
        ], $payment->toArray());
    }

    public function testVerifyForSuccessfulFirstPayment()
    {
        $this->testRecurringMandateCreate();

        $payment = $this->getDbLastPayment();

        $response = $this->verifyPayment($payment->getPublicId());
    }

    public function testSequenceNumberOnFirstDebitRetry()
    {
        // Hit Order Create API
        // Hit Payment Create API, where the auth_init fails
        $this->testRecurringTpvMandateCreateFailed();

        // To ensure no failure response is sent
        $this->mockServerContentFunction(function (& $content, $action) {});

        // Now retry hitting the Payment Create API with the same order id
        $this->doAuthPayment($this->payment);

        $orderId = $this->payment['order_id'];

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastEntity('upi');

        $upiMandate = $this->getDbLastEntity('upi_mandate');

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

        $sno = -1;
        // To ensure sequence number is sent as 1 for First Debit
        $this->mockServerRequestFunction(function (& $content, $action) use (& $sno)
        {
            if ($action === 'pay_init')
            {
                $sno = $content[Base\Entity::UPI][Base\Entity::GATEWAY_DATA][Base\Constants::SEQUENCE];
            }
        });

        $this->mandateCreateCallback($payment);

        $this->assertEquals($sno, 1, 'Sequence Number was not sent as 1 to the gateway');

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
            Entity::USED_COUNT      => 1,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW        => 'collect',
                Entity::VPA         => $this->payment['vpa'],
            ]
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'collect',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'execte',
                'ano'       => 1,
                'sno'       => 1,
                'ext'       => null,
            ],
        ], $upi->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        $this->firstDebitCallback($payment);

        $payment->reload();

        $token->reload();

        $upi->reload();

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS   => 'authorized',
            MetaData::MODE              => 'initial',
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING        => true,
            Token\Entity::RECURRING_STATUS => 'confirmed'
        ], $token->toArray());

        $this->assertNotNull($upiMandate[Entity::UMN]);
        $this->assertNotNull($upiMandate[Entity::RRN]);
        $this->assertNotNull($upiMandate[Entity::NPCI_TXN_ID]);

        $this->assertNotNull($payment[Payment\Entity::REFERENCE16]);
    }

    public function testRecurringMandateCreateOnDark(&$requestSentToDark = false)
    {
        // First set the config to mozart so that gateway data is created correctly
        config()->set('applications.mozart.live.url', 'https://mozart-dark.razorpay.com');

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
            Entity::USED_COUNT      => 1,
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION        => 'authenticate',
            Base\Entity::TYPE          => 'collect',
            Base\Entity::PAYMENT_ID    => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'create',
                'ano'       => 1,
                'sno'       => 1,
                'env'       => 1,
            ]
        ], $upi->toArray());

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'getReferenceNumberForCallback')
            {
                // First parameter is env for callback, which is 1 for dark
                $content[1] = 1;
            }
        });

        // Now reset back to prod
        config()->set('applications.mozart.live.url', 'https://mozart.razorpay.com');

        // First callback will not work
        $this->mandateCreateCallback($payment);

        $this->assertTrue($requestSentToDark, 'The callback was not sent to dark-api');

        // Now reset back to dark
        config()->set('applications.mozart.live.url', 'https://mozart-dark.razorpay.com');

        $requestSentToDark = false;

        $this->mandateCreateCallback($payment);

        $this->assertFalse($requestSentToDark, 'The callback was sent to dark-api');

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
            Entity::GATEWAY_DATA    => [
                Entity::VPA         => $this->payment['vpa'],
            ]
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'collect',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'execte',
                'ano'       => 1,
                'sno'       => 1,
                'env'       => 1,
                'ext'       => null,
            ],
        ], $upi->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        // Now reset back to prod
        config()->set('applications.mozart.live.url', 'https://mozart.razorpay.com');

        $requestSentToDark = false;

        $this->firstDebitCallback($payment);

        $this->assertTrue($requestSentToDark, 'The callback was not sent to dark-api');

        // Now reset back to dark
        config()->set('applications.mozart.live.url', 'https://mozart-dark.razorpay.com');

        $requestSentToDark = false;

        $this->firstDebitCallback($payment);

        $this->assertFalse($requestSentToDark, 'The callback was sent to dark-api');

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

    /**
     * This is a tabular test that checks ALL active / whitelisted handles and few inactive handles for upi recurring.
     * @dataProvider provideVpasWithThrowables
     * @param $vpa - VPA E.g. "shalem@okicici" etc.
     * @param array|null $throwable
     */
    public function testVpaWhitelistingForAutopay($vpa, ?array $throwable)
    {
        $this->payment['vpa'] = $vpa; // override the vpa to test this scenario in TEST env

        $this->goWithTheFlow(
            $throwable,
            function () {
                $this->testRecurringMandateCreate();
            }
        );
    }

    /**
     * This function provides the testcases for the @testVpaWhitelistingForAutopay function
     * Each testcase must consist of a
     * VPA (string)
     * throwable (associative array)
     * throwable contains the exception class and the error message if applicable
     * @return array of testcases
     */
    public function provideVpasWithThrowables(): array
    {
        $cases = [];

        /*
            Pattern followed to add / update testcase

            $cases[ <psp>_<handle>_<allow/reject> ] = [
                string: <handle>,
                array: [
                    'class'     => <Exception class>
                    'message'   => string
                ]
            ]

        */

        $throwables = [
            'whitelisted'       => null,
            'not_whitelisted'   => [
                'class'     => BadRequestException::class,
                'message'   => 'App not Supported for Upi AutoPay'
            ],
            'invalid_vpa'       => [
                'class'     => BadRequestException::class,
                'message'   => 'Invalid VPA. Please enter a valid Virtual Payment Address'
            ]
        ];

        $cases['invalid_vpa']                   = ['razorpay_upi.com', $throwables['invalid_vpa']];

        $cases['bhim_upi_allow']                = ['razorpay@upi', $throwables['whitelisted']];
        $cases['paytm_paytm_allow']             = ['razorpay@paytm', $throwables['whitelisted']];
        $cases['phonepe_ibl_allow']             = ['razorpay@ibl', $throwables['whitelisted']];
        $cases['phonepe_ybl_allow']             = ['razorpay@ybl', $throwables['whitelisted']];
        $cases['phonepe_axl_allow']             = ['razorpay@axl', $throwables['whitelisted']];
        $cases['gpay_okhdfcbank_allow']         = ['razorpay@okhdfcbank', $throwables['whitelisted']];
        $cases['gpay_okicici_allow']            = ['razorpay@okicici', $throwables['whitelisted']];
        $cases['amazonpay_apl_allow']           = ['razorpay@apl', $throwables['whitelisted']];
        $cases['amazonpay_yapl_allow']          = ['razorpay@yapl', $throwables['whitelisted']];
        $cases['barodapay_barodampay_allow']    = ['razorpay@barodampay', $throwables['whitelisted']];
        $cases['bankofindia_boi_allow']         = ['razorpay@boi', $throwables['whitelisted']];
        $cases['canarabank_cnrb_allow']         = ['razorpay@cnrb', $throwables['whitelisted']];
        $cases['imobile_icici_allow']           = ['razorpay@icici', $throwables['whitelisted']];
        $cases['gpay_okaxis_allow']             = ['razorpay@okaxis', $throwables['whitelisted']];
        $cases['nsdlpb_nsdl_allow']             = ['razorpay@nsdl', $throwables['whitelisted']];
        $cases['axispay_axisbank_allow']        = ['razorpay@axisbank', $throwables['whitelisted']];
        $cases['dakpay_postbank_allow']         = ['razorpay@postbank', $throwables['whitelisted']];
        $cases['mobikwik_ikwik_allow']          = ['razorpay@ikwik', $throwables['whitelisted']];
        $cases['digibank_dbs_allow']            = ['razorpay@dbs', $throwables['whitelisted']];
        $cases['bhim_dlb_allow']                = ['razorpay@dlb', $throwables['whitelisted']];
        $cases['bhim_axispay_allow']            = ['razorpay@sliceaxis', $throwables['whitelisted']];
        $cases['payzapp_allow']                 = ['razorpay@pz', $throwables['whitelisted']];
        $cases['bhim_induspay_allow']           = ['razorpay@indus', $throwables['whitelisted']];
        $cases['amazonpay_apay_allow']          = ['razorpay@apay', $throwables['whitelisted']];
        $cases['amazonpay_amazon_allow']        = ['razorpay@amazon', $throwables['whitelisted']];
        $cases['amazonpay_amazonpay_allow']     = ['razorpay@amazonpay', $throwables['whitelisted']];
        $cases['okcredit_axb_allow']            = ['razorpay@axb', $throwables['whitelisted']];
        $cases['groww_yesg_allow']              = ['razorpay@yesg', $throwables['whitelisted']];
        $cases['jio_jio_allow']                 = ['razorpay@jio', $throwables['whitelisted']];
        $cases['bhim_sbipay_allow']             = ['razorpay@sbi', $throwables['whitelisted']];
        $cases['idfc_fam_allow']                = ['razorpay@fam', $throwables['whitelisted']];
        $cases['tataneu_tapicici_allow']        = ['razorpay@tapicici', $throwables['whitelisted']];
        $cases['jupitermoney_jupiteraxis_allow']= ['razorpay@jupiteraxis', $throwables['whitelisted']];
        $cases['bhimpnb_pnb_allow']             = ['razorpay@pnb', $throwables['whitelisted']];
        $cases['fampay_fam_allow']              = ['razorpay@fam', $throwables['whitelisted']];
        $cases['fave_pinelabs_allow']           = ['razorpay@pinelabs', $throwables['whitelisted']];
        $cases['gpay_oksbi_allow']              = ['razorpay@oksbi', $throwables['whitelisted']];
        $cases['zomato_zoicici_allow']          = ['razorpay@zoicici', $throwables['whitelisted']];
        $cases['bajajfinserve_abfspay_allow']   = ['razorpay@abfspay', $throwables['whitelisted']];
        $cases['goniyo_niyoicici_allow']        = ['razorpay@niyoicici', $throwables['whitelisted']];
        $cases['equitassmallfinancebankltd_equitas_allow']  = ['razorpay@equitas', $throwables['whitelisted']];
        $cases['navi_naviaxis_allow']           = ['razorpay@naviaxis', $throwables['whitelisted']];
        $cases['shriramone_shriramhdfcbank_allow'] = ['razorpay@shriramhdfcbank', $throwables['whitelisted']];

        $cases['gpay_okbizaxis_reject']         = ['razorpay@okbizaxis', $throwables['not_whitelisted']];

        return $cases;
    }

    /***** Tests for as presented mandates *******/

    public function testRecurringMandateCreateForAsPresented($encrypted=false, $tpv=false, $bankAccount=[])
    {
        // If $tpv flag set to true then create order for TPV with bank_account details
        if ($tpv === true) {
            $orderId = $this->createUpiRecurringTpvOrder([
                'bank_account' => $bankAccount
            ]);
        }
        else
        {
            $orderId = $this->createUpiRecurringOrder([
                'token'   => [
                    'max_amount'        => 150000,
                    'frequency'         => 'as_presented',
                    'start_time'        => Carbon::now()->getTimestamp(),
                    'end_time'          => null,
                    'recurring_value'   => null,
                ]
            ]);
        }

        $upiMandate = $this->getDbLastEntity('upi_mandate');
        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'as_presented',
            Entity::RECURRING_VALUE =>  null,
            Entity::RECURRING_TYPE  => 'before',
            Entity::STATUS          => Status::CREATED,
            Entity::USED_COUNT      => 0,
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
            Entity::FREQUENCY       => 'as_presented',
            Entity::RECURRING_VALUE =>  null,
            Entity::RECURRING_TYPE  => 'before',
            Entity::STATUS          => Status::CREATED,
            Entity::TOKEN_ID        => $token['id'],
            Entity::USED_COUNT      => 1,
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

        $this->mandateCreateCallback($payment, $encrypted);

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
            Entity::FREQUENCY       => 'as_presented',
            Entity::RECURRING_VALUE => null,
            Entity::RECURRING_TYPE  => 'before',
            Entity::TOKEN_ID        => $token['id'],
            Entity::STATUS          => Status::CONFIRMED,
            Entity::USED_COUNT      => 1,
            Entity::GATEWAY_DATA    => [
                Entity::VPA         => $this->payment['vpa'],
            ],
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Base\Entity::ACTION      => 'authorize',
            Base\Entity::TYPE        => 'collect',
            Base\Entity::PAYMENT_ID  => $payment['id'],
            Base\Entity::GATEWAY_DATA  => [
                'act'       => 'execte',
                'ano'       => 1,
                'sno'       => 1,
                'ext'       => null,
            ],
        ], $upi->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        $this->firstDebitCallback($payment);

        $payment->reload();

        $token->reload();

        $upi->reload();

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS => 'authorized'
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING        => true,
            Token\Entity::RECURRING_STATUS => 'confirmed'
        ], $token->toArray());

        $this->assertNotNull($upiMandate[Entity::UMN]);
        $this->assertNotNull($upiMandate[Entity::RRN]);
        $this->assertNotNull($upiMandate[Entity::NPCI_TXN_ID]);

        $this->assertNotNull($payment[Payment\Entity::REFERENCE16]);
    }

    /************************************ HELPERS ***************************************/

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

    protected function mandateCreateCallback($payment, $encrypted=false)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponseMandateCreate($payment, $gateway, $encrypted);

        return $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, $gateway);
    }

    protected function firstDebitCallback($payment)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponseFirstDebit($payment, $gateway);

        $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, $gateway);
    }

    protected function mandatePauseCallback($mandate)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponsePause($mandate, $gateway);

        $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, $gateway);
    }

    protected function mandateResumeCallback($mandate)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponseResume($mandate, $gateway);

        $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, $gateway);
    }

    protected function mandateRevokeCallback($mandate)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponseRevoke($mandate, $gateway);

        $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, $gateway);
    }

    /**
     * @param $throwable null|array
     * @param $closure callable
     */
    protected function goWithTheFlow(?array $throwable, callable $closure)
    {
        // throwable is not expected if throwable is null
        $throwableExpected = ($throwable !== null);
        $throwableThrown = false;

        $messageFromThrowable = '';

        try
        {
            if (is_callable($closure) === true)
            {
                $closure();
            }
            else
            {
                throw new LogicException("Expected a callable");
            }
        }
        catch (\Throwable $t)
        {
            $throwableThrown        = true;
            $messageFromThrowable   = $t->getMessage();

            $this->assertExceptionClass($t, $throwable['class']);

            $message = $throwable['message'] ?? null;

            if ($message !== null)
            {
                $this->assertSame($message, $messageFromThrowable);
            }

        }
        finally
        {
            $errorMessage = 'Exception expected but none thrown';

            if (($throwableExpected === false) and ($throwableThrown === true))
            {
                $errorMessage = 'Exception thrown but not expected. ' . $messageFromThrowable;
            }

            $this->assertSame($throwableExpected, $throwableThrown, $errorMessage);
        }
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

    public function setAutopayPricing()
    {
        $this->ba->adminAuth();

        $upiAutopayPlan = [
            'plan_name'              => 'TestPlan1',
            'procurer'               => 'razorpay',
            'payment_method'         => 'upi',
            'payment_method_subtype' => 'initial',
            'feature'                => 'payment',
            'payment_method_type'    => null,
            'payment_network'        => null,
            'payment_issuer'         => null,
            'percent_rate'           => 100,
            'fixed_rate'             => 200,
            'type'                   => 'pricing',
            'international'          => 0,
            'amount_range_active'    => '0',
            'amount_range_min'       => null,
            'amount_range_max'       => null,
        ];

        $planId = $this->createPricingPlan($upiAutopayPlan)['id'];

        $upiPricingPlan = [
            'plan_name'              => 'TestPlan1',
            'procurer'               => 'razorpay',
            'payment_method'         => 'upi',
            'feature'                => 'payment',
            'payment_method_type'    => null,
            'payment_network'        => null,
            'payment_issuer'         => null,
            'percent_rate'           => 100,
            'fixed_rate'             => 100,
            'type'                   => 'pricing',
            'international'          => 0,
            'amount_range_active'    => '0',
            'amount_range_min'       => null,
            'amount_range_max'       => null,
        ];

        $recurringPricingPlan = [
            'plan_name'              => 'TestPlan1',
            'procurer'               => 'razorpay',
            'payment_method'         => 'upi',
            'feature'                => 'recurring',
            'payment_method_type'    => null,
            'payment_network'        => null,
            'payment_issuer'         => null,
            'percent_rate'           => 300,
            'fixed_rate'             => 300,
            'type'                   => 'pricing',
            'international'          => 0,
            'amount_range_active'    => '0',
            'amount_range_min'       => null,
            'amount_range_max'       => null,
        ];

        $upiAutoAutopayPlan = [
            'plan_name'              => 'TestPlan1',
            'procurer'               => 'razorpay',
            'payment_method'         => 'upi',
            'payment_method_subtype' => 'auto',
            'feature'                => 'payment',
            'payment_method_type'    => null,
            'payment_network'        => null,
            'payment_issuer'         => null,
            'percent_rate'           => 100,
            'fixed_rate'             => 600,
            'type'                   => 'pricing',
            'international'          => 0,
            'amount_range_active'    => '0',
            'amount_range_min'       => null,
            'amount_range_max'       => null,
        ];

        $this->addPricingPlanRule($planId, $upiPricingPlan);

        $this->addPricingPlanRule($planId, $upiAutoAutopayPlan);

        $this->addPricingPlanRule($planId, $recurringPricingPlan);

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $planId]);
    }

    protected function addPricingPlanRule($id, $rule = [])
    {
        $defaultRule = [
            'payment_method' => 'card',
            'payment_method_type'  => 'credit',
            'payment_network' => 'MAES',
            'payment_issuer' => 'HDFC',
            'percent_rate' => 1000,
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];

        $rule = array_merge($defaultRule, $rule);

        $request = array(
            'method' => 'POST',
            'url' => '/pricing/'.$id.'/rule',
            'content' => $rule);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
