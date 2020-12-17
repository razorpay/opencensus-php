<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Models\UpiMandate\Entity;
use RZP\Models\UpiMandate\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mozart\Mock\Server;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\UpiMetadata\Entity as MetaData;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;

class UpiInitialRecurringTestCase extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiRecurringTrait;

    /**
     * @var array
     */

    protected $payment;
    /**
     * @var Terminal\Entity
     */
    protected $terminal;

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

    public function testRecurringMandateCreateRejected()
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
            if ($action === 'auth_verify')
            {
                $content['success'] = false;
                $content['data']['status'] = 'callback_failed';
                $content['error']['internal_error_code'] = ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED;
            }
        });

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $upiMetadata->reload();

        $payment = $this->assertUpiDbLastEntity('payment', [
            'status'                => 'failed',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
        ]);
        $this->assertNotEmpty($payment->getVerifyAt());

        $this->assertEquals('created', $upiMandate['status']);

        $this->assertEquals('failed', $upiMetadata['internal_status']);
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
            Payment\Entity::STATUS          => 'authorized',
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
            Payment\Entity::STATUS           => 'authorized',
            Payment\Entity::LATE_AUTHORIZED  => true,
        ], $payment->toArray());
    }

    public function testVerifyForSuccessfulFirstPayment()
    {
        $this->testRecurringMandateCreate();

        $payment = $this->getDbLastPayment();

        $response = $this->verifyPayment($payment->getPublicId());
    }

    public function testRecurringMandateCreateOnDark()
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
        $exceptionThrown = false;
        try
        {
            $this->mandateCreateCallback($payment);
        }
        catch (\Exception $e)
        {
            // A work around to make sure redirection occurred
            $this->assertStringEndsWith('(Syntax error, malformed JSON).', $e->getMessage());
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Redirection exception not thrown');

        // Now reset back to prod
        config()->set('applications.mozart.live.url', 'https://mozart-dark.razorpay.com');

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

        $exceptionThrown = false;
        try
        {
            $this->firstDebitCallback($payment);
        }
        catch (\Exception $e)
        {
            // A work around to make sure redirection occurred
            $this->assertStringEndsWith('(Syntax error, malformed JSON).', $e->getMessage());
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Redirection exception not thrown');

        // Now reset back to prod
        config()->set('applications.mozart.live.url', 'https://mozart-dark.razorpay.com');

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

    protected function mandateCreateCallback($payment)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponseMandateCreate($payment, $gateway);

        return $this->makeS2sCallbackAndGetContentSilently($content, $gateway);
    }

    protected function firstDebitCallback($payment)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponseFirstDebit($payment, $gateway);

        $this->makeS2sCallbackAndGetContentSilently($content, $gateway);
    }

    protected function mandatePauseCallback($mandate)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponsePause($mandate, $gateway);

        $this->makeS2sCallbackAndGetContentSilently($content, $gateway);
    }

    protected function mandateResumeCallback($mandate)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponseResume($mandate, $gateway);

        $this->makeS2sCallbackAndGetContentSilently($content, $gateway);
    }

    protected function mandateRevokeCallback($mandate)
    {
        $gateway = $this->terminal->getGateway();

        $content = $this->mockMozartServer()->getAsyncCallbackResponseRevoke($mandate, $gateway);

        $this->makeS2sCallbackAndGetContentSilently($content, $gateway);
    }
}
