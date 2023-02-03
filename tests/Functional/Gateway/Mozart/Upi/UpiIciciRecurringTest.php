<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Models\UpiMandate;
use RZP\Models\Customer\Token;
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

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'upi_autopay_hybrid_encryption', 'on');
        });
    }

    public function testEncryptedRecurringCallback(){

        // test when callback is encrypted.
        $this->testRecurringMandateCreate(true);
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
