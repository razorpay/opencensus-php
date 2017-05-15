<?php

namespace RZP\Tests\Unit\LineItem\Tax;

use RZP\Tests\TestCase;
use RZP\Models\LineItem;
use RZP\Models\Tax;

/**
 * Unit tests for LineItem\Tax\Calculator.
 */
class CalculatorTest extends TestCase
{
    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/CalculatorTestData.php';

        parent::setUp();
    }

    /**
     * Tests getTaxAmount() method of Calculator against a set of data.
     *
     */
    public function testLineItemTaxCalculation()
    {
        foreach ($this->testData as $i => $testData)
        {
            $lineItem = (new LineItem\Entity)->build($testData['line_item']);

            $tax = (new Tax\Entity)->build($testData['tax']);

            $actualTaxAmount = (new LineItem\Tax\Calculator($lineItem, $tax))
                                    ->getTaxAmount();

            $this->assertEquals(
                $testData['expected_tax_amount'],
                $actualTaxAmount,
                "Tax amount does not match for {$i}th test data");
        }
    }
}
