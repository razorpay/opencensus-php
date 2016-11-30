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

    public function testCreateDraftInvoiceWithNoData()
    {
        $this->startTest();

        $order = $this->getLastEntity('order', true);
        $this->assertNull($order);
    }

    public function testCreateDraftInvoiceWithSomeData()
    {
        $response = $this->startTest();

        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['customer_id']);
        $this->assertNotEmpty($response['line_items'][0]['id']);
        $this->assertNotEmpty($response['line_items'][0]['item_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertNull($order);
    }

    public function testCreateDraftInvoiceAndView()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testCreateIssuedInvoice()
    {
        $response = $this->startTest();

        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['customer_id']);
        $this->assertNotEmpty($response['line_items'][0]['id']);
        $this->assertNotEmpty($response['line_items'][0]['item_id']);
        $this->assertNotEmpty($response['order_id']);
        $this->assertNotEmpty($response['short_url']);

        $order = $this->getLastEntity('order', true);
        $this->assertNotNull($order);
    }

    public function testUpdateDraftInvoiceWithBasicFields()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceAmountWhenLineItemsExists()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithLineItems()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithCustomerId()
    {
        $this->createDraftInvoice();

        $this->fixtures->create(
            'customer',
            [
                'id'      => '100001customer',
                'name'    => 'test 2',
                'email'   => 'test2@razorpay.com',
                'contact' => null,
            ]
        );

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithCustomerDetails()
    {
        $this->createDraftInvoice();

        $response = $this->startTest();

        $customer = $this->getLastEntity('customer', true);
        $this->assertEquals($customer['id'], $response['customer_id']);
    }

    public function testUpdateDraftInvoiceWithCustomerIdAndDetails()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateIssuedInvoice()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testUpdateIssuedInvoiceWithExtraFields()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testIssueInvoiceWithAmountAndDesc()
    {
        $this->fixtures->create(
            'invoice',
            [
                'status'    => 'draft',
                'order_id'  => null,
                'short_url' => null,
                'description' => 'For test item'
            ]
        );

        $response = $this->startTest();

        $this->assertNotEmpty($response['short_url']);
        $this->assertNotEmpty($response['order_id']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['id'], $response['order_id']);
        $this->assertEquals($order['amount'], $response['amount']);
    }

    public function testIssueInvoiceWithLineItems()
    {
        $this->fixtures->create(
            'invoice',
            [
                'status'    => 'draft',
                'order_id'  => null,
                'short_url' => null,
                'amount'    => 200000,
            ]
        );

        $this->fixtures->create('item');
        $this->fixtures->create('line_item', ['quantity' => 2]);

        $response = $this->startTest();

        $this->assertNotEmpty($response['short_url']);
        $this->assertNotEmpty($response['order_id']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['id'], $response['order_id']);
        $this->assertEquals($order['amount'], $response['amount']);
        $this->assertEquals(200000, $order['amount']);
    }

    public function testIssueInvoiceWithFailingData()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }
    
    public function testDeleteDraftInvoice()
    {
        $this->createDraftInvoice();

        $this->startTest();

        $invoice = $this->getLastEntity('invoice');
        $this->assertNull($invoice);
    }

    public function testDeleteIssuedInvoice()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice');

        $this->startTest();

        $invoice = $this->getLastEntity('invoice');
        $this->assertNotNull($invoice);
    }

    public function testAddLineItemToInvoice()
    {
        $this->createDraftInvoice();

        $response = $this->startTest();

        // ----

        // Replaying the same request with new line item content
        // Response:
        // - Should have new line item
        // - Updated amount data

        $this->fixtures->create('item');

        $testData = $this->testData[__FUNCTION__];

        $lineItem2 = [
            'item_id'  => 'item_1000000000item',
            'quantity' => 2,
        ];

        $testData['request']['content'] = $lineItem2;

        $testData['response']['content']['line_items'][] =[
            'quantity'         => 2,
            'name'             => 'Some item name',
            'description'      => 'Some item description',
            'amount'           => 100000,
            'currency'         => 'INR'
        ];

        $testData['response']['content']['amount'] += 200000;

        $response = $this->startTest($testData);
    }

    public function testAddLineItemToInvoiceWithBadData()
    {
        $this->createDraftInvoice();

        $response = $this->startTest();
    }

    public function testAddLineItemToIssuedInvoice()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testUpdateLineItemOfInvoice()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(1, $lineItems['count']);

        $items = $this->getEntities('item', [], true);
        $this->assertEquals(1, $items['count']);
    }

    public function testUpdateLineItemOfInvoiceWithNewItemData()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();

        // Above creates new item and associates new one with exisitng line item
        // Asserting if it's success

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(1, $lineItems['count']);

        $items = $this->getEntities('item', [], true);
        $this->assertEquals(2, $items['count']);
    }

    public function testUpdateLineItemOfInvoiceWithExistingItem()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->fixtures->create(
            'item',
            [
                'id'     => '1000000001item',
                'amount' => 5000
            ]
        );

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(1, $lineItems['count']);

        $items = $this->getEntities('item', [], true);
        $this->assertEquals(2, $items['count']);
    }

    public function testUpdateLineItemOfInvoiceWithBadData()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testUpdateLineItemOfIssuedInvoice()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice');
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testRemoveLineItemOfInvoice()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(0, $lineItems['count']);
    }

    public function testRemoveLineItemOfIssuedInvoice()
    {
        $this->fixtures->create('order', ['id' => '100000000order']);

        $this->fixtures->create('invoice');
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

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

        $this->fixtures->create('item', ['currency' => 'USD']);

        $this->fixtures->create('invoice', ['receipt' => '00000000000001']);

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

    // -------------------- Protected methods --------------------

    protected function assertInvoiceCreateResponse(array $response)
    {
        $order = $this->getLastEntity('order', true);

        $invoice = $this->getLastEntity('invoice', true);
        $lineItem = $this->getLastEntity('line_item', true);

        $this->assertEquals($order['id'], $response['order_id']);
        $this->assertEquals($order['payment_capture'], true);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItem['entity_id']);
        $this->assertContains('http://bitly.dev/', $invoice['short_url']);
        $this->assertEquals('10000000000000', $invoice['merchant_id']);
    }

    protected function createDraftInvoice()
    {
        $this->fixtures->create(
            'invoice',
            [
                'status'       => 'draft',
                'order_id'     => null,
                'short_url'    => null,
                'amount'       => 0,
                'sms_status'   => 'pending',
                'email_status' => 'pending',
            ]
        );
    }
}
