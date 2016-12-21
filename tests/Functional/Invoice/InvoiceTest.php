<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

use Carbon\Carbon;
use Mockery;

class InvoiceTest extends TestCase
{
    use PaymentTrait;

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
        $this->assertArrayNotHasKey('user_id', $response);

        // Asserts if have assigned default value to invoices.date
        $this->assertNotNull($response['date']);
    }

    public function testCreateInvoiceWithExistingCustomer()
    {
        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $this->assertEquals('cust_100000customer', $response['customer_id']);
    }

    public function testCreateInvoiceAndPay()
    {
        $order = $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $payment = $this->doAuthAndCapturePayment($payment);

        $order = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($payment['id'], $invoice['payment_id']);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($invoice['status'], 'paid');
        $this->assertEquals($invoice['id'], $payment['invoice_id']);
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

        $items = $this->getEntities('item', [], true);
        $this->assertEquals(2, $items['count']);

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['entity_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][1]['entity_id']);

        $this->assertEquals($items['items'][0]['id'], $lineItems['items'][0]['item_id']);
        $this->assertEquals($items['items'][1]['id'], $lineItems['items'][1]['item_id']);
    }

    public function testCreateInvoiceWithMultipleLineItemsAndUsingExistingItem()
    {
        $this->fixtures->create('item');

        $response = $this->startTest();

        $order = $this->getLastEntity('order', true);

        $this->assertEquals(600000, $order['amount']);
        $this->assertEquals('created', $order['status']);
        $this->assertEquals('cust_100000customer', $response['customer_id']);

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(2, $lineItems['count']);

        $items = $this->getEntities('item', [], true);
        $this->assertEquals(2, $items['count']);

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['entity_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][1]['entity_id']);

        $this->assertEquals($items['items'][0]['id'], $lineItems['items'][0]['item_id']);
        $this->assertEquals($items['items'][1]['id'], $lineItems['items'][1]['item_id']);
    }

    public function testCreateInvoiceWithNewCustomerAndAddress()
    {
        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $this->assertNotNull($response['customer_details']['customer_address']);

        $address = $this->getLastEntity('address', true);

        $this->assertEquals('shipping_address', $address['type']);
        $this->assertEquals('1', $address['primary']);
        $this->assertEquals($response['customer_id'], $address['entity_id']);
        $this->assertEquals('customer', $address['entity_type']);
    }

    public function testCreateInvoiceWithSmsNotifyFalseAndEmailNotifyTrue()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithDuplicateMerchantRefId()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice', ['receipt' => '00000000000001']);

        $this->startTest();
    }

    public function testCreateInvoiceWithMultipleLineItemsAndDifferentCurrency()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice', ['receipt' => '00000000000001']);

        $this->startTest();
    }

    public function testCreateInvoiceWithMultipleLineItemsAndDifferentCurrency2()
    {
        // Usage one existing item with different currency
        $this->fixtures->create('item', ['currency' => 'USD']);

        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testCreateInvoiceWithoutLineItemsWithAmountAndDesc()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithoutLineItemsWithAmount()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithoutLineItemsAmountAndDesc()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithLineItemsAmountAndDesc()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithNullCurrency()
    {
        $this->startTest();
    }

    public function testGetInvoice()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice');

        $this->fixtures->create('item');

        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testGetInvoiceByReceipt()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice');
        $this->fixtures->create(
            'invoice',
            [
                'id'      => '1000001invoice',
                'receipt' => '00000000000001',
            ]
        );
        $this->fixtures->create(
            'invoice',
            [
                'id'      => '1000002invoice',
                'receipt' => '00000000000002',
            ]
        );

        $this->startTest();
    }

    public function testGetMultipleInvoices()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);
        $this->fixtures->create('order', ['id' => '10000000order2']);

        $invoice1 = $this->fixtures->create('invoice', ['order_id' => '100000000order']);
        $invoice2 = $this->fixtures->create('invoice', ['id' => '100000invoice2', 'order_id' => '10000000order2']);

        $item1 = $this->fixtures->create('item');
        $item2 = $this->fixtures->create('item', ['id' => '1000000001item', 'name' => 'Item 2']);

        $this->fixtures->create('line_item', ['entity_id' => $invoice1->getId()]);
        $this->fixtures->create('line_item', ['id' => '10000lineitem2', 'entity_id' => $invoice2->getId(), 'item_id' => $item2->getId()]);

        $this->startTest();
    }

    public function testGetInvoicesOfCapturedPaymentId()
    {
        $order = $this->fixtures->create('order', ['id' => '100000000order']);

        $invoice = $this->fixtures->create('invoice', ['order_id' => '100000000order']);

        $this->fixtures->create('item');

        $this->fixtures->create('line_item', ['entity_id' => $invoice->getId()]);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $payment = $this->doAuthAndCapturePayment($payment);

        $order = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($payment['id'], $invoice['payment_id']);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($invoice['status'], 'paid');

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'] = ['payment_id' => $payment['id']];

        $this->ba->privateAuth();
        $response = $this->startTest($testData);

        $this->assertEquals(1, count($response['items']));
        $this->assertEquals($payment['id'], $response['items'][0]['payment_id']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);
    }

    public function testGetInvoicesAfterCreatingMultipleInvoicesAndPaying()
    {
        $order1 = $this->fixtures->create('order', ['id' => '100000000order']);
        $order2 = $this->fixtures->create('order', ['id' => '10000000order2']);

        $invoice1 = $this->fixtures->create('invoice', ['order_id' => '100000000order']);
        $invoice2 = $this->fixtures->create('invoice', ['id' => '100000invoice2', 'order_id' => '10000000order2']);

        $payment1 = $this->getDefaultPaymentArray();
        $payment2 = $this->getDefaultPaymentArray();

        $payment1['order_id'] = $order1->getPublicId();
        $payment2['order_id'] = $order2->getPublicId();

        $payment1['amount'] = $order1->getAmount();
        $payment2['amount'] = $order2->getAmount();

        $payment1 = $this->doAuthAndCapturePayment($payment1);
        $payment2 = $this->doAuthAndCapturePayment($payment2);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'] = ['payment_id' => $payment1['id']];

        $this->ba->privateAuth();
        $response = $this->startTest($testData);

        $this->assertEquals(1, count($response['items']));
        $this->assertEquals($payment1['id'], $response['items'][0]['payment_id']);
        $this->assertEquals($invoice1->getPublicId(), $response['items'][0]['id']);

        $testData['request']['content'] = ['payment_id' => $payment2['id']];

        $response = $this->startTest($testData);

        $this->assertEquals(1, count($response['items']));
        $this->assertEquals($payment2['id'], $response['items'][0]['payment_id']);
        $this->assertEquals($invoice2->getPublicId(), $response['items'][0]['id']);
    }

    public function testGetInvoiceStatus()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('order', ['id' => '100000000order']);
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testGetInvoiceStatusAfterPayment()
    {
        $order = $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $capturedPayment = $this->doAuthAndCapturePayment($payment);

        $order = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($capturedPayment['id'], $invoice['payment_id']);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($invoice['status'], 'paid');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals($capturedPayment['id'], $response['razorpay_payment_id']);
    }

    public function testGetInvoiceStatusAfterOneWeek()
    {
        $this->markTestSkipped();

        $this->ba->publicAuth();

        $this->fixtures->create('order', ['id' => '100000000order']);
        $this->fixtures->create('invoice');

        $currentTime = Carbon::now('Asia/Kolkata');
        $currentTime->addDays(18);
        Carbon::setTestNow($currentTime);

        $this->startTest();

        // Clear the mock.
        Carbon::setTestNow();
    }

    public function testSendNotificationWithSmsMode()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('order', ['id' => '100000000order']);
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testSendNotificationWithInvalidMode()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('order', ['id' => '100000000order']);
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    protected function assertInvoiceCreateResponse(array $response)
    {
        $order = $this->getLastEntity('order', true);

        $invoice = $this->getLastEntity('invoice', true);
        $lineItem = $this->getLastEntity('line_item', true);

        $this->assertEquals($order['id'], $response['order_id']);
        $this->assertEquals($order['payment_capture'], true);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItem['entity_id']);
        $this->assertContains('http://dwarf.razorpay.dev/', $invoice['short_url']);
        $this->assertEquals('10000000000000', $invoice['merchant_id']);
    }
}
