<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
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

    /**
     * This is a tabular test that checks ALL active / whitelisted handles and few inactive handles for upi recurring.
     * @dataProvider provideActiveInactiveVpaForAutopayTest
     * @param $vpa - VPA E.g. "shalem@okicici" etc.
     * @param int $errorCondition - -1 => Whitelisted, 0 => Not Whitelisted, 1 => Invalid VPA
     */
    public function testMandateCreateWithActiveInactiveHandles($vpa, int $errorCondition)
    {
        $this->payment['vpa'] = $vpa; // override the vpa to test this scenario in TEST env

        try
        {
            $this->testRecurringTpvMandateCreate();
        }
        catch (BadRequestException $e)
        {
            switch ($errorCondition)
            {
                case 0:
                    $this->assertSame('App not Supported for Upi AutoPay', $e->getMessage());
                    break;
                case 1:
                    $this->assertSame('Invalid VPA. Please enter a valid Virtual Payment Address', $e->getMessage());
                    break;
                default:
                    throw new LogicException("Testcase failed for Whitelisted VPA");
            }
        }
    }

    /**
     * This function provides the testcases for the @testMandateCreateWithActiveInactiveHandles function
     * Each testcase must consist of a VPA and a boolean that denotes if it's whitelisted for Autopay
     * NOTE: The test expects a Valid VPA.
     * The purpose of this test isn't to check if a VPA is valid
     * The main purpose of this test is to check if a VPA is whitelisted
     * @return array
     */
    public function provideActiveInactiveVpaForAutopayTest(): array
    {
        $cases = [];

        /*
            Pattern followed to add / update testcase

            $cases[ <psp>_<handle>_<allow/reject> ] = [
                string: <handle>,
                bool: <isWhitelisted>
            ]

        */

        /**
         * TODO : Change Dataprovider and caller in a way that it accepts optional throwables and grouped testcases
         */

        $cases['invalid_vpa']                   = ['razorpay_upi.com',           1];

        $cases['bhim_upi_allow']                = ['razorpay@upi',           -1];
        $cases['paytm_paytm_allow']             = ['razorpay@paytm',         -1];
        $cases['phonepe_ibl_allow']             = ['razorpay@ibl',           -1];
        $cases['phonepe_ybl_allow']             = ['razorpay@ybl',           -1];
        $cases['phonepe_axl_allow']             = ['razorpay@axl',           -1];
        $cases['gpay_okhdfcbank_allow']         = ['razorpay@okhdfcbank',    -1];
        $cases['amazonpay_apl_allow']           = ['razorpay@apl',           -1];
        $cases['barodapay_barodampay_allow']    = ['razorpay@barodampay',    -1];

        $cases['gpay_okaxis_reject']            = ['razorpay@okaxis',         0];
        $cases['gpay_okbizaxis_reject']         = ['razorpay@okbizaxis',      0];
        $cases['gpay_okicici_reject']           = ['razorpay@okicici',        0];
        $cases['gpay_oksbi_reject']             = ['razorpay@oksbi',          0];

        return $cases;
    }
}
