<?php

namespace Functional\Invoice;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class CheckoutInvoiceTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/CheckoutInvoiceTestData.php';

        parent::setUp();
    }

    public function testGetInvoiceDetailsForCheckout(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $order = $this->fixtures->create('order');

        $invoice = $this->fixtures->create('invoice', ["order_id" => $order->getId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/internal/invoices/checkout/' . $invoice->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($order->getPublicId(), $response['invoice']['order_id']);
    }

    public function testGetInvoiceDetailsForCheckoutByInvoiceIdInQueryParams(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $order = $this->fixtures->create('order');

        $invoice = $this->fixtures->create('invoice', ["order_id" => $order->getId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['invoice_id'] = $invoice->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($order->getPublicId(), $response['invoice']['order_id']);
    }

    public function testGetInvoiceDetailsForCheckoutBySubscriptionId(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $order = $this->fixtures->create('order');

        $subscriptionId = "abcdefg1234567";
        $invoice = $this->fixtures->create('invoice', [
            "order_id" => $order->getId(),
            "subscription_id" => $subscriptionId
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['subscription_id'] = $subscriptionId;

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($order->getPublicId(), $response['invoice']['order_id']);
    }
}
