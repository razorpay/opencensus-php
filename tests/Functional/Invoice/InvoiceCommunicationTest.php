<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Jobs\InvoiceAction;

class InvoiceCommunicationTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceCommunicationTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['invoice']);

        $this->ba->proxyAuth();
    }

    public function testSmsAndEmailNotify()
    {
        $this->startTest();

        //
        // TODO:
        // - Need to test, if flow dispatches jobs properly
        //   Tried in some ways but not working. Will come to this later.
        //
        // - Need to test InvoiceAction somehow too

        $this->assertStatusesWithLastEntity(['sms_status' => 'sent', 'email_status' => 'sent']);
    }

    public function testSmsNotifyNull()
    {
        $this->startTest();

        $this->assertStatusesWithLastEntity(['sms_status' => null, 'email_status' => 'sent']);
    }

    public function testNotifyWithNoCustomerEmail()
    {
        $this->startTest();

        $this->assertStatusesWithLastEntity(['sms_status' => 'sent', 'email_status' => 'pending']);
    }

    public function testNotifyWithNoEmailNoContact()
    {
        $this->startTest();

        $this->assertStatusesWithLastEntity(['sms_status' => 'pending', 'email_status' => null]);
    }

    public function testInvoiceSendNotificationsInBulk()
    {
        // TODO:
        // Not getting used as of now.
        // Will come into picture - when we introduce scheduled_at , due_by etc.
        // Let's think about it then. Leaving for now.
        //
    }

    protected function assertStatusesWithLastEntity(array $expected)
    {
        $invoice = $this->getLastEntity('invoice');

        $this->assertArraySelectiveEquals($expected, $invoice);
    }
}
