<?php

namespace RZP\Tests\Unit\LineItem\Tax;

use RZP\Models\Tax;
use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\LineItem;
use RZP\Tests\Functional\TestCase;
use RZP\Models\LineItem\Tax\Calculator;

/**
 * Unit tests for LineItem\Tax\Calculator.
 */
class CalculatorTest extends TestCase
{
    protected function setUp(): void
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
        $merchant = $this->fixtures->create('merchant');

        $invoice = new Invoice\Entity;

        $invoice->merchant()->associate($merchant);

        $invoice->build();

        foreach ($this->testData as $i => $testData)
        {
            $lineItem = new LineItem\Entity;

            //
            // Association with a dummy invoice entity is needed
            // as in Line item's validations this associated entity is used.
            //
            // In Core's flow the same is ensured.
            //
            $lineItem->entity()->associate($invoice);

            $lineItem->build($testData['line_item']['attributes']);

            $taxes = new Base\PublicCollection;

            foreach ($testData['taxes'] as $taxData)
            {
                $taxes->push((new Tax\Entity)->build($taxData['attributes']));
            }

            $actualTaxableAmount = Calculator::getTaxableAmountOfLineItem($lineItem, $taxes);

            $this->assertSame(
                $testData['line_item']['taxable_amount'],
                $actualTaxableAmount,
                "Taxable amount for {$i}th line item does not match");

            foreach ($taxes as $j => $tax)
            {
                $actualTaxAmount = Calculator::getTaxAmount($lineItem, $actualTaxableAmount, $tax);

                $this->assertEqualsWithDelta(
                    $testData['taxes'][$j]['tax_amount'],
                    $actualTaxAmount,
                    0.0001,
                    "Tax amount for {$i}th line item's {$j}th tax doesn't match");
            }
        }
    }
}
