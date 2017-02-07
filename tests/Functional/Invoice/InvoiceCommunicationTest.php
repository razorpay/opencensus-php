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
        //
        // TODO:
        // Add this post beta launch.
        //
    }

    protected function assertStatusesWithLastEntity(array $expected)
    {
        $invoice = $this->getLastEntity('invoice');

        $this->assertArraySelectiveEquals($expected, $invoice);
    }
}
