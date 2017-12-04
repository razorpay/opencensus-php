<?php

namespace RZP\Tests\Unit\Models\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Invoice;
use RZP\Models\Merchant;

class ViewDataSerializerTest extends TestCase
{
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
        $actual   = (new Invoice\ViewDataSerializer($invoice))->get();

        $this->assertArraySelectiveEquals($expected, $actual);

        $this->assertArrayNotHasKey('subscription_id', $actual['invoice']);

        $this->assertNotEmpty($actual['invoice']['issued_at_formatted']);
        $this->assertNotEmpty($actual['invoice']['date_formatted']);
        $this->assertNotEmpty($actual['invoice']['expire_by_formatted']);
    }

    public function testGetInvoiceWithPayments()
    {
        $invoice  = $this->createInvoiceWithPayment();

        $expected = $this->getExpectedSerializedInvoiceDataWithPayments();
        $actual   = (new Invoice\ViewDataSerializer($invoice))->get();

        $this->assertArraySelectiveEquals($expected, $actual);
    }

    public function testGetSubscriptionInvoice()
    {
        $invoice  = $this->createSubscriptionInvoice();

        $expected = $this->getExpectedSerializedSubscriptionInvoiceData();
        $actual   = (new Invoice\ViewDataSerializer($invoice))->getWithSubscriptionIfApplicable();

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
