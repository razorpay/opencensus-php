<?php

namespace RZP\Tests\Unit\Models\Invoice;

use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Tests\Functional\Invoice\InvoiceTest;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class ViewDataSerializerTestHosted extends TestCase
{
    use DbEntityFetchTrait;
    use Traits\CreatesInvoice;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/ViewDataSerializerHostedTestData.php';

        parent::setUp();
    }

    public function testGetInvoice()
    {
        $invoice  = $this->createInvoiceWithTaxesAndCustomerAddresses();

        $expected = $this->getExpectedSerializedInvoiceDataWithTaxesAndCustomerAddresses();
        $actual   = (new Invoice\ViewDataSerializerHosted($invoice))->serializeForHosted();

        $this->assertArraySelectiveEquals($expected, $actual);

        $this->assertArrayNotHasKey('subscription_id', $actual['invoice']);

        $this->assertNotEmpty($actual['invoice']['issued_at_formatted']);
        $this->assertNotEmpty($actual['invoice']['date_formatted']);
        $this->assertNotEmpty($actual['invoice']['expire_by_formatted']);
    }

    public function testGetInvoiceForMyMerchant()
    {
        $org = $this->fixtures->create('org:curlec_org');

        $this->fixtures->org->addFeatures([FeatureConstants::ORG_CUSTOM_BRANDING],$org->getId());

        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'MY','org_id'    => $org->getId()]);
        $invoice  = $this->createInvoiceWithPayment();

        $invoice['issued_at'] = 1681340400;
        $invoice['expire_by'] = 1681340400;
        $invoice['currency'] = 'MYR';
        $actual   = (new Invoice\ViewDataSerializerHosted($invoice))->serializeForHosted();

        $this->assertNotEmpty($actual['invoice']['issued_at_formatted']);
        $this->assertNotEmpty($actual['invoice']['date_formatted']);
        $this->assertNotEmpty($actual['invoice']['expire_by_formatted']);
        $this->assertNotEmpty($actual['org']['branding']['security_branding_logo']);

        $this->assertEquals('13 Apr 2023', $actual['invoice']['issued_at_formatted']);
        $this->assertEquals('13 Apr 2023', $actual['invoice']['expire_by_formatted']);
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

        $actual = (new Invoice\ViewDataSerializerHosted($invoice))->serializeForHosted();

        // Asserts that invoice's description contains line item's name (as line item's description is null)
        $this->assertEquals('Some item name', $actual['invoice']['description']);
        $this->assertEquals(false, $actual['invoice']['has_address_or_pos']);
    }

    public function testGetInvoiceWithPayments()
    {
        $invoice  = $this->createInvoiceWithPayment();

        // Having a failed payment for this same invoice, to assert that the same doesn't get sent in hosted view data
        $this->fixtures
            ->create(
                'payment:failed',
                [
                    'invoice_id' => '1000000invoice',
                    'order_id'   => '100000000order',
                ]);

        $expected = $this->getExpectedSerializedInvoiceDataWithPayments();
        $actual   = (new Invoice\ViewDataSerializerHosted($invoice))->serializeForHosted();

        $this->assertArraySelectiveEquals($expected, $actual);
    }

    public function testGetSubscriptionInvoice()
    {
        $this->markTestSkipped("Skipping the test, uses subscriptions table directly. Need Fix this.");

        $invoice  = $this->createSubscriptionInvoice();

        $expected = $this->getExpectedSerializedSubscriptionInvoiceData();
        $actual   = (new Invoice\ViewDataSerializerHosted($invoice))->serializeForHosted();

        $this->assertArraySelectiveEquals($expected, $actual);
    }

    protected function getExpectedSerializedInvoiceData(): array
    {
        return $this->testData['expectedSerializedInvoiceData'];
    }

    protected function getExpectedSerializedInvoiceDataWithTaxesAndCustomerAddresses(): array
    {
        $replaceWith = $this->testData['expectedReplacedSerializedInvoiceWithTaxesAndCustomerAddresses'];

        return array_replace_recursive($this->getExpectedSerializedInvoiceData(), $replaceWith);
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

    protected function createInvoiceWithTaxesAndCustomerAddresses(): Invoice\Entity
    {
        // Create merchant detail's entity with some parts of address populated
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                 => '10000000000000',
                'business_registered_address' => 'Line 1',
                'business_registered_city'    => 'Bangalore',
            ]);

        // Creates invoice with additional attributes
        $invoice  = $this->createInvoice(
            [
                Invoice\Entity::SUPPLY_STATE_CODE => 10,
                Invoice\Entity::MERCHANT_GSTIN    => '29kjsngjk213922',
            ]);

        // Adds customer billing & shipping addresses & associates with the invoice
        $invoiceCustomerBillingAddress  = $this->fixtures->create('address', ['type' => 'billing_address', 'line1' => 'billing address line 1']);
        $invoiceCustomerShippingAddress = $this->fixtures->create('address', ['type' => 'shipping_address']);

        $invoice->customerBillingAddress()->associate($invoiceCustomerBillingAddress);
        $invoice->customerShippingAddress()->associate($invoiceCustomerShippingAddress);

        // TODO: Add taxes and add assertions!

        $invoice->saveOrFail();

        return $invoice;
    }
}
