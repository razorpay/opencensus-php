<?php

namespace RZP\Tests\Functional\Payment\Analytics;

use RZP\Constants\Entity as E;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;

class AnalyticsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/AnalyticsTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testAttemptsWithCheckoutId()
    {
        $payment = $this->getDefaultPaymentArray();

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $payment['_']['checkout_id'] = $checkoutId;

        $payment = $this->doAuthPayment($payment);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals($checkoutId, $paymentAnalytic[AnalyticsEntity::CHECKOUT_ID]);

        $this->assertEquals(1, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);

        // ------------------------------------------------------------------ //

        $payment = $this->getDefaultPaymentArray();
        $payment['_']['checkout_id'] = $checkoutId;

        $payment = $this->doAuthPayment($payment);
        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals($checkoutId, $paymentAnalytic[AnalyticsEntity::CHECKOUT_ID]);

        $this->assertEquals(2, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);
    }

    public function testAttemptsWithOrderId()
    {
        // First payment attempt
        $order = $this->createOrder();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $rzpPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');
        $this->assertEquals($order['id'], $payment['order_id']);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);
        $this->assertEquals(1, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);

        // // ------------------------------------------------------------------ //
        // // TODO: Find a way to fail the first attempt
        // // Second payment attempt
        // $payment = $this->getDefaultPaymentArray();
        // $payment['order_id'] = $order['id'];
        // $rzpPayment = $this->doAuthPayment($payment);

        // $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);
        // $this->assertEquals(2, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);
    }

    public function testAttemptsWithoutCheckoutIdOrderId()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthPayment($payment);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertNull($paymentAnalytic[AnalyticsEntity::CHECKOUT_ID]);

        $this->assertEquals(1, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);
    }

    public function testHttpRequestDataForNonOtpBasedPayment()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer = [
                            'HTTP_USER_AGENT'   => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
                            'HTTP_REFERER'      => 'https://pay.com/demo'
                        ];

        $payment['_']['library'] = 'checkoutjs';

        $payment['_']['library_version'] = '3846fgjb';

        $payment['_']['platform'] = 'browser';

        $payment['_']['platform_version'] = '52.0.2743.116';

        $payment['_']['integration'] = 'woo_commerce';

        $payment['_']['integration_version'] = '0.1.2';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic, 'testPaymentAnalytics');
    }

    public function testHttpRequestDataForOtpBasedPayment()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_mobikwik_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mobikwik';

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->setMockGatewayTrue();

        $payment = $this->getDefaultWalletPaymentArray('mobikwik');

        $requestServer = [
                            'HTTP_USER_AGENT'   => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
                            'HTTP_REFERER'      => 'https://pay.com/demo'
                        ];

        $payment['_']['library'] = 'checkoutjs';

        $payment['_']['library_version'] = '3846fgjb';

        $payment['_']['platform'] = 'mobile_sdk';

        $payment['_']['platform_version'] = '0.4.12';

        $payment['_']['integration'] = 'magento';

        $payment['_']['integration_version'] = '3.1.2';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic, 'testPaymentAnalyticsOtp');
    }

    public function testDataForUserAgentAnomaly()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer = [
                            'HTTP_USER_AGENT'   => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
                            'HTTP_REFERER'      => 'https://api.razorpay.com/demo'
                        ];

        $payment['_']['browser'] = 'safari';

        $payment['_']['platform_version'] = '537.36';

        $payment['_']['os'] = 'ios';

        $payment['_']['os_version'] = '11.0';

        $payment['_']['device'] = 'mobile';

        $payment['_']['referer'] = 'http://a.com';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic, 'testDataForUserAgentAnomaly');
    }

    public function testHttpRequestDataForInvalidData()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'unknown_library';

        $payment['_']['platform'] = 'unknown_platform';

        $payment['_']['integration'] = 'unknown_integration';

        $payment['_']['browser'] = 'unknown_browser';

        $payment['_']['os'] = 'unknown_os';

        $payment['_']['device'] = 'unknown_device';

        $payment = $this->doAuthPayment($payment);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic, 'testHttpRequestDataForInvalidData');
    }

    public function testHttpRefer1()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer['HTTP_REFERER'] = 'https://api.razorpay.com/demo';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertNull($paymentAnalytic[AnalyticsEntity::REFERER]);
    }

    public function testHttpRefer2()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer['HTTP_REFERER'] = 'https://razorpay.com/demo';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertNull($paymentAnalytic[AnalyticsEntity::REFERER]);
    }

    public function testHttpRefer3()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer['HTTP_REFERER'] = 'https://hello.com';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals('https://hello.com', $paymentAnalytic[AnalyticsEntity::REFERER]);
    }
}
