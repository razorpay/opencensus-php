<?php

namespace RZP\Tests\Functional\PaymentLink;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PaymentLinkTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaymentLinkTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreatePaymentLink()
    {
        $this->startTest();
    }

    public function testFetchPaymentLink()
    {
        $testData = & $this->testData[__FUNCTION__];

        $paymentLink = $this->fixtures->create('payment_link');

        $paymentLinkId = $paymentLink->getPublicId();

        $url = $testData['request']['url'];

        $url = sprintf($url, $paymentLinkId);

        $testData['request']['url'] = $url;

        $this->startTest();
    }

    public function testFetchPaymentLinks()
    {
        $this->fixtures->create('payment_link');

        $this->startTest();
    }

    public function testUpdatePaymentLink()
    {
        $testData = & $this->testData[__FUNCTION__];

        $paymentLink = $this->fixtures->create('payment_link');

        $paymentLinkId = $paymentLink->getPublicId();

        $url = $testData['request']['url'];

        $url = sprintf($url, $paymentLinkId);

        $testData['request']['url'] = $url;

        $this->startTest();
    }

    public function testFetchPaymentLinkPayments()
    {
        $testData = & $this->testData[__FUNCTION__];

        $paymentLink = $this->fixtures->create('payment_link');

        $paymentLinkId = $paymentLink->getPublicId();

        $url = $testData['request']['url'];

        $url = sprintf($url, $paymentLinkId);

        $testData['request']['url'] = $url;

        $this->startTest();
    }
}
