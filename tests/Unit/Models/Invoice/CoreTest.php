<?php

namespace RZP\Tests\Unit\Models\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Invoice;
use RZP\Models\Merchant;

class CoreTest extends TestCase
{
    use Traits\CreatesInvoice;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/CoreTestData.php';

        parent::setUp();
    }

    /**
     * Invoice\Core::getFormattedInvoiceData() get used in /checkout/preferences
     * route and this test asserts that for invoices it returns proper payload
     * to the callee.
     *
     * @return void
     */
    public function testGetFormattedInvoiceData()
    {
        $invoice  = $this->createInvoice();
        $merchant = Merchant\Entity::find('10000000000000');

        $expected = $this->testData[__FUNCTION__];
        $actual   = (new Invoice\Core)->getFormattedInvoiceData($invoice->getPublicId(), $merchant);

        $this->assertArraySelectiveEquals($expected, $actual);
    }
}
