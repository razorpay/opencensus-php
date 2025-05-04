<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Models\UpiMandate;
use RZP\Models\Customer\Token;
use RZP\Models\UpiMandate\Entity;
use RZP\Models\UpiMandate\Status;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payment\UpiMetadata\Entity as MetaData;

class UpiIciciRecurringTest extends UpiInitialRecurringTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_icici_recurring_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();
        $this->mockSplitzTreatmentForAutopayRearch('variant_off');
    }

    public function testEncryptedRecurringCallback(){

        // test when callback is encrypted.
        $this->testRecurringMandateCreate(true);
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

        $this->mandateCreateCallback($payment, true);

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
        $this->assertNotNull($upiMandate[Entity::NPCI_TXN_ID]);
    }

    public function testRecurringOneTimeMandateExecute()
    {
        $this->testRecurringOneTimeMandateCreate();

        $input = $this->getDbUpiAutoRecurringPayment();

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'upi_icici',
            'cps_route' => 0,
        ]);

        $this->assertArraySubset([
            'razorpay_payment_id'   => $payment->getPublicId(),
            'razorpay_order_id'     => $this->order->getPublicId(),
        ], $response);

        $this->assertArrayHasKey('razorpay_signature', $response);

        $this->assertUpiDbLastEntity('upi', [
            'action'        => 'authorize',
            'status_code'   => '0',
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

        $content = $this->mockServer()->getAsyncCallbackResponseAutoDebitForIcici($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_icici');

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'captured',
            'reference1'    => 'HDFC00001124',
            'reference16'   => '019721040510',
        ], false);

        $this->assertUpiDbLastEntity('upi', [
            'action'                => 'authorize',
            'merchant_reference'    => $this->upiMandate->getId(),
            'gateway_payment_id'    => 'GatewayPaymentIdDebit',
            'status_code'           => '0',
            'npci_txn_id'           => 'HDFC00001124',
            'npci_reference_id'     => '019721040510',
            'gateway_error'         => [
                'gatewayStatusCode'     => null,
                'gatewayStatusDesc'     => 'Debit Success',
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

    public function testRecurringTpvMandateCreate()
    {
        $this->enableRecurringTpv();

        $this->testRecurringMandateCreate(false, true,
            [
                'name'              =>  'Test Recurring TPV',
                'account_number'    =>  '12345678921',
                'ifsc'              =>  'ICIC0001183'
            ]
        );
    }

    public function testRecurringTpvMandateCreateWithInvalidAccountNumber()
    {
        $this->enableRecurringTpv();

        $this->expectExceptionMessage('The bank account.account number must be between 5 and 35 characters.');

        $this->testRecurringMandateCreate(false, true,
            [
                'name'              =>  'Test Recurring TPV',
                'account_number'    =>  '123',
                'ifsc'              =>  'ICIC0001183'
            ]
        );
    }

    public function testRecurringMandateCreateOnDark(&$requestSentToDark = false)
    {
        $this->mockServerContentFunction(function (&$content, $action) use (&$requestSentToDark)
        {
            if ($action === 'redirectToDark')
            {
                $requestSentToDark = true;
            }
        }, 'upi_icici');

        parent::testRecurringMandateCreateOnDark($requestSentToDark);
    }

    public function testRecurringTpvMandateCreateFailed()
    {
        $this->enableRecurringTpv();

        $orderId = $this->createUpiRecurringTpvOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['customer_id'] = 'cust_100000customer';

        // Mock error from Mozart Gateway
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'auth_init')
            {
                $content['success'] = false;
                $content['error'] = [
                    'internal_error_code'       => ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
                    'description'               => 'Service unavailable.',
                    'gateway_error_code'        =>  '5009',
                    'gateway_error_description' => 'Service unavailable.',
                    'gateway_status_code'       =>  200
                ];
            }
        });
        $payment = $this->payment;

        // Assert exception message
        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            GatewayErrorException::class,
            'Payment processing failed due to error at bank or wallet gateway'.PHP_EOL.
            'Gateway Error Code: 5009'.PHP_EOL.
            'Gateway Error Desc: Service unavailable.'
        );

        $payment = $this->getDbLastPayment();

        // Mock Failure callback content
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'auth_verify')
            {
                $content['success'] = false;
                $content['data']['status'] = 'callback_failed';
                $content['error']['internal_error_code'] = ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_REJECTED;
            }
        });

        $this->mandateCreateCallback($payment);

        $this->assertUpiDbLastEntity('payment', [
            'status'    => 'failed'
        ]);
    }

    // For failure response from Mandate QR API
    public function testRecurringMandateCreateViaIntentFailed()
    {
        // Mock error from Mozart Gateway
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'auth_init')
            {
                $content['success'] = false;
                $content['error'] = [
                    'internal_error_code'       => ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
                    'description'               => 'Service unavailable.',
                    'gateway_error_code'        => '5009',
                    'gateway_error_description' => 'Service unavailable.',
                    'gateway_status_code'       =>  200
                ];
                $content['data']['intent_url'] = '';
                $content['data']['upi']['status_code'] = '5009';
            }
        });

        $this->goWithTheFlow(
            [
                'class'     => GatewayErrorException::class,
                'message'   => 'Payment processing failed due to error at bank or wallet gateway'.PHP_EOL.
                               'Gateway Error Code: 5009'.PHP_EOL.
                               'Gateway Error Desc: Service unavailable.'
            ],
            function() {
                $this->testRecurringMandateCreateViaIntent(false, false, []);
            }
        );


        $token = $this->getDbLastEntity('token');

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $upi = $this->getDbLastEntity('upi');

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $payment = $this->getDbLastEntity('payment');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS   => 'pending_for_authenticate',
            MetaData::FLOW              => 'intent',
            MetaData::VPA               => null
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => null,
            Token\Entity::VPA_ID           => null
        ], $token->toArray());

        $this->assertArraySubset([
            UpiMandate\Entity::CUSTOMER_ID     => '100000customer',
            UpiMandate\Entity::FREQUENCY       => 'monthly',
            UpiMandate\Entity::RECURRING_VALUE => 31,
            UpiMandate\Entity::RECURRING_TYPE  => 'before',
            UpiMandate\Entity::STATUS          => UpiMandate\Status::CREATED,
            UpiMandate\Entity::TOKEN_ID        => $token['id'],
            UpiMandate\Entity::USED_COUNT      => 1,
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

        // Payment should be marked as failed
        $this->assertArraySubset([
            Payment\Entity::STATUS              => 'failed',
            Payment\Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Payment\Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_SYSTEM_UNAVAILABLE'
        ], $payment->toArray());
    }

    public function testMandateCallbackFailed()
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

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'auth_verify')
            {
                $content['success'] = false;
                $content['data']['status'] = "mandate_failed";
                $content['error']['internal_error_code'] = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
            }
        });

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $token->reload();

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'failed',
            Payment\Entity::INTERNAL_ERROR_CODE => ErrorCode::GATEWAY_ERROR_BANK_OFFLINE
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::TOKEN_ID        => $token['id'],
            Entity::STATUS          => Status::CREATED,
            Entity::GATEWAY_DATA    => [
                Entity::FLOW        => 'collect',
            ]
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());
    }

    public function testInitialDebitCallbackFailed()
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

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $token->reload();

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
                Entity::VPA         => 'vishnu@icici',
            ]
        ], $upiMandate->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'pay_verify')
            {
                $content['success'] = false;
                $content['data']['status'] = "mandate_failed";
                $content['error']['internal_error_code'] = ErrorCode::GATEWAY_ERROR_INSUFFICIENT_FUNDS_REMITTER_ACCOUNT;
            }
        });

        $this->firstDebitCallback($payment);

        $payment->reload();

        $token->reload();

        $upi->reload();

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertArraySubset([
            MetaData::INTERNAL_STATUS => 'failed'
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING        => false,
            Token\Entity::RECURRING_STATUS => 'initiated'
        ], $token->toArray());

        $this->assertNotNull($upiMandate[Entity::UMN]);
        $this->assertNotNull($upiMandate[Entity::RRN]);
        $this->assertNotNull($upiMandate[Entity::NPCI_TXN_ID]);

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($orderId, 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'failed',
            Payment\Entity::INTERNAL_ERROR_CODE => ErrorCode::GATEWAY_ERROR_INSUFFICIENT_FUNDS_REMITTER_ACCOUNT
        ], $payment->toArray());
    }

    /**
     * This is a tabular test that checks all MID based whitelisted vpa handles for upi recurring
     *
     * @dataProvider merchantBasedVpaWhitelistingForAutopayDataProvider
     * @param $vpa - VPA E.g. "anish@oksbi" etc.
     */
    public function testMerchantBasedVpaWhitelisting($vpa)
    {
        $vpaHandle  = substr($vpa, (strpos($vpa, '@') + 1));
        $key        = 'gateway.upi_icici.recurring_' . $vpaHandle . '_test_merchants';

        $this->app['config']->set($key, ['10000000000000']);

        $this->payment['vpa'] = $vpa; // override the vpa to test this scenario in TEST env

        $this->goWithTheFlow(
            null,
            function () {
                $this->testRecurringMandateCreate();
            }
        );
    }

    /**
     * This function provides the testcases for the @testMerchantBasedVpaWhitelisting
     * Each testcase must consist of a VPA (string)
     *
     * @return array of testcases
     */
    public function merchantBasedVpaWhitelistingForAutopayDataProvider()
    {
        $cases = [];

        $cases['oksbi_allow']   = ['razorpay@oksbi'];
        $cases['okicici_allow'] = ['razorpay@okicici'];

        return $cases;
    }

    protected function enableRecurringTpv()
    {
        // Create dedicated terminal for TPV Payment
        $this->fixtures->create('terminal:dedicated_upi_icici_tpv_terminal', ['tpv' => 2]);

        // Enable Merchant TPV Feature
        $this->fixtures->merchant->enableTpv('10000000000000');
    }
}
