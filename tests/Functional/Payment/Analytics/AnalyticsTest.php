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

        $this->assertEquals($checkoutId,
            $paymentAnalytic[AnalyticsEntity::CHECKOUT_ID]);

        $this->assertEquals(1, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);

        // ------------------------------------------------------------------//

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

        // // -------------------------------------------------------------- //
        // // TODO: Find a way to fail the first attempt
        // // Second payment attempt
        // $payment = $this->getDefaultPaymentArray();
        // $payment['order_id'] = $order['id'];
        // $rzpPayment = $this->doAuthPayment($payment);

        // $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);
        // $this->assertEquals(2, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);
    }

    public function testAttemptsWithoutCheckoutIdOrOrderId()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthPayment($payment);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertNull($paymentAnalytic[AnalyticsEntity::CHECKOUT_ID]);

        $this->assertEquals(1, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);
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

        // @codingStandardsIgnoreStart
        $requestServer = [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36',
            'HTTP_REFERER'    => 'https://pay.com/demo'
        ];
        // @codingStandardsIgnoreEnd

        $payment['_']['library'] = 'checkoutjs';

        $payment['_']['library_version'] = '3846fgjb';

        $payment['_']['integration'] = 'woo_commerce';

        $payment['_']['integration_version'] = '0.1.2';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic);
    }

    public function testHttpRequestDataForS2sPayments()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer = [
            'HTTP_USER_AGENT' => 'Razorpay UA',
            'HTTP_REFERER'    => 'https://pay.com/demo'
        ];

        $payment['_']['library'] = 'direct';

        $payment['_']['device'] = 'desktop';

        $this->fixtures->merchant->addFeatures(['s2s']);
        $payment = $this->doS2SPrivateAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic);
    }

    public function testAnalyticsForS2sPayments()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['ip']         = '52.34.123.23';
        $payment['referer']    = 'https://pay.com/demo';
        $payment['user_agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

        $payment['_'] = [
            'library'    => 'direct',
            'device'     => 'desktop',
        ];

        $this->fixtures->merchant->addFeatures(['s2s']);
        $payment = $this->doS2SPrivateAuthPayment($payment);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic);
    }

    public function testHttpRequestDataForOtpBasedPayment()
    {
        $this->sharedTerminal = $this->fixtures->create(
            'terminal:shared_mobikwik_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mobikwik';

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->setMockGatewayTrue();

        $payment = $this->getDefaultWalletPaymentArray('mobikwik');

        // @codingStandardsIgnoreStart
        $requestServer = [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
            'HTTP_REFERER'    => 'https://pay.com/demo'
        ];
        // @codingStandardsIgnoreEnd

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

        // @codingStandardsIgnoreStart
        $requestServer = [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
            'HTTP_REFERER'    => 'https://api.razorpay.com/demo'
        ];
        // @codingStandardsIgnoreEnd

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

        $payment['_']['os'] = 'unknown_os';

        $payment['_']['device'] = 'unknown_device';

        $payment = $this->doAuthPayment($payment);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertTestResponse($paymentAnalytic, 'testHttpRequestDataForInvalidData');
    }

    public function testOs1()
    {
        $payment = $this->getDefaultPaymentArray();

        // @codingStandardsIgnoreStart
        $requestServer = [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac androidos 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
        ];
        // @codingStandardsIgnoreEnd

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals(Metadata::ANDROID,
            $paymentAnalytic[AnalyticsEntity::OS]);
    }

    public function testOs2()
    {
        $payment = $this->getDefaultPaymentArray();

        // @codingStandardsIgnoreStart
        $requestServer = [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0',
        ];
        // @codingStandardsIgnoreEnd

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals(Metadata::UBUNTU,
            $paymentAnalytic[AnalyticsEntity::OS]);
    }

    public function testMozilla()
    {
        $payment = $this->getDefaultPaymentArray();

        // @codingStandardsIgnoreStart
        $requestServer = [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 10_2 like Mac OS X) AppleWebKit/602.3.12 (KHTML, like Gecko) Firefox/46.0',
        ];
        // @codingStandardsIgnoreEnd

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals(Metadata::FIREFOX, $paymentAnalytic[AnalyticsEntity::BROWSER]);

        $this->assertEquals('46.0', $paymentAnalytic[AnalyticsEntity::BROWSER_VERSION]);
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

    public function testRiskScoreAnalyticsPaymentSuccess()
    {
        $this->mockMaxmind();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->fixtures->merchant->enableInternational();
        $this->fixtures->iin->create([
            'iin'     => '555555',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $requestServer['HTTP_REFERER'] = 'https://hello.com';

        $payment = $this->doAuthPayment($payment, $requestServer);
        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals((float) 2.4, $paymentAnalytic[AnalyticsEntity::RISK_SCORE]);
        $this->assertEquals('maxmind', $paymentAnalytic[AnalyticsEntity::RISK_ENGINE]);
    }

    public function testRiskScoreAnalyticsPaymentFailed()
    {
        $this->mockMaxmind();

        $this->fixtures->merchant->enableInternational();
        $this->fixtures->create('terminal:shared_sharp_terminal');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4012010000000007';

        $requestServer['HTTP_REFERER'] = 'https://hello.com';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment, $requestServer)
        {
            $this->doAuthPayment($payment, $requestServer);
        });

        $paymentAnalytic = $this->getLastEntity(E::PAYMENT_ANALYTICS, true);

        $this->assertEquals((float) 15.3, $paymentAnalytic[AnalyticsEntity::RISK_SCORE]);
        $this->assertEquals('maxmind', $paymentAnalytic[AnalyticsEntity::RISK_ENGINE]);

        // Increases risk threshold for merchant from 5 to 15
        $this->fixtures->merchant->edit('10000000000000', ['risk_threshold' => '16']);
        $this->doAuthPayment($payment, $requestServer);
    }

    public function testAnalyticsCountOnSuccessfulPayment()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer['HTTP_REFERER'] = 'https://hello.com';

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytics = $this->getEntities(E::PAYMENT_ANALYTICS, ['payment_id' => $payment['razorpay_payment_id']], true);

        $this->assertEquals(1, $paymentAnalytics['count']);
    }

    public function testAnalyticsCountOnFailedPayment()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer['HTTP_REFERER'] = 'https://hello.com';

        $this->gateway = 'hdfc';

        $this->hdfcPaymentMockResultCode('DENIED BY RISK', 'authorize');
        // For 3dsecure case
        $this->makeRequestAndCatchException(function () use ($payment, $requestServer)
        {
            $payment['card']['number'] = '4012001037490014';

            $payment = $this->doAuthPayment($payment, $requestServer);
        });

        $payment = $this->getLastEntity(E::PAYMENT, true);

        $paymentAnalytics = $this->getEntities(E::PAYMENT_ANALYTICS, ['payment_id' => $payment['id']], true);

        $this->assertEquals(1, $paymentAnalytics['count']);
    }
}
