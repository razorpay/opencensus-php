<?php
namespace RZP\Tests\Unit\Models\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\PublicCollection;
use RZP\Tests\Functional\CustomAssertions;

class EntityTest extends TestCase
{
    use CustomAssertions;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/EntityTestData.php';

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
        $order   = $this->fixtures->create('order', ['id' => '100000000order']);
        $invoice = $this->fixtures->create('invoice');

        $actual   = $invoice->toArrayHosted();
        $expected = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expected, $actual);

        // Assert over collection as well
        $invoices = (new PublicCollection)->push($invoice);

        $actual   = $invoices->toArrayHosted();
        $expected = [$expected];

        $this->assertArraySelectiveEquals($expected, $actual);
    }
}
