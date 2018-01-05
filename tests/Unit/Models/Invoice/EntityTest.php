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

    /**
     * Tests toArrayHosted() method workings.
     * It is expected to serialize the entity and make available only
     * those fields which are listed in $hosted attribute (defaults to []).
     * Also the same should work for collection as well.
     *
     * @return void
     */
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
