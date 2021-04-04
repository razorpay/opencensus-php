<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

/**
 * Includes CRUD tests for Invoices(and line items) involving taxes.
 * Keeping this independent as it will be cleaner to only cover taxes handling
 * of invoices in this test file.
 *
 */
class InvoiceTaxesTest extends TestCase
{
    use InvoiceTestTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceTaxesTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->seed('TaxGroupAndTaxSeeder');

        // Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                 => '10000000000000',
                'business_registered_address' => '#1205, Rzp, Outer Ring Road, Bangalore',
            ]);
    }

    /**
     * Tests invoice creation with multiple line items and taxes.
     *
     */
    public function testCreateInvoiceWithTaxes1()
    {
        $response = $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);

        // Verifies that order's amount is same as invoice's net amount.

        $order = $this->getLastEntity('order');

        $this->assertEquals($order['amount'], $response['amount']);
    }

    /**
     * Test invoice creation with another sample data, usage item's template.
     *
     */
    public function testCreateInvoiceWithTaxes2()
    {
        $this->fixtures->create('item', [
                'id' => '00000000000001',
            ]);

        $this->fixtures->create('item', [
                'id'     => '00000000000002',
                'tax_id' => '00000000000001'
            ]);

        $this->fixtures->create('item', [
                'id'           => '00000000000003',
            ]);

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    /**
     * Test invoice creation with another sample data, usage tax_inclusive
     * line item amounts.
     *
     */
    public function testCreateInvoiceWithTaxes3()
    {
        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    /**
     * Tests creation with line items which are mixed of tax inclusive and exclusive
     * amounts.
     * In general this won't be there practically, but still API supports it
     * so dropping a test around it.
     */
    public function testCreateInvoiceWithTaxes4()
    {
        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testCreateInvoiceWithMultipleTaxIds()
    {
        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testCreateInvoiceLineItemWithTaxIdAndTaxGroupId()
    {
        $this->fixtures->create('item', [
            'id'           => '00000000000003',
            'tax_group_id' => '00000000000002',
        ]);

        $this->startTest();
    }

    public function testCreateInvoiceLineItemWithTaxIdAndTaxIds()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithSharedGstTaxes()
    {
        $this->startTest();
    }

    /**
     * Asserts that tax of 0.125% is working fine.
     * Adding this after we have made tax.rate multiple of 10000 to keep values up to more precision
     */
    public function testCreateInvoiceWithSharedGstTaxes2()
    {
        $this->startTest();

        // Asserts that model values were flushed in db correctly
        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testCreateInvoiceLineItemWithItemCessTax()
    {
        $this->fixtures->create('item', [
            'name'   => 'Item Name',
            'id'     => '00000000000003',
            'tax_id' => '00000000000002',
        ]);

        $this->startTest();
    }

    public function testCreateInvoiceWithCgstAndSgstTaxes()
    {
        $this->startTest();
    }

    /**
     * Tests invoice update, includes removal/addition/updates
     * of line items and taxes.
     *
     */
    public function testUpdateInvoiceWithTaxes()
    {
        // It's easy to get invoice created properly with line items and taxes
        // by running above create request again.

        $this->testCreateInvoiceWithTaxes2();

        // Updates request content of the test:
        // - Update the invoice id of request url,
        // - Update line items id in request content

        $invoice = $this->getLastEntity('invoice');

        $invoiceId = $invoice['id'];

        $lineItemIds = array_column($invoice['line_items'], 'id');

        $this->testData[__FUNCTION__]['request']['url'] = "/invoices/{$invoiceId}";

        $this->testData[__FUNCTION__]['request']['content']['line_items'][0]['id'] = $lineItemIds[0];
        $this->testData[__FUNCTION__]['request']['content']['line_items'][1]['id'] = $lineItemIds[1];
        $this->testData[__FUNCTION__]['request']['content']['line_items'][2]['id'] = $lineItemIds[2];

        $response = $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }
}
