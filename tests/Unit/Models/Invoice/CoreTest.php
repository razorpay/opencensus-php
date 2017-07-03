<?php
namespace RZP\Tests\Unit\Models\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Invoice;
use RZP\Models\Merchant;

class CoreTest extends TestCase
{
    function setUp()
    {
        parent::setUp();

        $this->core = new Invoice\Core;
    }

    public function testGetFormattedInvoiceData()
    {
        $order = $this->fixtures
                      ->create(
                            'order',
                            [
                                'id'              => '100000000order',
                                'amount'          => 100000,
                                'payment_capture' => true,
                            ]
                        );

        $invoice = $this->fixtures->create('invoice');

        $merchant = Merchant\Entity::find('10000000000000');

        $actual = $this->core->getFormattedInvoiceData($invoice->getPublicId(), $merchant);

        $expected = [
            'invoice'  => [
                'order_id' => 'order_100000000order',
                'url'      => 'http://bitly.dev/2eZ11Vn',
                'amount'   => 100000,
            ],
            'customer' => [
                'id'         => 'cust_100000customer',
                'entity'     => 'customer',
                'name'       => 'test',
                'email'      => 'test@razorpay.com',
                'contact'    => '1234567890',
                'notes'      => [],
            ],
        ];

        $this->assertArraySelectiveEquals($expected, $actual);

        $this->assertNotEmpty($actual['customer']['created_at']);
    }
}
