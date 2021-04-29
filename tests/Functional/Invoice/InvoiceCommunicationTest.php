<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InvoiceCommunicationTest extends TestCase
{
    use TestsMetrics;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceCommunicationTestData.php';

        parent::setUp();

        // Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                 => '10000000000000',
                'business_registered_address' => '#1205, Rzp, Outer Ring Road, Bangalore',
            ]);

        $this->ba->privateAuth();
    }

    public function testSmsAndEmailNotify()
    {
        config(['app.query_cache.mock' => false]);

        // TODO: Very brittle testcase around metrics, should refactor we test this before enabling again
//        $metrics = $this->createMetricsMock();
//
//        $metrics->expects($this->at(17))
//                ->method('count')
//                ->with(
//                    'invoice_email_notify_total',
//                    1,
//                    [
//                        'email_type'       => 'issued',
//                        'type'             => 'invoice',
//                        'has_batch'        => 0,
//                        'has_subscription' => 0,
//                    ]);
//
//        $metrics->expects($this->at(18))
//                ->method('count')
//                ->with(
//                    'invoice_sms_notify_total',
//                    1,
//                    [
//                        'sms_type'         => 'issued',
//                        'type'             => 'invoice',
//                        'has_batch'        => 0,
//                        'has_subscription' => 0,
//                    ]);

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
    }

    protected function assertStatusesWithLastEntity(array $expected)
    {
        $invoice = $this->getLastEntity('invoice');

        $this->assertArraySelectiveEquals($expected, $invoice);
    }
}
