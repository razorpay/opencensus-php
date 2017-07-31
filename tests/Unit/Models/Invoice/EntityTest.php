<?php
namespace RZP\Tests\Unit\Models\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\PublicCollection;

class EntityTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testToArrayHosted()
    {
        $order = $this->fixtures->create('order', ['id' => '100000000order']);

        $invoice = $this->fixtures->create('invoice');

        $actual = $invoice->toArrayHosted();

        $this->assertEquals([], $actual);

        // Assert over collection as well

        $invoices = (new PublicCollection)->push($invoice);

        $actual = $invoices->toArrayHosted();

        $this->assertEquals([[]], $actual);
    }
}
