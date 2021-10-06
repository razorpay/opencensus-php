<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;

class UpiIciciRecurringTest extends UpiInitialRecurringTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:shared_icici_recurring_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();
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

    protected function enableRecurringTpv()
    {
        // Create shared terminal for TPV Payment
        $this->fixtures->create('terminal:shared_upi_icici_tpv_terminal', ['tpv' => 2]);

        // Enable Merchant TPV Feature
        $this->fixtures->merchant->enableTpv('10000000000000');
    }
}
