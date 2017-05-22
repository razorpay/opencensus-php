<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;

use Carbon\Carbon;
use Mockery;

class InvoiceTest extends TestCase
{
    use InvoiceTestTrait;
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceTestData.php';

        parent::setUp();

        // Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                 => '10000000000000',
                'business_registered_address' => '#1205, Rzp, Outer Ring Road, Bangalore',
            ]);

        $this->ba->proxyAuth();
    }

    // ------------------------------------------------------------
    // Tests around creation and payment of invoice
    // ------------------------------------------------------------

    public function testCreateInvoiceWithNewCustomer()
    {
        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $customer = $this->getLastEntity('customer', true);

        $this->assertEquals($customer['id'], $response['customer_id']);
        $this->assertEquals('10000000000000', $customer['merchant_id']);

        // Asserts if have assigned default value to invoices.date
        $this->assertNotNull($response['date']);

        //
        // Asserts expire_by's default value. Must be set to 60 days from
        // created_at.
        //
        $this->assertNotNull($response['expire_by']);
        $this->assertInternalType('int', $response['expire_by']);

        //
        // Use delta of 5 secs to avoid random failures of tests
        //
        $this->assertEquals(
            5184000,
            $response['expire_by'] - $response['created_at'],
            'expire_by should be by default 60 days in future',
            5
        );
    }

    public function testCreateInvoiceWithExistingCustomer()
    {
        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $this->assertEquals('cust_100000customer', $response['customer_id']);
    }

    public function testCreateInvoiceAndPay()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());
    }

    public function testCreateLinkWithSource()
    {
        $this->startTest();
    }

    public function testCreateLinkWithInvalidSource()
    {
        //
        // TODO: (Low priority)
        // - Fix Source::checkType and Type::validateType methods.
        //
    }

    public function testCreateLinkWithTooLargeAmount()
    {
        $this->startTest();
    }

    public function testCreateLinkAndPayAndCheckCustomerDetailsInInvoice()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice',
            [
                'customer_id'      => null,
                'customer_name'    => null,
                'customer_email'   => null,
                'customer_contact' => null,
                'type'             => 'link',
            ]);

        //
        // While making payment, we pull customer data from payment and fill in
        // invoice columns.
        //

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($invoice['customer']['email'], 'a@b.com');
        $this->assertEquals($invoice['customer']['contact'], '+919918899029');
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

        //
        // Now, item wouldn't be getting created via line_items. line_items has
        // item related fields and the sent input will be consumed there. item_id
        // for all such line_items will be null.
        // Keeping this one test to just ensure that, as preeviously it used
        // to happen.
        //
        $items = $this->getEntities('item', [], true);
        $this->assertEquals(0, $items['count']);

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['entity_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][1]['entity_id']);
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

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['entity_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][1]['entity_id']);
    }

    public function testCreateInvoiceWithUsingInactiveItem()
    {
        $this->fixtures->create('item', ['active' => 0]);

        $response = $this->startTest();
    }

    public function testCreateInvoiceWithNewCustomerAndAddress()
    {
        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $address = $this->getLastEntity('address', true);

        $this->assertEquals('billing_address', $address['type']);
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
        //
        // This asserts Invoice.getPaymentIdAttribute is working fine.
        //
        $this->fixtures->create('payment:captured');

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

        $order = $this->getLastEntity('order', true);
        $this->assertNull($order);
    }

    public function testCreateDraftInvoiceAndView()
    {
        $skipReason = 'View endpoint will now not throw exception.
                      It will serve error page.
                      Will remove this if required later.';

        $this->markTestSkipped($skipReason);

        $this->createDraftInvoice();

        $this->startTest();
    }

    /**
     * Creates invoice with few line items such that the total invoice amount
     * exceeds the allowed payment amount for merchant.
     *
     * @return
     */
    public function testCreateDraftInvoiceWithLineItemsAndMaxAllowedAmount()
    {
        $this->startTest();
    }

    public function testInvoiceViewWithExpiredInvoice()
    {
        $skipReason = 'View endpoint will now not throw exception.
                      It will serve error page.
                      Will remove this if required later.';

        $this->markTestSkipped($skipReason);

        $this->fixtures->create('invoice',
            [
                'status'   => 'expired',
                'order_id' => null
            ]);

        $this->startTest();
    }

    public function testCreateIssuedInvoice()
    {
        $response = $this->startTest();

        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['customer_id']);
        $this->assertNotEmpty($response['line_items'][0]['id']);
        $this->assertNotEmpty($response['order_id']);
        $this->assertNotEmpty($response['short_url']);

        $order = $this->getLastEntity('order', true);
        $this->assertNotNull($order);
    }

    public function testCreateIssuedInvoiceAndPay()
    {
        $testData = $this->testData['testCreateIssuedInvoice'];
        $response = $this->startTest($testData);

        $order = $this->getLastEntity('order', true);
        $this->assertNotNull($order);
        $this->assertEquals($order['id'], $response['order_id']);

        $this->makePaymentForInvoiceAndAssert($response);
    }

    public function testCreateInvoiceWithDuplicateMerchantRefId()
    {
        $this->createOrder();

        $this->fixtures->create('invoice', ['receipt' => '00000000000001']);

        $this->startTest();
    }

    public function testCreateInvoiceWithMultipleLineItemsAndDifferentCurrency()
    {
        $skipReason = 'Only allowed currency is INR in validators of invoice, line_item and item for now.';

        $this->markTestSkipped($skipReason);

        $this->createOrder();

        $this->fixtures->create('item', ['currency' => 'USD']);

        $this->fixtures->create('invoice', ['receipt' => '00000000000001']);

        $this->startTest();
    }

    public function testCreateDraftLinkWithAmountAndDesc()
    {
        $this->startTest();
    }

    public function testCreateIssuedLinkWithAmountAndDesc()
    {
        $this->startTest();
    }

    public function testCreateDraftLinkWithAmount()
    {
        $this->startTest();
    }

    public function testCreateIssuedLinkWithAmount()
    {
        $this->startTest();
    }

    public function testCreateIssuedLinkWithoutLineItemsAmount()
    {
        $this->startTest();
    }

    public function testCreateDraftLinkWithLineItemsAndAmount()
    {
        $this->startTest();
    }

    public function testCreateIssuedInvoiceWithLineItemsAndAmount()
    {
        $this->startTest($this->testData['testCreateDraftLinkWithLineItemsAndAmount']);
    }

    public function testCreateInvoiceWithNullCurrency()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithAmount()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithBadExpiredBy()
    {
        $this->startTest();
    }

    public function testCreateInvoiceAndAssertEsSync()
    {
        $esMock = $this->createEsMock(['bulkUpdate']);

        //
        // For the first time, it will createIndex as indexExists will return false.
        // Asserting all of it.
        //

        $expected = $this->getExpectedUpsertIndexParams();

        $esMock->expects($this->once())
               ->method('bulkUpdate')
               ->with(
                    $this->callback(
                        function ($actual) use ($expected)
                        {
                            $this->assertArraySelectiveEquals($expected, $actual);

                            $this->assertNotEmpty($actual['body'][0]['index']['_id']);
                            $this->assertNotEmpty($actual['body'][1]['id']);

                            return true;
                        }));

        $this->startTest();
    }



    // ------------------------------------------------------------
    // Tests around updation of invoice
    // ------------------------------------------------------------

    public function testUpdateDraftInvoiceWithAmount()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithBasicFields()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateDraftInvoiceAndIssue()
    {
        $this->createDraftInvoice(['amount' => 100000]);

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        //
        // On sending 'draft'='0', it should just issue the invoice
        //

        $response = $this->startTest();

        $this->assertNotEmpty($response['short_url']);
        $this->assertNotEmpty($response['order_id']);
        $this->assertNotEmpty($response['issued_at']);
    }

    public function testUpdateDraftInvoiceWithBasicFieldsAndLineItems()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->fixtures->create('item', ['id' => '1000000001item']);
        $this->fixtures->create('line_item', ['id' => '100001lineitem', 'item_id' => '1000000001item']);

        $this->fixtures->create('item', ['id' => '1000000002item']);
        $this->fixtures->create('line_item', ['id' => '100002lineitem', 'item_id' => '1000000002item']);

        $this->fixtures->create('item', ['id' => '1000000003item']);
        $this->fixtures->create('line_item', ['id' => '100003lineitem', 'item_id' => '1000000003item']);

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(4, $lineItems['count']);

        $lineItemIds = collect($lineItems['items'])->pluck('id')->all();

        $this->assertNotContains('1000000002item', $lineItemIds);
        $this->assertNotContains('1000000003item', $lineItemIds);

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateDraftInvoiceWithLineItemsTooLargeAmount()
    {
        $this->testAddManyLineItemsToInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceAmountWhenLineItemsExists()
    {
        $this->createDraftInvoice(['type' => 'link']);

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

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

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateDraftInvoiceWithCustomerDetails()
    {
        $this->createDraftInvoice();

        $response = $this->startTest();

        $customer = $this->getLastEntity('customer', true);
        $this->assertEquals($customer['id'], $response['customer_id']);

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateDraftInvoiceWithCustomerIdAndDetails()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateIssuedInvoice()
    {
        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateIssuedInvoiceWithExtraFields()
    {
        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testUpdateInvoiceAndAssertEsSync()
    {
        $invoice = $this->createDraftInvoice();

        $esMock = $this->createEsMock(['bulkUpdate']);

        $expected = $this->getExpectedUpsertIndexParams(
            [
                'id'      => $invoice->getId(),
                'receipt' => 'inv_receipt_0001',
                'terms'   => 'Updated terms & conditions',
            ]);

        $esMock->expects($this->once())
               ->method('bulkUpdate')
               ->with(
                    $this->callback(
                        function ($actual) use ($expected)
                        {
                            $this->assertArraySelectiveEquals($expected, $actual);

                            return true;
                        }));

        $this->startTest();
    }

    public function testUpdateInvoiceAndAssertEsNoSync()
    {
        //
        // Case:
        // When dirtied fields are not in index, es sync must not happen
        // unnecessarily.
        //

        $invoice = $this->createDraftInvoice();

        $esMock = $this->createEsMock(['bulkUpdate']);

        $esMock->expects($this->never())
               ->method('bulkUpdate');

        $this->startTest();
    }

    public function testIssueInvoiceWithAmountAndDesc()
    {
        $this->fixtures->create(
            'invoice',
            [
                'status'      => 'draft',
                'order_id'    => null,
                'short_url'   => null,
                'description' => 'For test item',
                'type'        => 'link',
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

    public function testIssueInvoiceWithoutLineItems()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testIssueInvoiceWithoutCustomer()
    {
        $this->createDraftInvoice([
                'customer_id'      => null,
                'customer_name'    => null,
                'customer_email'   => null,
                'customer_contact' => null,
            ]);

        $this->fixtures->create('item');
        $this->fixtures->create('line_item', ['quantity' => 2]);

        $this->startTest();
    }

    public function testDeleteInvoice()
    {
        $this->createOrder();

        $this->createDraftInvoice();

        $this->startTest();

        $invoice = $this->getLastEntity('invoice');
        $this->assertNull($invoice);
    }

    public function testDeletePaidInvoice()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $this->startTest();

        $invoice = $this->getLastEntity('invoice');
        $this->assertNotNull($invoice);
    }

    public function testDeleteInvoiceAndAssertEsSync()
    {
        $this->createDraftInvoice();

        $esMock = $this->createEsMock(['delete']);

        $esMock->expects($this->once())
               ->method('delete')
               ->with(
                    [
                        'index' => 'invoice_test',
                        'type'  => 'invoice_test',
                        'id'    => '1000000invoice',
                    ]);

        $testData = $this->testData['testDeleteInvoice'];

        $this->startTest($testData);
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

        $input = [
            [
                'item_id'  => 'item_1000000000item',
                'quantity' => 2,
            ]
        ];

        $testData['request']['content'] = $input;

        $testData['response']['content']['line_items'][] =[
            'quantity'         => 2,
            'name'             => 'Some item name',
            'description'      => 'Some item description',
            'amount'           => 100000,
            'currency'         => 'INR'
        ];

        $testData['response']['content']['amount'] += 200000;

        $this->runRequestResponseFlow($testData);
    }

    public function testAddManyLineItemsToInvoice()
    {
        $this->createDraftInvoice();

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testAddTooManyLineItemsToInvoice()
    {
        $this->createDraftInvoice();

        foreach (range(1, 18) as $i)
        {
            $item = $this->fixtures->create('item',
                [
                    'id' => UniqueIdEntity::generateUniqueId(),
                ]);

            $this->fixtures->create('line_item',
                [
                    'id'      => UniqueIdEntity::generateUniqueId(),
                    'item_id' => $item->getId(),
                ]);
        }

        $this->startTest();

        $invoice = $this->getLastEntity('invoice');

        $this->assertCount(18, $invoice['line_items']);
    }

    public function testAddLineItemToInvoiceWithBadData()
    {
        $this->createDraftInvoice();

        $response = $this->startTest();
    }

    public function testAddManyLineItemsToInvoiceWithBadData()
    {
        $this->createDraftInvoice();

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(0, $lineItems['count']);
    }

    public function testAddLineItemsToIssuedInvoice()
    {
        $this->createOrder();

        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testAddManyLineItemsToIssuedInvoice()
    {
        $this->createOrder();

        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testAddLineItemsToInvoiceAndIssueAndPay()
    {
        // Steps:
        // - Creates a draft invoice
        // - Adds 2 line items to it
        // - Issues the invoice
        // - Makes the payment

        // Re-using some existing test data

        $this->createDraftInvoice();

        $testData = $this->testData['testAddLineItemToInvoice'];

        $response = $this->startTest($testData);

        // Replaying the same request with new line item content

        $this->fixtures->create('item');

        $input = [
            [
                'item_id'  => 'item_1000000000item',
                'quantity' => 2,
            ],
        ];

        $testData['request']['content'] = $input;

        $testData['response']['content']['line_items'][] = [
            'quantity'         => 2,
            'name'             => 'Some item name',
            'description'      => 'Some item description',
            'amount'           => 100000,
            'currency'         => 'INR'
        ];

        $testData['response']['content']['amount'] += 200000;

        $response = $this->startTest($testData);

        // ---
        // Issue the invoice

        $testData = [
            'request' => [
                'url'       => '/invoices/' . $response['id'] . '/issue',
                'method'    => 'post',
                'content'   => [],
            ],
            'response' => [
                'content' => [],
            ]
        ];

        $response = $this->startTest($testData);

        // Make payment to invoice now

        $this->makePaymentForInvoiceAndAssert($response);
    }

    public function testUpdateLineItemOfInvoice()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
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
                'amount' => 5000,
                'name'   => 'A different item',
            ]
        );

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
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
        $this->createOrder();

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

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testRemoveManyLineItemsOfInvoice()
    {
        $this->createDraftInvoice();

        $this->createFewLineItems();

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(1, $lineItems['count']);

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testRemoveManyLineItemsOfInvoiceWithBadData()
    {
        $this->createDraftInvoice();

        $this->createFewLineItems();

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(3, $lineItems['count']);
    }

    public function testRemoveLineItemOfIssuedInvoice()
    {
        $this->createOrder();

        $this->fixtures->create('invoice');
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testRemoveManyLineItemsOfIssuedInvoice()
    {
        $this->createOrder();

        $this->fixtures->create('invoice');

        $this->createFewLineItems();

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(3, $lineItems['count']);
    }

    public function testSendNotificationWithSmsMode()
    {
        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testSendNotificationWithEmailMode()
    {
        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testSendNotificationWithSmsModeForDraftInvoice()
    {
        $this->ba->publicAuth();

        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testSendNotificationWithInvalidMode()
    {
        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    // ------------------------------------------------------------
    // Tests around get invoice
    // ------------------------------------------------------------

    public function testGetInvoice()
    {
        $this->createOrder();

        $this->fixtures->create('invoice');

        $this->fixtures->create('item');

        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testGetInvoiceByReceipt()
    {
        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(
            [
                'id'       => '1000001invoice',
                'order_id' => $order->getId(),
                'receipt'  => '00000000000001'
            ]);

        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(
            [
                'id'       => '1000002invoice',
                'order_id' => $order->getId(),
                'receipt'  => '00000000000002'
            ]);


        $esMock = $this->createEsMock(['search']);

        $this->setEsMockSearchExpectations(__FUNCTION__, $esMock);

        $this->startTest();
    }

    public function testGetInvoiceByOrderAndPayment()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $payment = $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->privateAuth();

        //
        // Test data usage, query params with sign, and combination for which there
        // is no results.
        //

        $this->startTest();

        //
        // Test data usage, query params without sign, and combination for which
        // there is a result.
        //

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = '100000000order';
        $testData['request']['content']['payment_id'] = $payment['id'];

        $testData['response']['content']['count'] = 1;

        $this->startTest($testData);
    }

    public function testGetMultipleInvoices()
    {
        $this->createOrder();
        $this->createOrder(['id' => '10000000order2']);

        $invoice1 = $this->fixtures->create('invoice', ['order_id' => '100000000order']);
        $invoice2 = $this->fixtures->create('invoice', ['id' => '100000invoice2', 'order_id' => '10000000order2']);

        $item1 = $this->fixtures->create('item');
        $item2 = $this->fixtures->create('item', ['id' => '1000000001item', 'name' => 'Item 2']);

        $this->fixtures->create('line_item', ['entity_id' => $invoice1->getId()]);
        $this->fixtures->create('line_item', ['id' => '10000lineitem2', 'entity_id' => $invoice2->getId(), 'item_id' => $item2->getId()]);

        $this->startTest();
    }

    // -------------------------------------------------------------------------
    // Following tests asserts working of es fetch in various cases.
    //

    public function testGetMultipleInvoicesOnlyEsFields()
    {
        $this->createManyInvoicesForFetchTests();

        $esMock = $this->createEsMock(['search']);

        $this->setEsMockSearchExpectations(__FUNCTION__, $esMock);

        $this->startTest();
    }

    public function testGetMultipleInvoicesByQ()
    {
        $this->createManyInvoicesForFetchTests();

        $esMock = $this->createEsMock(['search']);

        $this->setEsMockSearchExpectations(__FUNCTION__, $esMock);

        $this->startTest();
    }

    public function testGetMultipleInvoicesOnlyMysqlFields()
    {
        $this->createDraftInvoice([
                'id'      => '1000000invoice',
                'user_id' => '1000000000user',
                'type'    => 'link',
            ]);

        $this->createDraftInvoice([
                'id'   => '1000001invoice',
                'type' => 'link',
            ]);

        $this->createDraftInvoice([
                'id'      => '1000002invoice',
                'user_id' => '1000000000user',
                'type'    => 'invoice',
            ]);


        $esMock = $this->createEsMock(['search']);

        $esMock->expects($this->never())
               ->method('search');

        $this->startTest();
    }

    public function testGetMultipleInvoicesMixedFields()
    {
        $this->startTest();
    }

    public function testGetMultipleInvoicesSearchHitsOnly()
    {
        $this->markTestSkipped(
            'Temporarily disabled, waiting for one other pr
            which handles eager loading of relations to go out.');

        $esMock = $this->createEsMock(['search']);

        $this->setEsMockSearchExpectations(__FUNCTION__, $esMock);

        $this->startTest();
    }

    // -------------------------------------------------------------------------

    public function testGetInvoicesOfCapturedPaymentId()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice', ['order_id' => '100000000order']);

        $this->fixtures->create('item');

        $this->fixtures->create('line_item', ['entity_id' => $invoice->getId()]);

        // Serializes invoice entity
        $invoice = $invoice->toArrayPublic();

        $payment = $this->makePaymentForInvoiceAndAssert($invoice);

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
        $order1 = $this->createOrder();

        $invoice1 = $this->fixtures->create('invoice', ['order_id' => '100000000order']);

        $payment1 = $this->makePaymentForInvoiceAndAssert($invoice1->toArrayPublic());

        // -----

        $order2 = $this->createOrder(['id' => '10000000order2']);

        $invoice2 = $this->fixtures->create('invoice', ['id' => '100000invoice2', 'order_id' => '10000000order2']);

        $payment2 = $this->makePaymentForInvoiceAndAssert($invoice2->toArrayPublic());

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

        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testGetInvoiceStatusAfterPayment()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $payment = $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);
    }

    public function testGetInvoiceStatusAfterOneWeek()
    {
        $this->markTestSkipped();

        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $currentTime = Carbon::now('Asia/Kolkata');
        $currentTime->addDays(18);
        Carbon::setTestNow($currentTime);

        $this->startTest();

        // Clear the mock.
        Carbon::setTestNow();
    }

    //
    // Following 2 tests, just test if we're getting OK status for the view endpoint.
    //

    public function testGetLinkView()
    {
        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice', ['type' => 'link']);

        $response = $this->call('GET', '/v1/t/inv_1000000invoice', ['key_id' => $this->ba->getKey()]);

        $this->assertResponseOk();
    }

    public function testGetInvoiceView()
    {
        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $response = $this->call('GET', '/v1/t/inv_1000000invoice', ['key_id' => $this->ba->getKey()]);

        $this->assertResponseOk();
    }

    public function testPayExpiredInvoice()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice', ['status' => 'expired']);

        $payment             = $this->getDefaultPaymentArray();
        $payment['order_id'] = $invoice->order->getPublicId();
        $payment['amount']   = $invoice->getAmount();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPayDeletedInvoice()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice', ['deleted_at' => time()]);

        $this->assertNull($this->getLastEntity('invoice'));

        $payment             = $this->getDefaultPaymentArray();
        $payment['order_id'] = $invoice->order->getPublicId();
        $payment['amount']   = $invoice->getAmount();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPartialPayment()
    {
        $order = $this->fixtures->create(
                                    'order',
                                    [
                                        'id'              => '100000000order',
                                        'amount'          => 1000,
                                        'partial_payment' => true,
                                        'payment_capture' => true,
                                    ]);

        $invoice = $this->fixtures->create(
                                        'invoice',
                                        [
                                            'partial_payment' => true,
                                            'amount'          => 1000,
                                        ]);

        // Makes a partial payment

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 600;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order->getPublicId(),
            'invoice_id' => $invoice->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('partially_paid', $invoice['status']);
        $this->assertEquals(600, $invoice['amount_paid']);
        $this->assertEquals(400, $invoice['amount_due']);
    }

    public function testMultiplePartialPayments()
    {
        $this->testPartialPayment();

        $order = $this->getLastEntity('order');
        $invoice = $this->getLastEntity('invoice');

        // Make another 2 partial payments and check if invoice is paid

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 300;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order['id'],
            'invoice_id' => $invoice['id'],
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('partially_paid', $invoice['status']);
        $this->assertEmpty($invoice['paid_at']);
        $this->assertEquals(900, $invoice['amount_paid']);
        $this->assertEquals(100, $invoice['amount_due']);

        // Last partial payment of 100 should turn invoice into paid.

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 100;

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('paid', $invoice['status']);
        $this->assertNotEmpty($invoice['paid_at']);
        $this->assertEquals(1000, $invoice['amount_paid']);
        $this->assertEquals(0, $invoice['amount_due']);
    }

    public function testMultiplePartialPaymentsAndRefund()
    {
        $this->testPartialPayment();

        $order = $this->getLastEntity('order');
        $invoice = $this->getLastEntity('invoice');

        // Invoice amount: 1000,  Paid: 600, Due: 400. Number of payments = 1

        // 1. Refund the existing payment partially

        $payment = $this->getLastEntity('payment');

        $paymentId1 = $payment['id'];

        $this->refundPayment($paymentId1, 500);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('partially_paid', $invoice['status']);
        $this->assertEmpty($invoice['paid_at']);
        $this->assertEquals(100, $invoice['amount_paid']);
        $this->assertEquals(900, $invoice['amount_due']);

        // 2. Make another partial payment for 900

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 900;

        $payment = $this->doAuthAndGetPayment(
                            $payment,
                            [
                                'status'   => 'captured',
                                'order_id' => 'order_100000000order',
                            ]);

        $paymentId2 = $payment['id'];

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('paid', $invoice['status']);
        $this->assertNotEmpty($invoice['paid_at']);
        $this->assertEquals(1000, $invoice['amount_paid']);
        $this->assertEquals(0, $invoice['amount_due']);

        // 3. Refund both payments in full

        $this->refundPayment($paymentId1);
        $this->refundPayment($paymentId2);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('issued', $invoice['status']);
        $this->assertEmpty($invoice['paid_at']);
        $this->assertEquals(0, $invoice['amount_paid']);
        $this->assertEquals(1000, $invoice['amount_due']);
    }

    public function testCancelInvoice()
    {
        $this->createOrder();
        $this->fixtures->create('invoice');
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testCancelPaymentInProgressInvoice()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        //
        // Just adds one failed payment too, for testing purposes.
        //
        $this->fixtures->create('payment:failed',
            [
                'order_id'   => '100000000order',
                'invoice_id' => '1000000invoice',
                'card_id'    => null,
            ]);

        $this->fixtures->create('payment:authorized',
            [
                'order_id'   => '100000000order',
                'invoice_id' => '1000000invoice',
                'card_id'    => null,
            ]);

        $this->startTest();
    }

    public function testCancelPaidInvocie()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCancelInvoiceWithFailedPayment()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $this->fixtures->create('payment:failed',
            [
                'order_id'   => '100000000order',
                'invoice_id' => '1000000invoice',
                'card_id'    => null,
            ]);

        $this->startTest();
    }

    public function testExpireInvoices()
    {
        // Issued invoice
        $this->createOrder();
        $this->fixtures->create('invoice');

        // Picked and expired: Issued invoice and past expire_by
        $this->createOrder(['id' => '100000001order']);
        $this->fixtures->create('invoice',
            [
                'id'         => '1000001invoice',
                'order_id'   => '100000001order',
                'expire_by'  => 1484519217,
            ]);

        // Not picked: Cancelled invoice, can be past expire_by if cancelled in
        // between after issuing.
        $this->createOrder(['id' => '100000002order']);
        $this->fixtures->create('invoice',
            [
                'id'           => '1000002invoice',
                'order_id'     => '100000002order',
                'expire_by'    => 1484519217,
                'status'       => 'cancelled',
                'cancelled_at' => 1484519200,
            ]);

        // Not picked: Draft invoice
        $this->createDraftInvoice(['id' => '1000003invoice']);

        // Not picked: Past expire_by but paid invoice
        $this->createOrder(['id' => '100000004order']);
        $this->fixtures->create('invoice',
            [
                'id'         => '1000004invoice',
                'order_id'   => '100000004order',
                'expire_by'  => 1484519217,
                'status'     => 'paid',
            ]);

        // Picked and failed: Issued invoice with created payments (not captured
        // so invoice still not paid) and past expire_by.
        $this->createOrder(['id' => '100000005order']);
        $this->fixtures->create('invoice',
            [
                'id'         => '1000005invoice',
                'order_id'   => '100000005order',
                'expire_by'  => 1484519217,
                'status'     => 'issued',
            ]);
        $this->fixtures->payment->createAuthorized(
            [
                'order_id'   => '100000005order',
                'invoice_id' => '1000005invoice',
            ]);

        // Picked and expired: Issued invoice with a payment which might be
        // late authorized and got auto refunded by cron.
        $this->createOrder(['id' => '100000006order']);
        $this->fixtures->create('invoice',
            [
                'id'         => '1000006invoice',
                'order_id'   => '100000006order',
                'expire_by'  => 1484519217,
                'status'     => 'issued',
            ]);
        $payment = $this->fixtures->payment->createAuthorized(
                        [
                            'order_id'   => '100000006order',
                            'invoice_id' => '1000006invoice',
                        ]);
        $this->refundAuthorizedPayment($payment->getPublicId());

        $this->ba->appAuth();

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
        $this->assertContains('http://dwarf.razorpay.dev/', $invoice['short_url']);
        $this->assertEquals('10000000000000', $invoice['merchant_id']);
    }

    protected function createOrder(array $overrideWith = [])
    {
        $order = $this->fixtures
                      ->create(
                            'order',
                            array_merge(
                                [
                                    'id'              => '100000000order',
                                    'amount'          => 100000,
                                    'payment_capture' => true,
                                ],
                                $overrideWith
                            )
                        );

        return $order;
    }

    /**
     * Helper method to make payment for given invoice and do the necessary
     * assertions.
     */
    protected function makePaymentForInvoiceAndAssert(array $invoice)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $invoice['order_id'];
        $payment['amount']   = $invoice['amount'];

        $payment = $this->doAuthAndGetPayment(
            $payment,
            [
                'status'   => 'captured',
                'order_id' => $invoice['order_id'],
            ]
        );

        $order   = $this->getLastEntity('order', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($payment['id'], $invoice['payment_id']);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($invoice['status'], 'paid');
        $this->assertEquals($invoice['id'], $payment['invoice_id']);

        return $payment;
    }

    protected function createFewLineItems()
    {
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->fixtures->create('item', ['id' => '1000000001item']);
        $this->fixtures->create('line_item', ['id' => '100001lineitem', 'item_id' => '1000000001item']);

        $this->fixtures->create('item', ['id' => '1000000002item']);
        $this->fixtures->create('line_item', ['id' => '100002lineitem', 'item_id' => '1000000002item']);
    }

    protected function setEsMockSearchExpectations($callee, $esMock)
    {
        $expectedSearchParams = $this->testData["{$callee}ExpectedSearchParams"];
        $expectedSearchRes    = $this->testData["{$callee}ExpectedSearchResponse"];

        $esMock->expects($this->once())
               ->method('search')
               ->with($expectedSearchParams)
               ->willReturn($expectedSearchRes);
    }

    protected function createManyInvoicesForFetchTests()
    {
        $this->createDraftInvoice(
            [
                'id'    => '1000000invoice',
                'notes' => [
                    'extra' => 'Extra Information in notes key!!',
                    'ref'   => 'Sample Reference Number',
                ]
            ]);

        $this->createDraftInvoice(
            [
                'id'    => '1000001invoice',
                'terms' => 'Random terms and conditions',
            ]);

        // $this->createDraftInvoice(
        //     [
        //         'id' => '1000002invoice',
        //     ]);

        // $this->createDraftInvoice(
        //     [
        //         'id' => '1000003invoice',
        //     ]);

        $merchant = $this->fixtures->create('merchant');

        $this->createDraftInvoice(
            [
                'id'          => '1000004invoice',
                'merchant_id' => $merchant->getId(),
            ]);

        // $this->createDraftInvoice(
        //     [
        //         'id' => '1000005invoice',
        //     ]);

        $order = $this->createOrder();

        $this->createIssuedInvoice(
            [
                'id'       => '1000006invoice',
                'order_id' => $order->getId(),
            ]);

        $order = $this->createOrder(['id' => '100000001order']);

        $this->createIssuedInvoice(
            [
                'id'       => '1000007invoice',
                'order_id' => $order->getId(),
            ]);

    }
}
