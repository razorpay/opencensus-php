<?php

namespace RZP\Tests\Unit\LineItem\Tax;

use RZP\Tests\TestCase;
use RZP\Models\LineItem;
use RZP\Models\LineItem\Tax\Calculator;
use RZP\Models\Tax;
use RZP\Models\Base;

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
     * Tests both the methods(was easy this way) by iterating over give test data
     * (in a format explained in the class do there).
     *
     */
    public function testTaxCalculationAgainstLineItemAndTaxes()
    {
        foreach ($this->testData as $i => $testData)
        {
            $lineItem = (new LineItem\Entity)
                            ->build($testData['line_item']['attributes']);

            $taxes = new Base\PublicCollection;

            foreach ($testData['taxes'] as $taxData)
            {
                $taxes->push((new Tax\Entity)->build($taxData['attributes']));
            }

            $actualTaxableAmount = Calculator::getTaxableAmountOfLineItem(
                                                    $lineItem,
                                                    $taxes);

            $this->assertEquals(
                $testData['line_item']['taxable_amount'],
                $actualTaxableAmount,
                "Taxable amount for {$i}th line item does not match");

            foreach ($taxes as $j => $tax)
            {
                $actualTaxAmount = Calculator::getTaxAmount(
                                                    $lineItem,
                                                    $actualTaxableAmount,
                                                    $tax);

                $this->assertEquals(
                    $testData['taxes'][$j]['tax_amount'],
                    $actualTaxAmount,
                    "Tax amount for {$i}th line item's {$j}th tax doesn't match");
            }
        }
    }
}
