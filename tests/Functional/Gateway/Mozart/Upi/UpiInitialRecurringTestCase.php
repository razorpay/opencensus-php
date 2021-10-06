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
        $this->assertEquals("Mandate rejected by PSP", $token['recurring_failure_reason']);

        // Assert if it is equal to payment error description.
        $this->assertEquals($payment->getErrorDescription(), $token['recurring_failure_reason']);
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
        $cases['amazonpay_apl_allow']           = ['razorpay@apl', $throwables['whitelisted']];
        $cases['barodapay_barodampay_allow']    = ['razorpay@barodampay', $throwables['whitelisted']];

        $cases['gpay_okaxis_reject']            = ['razorpay@okaxis', $throwables['not_whitelisted']];
        $cases['gpay_okbizaxis_reject']         = ['razorpay@okbizaxis', $throwables['not_whitelisted']];
        $cases['gpay_okicici_reject']           = ['razorpay@okicici', $throwables['not_whitelisted']];
        $cases['gpay_oksbi_reject']             = ['razorpay@oksbi', $throwables['not_whitelisted']];

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

    /**
     * @param $throwable null|array
     * @param $closure callable
     */
    private function goWithTheFlow(?array $throwable, callable $closure)
    {
        // throwable is not expected if throwable is null
        $throwableExpected = ($throwable !== null);
        $throwableThrown = false;

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
            $throwableThrown = true;

            $this->assertExceptionClass($t, $throwable['class']);

            $message = $throwable['message'] ?? null;

            if ($message !== null)
            {
                $this->assertSame($message, $t->getMessage());
            }
        }
        finally
        {
            $this->assertSame($throwableExpected, $throwableThrown, 'Exception not thrown');
        }
    }
}
