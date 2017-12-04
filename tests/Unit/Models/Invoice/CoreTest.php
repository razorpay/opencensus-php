<?php

namespace RZP\Tests\Unit\Models\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Invoice;
use RZP\Models\Merchant;

class CoreTest extends TestCase
{
    use Traits\CreatesInvoice;

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

        $expected = $this->getExpectedFormattedInvoiceData();
        $actual   = (new Invoice\Core)
                        ->getFormattedInvoiceData(
                            $invoice->getPublicId(),
                            $merchant);

        $this->assertArraySelectiveEquals($expected, $actual);
    }

    protected function getExpectedFormattedInvoiceData(): array
    {
        return [
            'invoice'  => [
                'order_id'        => 'order_100000000order',
                'url'             => 'http://bitly.dev/2eZ11Vn',
                'amount'          => 100000,
            ],
            'order' => [
                'partial_payment' => false,
                'amount'          => 100000,
                'amount_paid'     => 0,
                'amount_due'      => 100000,
            ],
            'customer' => [
                'id'              => 'cust_100000customer',
                'entity'          => 'customer',
                'name'            => 'test',
                'email'           => 'test@razorpay.com',
                'contact'         => '1234567890',
                'notes'           => [],
            ],
        ];
    }
}
