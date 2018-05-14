<?php

namespace RZP\Tests\Unit\Models\Invoice;

use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class ViewDataSerializerTest extends TestCase
{
    use DbEntityFetchTrait;
    use Traits\CreatesInvoice;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/ViewDataSerializerTestData.php';

        parent::setUp();
    }

    public function testGetInvoice()
    {
        $invoice  = $this->createInvoice();

        $expected = $this->getExpectedSerializedInvoiceData();
        $actual   = (new Invoice\ViewDataSerializer($invoice))->serializeForHosted();

        $this->assertArraySelectiveEquals($expected, $actual);

        $this->assertArrayNotHasKey('subscription_id', $actual['invoice']);

        $this->assertNotEmpty($actual['invoice']['issued_at_formatted']);
        $this->assertNotEmpty($actual['invoice']['date_formatted']);
        $this->assertNotEmpty($actual['invoice']['expire_by_formatted']);
    }

    /**
     * The serialized entity of invoice of type=link should contain description no matter what.
     * Explanation: Some people send line_items in type=link and so description is empty. This makes UX (hosted, emails)
     * bad. Instead of their checking for line items existence backend itself in serialized response for view send
     * description, else first line item(say x)'s description else x's name.
     */
    public function testGetPaymentLinkWhenDescriptionIsEmpty()
    {
        $invoiceAttributes = [
            'type'        => 'link',
            'customer_id' => null,
            'description' => null,
        ];

        $this->fixtures->create('order', ['id' => '100000000order']);
        $this->fixtures->create('invoice', $invoiceAttributes);
        $this->fixtures->create('line_item', ['item_id' => null, 'description' => null]);

        $invoice = $this->getDbLastEntity('invoice');

        $actual = (new Invoice\ViewDataSerializer($invoice))->serializeForHosted();

        // Asserts that invoice's description contains line item's name (as line item's description is null)
        $this->assertEquals('Some item name', $actual['invoice']['description']);
    }

    public function testGetInvoiceWithPayments()
    {
        $invoice  = $this->createInvoiceWithPayment();

        $expected = $this->getExpectedSerializedInvoiceDataWithPayments();
        $actual   = (new Invoice\ViewDataSerializer($invoice))->serializeForHosted();

        $this->assertArraySelectiveEquals($expected, $actual);
    }

    public function testGetSubscriptionInvoice()
    {
        $invoice  = $this->createSubscriptionInvoice();

        $expected = $this->getExpectedSerializedSubscriptionInvoiceData();
        $actual   = (new Invoice\ViewDataSerializer($invoice))->serializeForHosted();

        $this->assertArraySelectiveEquals($expected, $actual);
    }

    protected function getExpectedSerializedInvoiceData(): array
    {
        return $this->testData['expectedSerializedInvoiceData'];
    }

    protected function getExpectedSerializedInvoiceDataWithPayments(): array
    {
        $replaceWith = $this->testData['expectedReplacedSerializedInvoiceWithPaymentsData'];

        return array_replace_recursive($this->getExpectedSerializedInvoiceData(), $replaceWith);
    }

    protected function getExpectedSerializedSubscriptionInvoiceData(): array
    {
        $replaceWith = $this->testData['expectedReplacedSerializedSubscriptionInvoiceData'];

        return array_replace_recursive($this->getExpectedSerializedInvoiceData(), $replaceWith);
    }
}
