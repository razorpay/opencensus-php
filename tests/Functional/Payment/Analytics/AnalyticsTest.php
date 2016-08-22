<?php

namespace RZP\Tests\Functional\Payment\Analytics;

use RZP\Constants\Table;
use RZP\Constants\HttpRequestHeader;
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

        $payment['_']['checkout_id'] = $checkoutId;

        $payment = $this->doAuthPayment($payment);
        $paymentAnalytic = $this->getLastEntity(Table::PAYMENT_ANALYTICS, true);

        $this->assertEquals($checkoutId, $paymentAnalytic[AnalyticsEntity::CHECKOUT_ID]);
        $this->assertEquals(1, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);

        // ------------------------------------------------------------------ //

        $payment = $this->getDefaultPaymentArray();
        $payment['_']['checkout_id'] = $checkoutId;

        $payment = $this->doAuthPayment($payment);
        $paymentAnalytic = $this->getLastEntity(Table::PAYMENT_ANALYTICS, true);

        $this->assertEquals($checkoutId, $paymentAnalytic[AnalyticsEntity::CHECKOUT_ID]);
        $this->assertEquals(2, $paymentAnalytic[AnalyticsEntity::ATTEMPTS]);
    }

    public function testHttpRequestData()
    {
        $payment = $this->getDefaultPaymentArray();

        $requestServer = [
                            'HTTP_USER_AGENT'   => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36',
                            'HTTP_REFERER'      => 'https://razorpay.com/demo'
                        ];

        $payment = $this->doAuthPayment($payment, $requestServer);

        $paymentAnalytic = $this->getLastEntity(Table::PAYMENT_ANALYTICS, true);
        // s($paymentAnalytic);
        $this->assertTestResponse($paymentAnalytic, 'testPaymentAnalytics');
    }
}
