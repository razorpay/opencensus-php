<?php

namespace RZP\Tests\Functional\Payment\Analytics;

use RZP\Constants\Entity as E;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

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

    public function testAttempts()
    {
        $payment = $this->getDefaultPaymentArray();

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $payment['_'][AnalyticsEntity::CHECKOUT_ID] = $checkoutId;

        $payment = $this->doAuthPayment($payment);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals($checkoutId, $paymentAnalytic[AnalyticsEntity::CHECKOUT_ID]);

        $this->assertEquals(1, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);

        // ------------------------------------------------------------------ //

        $payment = $this->getDefaultPaymentArray();
        $payment['_'][AnalyticsEntity::CHECKOUT_ID] = $checkoutId;

        $payment = $this->doAuthPayment($payment);
        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals($checkoutId, $paymentAnalytic[AnalyticsEntity::CHECKOUT_ID]);

        $this->assertEquals(2, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);
    }

    public function testHttpRequestDataForNonOtpBasedPayment()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer = [
                            'HTTP_USER_AGENT'   => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
                            'HTTP_REFERER'      => 'https://razorpay.com/demo'
                        ];

        $payment['_'][AnalyticsEntity::LIBRARY] = 'checkoutjs';

        $payment['_'][AnalyticsEntity::LIBRARY_VERSION] = '3846fgjb';

        $payment['_'][AnalyticsEntity::PLATFORM] = 'browser';

        $payment['_'][AnalyticsEntity::PLATFORM_VERSION] = '52.0.2743.116';

        $payment['_'][AnalyticsEntity::INTEGRATION] = 'woo_commerce';

        $payment['_'][AnalyticsEntity::INTEGRATION_VERSION] = '0.1.2';

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
                            'HTTP_REFERER'      => 'https://razorpay.com/demo'
                        ];

        $payment['_'][AnalyticsEntity::LIBRARY] = 'checkoutjs';

        $payment['_'][AnalyticsEntity::LIBRARY_VERSION] = '3846fgjb';

        $payment['_'][AnalyticsEntity::PLATFORM] = 'mobile_sdk';

        $payment['_'][AnalyticsEntity::PLATFORM_VERSION] = '0.4.12';

        $payment['_'][AnalyticsEntity::INTEGRATION] = 'magento';

        $payment['_'][AnalyticsEntity::INTEGRATION_VERSION] = '3.1.2';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic, 'testPaymentAnalyticsOtp');
    }

    public function testDataForUserAgentAnomaly()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer = [
                            'HTTP_USER_AGENT'   => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
                        ];

        $payment['_'][AnalyticsEntity::BROWSER] = 'safari';

        $payment['_'][AnalyticsEntity::PLATFORM_VERSION] = '537.36';

        $payment['_'][AnalyticsEntity::OS] = 'ios';

        $payment['_'][AnalyticsEntity::OS_VERSION] = '11.0';

        $payment['_'][AnalyticsEntity::DEVICE] = 'mobile';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic, 'testDataForUserAgentAnomaly');
    }
}
