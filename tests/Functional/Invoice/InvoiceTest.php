<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class InvoiceTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['invoice']);

        $this->ba->privateAuth();
    }

    public function testCreateInvoiceWithNewCustomer()
    {
        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $customer = $this->getLastEntity('customer', true);

        $this->assertEquals($customer['id'], $response['customer_id']);
        $this->assertEquals('10000000000000', $customer['merchant_id']);
    }

    public function testCreateInvoiceWithExistingCustomer()
    {
        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $this->assertEquals('cust_100000customer', $response['customer_id']);
    }

    public function testCreateInvoiceWithMultipleLineItems()
    {
        $response = $this->startTest();

        $order = $this->getLastEntity('order', true);

        $this->assertEquals(500000, $order['amount']);
        $this->assertEquals('created', $order['status']);
        $this->assertEquals('cust_100000customer', $response['customer_id']);

        $lineItems = $this->getEntities('line_item', [], true);

        $this->assertEquals(2, $lineItems['count']);

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['invoice_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][1]['invoice_id']);
    }

    public function testCreateInvoiceWithSmsNotifyFalseAndEmailNotifyTrue()
    {
        $this->startTest();
    }

    protected function assertInvoiceCreateResponse(array $response)
    {
        $order = $this->getLastEntity('order', true);

        $invoice = $this->getLastEntity('invoice', true);
        $lineItem = $this->getLastEntity('line_item', true);

        $this->assertEquals($order['id'], $response['order_id']);
        $this->assertEquals($order['payment_capture'], true);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItem['invoice_id']);
        $this->assertContains('http://bit.ly/', $invoice['short_url']);
        $this->assertEquals('10000000000000', $invoice['merchant_id']);
    }
}
